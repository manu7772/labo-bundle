<?php
namespace Aequation\LaboBundle\Controller\Cruds;

use Aequation\LaboBundle\Form\Type\WebpageType;
use Aequation\LaboBundle\Security\Voter\WebsectionVoter;
use App\Entity\Websection;
use App\Entity\Webpage;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/ae-labo/entity', name: 'aequation_labo_entity_')]
#[IsGranted('ROLE_EDITOR')]
class LaboWebpageController extends LaboEntityController
{

    public const CLASSNAME = Webpage::class;
    public const ENTITY = 'Webpage';
    public const ENTITYL = 'webpage';
    public const ENTITY_TYPE = WebpageType::class;

    #[Route('/'.self::ENTITYL, name: self::ENTITYL.'_index', methods: ['GET'])]
    public function index(): Response
    { return parent::index(); }

    #[Route('/'.self::ENTITYL.'/new', name: self::ENTITYL.'_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    { return parent::new($request); }

    #[Route('/'.self::ENTITYL.'/{id}', name: self::ENTITYL.'_show', methods: ['GET'])]
    public function show(int $id): Response
    { return parent::show($id); }

    #[Route('/'.self::ENTITYL.'/{id}/edit', name: self::ENTITYL.'_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    { return parent::edit($request, $id); }

    #[Route('/'.self::ENTITYL.'/{id}', name: self::ENTITYL.'_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    { return parent::delete($request, $id); }

    #[Route('/webpage/remove-websection/{webpage}/{websection}', name: 'webpage_remove_websection', methods: ['GET'])]
    public function removeWebsection(
        Webpage $webpage,
        Websection $websection,
        Request $request
    ): Response
    {
        // return new Response(vsprintf('Remove websection %s from webpage %s', [$websection->getId(), $webpage->getId()]));
        // dd($webpage, $websection, $request);
        $websections = $webpage->getWebsections();
        if($websections->contains($websection)) {
            $webpage->removeWebsection($websection);
            $this->manager->flush();
        }
        $route = $request->headers->get('referer');
        $route ??= $this->generateUrl('app_home');
        return $this->redirect($route);
    }

    #[Route('/webpage/move-websection/{webpage}/{websection}/{position}', name: 'webpage_move_websection', methods: ['GET'])]
    public function moveWebsection(
        Webpage $webpage,
        Websection $websection,
        string $position,
        Request $request
    ): Response
    {
        // return new Response(vsprintf('Move websection %s from webpage %s', [$websection->getId(), $webpage->getId()]));
        // dd($webpage, $websection, $position, $request);
        if($webpage->changePosition($websection, $position)) {
            $this->manager->flush();
        }
        $route = $request->headers->get('referer');
        $route ??= $this->generateUrl('app_home');
        return $this->redirect($route);
    }

}
