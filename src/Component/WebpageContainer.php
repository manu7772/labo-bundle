<?php
namespace Aequation\LaboBundle\Component;

use Aequation\LaboBundle\Component\Interface\WebpageContainerInterface;
use Aequation\LaboBundle\Model\Final\FinalMenuInterface;
use Aequation\LaboBundle\Model\Final\FinalWebpageInterface;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Model\Interface\HasCurrentItemInterface;
use Aequation\LaboBundle\Model\Interface\ScreenableInterface;
use Aequation\LaboBundle\Model\Interface\SlugInterface;
use Aequation\LaboBundle\Repository\Interface\CommonReposInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Interface\LaboWebpageServiceInterface;
use LogicException;
use ReflectionClass;

class WebpageContainer implements WebpageContainerInterface
{
    public const ICON = 'tabler:brackets-contain';

    public const DIRECTION_HOME = 'home';
    public const DIRECTION_WEBPAGE = 'webpage';
    public const DIRECTION_MENU = 'menu';
    public const DIRECTION_ERROR = 'error';

    public const DEFAULT_HOMEPAGE = 'webpage/homepage.html.twig';
    public const DEFAULT_MENUPAGE = 'menu/menu_items.html.twig';
    public const DEFAULT_ERRORPAGE = 'webpage/error.html.twig';

    protected array $elements = [];
    protected array $repos = [];

    public readonly WebpageContainerInterface $wpContainer;
    public readonly HasCurrentItemInterface $webpage;
    public readonly ?FinalWebpageInterface $subwebpage;
    public readonly ?FinalMenuInterface $menu;
    public readonly ?AppEntityInterface $currentItem;
    public readonly string $basepath;
    public readonly string $path;
    public readonly string $original_path;

    public function __construct(
        array $elements,
        protected AppEntityManagerInterface $appEm
    ) {
        $this->initialize($elements);
        dump($this->toArray(true));
    }

    public function getId(): string
    {
        return 'wpcontainer';
    }

    public static function getShortname(): string
    {
        $rc = new ReflectionClass(static::class);
        return $rc->getShortName();
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        $firstElement = reset($this->elements);
        return $firstElement ? $firstElement->getName() : "Conteneur de page";
    }

    public function isWpContainer(): bool
    {
        return true;
    }

    protected function initialize(array $elements): void
    {
        $path = $elements;
        $this->original_path = $this->toStringPath($path);
        $elements = array_filter($elements, fn($element) => !empty($element) && (is_string($element) || $element instanceof AppEntityInterface));
        if(empty($this->repos)) {
            $entityNames = array_filter($this->appEm->getEntityNames(true, false, true), fn($classname) => is_a($classname, SlugInterface::class, true) && (is_a($classname, ScreenableInterface::class, true) || is_a($classname, FinalWebpageInterface::class, true)), ARRAY_FILTER_USE_KEY);
            foreach ($entityNames as $classname => $shortname) {
                $this->repos[$shortname] = $this->appEm->getRepository($classname);
                if(!($this->repos[$shortname] instanceof CommonReposInterface)) {
                    throw new LogicException(sprintf("Repository for entity %s must implement CommonReposInterface", $classname));
                }
            }
        }
        $this->elements = [];
        // If empty, we add the webpage repository to be able to find the prefered webpage if no slug is provided in url
        if(empty($elements)) {
            $wpservice = $this->appEm->getAppService()->get(LaboWebpageServiceInterface::class);
            $elements = ($prefered = $wpservice->getPreferedWebpage()) ? [$prefered] : [];
        }
        $previousElement = null;
        // dump($elements);
        foreach ($elements as $element) {
            if(is_string($element)) {
                foreach ($this->repos as $repo) {
                    if($test = $repo->findOneBy(['slug' => $element])) {
                        $element = $test;
                        break;
                    }
                }
            }
            // dump($key, $element);
            if($element instanceof AppEntityInterface) {
                $this->elements[$element->getEuid()] = $element;
                if($previousElement) {
                    if($this->isEmbedder($previousElement)) {
                        $previousElement->currentItem = $element;
                    } else {
                        throw new LogicException(sprintf("Element with slug %s is not an embedder and cannot have a current item", $previousElement?->__toString()));
                    }
                }
                $previousElement = $element;
            } else {
                throw new LogicException(sprintf("Element %s is not an instance of AppEntityInterface", is_object($element) ? get_class($element) : gettype($element)));
            }
        }
        // $this->elements = array_filter($new_elements['elements'], fn($element) => $element instanceof AppEntityInterface && (!($element instanceof EnabledInterface) || $element->isActive()));
        // $this->elements = array_filter($new_elements['elements'], fn($element) => is_object($element));
        // If last element is a menu, we consider the next element as a subwebpage of this menu and we add it to the container elements, so it can be used in the menu template to display the subwebpage content if needed
        // $lastElement = end($this->elements);
        // if($lastElement instanceof FinalMenuInterface) {
        //     $menuItems = $lastElement->getItems()->filter(fn($item) => $item->isActive());
        //     if($subwebpage = $menuItems->first()) {
        //         $this->subwebpage = $subwebpage;
        //     }
        // }
        // Add webpage
        $this->webpage = $this->getWebpage();
        // Add menu if direction is menu
        if($this->isMenu()) {
            $this->menu = reset($this->elements);
            if(!isset($this->subwebpage)) {
                foreach ($this->elements as $element) {
                    if($element instanceof FinalWebpageInterface) {
                        $this->subwebpage = $element;
                        $this->elements[$element->getEuid()] = $element;
                        break;
                    }
                }
            }
            if(!isset($this->subwebpage)) {
                $menuItems = $this->menu->getItems()->filter(fn($item) => $item->isActive());
                if($subwebpage = $menuItems->first()) {
                    $this->subwebpage = $subwebpage;
                    $this->elements[$subwebpage->getEuid()] = $subwebpage;
                }
            }
        }
        // Default items
        $this->menu ??= null;
        $this->subwebpage ??= null;
        $last = end($this->elements);
        $this->currentItem = $this->isEmbedder($last) ? null : $last;
        // Finale base path
        // Get all elements but last
        $basepathElements = array_slice($this->elements, 0, -1);
        $this->basepath = implode('/', array_map(fn($element) => $element->getSlug(), $basepathElements));
        // final path
        // $this->path = array_map(fn($element) => $element instanceof SlugInterface ? $element->getSlug() : (is_string($element) ? $element : null), $this->elements);
        $this->path = implode('/', array_map(fn($element) => $element->getSlug(), $this->elements));
    }

    public function getElements(): array
    {
        return $this->elements;
    }

    public function getUserItems(): array
    {
        return $this->elements;
    }

    protected function isEmbedder($element): bool
    {
        return $element instanceof HasCurrentItemInterface;
    }

    public function getAppEntityManager(): AppEntityManagerInterface
    {
        return $this->appEm;
    }

    public function toArray(bool $includeSelf = false): array
    {
        $data = [
            'elements' => $this->elements,
            'webpage' => $this->webpage,
            'subwebpage' => $this->subwebpage,
            'menu' => $this->menu,
            // 'path' => $this->path,
            // 'original_path' => $this->original_path,
        ];
        return $includeSelf ? array_merge(['wpContainer' => $this], $data) : $data;
    }

/**
 * [WARNING] The configuration location for the Symfony CLI has changed in v5.17.0.                                       
 * Your configuration is still stored in the legacy directory.                                                            
 * Please follow these instructions:                                                                                      
 * symfony server:stop --all                                                                                           
 * symfony proxy:stop                                                                                                  
 * move "/home/dujardin2026/.symfony5" to "/home/dujardin2026/.config/symfony-cli"
 */

    public function getWebpage(): ?FinalWebpageInterface
    {
        $firstElement = reset($this->elements);
        switch (true) {
            case $firstElement instanceof FinalWebpageInterface:
                return $firstElement;
                break;
            case $firstElement instanceof FinalMenuInterface:
                return $firstElement->getWebpage();
                break;
            default:
                return null;
                break;
        }
    }

    public function getTwigfile(): string
    {
        $wp = $this->getWebpage();
        return $wp ? $wp->getTwigfile() : null;
    }

    public function getDirection(): string
    {
        $firstElement = reset($this->elements);
        switch (true) {
            case false === $firstElement:
                return static::DIRECTION_ERROR;
                break;
            case $firstElement instanceof FinalWebpageInterface:
                return $firstElement->isPrefered() ? static::DIRECTION_HOME : static::DIRECTION_WEBPAGE;
                break;
            case $firstElement instanceof FinalMenuInterface:
                return static::DIRECTION_MENU;
                break;
            default:
                return static::DIRECTION_ERROR;
                break;
        }
    }

    public function isHome(): bool
    {
        return $this->getDirection() === static::DIRECTION_HOME;
    }

    public function isWebpage(): bool
    {
        return in_array($this->getDirection(), [static::DIRECTION_HOME, static::DIRECTION_WEBPAGE]);
    }

    public function isMenu(): bool
    {
        return $this->getDirection() === static::DIRECTION_MENU;
    }

    public function isError(): bool
    {
        return $this->getDirection() === static::DIRECTION_ERROR;
    }

    protected function toStringPath(array $path): string
    {
        // Transform elements to their slug if they are objects, or keep them as strings if they are already strings, and filter out any null values
        $path = array_map(fn($element) => is_string($element) ? $element : ($element instanceof SlugInterface ? $element->getSlug() : null), $path);
        $path = array_filter($path, fn($element) => $element !== null);
        return implode('/', $path);
    }

}