<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Model\Interface\PhpDataInterface;
use Aequation\LaboBundle\Service\Base\BaseService;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
// use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Aequation\LaboBundle\Service\Interface\CacheServiceInterface;
use Aequation\LaboBundle\Service\Tools\Files;

use App\phpdata\PhpData;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use Exception;
use SplFileInfo;

#[AsAlias(CacheServiceInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: false)]
class CacheService extends BaseService implements CacheServiceInterface
{

    public const PHP_DATA_PATH = 'src/phpdata';
    public const PHP_DATA_CLASSNAME = 'PhpData';
    public const DATA_CACHE_NAME = 'data.cache';
    // Dev shortcut
    public const DEV_SHORTCUT_NAME = 'cache.dev.shortcuts';
    public const DEFAULT_DEV_SHORTCUT = true;

    public readonly PhpDataInterface $phpData;
    public readonly ?SessionInterface $session;
    protected array $sessionDevShortcuts = [];

    public function __construct(
        protected AppServiceInterface $appService,
        protected KernelInterface $kernel,
        protected CacheInterface $cache,
        #[Autowire(param: 'kernel.cache_dir')]
        public string $cacheDir,
    ) {
        if (!file_exists($this->getPhpDataFilePath())) {
            if (!$this->savePhpData()) {
                die('Le fichier de cache ' . $this->getPhpDataFilePath() . ' n\'a pu être enregistré, veuillez juste relancer la dernière requête S.V.P.');
            }
            die('Une réinitialisation a été nécessaire, tout est rentré dans l\'ordre, veuillez juste relancer la dernière requête S.V.P.');
        }
        $this->phpData = new PhpData();
        $this->session = $this->appService->getSession();
        if($this->session) {
            $this->sessionDevShortcuts = $this->session->get(static::DEV_SHORTCUT_NAME, $this->getDefaultsDevShortcuts());
        } else {
            $this->sessionDevShortcuts = $this->getDefaultsDevShortcuts();
        }
    }

    public function getSessionDevShortcuts(): ?array
    {
        return $this->session
            ? $this->session->get(static::DEV_SHORTCUT_NAME, null)
            : null;
    }

    public function getDefaultsDevShortcuts(): array
    {
        $defaults = [];
        foreach ($this->getKeys(false) as $key) {
            $defaults[$key] = static::DEFAULT_DEV_SHORTCUT;
        }
        return $defaults;
    }

    public function resetDevShortcuts(): static
    {
        if($this->session) {
            $this->session->set(static::DEV_SHORTCUT_NAME, $this->getDefaultsDevShortcuts());
        }
        return $this;
    }

    public function isDevShortcut(string $key): bool
    {
        return $this->sessionDevShortcuts[$key] ?? static::DEFAULT_DEV_SHORTCUT;
    }

    public function setDevShortcut(
        string $key,
        bool $enabled
    ): static
    {
        $this->sessionDevShortcuts[$key] = $enabled;
        if($this->session) {
            $this->session->set(static::DEV_SHORTCUT_NAME, $this->sessionDevShortcuts);
        }
        return $this;
    }

    public function setDevShortcutAll(bool $onoff): static
    {
        foreach ($this->getKeys(false) as $key) {
            $this->setDevShortcut($key, $onoff);
        }
        return $this;
    }

    public function toggleDevShortcut(string $key): static
    {
        $toggled = !$this->isDevShortcut($key);
        return $this->setDevShortcut($key, $toggled);
    }

    public function get(
        string $key,
        callable $callback,
        string $commentaire = null,
        float $beta = null,
        array $metadata = null
    ): mixed {
        $this->addKey($key, $commentaire);
        if(!$this->appService->isProd() && $this->isDevShortcut($key)) {
            // dump('Delete cache '.$key.'...');
            $this->delete($key);
        }
        return $this->cache->get(key: $key, callback: $callback, beta: $beta, metadata: $metadata);
    }

    public function delete(
        string $key
    ): bool {
        return $this->cache->delete($key);
    }

    public function deleteAll(): bool
    {
        $result = true;
        foreach ($this->getKeys(false) as $key) {
            $result = $result && $this->delete($key);
        }
        return $result;
    }

    public function getKeys(
        $withCommentaires = true
    ): array {
        $data = $this->getPhpData(static::DATA_CACHE_NAME);
        return $withCommentaires
            ? $data
            : array_keys($data);
    }

    public function hasKey(
        string $key
    ): bool
    {
        $data = $this->getPhpData(static::DATA_CACHE_NAME);
        return array_key_exists($key, $data);
    }

    protected function addKey(
        string $key,
        string $commentaire = null
    ): static {
        $data = $this->getPhpData(static::DATA_CACHE_NAME);
        $data[$key] = empty($commentaire) ? $key : $commentaire;
        $this->setPhpData(static::DATA_CACHE_NAME, $data);
        if(!$this->hasKey($key)) $this->setDevShortcut($key, static::DEFAULT_DEV_SHORTCUT);
        return $this;
    }

    protected function removeKey(
        string|array $key
    ): static {
        $key = (array)$key;
        $data = $this->getPhpData(static::DATA_CACHE_NAME);
        $data = array_filter($data, function ($k) use ($key) {
            return !in_array($k, $key);
        }, ARRAY_FILTER_USE_KEY);
        $this->setPhpData(static::DATA_CACHE_NAME, $data);
        return $this;
    }


    /************************************************************************************************
     * CACHE FILES
     */

    public function cacheClear(
        string $method = 'exec',
    ): static {
        switch ($method) {
            case 'exec':
                $application = new Application($this->kernel);
                $application->setAutoExit(false);
                $input = new ArrayInput(['command' => 'cache:clear']);
                $output = new BufferedOutput();
                $application->run($input, $output);
                break;
            case 'console':
                $input = new ArgvInput(['console', 'cache:clear']);
                $application = new Application($this->kernel);
                $application->run($input);
                break;
            case 'rmdir':
                $fs = new Filesystem();
                $cachedir = $this->cacheDir;
                $fs->remove($cachedir);
                break;
            default:
                throw new Exception(vsprintf('Error %s line %d: method "%s" is not available.', [__METHOD__, __LINE__, $method]));
                break;
        }
        return $this;
    }

    public function getCacheDir(): ?SplFileInfo
    {
        return Files::getParentDir($this->cacheDir);
    }

    public function getCacheDirs(
        int $depth = 0
    ): array {
        $parent = $this->getCacheDir();
        return Files::listDirs(path: $parent, depth: $depth);
        // return Files::nestedDirs(path: $parent, depth: $depth);
    }


    /************************************************************************************************
     * PHP DATA
     */

    public function getPhpData(
        string $name = null,
        mixed $default = [],
    ): mixed {
        return isset($this->phpData)
            ? $this->phpData->get($name, $default)
            : [];
    }

    public function setPhpData(
        string $name,
        mixed $data
    ): static {
        $this->phpData->set($name, $data);
        if ($this->phpData->needUpdate()) $this->savePhpData();
        return $this;
    }

    public function updatePhpData(): static {
        $this->savePhpData();
        return $this;
    }

    public function getPhpDataPath(): string|false
    {
        $path = Files::createPath(static::PHP_DATA_PATH);
        return $path;
    }

    public function getPhpDataFilePath(): string|false
    {
        $path = $this->getPhpDataPath();
        return $path
            ? $path . DIRECTORY_SEPARATOR . static::PHP_DATA_CLASSNAME . '.php'
            : false;
    }

    protected function savePhpData(): bool
    {
        $twig = $this->appService->getTwig();
        $template = '@AequationLabo/phpmodels/PhpData.twig';
        if(!$twig->getLoader()->exists($template)) {
            throw new Exception(vsprintf('Error %s line %d: template %s not found!', [__METHOD__, __LINE__, $template]));
        }
        return Files::putFileContent(
            path: $this->getPhpDataPath(),
            filename: static::PHP_DATA_CLASSNAME . '.php',
            content: $twig->render($template, [
                'version' => $this->appService->getCurrentDatetime()->format(DATE_ATOM),
                'data' => $this->getPhpData(),
                // 'debug' => $this->appService->isDev(),
            ]),
        );
    }

    // protected function savePhpData_old(): bool
    // {
    //     // $content = Files::getFileContent('lib/aequation/labo-bundle/phpmodels', static::PHP_DATA_CLASSNAME.'.txt');
    //     $content = false;
    //     if (!$content) {
    //         $content = <<<EOF
    //         <?php
    //         namespace App\phpdata;

    //         use Aequation\LaboBundle\Model\Interface\PhpDataInterface;

    //         use DateTimeImmutable;
    //         use DateTimeInterface;

    //         class PhpData implements PhpDataInterface
    //         {

    //             public const VERSION = "#####VERSION#####";
    //             public const JSON_DATA = "#####JSON_DATA#####";

    //             private array \$data;
    //             private DateTimeInterface \$version;

    //             public function __construct()
    //             {
    //                 \$this->data = json_decode(static::JSON_DATA, true);
    //                 \$this->version = new DateTimeImmutable(static::VERSION);
    //             }

    //             public function needUpdate(): bool
    //             {
    //                 return json_encode(\$this->data) !== static::JSON_DATA;
    //             }

    //             public function get(
    //                 string \$name = null,
    //                 mixed \$default = null,
    //             ): mixed
    //             {
    //                 return empty(\$name)
    //                     ? \$this->data
    //                     : \$this->data[\$name] ?? \$default;
    //             }

    //             public function set(
    //                 string \$name,
    //                 mixed \$data
    //             ): static
    //             {
    //                 \$this->data[\$name] = \$data;
    //                 return \$this;
    //             }

    //             public function getVersion(
    //                 bool \$toDatetime = false
    //             ): DateTimeInterface
    //             {
    //                 return \$toDatetime
    //                     ? \$this->version
    //                     : \$this->version->getTimestamp();
    //             }

    //         }
    //         EOF;
    //     }
    //     if (!$content) throw new Exception(vsprintf('Error %s line %d: file %s not found!', [__METHOD__, __LINE__, 'lib/aequation/labo-bundle/phpmodels/' . static::PHP_DATA_CLASSNAME . '.txt']));
    //     // $version = new DateTimeImmutable('NOW');
    //     $data = json_encode($this->getPhpData());
    //     return Files::putFileContent(
    //         path: $this->getPhpDataPath(),
    //         filename: static::PHP_DATA_CLASSNAME . '.php',
    //         content: preg_replace(['/(#####VERSION#####)/', '/("#####JSON_DATA#####")/'], [$this->appService->getCurrentDatetime()->format(DATE_ATOM), json_encode($data)], $content)
    //     );
    // }

}
