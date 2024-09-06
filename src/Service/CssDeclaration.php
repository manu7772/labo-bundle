<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Component\CssManager;
use Aequation\LaboBundle\EventListener\Attribute\AppEvent;
use Aequation\LaboBundle\Form\Type\CssType;
use Aequation\LaboBundle\Model\Attribute\CssClasses;
use Aequation\LaboBundle\Service\Base\BaseService;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Aequation\LaboBundle\Service\Interface\CacheServiceInterface;
use Aequation\LaboBundle\Service\Interface\CssDeclarationInterface;
use Aequation\LaboBundle\Service\Interface\FormServiceInterface;
use Aequation\LaboBundle\Service\Tools\Classes;
use Aequation\LaboBundle\Service\Tools\Files;
use Aequation\LaboBundle\Service\Tools\Iterables;
use Aequation\LaboBundle\Service\Tools\Strings;
use App\Entity\Slide;
use DateTime;
use DateTimeZone;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Process\Process;
use Symfony\Contracts\Cache\ItemInterface;
use Symfonycasts\TailwindBundle\TailwindBuilder;

// #[AsAlias(CssDeclarationInterface::class, public: true)]
class CssDeclaration extends BaseService implements CssDeclarationInterface
{

    public const CACHE_CSS_ATTRIBUTES_NAME = 'Cache_Css_Attributes';
    public const CACHE_CSS_ATTRIBUTES_LIFE = 24 * 3600;

    public const FILE_PATH = 'templates';
    public const FILE_NAME = 'tailwind_css_declarations.html.twig';

    public const BASE_PATH = 'lib/aequation/labo-bundle/templates';
    public const BASE_NAME = 'tailwind_css_declarations.html.twig';

    public const CLASS_TYPES = ['ORIGIN', 'COMPUTED', 'ADDED','UNKNOWN'];

    protected string $filepath;
    protected string $filename;
    protected string|false $filecontent;
    protected array $classes;
    protected array $base_classes;
    protected array $computed_classes;
    protected array $cssAttributes;

    public function __construct(
        protected AppServiceInterface $appService,
        #[Autowire(service: 'tailwind.builder')]
        protected TailwindBuilder $tailwindBuilder,
    )
    {
        $this->setFilepath(static::FILE_PATH);
        $this->setFilename(static::FILE_NAME);
    }

    public function setFilepath(
        string $filepath,
        bool $create = true
    ): static
    {
        $this->filepath = Files::getProjectDir($filepath, $create);
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
            $base = Files::getFileContent(static::BASE_PATH, static::BASE_NAME);
            $this->base_classes = [];
            if($base) {
                $this->base_classes = $this->parseClasses($base);
            }
        }
        return $this->base_classes;
    }

    private function getCssAttributes(): array
    {
        // if(!$this->appService->isProd()) {
        //     $this->appService->getCache()->delete(static::CACHE_CSS_ATTRIBUTES_NAME);
        // }
        $this->cssAttributes ??= $this->appService->getCache()->get(
            key: static::CACHE_CSS_ATTRIBUTES_NAME,
            callback: function(ItemInterface $item) {
                if(!empty(static::CACHE_CSS_ATTRIBUTES_LIFE)) {
                    $item->expiresAfter(static::CACHE_CSS_ATTRIBUTES_LIFE);
                }
                /** @var AppEntityManagerInterface $app_em */
                $app_em = $this->appService->get(AppEntityManagerInterface::class);
                $classes = [];
                foreach ($this->appService->getAppClasses(false) as $service) {
                    if($app_em->entityExists(classname: $service['classname'], allnamespaces: false, onlyInstantiables: true)) {
                        $classes[] = $entity = $app_em->getNew($service['classname']);
                        $app_em->initEntity(entity: $entity, event: AppEvent::PRE_SET_DATA);
                    } else {
                        $classes[] = empty($service['service']) ? $service['classname'] : $service['service'];
                    }
                }
                // dump($classes);
                return Classes::getAttributes(CssClasses::class, $classes);
            },
            commentaire: "CssClasses attributes on all classes",
        );
        // dump($this->cssAttributes);
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
            // dump($this->computed_classes);
        }
        return $this->computed_classes;
    }

    private function getFileContent(
        bool $refresh = false
    ): string|false
    {
        if(!isset($this->filecontent) || $refresh) $this->filecontent = Files::getFileContent($this->filepath, $this->filename);
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
        return Files::putFileContent($this->filepath, $this->filename, $content);
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
        return Files::putFileContent($this->filepath, $this->filename, $content);
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
        $formService = $this->appService->get(FormServiceInterface::class);
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
        // return '{# '.PHP_EOL.PHP_EOL.'DECLARATIONS DE CLASSES POUR LA GÉNÉRATION TAILWIND DES STYLES CSS'.PHP_EOL.'NON VISIBLES CAR GÉNÉRÉS DYNAMIQUEMENT'.PHP_EOL.'UPDATED: '.$this->appService->getCurrentDatetime()->format(DATE_ATOM).PHP_EOL.PHP_EOL.'CE FICHIER NE DOIT PAS ÊTRE UTILISÉ'.PHP_EOL.'-----------------------------------------------------'.PHP_EOL;
        $date = $this->appService->getCurrentDatetime()->format(DATE_ATOM);
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