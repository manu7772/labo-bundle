<?php
namespace Aequation\LaboBundle\Controller\Cruds;

use App\Entity\Slide;
use App\Entity\Websection;
use Aequation\LaboBundle\Entity\Item;
use Aequation\LaboBundle\Entity\Ecollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Aequation\LaboBundle\Form\Type\SlideType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Aequation\LaboBundle\Security\Voter\SlideVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Aequation\LaboBundle\Model\Final\FinalWebpageInterface;
use Aequation\LaboBundle\Model\Interface\WebsectionInterface;
use Aequation\LaboBundle\Model\Interface\EcollectionInterface;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Aequation\LaboBundle\Service\Interface\EcollectionServiceInterface;

#[Route(path: '/ae-labo/entity', name: 'aequation_labo_entity_')]
#[IsGranted('ROLE_EDITOR')]
class LaboSlideController extends LaboEntityController
{

    public const CLASSNAME = Slide::class;
    public const ENTITY = 'Slide';
    public const ENTITYL = 'slide';
    public const ENTITY_TYPE = SlideType::class;

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

    #[Route('/'.self::ENTITYL.'/{id}/{field}/{value}', name: self::ENTITYL.'_boolean', methods: ['GET', 'POST'], requirements: ['value' => '0|1'], defaults: ['value' => null])]
    public function boolvalue(Request $request, int $id, string $field, ?bool $value): Response
    { return parent::boolvalue($request, $id, $field, $value); }

}
