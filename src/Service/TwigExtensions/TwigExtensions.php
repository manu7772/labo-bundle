<?php
namespace Aequation\LaboBundle\Service\TwigExtensions;

use Aequation\LaboBundle\Service\Interface\LaboAppVariableInterface;
use Aequation\LaboBundle\Service\AppService;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Tools\Classes;
use Aequation\LaboBundle\Service\Tools\Encoders;
use Aequation\LaboBundle\Service\Tools\HtmlDom;
use Aequation\LaboBundle\Service\Tools\Icons;
use Aequation\LaboBundle\Service\Tools\Strings;
use Aequation\LaboBundle\Service\Tools\Times;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Icons\IconRenderer;
use Doctrine\Common\Collections\Collection;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\Markup;

use DateTime;
use Stringable;

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
            new TwigFilter('slug', [Strings::class, 'getSlug']),
            new TwigFilter('hasText', [Strings::class, 'hasText']),
            new TwigFilter('text2array', [Strings::class, 'text2array']),
            new TwigFilter('flashes_to_json', [$this, 'flashesToSJson']),
            // new TwigFilter('ea_apply_filter_if_exists', [$this, 'applyFilterIfExists'], ['needs_environment' => true]),
            new TwigFilter('sort_collection', [$this, 'sortCollection']),
            new TwigFilter('classname', [Classes::class, 'getClassname']),
            new TwigFilter('shortname', [Classes::class, 'getShortname']),
            new TwigFilter('toHtmlAttributes', [HtmlDom::class, 'toHtmlAttributes'], ['is_safe' => ['html']]),
            new TwigFilter('normalize', [$this->appService, 'getNormalized']),
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
            // dump($icon);
            $icon = $this->iconRenderer->renderIcon($icon['icon'], $icon['attributes']);
        }
        return $icon;
    }

    public function getImageBase64(string $path): ?string
    {
        $path = preg_replace('#(resolve\\/)#', '', $path); // ?????
        $path = ltrim($path, '/');
        $path = $this->appService->getDir('public/'.$path);
        if(!file_exists($path)) {
            throw new \Exception("File not found: $path");
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
            // dump($icon);
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
        return Strings::markup(' data-turbo="'.($enable ? 'true' : 'false').'"');
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
        if($data instanceof Collection && !$data->isEmpty()) {
            $sorted_data = [];
            $index = $data->count();
            foreach ($data as $item) {
                $sorted_data[$item->getId()] = $index--;
            }
            // dump($sorted_data);
            uasort($choices, function($a, $b) use ($sorted_data) {
                if(!array_key_exists($a->value, $sorted_data)) return 1;
                if(!array_key_exists($b->value, $sorted_data)) return -1;
                return $sorted_data[$a->value] > $sorted_data[$b->value] ? -1 : 1;
            });
        }
        return $choices;
    }


}
