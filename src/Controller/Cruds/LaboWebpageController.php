<?php
namespace Aequation\LaboBundle\Controller\Cruds;

use Aequation\LaboBundle\Entity\Ecollection;
use Aequation\LaboBundle\Entity\Item;
use Aequation\LaboBundle\Form\Type\WebpageType;
use Aequation\LaboBundle\Model\Interface\EcollectionInterface;
use Aequation\LaboBundle\Security\Voter\WebsectionVoter;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Aequation\LaboBundle\Service\Interface\EcollectionServiceInterface;
use App\Entity\Websection;
use App\Entity\Webpage;
use Aequation\LaboBundle\Model\Interface\WebpageInterface;
use Aequation\LaboBundle\Model\Interface\WebsectionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    #[Route('/collection/sort-items/{entity}', name: 'sort_items', methods: ['POST'])]
    public function sortItems(
        Ecollection $entity,
        Request $request,
        AppServiceInterface $appService
    ): JsonResponse
    {
        /** @var WebpageInterface|EcollectionInterface $entity */
        /** @var EcollectionServiceInterface */
        $service = $this->manager->getEntityService($entity);
        $raw = json_decode($request->getContent(), true);
        $field = $raw['parentFieldName'] ?? Ecollection::RELATION_FIELDNAME;
        $items = $raw['items'] ?? null;
        if(empty($raw)) {
            // Get only list of items
            $items = $entity->getWebsectionsOrdered(false)->toArray();
            $status = Response::HTTP_OK;
            $data = [
                'user' => $appService->getNormalized($this->getUser(), null, ['groups' => ['index']]),
                'result' => 'Sorted items',
                'message' => 'Got sorted items of entity',
                'date' => date(DATE_ATOM),
                'entity' => $entity->getId(),
                'items' => $appService->getNormalized($items, null, ['groups' => ['index']]), // serialized
            ];    
            return new JsonResponse($data, $status);
        }
        if(is_array($items)) {
            // set the order of the items
            /** @var WebpageInterface|EcollectionInterface $entity */
            $entity = $service->setEcollectionItems($entity, $items, $field);
            $sorted_items = $entity->getWebsectionsOrdered(false)->toArray();
            $sorted_items_euid = array_map(fn(Item $item) => $item->getEuid(), $sorted_items);
            $changed = json_encode($items) === json_encode($sorted_items_euid);
            $message = $changed ? 'Items are sorted' : 'No items were sorted';
            $result = $changed ? 'changed' : 'unchanged';
            $status = Response::HTTP_OK;
        } else {
            $sorted_items = false;
            $message = 'No items to sort';
            $result = 'failed';
            $status = Response::HTTP_BAD_REQUEST;
        }
        $data = [
            'user' => $appService->getNormalized($this->getUser(), null, ['groups' => ['index']]),
            'result' => $result,
            'message' => $message,
            'date' => date(DATE_ATOM),
            'entity' => $entity->getId(),
            'items' => $appService->getNormalized($sorted_items, null, ['groups' => ['index']]), // serialized
        ];
        return new JsonResponse($data, $status);
    }

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
        $em = $this->manager->getEntityManager();
        $uow = $this->manager->getUnitOfWork();
        /** @var Item $websection */
        if($webpage->changePosition($websection, $position)) {
            $this->manager->flush();
        }
        $route = $request->headers->get('referer');
        $route ??= $this->generateUrl('app_home');
        return $this->redirect($route);
    }

}
