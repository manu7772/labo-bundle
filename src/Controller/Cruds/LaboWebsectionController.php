<?php
namespace Aequation\LaboBundle\Controller\Cruds;

use Aequation\LaboBundle\Form\Type\WebsectionType;
use Aequation\LaboBundle\Security\Voter\WebsectionVoter;
use App\Entity\Websection;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/ae-labo/entity', name: 'aequation_labo_entity_')]
class LaboWebsectionController extends LaboEntityController
{

    public const CLASSNAME = Websection::class;
    public const ENTITY = 'Websection';
    public const ENTITYL = 'websection';
    public const ENTITY_TYPE = WebsectionType::class;

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

    #[Route('/'.self::ENTITYL.'/enable/{websection}/{value}', name: self::ENTITYL.'_enable', methods: ['GET'])]
    #[IsGranted('ROLE_EDITOR')]
    public function moveWebsection(
        Websection $websection,
        int $value,
        Request $request
    ): Response
    {
        $route = $request->headers->get('referer');
        // dd($route, $websection, $value, $request);
        switch ($value) {
            case 0:
                $websection->setEnabled(false);
                break;
            default:
                $websection->setEnabled(true);
                break;
        }
        $this->manager->flush();
        return $this->redirect($route);
    }

}
