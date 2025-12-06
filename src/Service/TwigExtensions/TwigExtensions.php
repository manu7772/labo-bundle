<?php
namespace Aequation\LaboBundle\Service\TwigExtensions;

use DateTime;
use Exception;
use Throwable;
use Stringable;
use Twig\Markup;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\Runtime\EscaperRuntime;

use Symfony\UX\Icons\IconRenderer;
use Twig\Extension\GlobalsInterface;
use Twig\Extension\AbstractExtension;
use Aequation\LaboBundle\Service\AppService;
use Aequation\LaboBundle\Service\Tools\Icons;
use Aequation\LaboBundle\Service\Tools\Times;
use Aequation\LaboBundle\Service\Tools\Classes;
use Aequation\LaboBundle\Service\Tools\HtmlDom;
use Aequation\LaboBundle\Service\Tools\Strings;
use Aequation\LaboBundle\Service\Tools\Encoders;
use Symfony\Component\HttpKernel\KernelInterface;

use Symfony\UX\TwigComponent\ComponentAttributes;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Contracts\Translation\TranslatorInterface;
use Aequation\LaboBundle\Model\Interface\ImageInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Aequation\LaboBundle\Service\Interface\ImageServiceInterface;
use Aequation\LaboBundle\Repository\Interface\CommonReposInterface;
use Aequation\LaboBundle\Service\Interface\LaboAppVariableInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Defines the filters and functions used to render the bundle's templates.
 * Also injects the admin context into Twig global variables as `ea` in order
 * to be used by admin templates.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class TwigExtensions extends AbstractExtension implements GlobalsInterface
{

    public readonly AppEntityManagerInterface $appEntityManager;

    public function __construct(
        private KernelInterface $kernel,
        private AppService $appService,
        private LaboAppVariableInterface $laboAppVariable,
        #[Autowire(service: '.ux_icons.icon_renderer')]
        private IconRenderer $iconRenderer,
        private TranslatorInterface $translator,
        protected ImageServiceInterface $imageService
    ) {
        $this->appEntityManager = $this->appService->get(AppEntityManagerInterface::class);
    }

    /**
     * Get Twig functions
     * @return array
     */
    public function getFunctions(): array
    {
        $functions = [
            // Year
            new TwigFunction('current_year', [Times::class, 'getCurrentYear']),
            // Grants
            new TwigFunction('user_granted', [$this->appService, 'isUserGranted']),
            // Routes
            new TwigFunction('route_exists', [$this->appService, 'routeExists']),
            new TwigFunction('url_if_exists', [$this->appService, 'getUrlIfExists']),
            new TwigFunction('is_current_route', [$this->appService, 'isCurrentRoute']),
            new TwigFunction('findEntitiesByCategorys', [$this, 'findEntitiesByCategorys']),
            new TwigFunction('findEntityBy', [$this, 'findEntityBy']),
            // HTML tools & icons
            new TwigFunction('getImageBase64', [$this, 'getImageBase64']),
            new TwigFunction('decorate', [Strings::class, 'decorate']),
            new TwigFunction('icon', [$this, 'getIcon'], ['is_safe' => ['html']]),
            new TwigFunction('validIcon', [$this, 'getValidIcon'], ['is_safe' => ['html']]),
            // new TwigFunction('uxicon', [IconRenderer::class, 'renderIcon'], ['is_safe' => ['html']]),
            // new TwigFunction('icon', [$this, 'icon']),
            // new TwigFunction('ea_call_function_if_exists', [$this, 'callFunctionIfExists'], ['needs_environment' => true, 'is_safe' => ['html' => true]]),
            // TURBO-UX
            new TwigFunction('turbo_off', [$this, 'turboOff']),
            new TwigFunction('turbo_enable', [$this, 'turboEnable']),
            // Print tools
            new TwigFunction('classname', [Classes::class, 'getClassname']),
            new TwigFunction('shortname', [Classes::class, 'getShortname']),
            new TwigFunction('parent_classes', [Classes::class, 'getParentClasses']),
            new TwigFunction('printr', [Encoders::class, 'getPrintr']),
            new TwigFunction('printFiles', [Encoders::class, 'getPrintFiles']),
            // Globals added to twig
            new TwigFunction('globals', [$this, 'getGlobals']),
            new TwigFunction('liipfilters', [$this, 'getLiipFilters']),
            new TwigFunction('liipformat', [$this, 'getLiipFormat']),
            new TwigFunction('getImageInfo', [$this->imageService, 'getImageInfo']),
            new TwigFunction('anchor', [$this, 'getAnchor']),
        ];

        if($this->kernel->getEnvironment() !== 'dev') {
            // Prevent dump function call if not in dev evnironment
            $functions[] = new TwigFunction('dump', [$this, 'dump']);
        }
        return $functions;
    }

    /**
     * Get Twig filters
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('textToBr', [$this, 'textToBr']),
            new TwigFilter('ucfirst', [$this, 'getUcfirst']),
            new TwigFilter('preg_replace', [$this, 'getPregReplace']),
            new TwigFilter('slug', [Strings::class, 'getSlug']),
            new TwigFilter('formateForWebpage', [Strings::class, 'formateForWebpage']),
            new TwigFilter('hasText', [Strings::class, 'hasText']),
            new TwigFilter('text2array', [Strings::class, 'text2array']),
            new TwigFilter('flashes_to_json', [$this, 'flashesToSJson']),
            // new TwigFilter('ea_apply_filter_if_exists', [$this, 'applyFilterIfExists'], ['needs_environment' => true]),
            new TwigFilter('sort_collection', [$this, 'sortCollection']),
            new TwigFilter('classname', [Classes::class, 'getClassname']),
            new TwigFilter('shortname', [Classes::class, 'getShortname']),
            new TwigFilter('toHtmlAttributes', [HtmlDom::class, 'toHtmlAttributes'], ['is_safe' => ['html']]),
            new TwigFilter('htmlAttributes', [$this, 'getComponentAttributes']),
            new TwigFilter('normalize', [$this->appService, 'getNormalized']),
            new TwigFilter('htmlentities', [$this, 'getHtmlentities']),
            new TwigFilter('isImageEntity', [$this, 'isImageEntity']),
            new TwigFilter('uid', [$this, 'getUid']),
        ];
    }

    /**
     * Get Twig globals
     * @return array
     */
    public function getGlobals(): array
    {
        return [
            'currentYear' => $this->getCurrentYear(),
            'Identity' => $this->appService->getMainEntreprise(),
        ];
    }

    public function getLiipFilters(): array
    {
        return $this->imageService->getLiipFilters()->all();
    }

    public function getLiipFormat(string $filter): string
    {
        try {
            $config = $this->imageService->getLiipFilters()->get($filter);
        } catch (Throwable $th) {
            trigger_error("LiipImagine filter '$filter' not found: ".$th->getMessage(), E_USER_WARNING);
            return 'undefined';
        }
        if(!isset($config['filters']) || !is_array($config['filters']) || empty($config['filters'])) {
            return 'undefined';
        }
        $size = null;
        foreach ($config['filters'] as $filter_name => $data) {
            switch (true) {
                case in_array($filter_name, ['scale']):
                    if(is_array($data['dim'] ?? null) && count($data['dim']) >= 2) {
                        $size = ['width' => $data['dim'][0], 'height' => $data['dim'][1]];
                        break 2;
                    }
                    break;
                case in_array($filter_name, ['thumbnail', 'crop']):
                    if(is_array($data['size'] ?? null) && count($data['size']) >= 2) {
                        $size = ['width' => $data['size'][0], 'height' => $data['size'][1]];
                        break 2;
                    }
                    break;
                case in_array($filter_name, ['fixed']):
                    $size = ['width' => $data['width'] ?? $data['height'], 'height' => $data['height'] ?? $data['width']];
                    break 2;
            }
        }
        if(!is_array($size) || count($size) < 2) {
            return 'undefined';
        }
        return $size['width'] > $size['height'] * 1.5
            ? 'landscape'
            : 'portrait';
    }

    public function getAnchor(AppEntityInterface $entity, ?string $prefix = null): string
    {
        return ($prefix ?? 'anchor') . '_' . $entity->getId();
    }


    /*************************************************************************************
     * FUNCTIONS
     *************************************************************************************/

    /**
     * Get current year as YYYY
     * @return string
     */
    public function getCurrentYear(): string
    {
        $date = new DateTime('NOW');
        return $date->format('Y');
    }

    /**
     * Removed dump() function to prevent error when production environment
     * @param mixed $value
     * @return null
     */
    public function dump(mixed $value): null
    {
        return null;
    }

    public function getIcon(
        string|object $icon,
        array|string $attributes = []
    ): ?string
    {
        if(is_string($icon) && $test = $this->appEntityManager->getClassnameByShortname($icon)) {
            $icon = $test;
        }
        $icon = Icons::getIcon($icon, $attributes, true);
        if(is_array($icon)) {
            $icon = $this->iconRenderer->renderIcon($icon['icon'], $icon['attributes']);
        }
        return $icon;
    }

    public function findEntitiesByCategorys(
        string $entityClass,
        array|string $categories = [],
        ?string $search = null,
        ?string $context = 'auto'
    ): array
    {
        if(!class_exists($entityClass)) {
            $entityClass = $this->appEntityManager->getClassnameByShortname($entityClass);
        }
        /** @var CommonReposInterface */
        $repo = $this->appEntityManager->getRepository($entityClass);
        if($repo && method_exists($repo, 'findByCategorys')) {
            return $repo->findByCategorys($categories, $search, $context);
        } else if($this->appService->isDev()) {
            throw new Exception("Repository not found for entity ".$entityClass." or has no method findByCategorys");
        }
        return [];
    }

    public function findEntityBy(
        string $entityClass,
        array $criteria,
        ?array $orderBy = null
    ): ?object
    {
        if(!class_exists($entityClass)) {
            $entityClass = $this->appEntityManager->getClassnameByShortname($entityClass);
        }
        /** @var ServiceEntityRepository */
        $repo = $this->appEntityManager->getRepository($entityClass);
        if($repo) {
            return $repo->findOneBy($criteria, $orderBy);
        } else if($this->appService->isDev()) {
            throw new Exception("Repository not found for entity ".$entityClass);
        }
        return null;
    }

    public function getImageBase64(string $path): ?string
    {
        $path = preg_replace('#(resolve\\/)#', '', $path); // ?????
        $path = ltrim($path, '/');
        $path = $this->appService->getDir('public/'.$path);
        if(!file_exists($path)) {
            if($this->appService->isDev()) {
                throw new Exception("File not found: $path");
            }
            return null;
        }
        return @file_exists($path)
            ? 'data:image/'.pathinfo($path, PATHINFO_EXTENSION).';base64,'.base64_encode(file_get_contents($path))
            : null;
    }

    public function getValidIcon(
        bool $value = true,
        string|array $attributes = [],
        string $icon_true = 'ux@tabler:check',
        string $icon_false = 'ux@tabler:x',
    ) : ?string
    {
        $icon = Icons::getValidIcon($value, $attributes, $icon_true, $icon_false, true);
        if(is_array($icon)) {
            $icon = $this->iconRenderer->renderIcon($icon['icon'], $icon['attributes']);
        }
        return $icon;
    }

    // public function decorate(
    //     string $text,
    //     string $tagname,
    //     array $attributes = [],
    // ): Markup
    // {
    //     return Strings::decorate(text: $text, tagname: $tagname, attributes: $attributes);
    // }

    // public function icon(
    //     string $name,
    //     array $attributes = [],
    //     string $default_type = "fa-regular",
    // ): Markup
    // {
    //     $attributes['class'] ??= [];
    //     if(is_string($attributes['class'])) {
    //         $attributes['class'] = preg_split('/\\s+/', $attributes['class'], -1, PREG_SPLIT_NO_EMPTY);
    //     }
    //     $names = preg_split('/\\s+/', $name, -1, PREG_SPLIT_NO_EMPTY);
    //     // $names = array_map(function ($n) {
    //     //     return preg_replace('/^(fa-)?/', 'fa-', $n);
    //     // }, $names);
    //     foreach (array_reverse($names) as $n) {
    //         array_unshift($attributes['class'], $n);
    //     }
    //     if(empty(array_intersect(["fa-regular", "fa-solid", "fa-light", "fa-duotone", "fa-thin"], $attributes['class']))) {
    //         array_unshift($attributes['class'], $default_type);
    //     }
    //     return Strings::markup('<i'.Strings::htmlAttributes($attributes).'></i>');
    // }

    public function turboOff() : Markup
    {
        return Strings::markup(' data-turbo-temporary="false"');
    }

    public function turboEnable(bool $enable) : Markup
    {
        /** @see https://turbo.hotwired.dev/handbook/drive#prefetching-links-on-hover */
        return Strings::markup(' data-turbo-prefetch="'.($enable ? 'true' : 'false').'"');
        // return Strings::markup(' data-turbo="'.($enable ? 'true' : 'false').'"');
    }

    public function getShortname(object|string $objectOrClass, bool $getfast = false): string
    {
        return is_object($objectOrClass) || class_exists($objectOrClass)
            ? Classes::getShortname($objectOrClass, $getfast)
            : '';
    }

    // public function getParentClasses(object|string $objectOrClass, bool $reverse = false, bool $asReflclass = true): array
    // {
    //     return Classes::getParentClasses($objectOrClass, $reverse, $asReflclass);
    // }


    /*************************************************************************************
     * FILTERS
     *************************************************************************************/

    public function textToBr(string $string): Markup
    {
        return Strings::markup(nl2br($string));
    }

    public function getUcfirst(string $string): string
    {
        return ucfirst($string);
    }

    public function getPregReplace(string $string, string $pattern, string $replacement): string
    {
        return preg_replace($pattern, $replacement, $string) ?? $string;
    }

    public function flashesToSJson(array $flashes): string
    {
        foreach ($flashes as $type => $messages) {
            if(in_array($type, ['info','success','warning','error'])) {
                foreach ($messages as $key => $message) {
                    switch (true) {
                        case is_string($message):
                            // do nothing
                            break;
                        case $messages instanceof TranslatableInterface:
                            $flashes[$type][$key] = $message->trans($this->translator);
                            break;
                        case $message instanceof Stringable:
                            $flashes[$type][$key] = $message->__toString();
                            break;
                        default:
                            # code...
                            break;
                    }
                }
            }
        }
        return json_encode($flashes);
    }

    public function sortCollection(array $choices, mixed $data): array
    {
        // if($data instanceof Collection && !$data->isEmpty()) {
        //     dd($choices, $data);
        //     $sorted_data = [];
        //     $index = $data->count();
        //     foreach ($data as $item) {
        //         $sorted_data[$item->getId()] = $index--;
        //     }
        //     uasort($choices, function($a, $b) use ($sorted_data) {
        //         if(!array_key_exists($a->value, $sorted_data)) return 1;
        //         if(!array_key_exists($b->value, $sorted_data)) return -1;
        //         return $sorted_data[$a->value] > $sorted_data[$b->value] ? -1 : 1;
        //     });
        // }
        return $choices;
    }

    public function getComponentAttributes(
        ?array $attributes = []
    ): ComponentAttributes
    {
        array_walk($attributes, function(&$v) {
            if(is_array($v)) {
                $v = array_filter($v, function($vv) {
                    return !empty(is_string($vv) ? trim($vv) : $vv);
                });
                $v = implode(' ', $v);
            }
            if(is_string($v)) {
                $v = trim($v);
                if(empty($v)) {
                    $v = null;
                }
            }
        });
        $attributes = array_filter($attributes, function($v) {
            return !empty($v);
        });
        return new ComponentAttributes($attributes, new EscaperRuntime(Strings::CHARSET));
    }

    public function getHtmlentities(?string $string, bool $striptags = false, int $flags = ENT_QUOTES|ENT_SUBSTITUTE): Markup
    {
        return Strings::markup(empty($string) ? '' : htmlentities($striptags ? strip_tags($string) : $string, $flags));
    }

    public function isImageEntity(mixed $entity): bool
    {
        if(is_object($entity)) {
            if($entity instanceof EntityDto) {
                $entity = $entity->getInstance();
            }
            return $entity instanceof ImageInterface;
        }
        return false;
    }

    public function getUid(mixed $object, ?string $prefix = null): ?string
    {
        switch(true) {
            case is_string($object):
                return (strlen((string) $prefix) ? $prefix.'-' : '').md5($object);
                break;
            case is_int($object):
                return (strlen((string) $prefix) ? $prefix.'-' : '').$object;
                break;
            case is_float($object):
                return (strlen((string) $prefix) ? $prefix.'-' : '').md5(str_replace('.', '_', (string)$object));
                break;
            case $object instanceof AppEntityInterface:
                return (strlen((string) $prefix) ? $prefix.'-' : '').md5($object->getEuid());
                break;
            case is_object($object):
                return (strlen((string) $prefix) ? $prefix.'-' : '').spl_object_id($object);
                break;
            default:
                return null;
                break;
        }
    }

}
