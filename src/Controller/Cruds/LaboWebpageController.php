<?php
namespace Aequation\LaboBundle\Controller\Cruds;

use Aequation\LaboBundle\Form\Type\WebpageType;

use App\Entity\Webpage;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/ae-labo/entity', name: 'aequation_labo_entity_')]
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

}
