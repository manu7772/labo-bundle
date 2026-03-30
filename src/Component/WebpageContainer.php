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

class WebpageContainer implements WebpageContainerInterface
{
    public const DIRECTION_HOME = 'home';
    public const DIRECTION_WEBPAGE = 'webpage';
    public const DIRECTION_MENU = 'menu';
    public const DIRECTION_ERROR = 'error';

    public const DEFAULT_HOMEPAGE = 'webpage/homepage.html.twig';
    public const DEFAULT_MENUPAGE = 'menu/menu_items.html.twig';
    public const DEFAULT_ERRORPAGE = 'webpage/error.html.twig';

    protected array $elements = [];
    protected array $repos = [];

    public function __construct(
        array $elements,
        protected AppEntityManagerInterface $appEm
    ) {
        $this->initialize($elements);
    }

    protected function initialize(array $elements): void
    {
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
        // If empty, we add the webpage repository to be able to find the prefered webpage if no slug is provided in url
        if(empty($elements)) {
            $wpservice = $this->appEm->getAppService()->get(LaboWebpageServiceInterface::class);
            $elements = ($prefered = $wpservice->getPreferedWebpage()) ? [$prefered] : [];
        }
        $this->elements = [
            'elements' => [],
            'webpage' => null,
            'subwebpage' => null,
            'menu' => null,
        ];
        $previousElement = null;
        // dump($elements);
        foreach ($elements as $key => $element) {
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
                $this->elements['elements'][$element->getEuid()] = $element;
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
        // $this->elements['elements'] = array_filter($new_elements['elements'], fn($element) => $element instanceof AppEntityInterface && (!($element instanceof EnabledInterface) || $element->isActive()));
        // $this->elements['elements'] = array_filter($new_elements['elements'], fn($element) => is_object($element));
        // If last element is a menu, we consider the next element as a subwebpage of this menu and we add it to the container elements, so it can be used in the menu template to display the subwebpage content if needed
        // $lastElement = end($this->elements['elements']);
        // if($lastElement instanceof FinalMenuInterface) {
        //     $menuItems = $lastElement->getItems()->filter(fn($item) => $item->isActive());
        //     if($subwebpage = $menuItems->first()) {
        //         $this->elements['subwebpage'] = $subwebpage;
        //     }
        // }
        // Add webpage
        $this->elements['webpage'] = $this->getWebpage();
        // Add menu if direction is menu
        if($this->isMenu()) {
            $this->elements['menu'] = reset($this->elements['elements']);
            if(empty($this->elements['subwebpage'])) {
                foreach ($this->elements['elements'] as $element) {
                    if($element instanceof FinalWebpageInterface) {
                        $this->elements['subwebpage'] = $element;
                        break;
                    }
                }
            }
            if(empty($this->elements['subwebpage'])) {
                $menuItems = $this->elements['menu']->getItems()->filter(fn($item) => $item->isActive());
                if($subwebpage = $menuItems->first()) {
                    $this->elements['subwebpage'] = $subwebpage;
                }
            }
        }
        // dump(
        //     $this->elements,
        //     [
        //         'isHome' => $this->isHome(),
        //         'isWebpage' => $this->isWebpage(),
        //         'isMenu' => $this->isMenu(),
        //         'isError' => $this->isError(),
        //     ],
        // );
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
        return $includeSelf ? array_merge(['wpContainer' => $this], $this->elements) : $this->elements;
    }

    public function getWebpage(): ?FinalWebpageInterface
    {
        $firstElement = reset($this->elements['elements']);
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
        $firstElement = reset($this->elements['elements']);
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

}