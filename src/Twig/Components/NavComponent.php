<?php
namespace Aequation\LaboBundle\Twig\Components;

use Aequation\LaboBundle\Model\Interface\MenuInterface;
use Aequation\LaboBundle\Model\Interface\WebpageInterface;
use Aequation\LaboBundle\Repository\Interface\MenuRepositoryInterface;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\Component\Routing\Router;

#[AsTwigComponent()]
class NavComponent extends AbstractController
{

    public ?WebpageInterface $webpage;

    public function __construct(
        private AppServiceInterface $appService,
        private MenuRepositoryInterface $menuRepository,
    ) {}

    public function getItems(): array
    {
        $items = [];
        /** @var ?MenuInterface $menu_items */
        $menu_items = isset($this->webpage)
            ? $this->webpage->getMainmenu() ?? $this->menuRepository->findPreferedMenu()
            : $this->menuRepository->findPreferedMenu();
        if($menu_items && !$menu_items->getItems(true)->isEmpty()) {
            $items = $this->appService->getNormalized($menu_items->getItems(true), null, ['groups' => 'index']);
            foreach ($items as $index => $item) {
                $items[$index]['href'] = $this->appService->getUrlIfExists('app_webpage', ['webpage' => $item['slug']], Router::ABSOLUTE_URL);
            }
        }
        return array_values($items);
    }

}