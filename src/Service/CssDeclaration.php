<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\AequationLaboBundle;
use Aequation\LaboBundle\Component\CssManager;
use Aequation\LaboBundle\EventListener\Attribute\AppEvent;
use Aequation\LaboBundle\Form\Type\CssType;
use Aequation\LaboBundle\Model\Attribute\CssClasses;
use Aequation\LaboBundle\Service\Base\BaseService;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Interface\CssDeclarationInterface;
use Aequation\LaboBundle\Service\Interface\FormServiceInterface;
use Aequation\LaboBundle\Service\Interface\LaboBundleServiceInterface;
use Aequation\LaboBundle\Service\Tools\Classes;
use Aequation\LaboBundle\Service\Tools\Files;
use Aequation\LaboBundle\Service\Tools\Iterables;
use Aequation\LaboBundle\Service\Tools\Strings;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Process\Process;
use Symfony\Contracts\Cache\ItemInterface;
use Symfonycasts\TailwindBundle\TailwindBuilder;

#[AsAlias(CssDeclarationInterface::class, public: true)]
#[Autoconfigure(autowire: true, lazy: true)]
class CssDeclaration extends BaseService implements CssDeclarationInterface
{

    public const CACHE_CSS_ATTRIBUTES_NAME = 'Cache_Css_Attributes';
    public const CACHE_CSS_ATTRIBUTES_LIFE = 24 * 3600;

    public const FILE_PATH = 'templates';
    public const FILE_NAME = 'tailwind_css_declarations.html.twig';

    public const BUNDLE_FILE_PATH = 'templates';
    public const BUNDLE_FILE_NAME = 'tailwind_css_declarations.html.twig';

    public const CLASS_TYPES = ['ORIGIN', 'COMPUTED', 'ADDED','UNKNOWN'];

    public readonly Files $tool_files;
    protected string $filepath;
    protected string $filename;
    protected string|false $filecontent;
    protected array $classes;
    protected array $base_classes;
    protected array $computed_classes;
    protected array $cssAttributes;

    public function __construct(
        protected LaboBundleServiceInterface $laboAppService,
        #[Autowire(service: 'tailwind.builder')]
        protected TailwindBuilder $tailwindBuilder,
    )
    {
        $this->tool_files = $this->laboAppService->get('Tool:Files');
        $this->setFilepath(static::FILE_PATH);
        $this->setFilename(static::FILE_NAME);
    }

    public function setFilepath(
        string $filepath,
        bool $create = true
    ): static
    {
        $this->filepath = $this->tool_files->getProjectDir($filepath, $create);
        return $this;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;
        return $this;
    }

    public function getClasses(
        bool $refresh = false
    ): array
    {
        if(!isset($this->classes) || $refresh) $this->classes = $this->readClassesList(true);
        return $this->classes;
    }

    public function getClassesGrouped(
        bool $refresh = false
    ): array
    {
        $grouped = [];
        foreach ($this->getClasses($refresh) as $class) {
            $group = Strings::getBefore($class, '-', false);
            $grouped[$group][$class] = $class;
        }
        ksort($grouped);
        return $grouped;
    }

    public function getSortedAllFinalClasses(
        bool $refresh = false
    ): array
    {
        $classes = $this->getBaseClasses();
        foreach ($this->getComputedClasses() as $class) {
            $classes[$class] = $class;
        }
        foreach ($this->getClasses($refresh) as $class) {
            $classes[$class] = $class;
        }
        ksort($classes);
        return $classes;
    }

    public function addClasses(
        string|array $classes
    ): int // nombre de classes ajoutées
    {
        $init = count($this->getClasses());
        foreach (Iterables::toClassList($classes, false) as $class) {
            $this->classes[$class] = $class;
        }
        ksort($this->classes);
        return count($this->classes) - $init;
    }

    public function removeClasses(
        string|array $classes
    ): int // nombre de classes retirées
    {
        $init = count($this->getClasses());
        $classes = Iterables::toClassList($classes, false);
        $this->classes = array_filter($this->classes, function($class) use ($classes) {
            return !in_array($class, $classes);
        });
        ksort($this->classes);
        return $init - count($this->classes);
    }

    public function isRemovable(
        string $class
    ): bool
    {
        return in_array($this->getClassType($class, true), [2,3]);
    }

    public function getClassType(
        string $class,
        bool $asIndex = false,
    ): string
    {
        if(in_array($class, $this->getBaseClasses())) return $asIndex ? 0 : static::CLASS_TYPES[0];
        if(in_array($class, $this->getComputedClasses())) return $asIndex ? 1 : static::CLASS_TYPES[1];
        if(in_array($class, $this->getClasses())) return $asIndex ? 2 : static::CLASS_TYPES[2];
        return $asIndex ? 3 : static::CLASS_TYPES[3];
    }

    private function getBaseClasses(): array
    {
        if(!isset($this->base_classes)) {
            $this->base_classes = [];
            $base = $this->tool_files->getFileContent(AequationLaboBundle::getProjectPath(true).static::BUNDLE_FILE_PATH, static::BUNDLE_FILE_NAME);
            if($base) {
                $this->base_classes = $this->parseClasses($base);
            }
        }
        return $this->base_classes;
    }

    private function getCssAttributes(): array
    {
        if(!isset($this->cssAttributes)) {
            $this->cssAttributes = $this->laboAppService->getCache()->get(
                key: static::CACHE_CSS_ATTRIBUTES_NAME,
                callback: function(ItemInterface $item) {
                    if(!empty(static::CACHE_CSS_ATTRIBUTES_LIFE)) {
                        $item->expiresAfter(static::CACHE_CSS_ATTRIBUTES_LIFE);
                    }
                    /** @var AppEntityManagerInterface $app_em */
                    $app_em = $this->laboAppService->get(AppEntityManagerInterface::class);
                    $classes = [];
                    $index = 0;
                    // All App services (public)
                    $services = [];
                    foreach ($app_em->getEntityNames(false, false, true) as $class) {
                        $classes[$index] = $app_em->getModel($class, null, AppEvent::PRE_SET_DATA);
                        if(!$classes[$index] && $app_em->isDev()) throw new Exception(vsprintf('Error %s line %d: failed to create new %s entity!', [__METHOD__, __LINE__, $class]));
                        $index++;
                    }
                    foreach ($this->laboAppService->getAppServices() as $service) {
                        // if(!empty($service['classname'])) {
                            try {
                                $classes[$index] = $this->laboAppService->get($service['id'], ContainerInterface::NULL_ON_INVALID_REFERENCE);
                                if(!$classes[$index] && $app_em->isDev()) throw new Exception(vsprintf('Error %s line %d: failed to call service of ID %s (class: %s)!', [__METHOD__, __LINE__, $service['id'], $service['classname']]));
                                $index++;
                            } catch (\Throwable $th) {
                                //throw $th;
                                if($app_em->isDev()) throw new Exception(vsprintf('Error %s line %d: failed to call service of ID %s (class: %s)!%s', [__METHOD__, __LINE__, $service['id'], $service['classname'], PHP_EOL.$th->getMessage()]));
                            }
                        // }
                    }
                    return Classes::getAttributes(CssClasses::class, $classes);
                },
                commentaire: "CssClasses attributes on all classes",
            );
        }
        return $this->cssAttributes;
    }

    public function getComputedClasses(): array
    {
        if(!isset($this->computed_classes)) {
            $this->computed_classes = [];
            foreach ($this->getCssAttributes() as $cssClass) {
                foreach ($cssClass->getCssClasses() as $class) {
                    $this->computed_classes[$class] = $class;
                }
            }
        }
        return $this->computed_classes;
    }

    private function getFileContent(
        bool $refresh = false
    ): string|false
    {
        if(!isset($this->filecontent) || $refresh) $this->filecontent = $this->tool_files->getFileContent($this->filepath, $this->filename);
        return $this->filecontent;
    }

    public function readClassesList(
        bool $refresh = false
    ): array
    {
        $classes = $this->parseClasses($this->getFileContent($refresh));
        ksort($classes);
        return $classes;
    }

    public function refreshClasses(): bool
    {
        $classes = $this->getSortedAllFinalClasses(true);
        $content = $this->getFileHead();
        $fl = substr(reset($classes), 0, 1);
        foreach ($classes as $class) {
            $ln = $fl !== substr($class, 0, 1) ? PHP_EOL.PHP_EOL : PHP_EOL;
            $fl = substr($class, 0, 1);
            $content .= $ln.'<span class="'.$class.'"></span>';
        }
        $content .= $this->getFileEnd();
        return $this->tool_files->putFileContent($this->filepath, $this->filename, $content);
    }

    public function saveClasses(): bool
    {
        $classes = $this->getSortedAllFinalClasses(false);
        $content = $this->getFileHead();
        $fl = substr(reset($classes), 0, 1);
        foreach ($classes as $class) {
            $ln = $fl !== substr($class, 0, 1) ? PHP_EOL.PHP_EOL : PHP_EOL;
            $fl = substr($class, 0, 1);
            $content .= $ln.'<span class="'.$class.'"></span>';
        }
        $content .= $this->getFileEnd();
        return $this->tool_files->putFileContent($this->filepath, $this->filename, $content);
    }

    public function resetAll(): bool
    {
        $save = $this->classes ?? [];
        $this->classes = [];
        if($result = $this->saveClasses()) {
            $this->getClasses(true); // Refresh class list
        } else {
            $this->classes = $save;
        }
        return $result;
    }


    /**********************************************************************************
     * FORM
     */

    public function getCssForm(?CssManager $cssManager = null): FormInterface
    {
        /** @var FormServiceInterface */
        $formService = $this->laboAppService->get(FormServiceInterface::class);
        return $formService->getForm(CssType::class, $cssManager);
    }


    /**********************************************************************************
     * INTERNAL PRIVATE
     */

    public function buildTailwindCss(bool $watch, bool $poll, bool $minify, ?callable $callback = null): Process
    {
        $process = $this->tailwindBuilder->runBuild($watch, $poll, $minify);
        if(is_callable($callback)) $process->wait($callback);
        return $process;
    }

    /**********************************************************************************
     * INTERNAL PRIVATE
     */

    private function getFileHead(): string
    {
        // return '{# '.PHP_EOL.PHP_EOL.'DECLARATIONS DE CLASSES POUR LA GÉNÉRATION TAILWIND DES STYLES CSS'.PHP_EOL.'NON VISIBLES CAR GÉNÉRÉS DYNAMIQUEMENT'.PHP_EOL.'UPDATED: '.$this->laboAppService->getCurrentDatetime()->format(DATE_ATOM).PHP_EOL.PHP_EOL.'CE FICHIER NE DOIT PAS ÊTRE UTILISÉ'.PHP_EOL.'-----------------------------------------------------'.PHP_EOL;
        $date = $this->laboAppService->getCurrentDatetime()->format(DATE_ATOM);
        return <<<EOL
            {# 

            DECLARATIONS DE CLASSES POUR LA GÉNÉRATION TAILWIND DES STYLES CSS
            NON VISIBLES CAR GÉNÉRÉS DYNAMIQUEMENT
            UPDATED: $date

            CE FICHIER NE DOIT PAS ÊTRE UTILISÉ DANS LES TEMPLATES TWIG

            -----------------------------------------------------
            EOL;
    }

    private function getFileEnd(): string{
        return PHP_EOL.PHP_EOL.'#}';
    }

    private function parseClasses(
        string $classes
    ): array
    {
        $list = [];
        preg_match_all('/class="([\w\s-]+)"/', $classes, $search);
        if(count($search) > 1) {
            foreach (Iterables::toClassList($search[1], false) as $class) {
                $list[$class] = $class;
            }
        }
        return $list;
    }

}