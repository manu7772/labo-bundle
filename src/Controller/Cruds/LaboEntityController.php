<?php
namespace Aequation\LaboBundle\Controller\Cruds;

use Aequation\LaboBundle\Component\ClassmetadataReport;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Model\Interface\WebpageInterface;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Interface\LaboBundleServiceInterface;
use Aequation\LaboBundle\Service\Tools\Classes;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

use Exception;
use Symfony\Component\HttpFoundation\Request;

abstract class LaboEntityController extends AbstractController
{

    public const CLASSNAME = '';
    public const ENTITY = '';
    public const ENTITYL = '';
    public const ENTITY_TYPE = '';

    public readonly AppEntityManagerInterface $manager;
    public readonly ClassmetadataReport $meta_info;

    public function __construct(
        protected LaboBundleServiceInterface $laboService,
        protected AppEntityManagerInterface $appEntityManager,
        protected Environment $template,
        #[Autowire('%env(APP_ENV)%')]
        protected string $env,
    )
    {
        $this->manager = $this->appEntityManager->getEntityService(static::CLASSNAME);
        $this->meta_info = $this->manager->getEntityMetadataReport();
        // control constants
        if($this->env === 'dev') {
            // dump($this->manager);
            if(!$this->appEntityManager->entityExists(static::CLASSNAME)) {
                throw new Exception(vsprintf('Erreur %s ligne %d: entity %s not found!', [__METHOD__, __LINE__, static::CLASSNAME]));
            }
            $shortname = Classes::getShortname(static::CLASSNAME);
            if(empty($shortname)) {
                throw new Exception(vsprintf('Erreur %s ligne %d: shortname of entity "%s" could not be determined!', [__METHOD__, __LINE__, static::CLASSNAME]));
            }
            if($shortname !== static::ENTITY) {
                throw new Exception(vsprintf('Erreur %s ligne %d: static shortname of entity "%s" should be "%s"!', [__METHOD__, __LINE__, static::ENTITY, $shortname]));
            }
            // $shortname = strtolower($shortname);
            // if($shortname !== static::ENTITYL) {
            //     throw new Exception(vsprintf('Erreur %s ligne %d: static LOWER shortname of entity %s should be %s!', [__METHOD__, __LINE__, static::ENTITYL, $shortname]));
            // }
        }
    }

    protected function getCrudTemplate(
        string $name,
        AppEntityInterface|string|null $entity = null,
    ): string
    {
        $entity = strtolower(Classes::getShortname($entity ?? static::CLASSNAME)) ?? static::ENTITYL;
        $folders = [$entity, 'default'];
        foreach ($folders as $folder) {
            $template = vsprintf('@AequationLabo/cruds/%s/%s.html.twig', [$folder, $name]);
            if($this->template->getLoader()->exists($template)) return $template;
        }
        throw new Exception(vsprintf('Erreur %s ligne %d: template %s inconnu', [__METHOD__, __LINE__, $entity]));
    }

    protected function getCrudRoute(
        string $name,
        AppEntityInterface|string|null $entity = null,
    ): string
    {
        $entity = strtolower(Classes::getShortname($entity ?? static::CLASSNAME)) ?? static::ENTITYL;
        return vsprintf('aequation_labo_entity_%s_%s', [$entity, $name]);
    }

    /********************************************************************************************
     * ALL METHODS
     ********************************************************************************************/

    public function index(): Response
    {
        $data = [
            'meta_info' => $this->meta_info,
            'laboService' => $this->laboService,
        ];
        return $this->render($this->getCrudTemplate('index'), $data);
    }

    public function new(
        Request $request,
    ): Response
    {
        /** @var WebpageInterface */
        $entity = $this->manager->getNew();
        // $form = $this->createForm(static::ENTITY_TYPE, $entity);
        // $form->handleRequest($request);
        // if ($form->isSubmitted() && $form->isValid()) {
        //     $result = $this->manager->save($entity);
        //     if($result) {
        //         $this->addFlash('success', vsprintf('La %s "%s" a été enregistrée', [static::ENTITY, $entity]));
        //         return $this->redirectToRoute($this->getCrudRoute('index'), [], Response::HTTP_SEE_OTHER);
        //     }
        //     $this->addFlash('error', vsprintf('La nouvelle %s n\'a pu être enregistrée, une erreur est survenue', [static::ENTITY, $entity]));
        // }
        return $this->render($this->getCrudTemplate('new'), [
            'entity' => $entity,
            // 'form' => $form,
            'meta_info' => $this->meta_info,
            'laboService' => $this->laboService,
        ]);
    }

    public function show(int $id): Response
    {
        /** @var ServiceEntityRepository $repo */
        $repo = $this->manager->getRepository();
        $entity = $repo->find($id);
        return $this->render($this->getCrudTemplate('show'), [
            'entity' => $entity,
            'laboService' => $this->laboService,
            'meta_info' => $this->meta_info,
        ]);
    }

    public function edit(
        Request $request,
        int $id,
    ): Response
    {
        /** @var ServiceEntityRepository $repo */
        $repo = $this->manager->getRepository();
        $entity = $repo->find($id);
        $form = $this->createForm(static::ENTITY_TYPE, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $result = $this->manager->save($entity);
            if($result) {
                $this->addFlash('success', vsprintf('La %s "%s" a été enregistrée', [static::ENTITY, $entity]));
                return $this->redirectToRoute($this->getCrudRoute('index'), [], Response::HTTP_SEE_OTHER);
            }
            $this->addFlash('error', vsprintf('La %s n\'a pu être enregistrée, une erreur est survenue', [static::ENTITY, $entity]));
        }

        return $this->render($this->getCrudTemplate('edit'), [
            'entity' => $entity,
            'form' => $form,
            'laboService' => $this->laboService,
            'meta_info' => $this->meta_info,
        ]);
    }

    public function delete(
        Request $request,
        int $id,
    ): Response
    {
        /** @var ServiceEntityRepository $repo */
        $repo = $this->manager->getRepository();
        $entity = $repo->find($id);
        $result = false;
        if(
            $this->isCsrfTokenValid('delete'.$entity->getId(), $request->getPayload()->getString('_token'))
            && $result = $this->manager->delete($entity)
        ) {
            $this->addFlash('success', vsprintf('La %s "%s" a été supprimée', [static::ENTITY, $entity]));
        }
        if(!$result) {
            $this->addFlash('error', vsprintf('La %s n\'a pu être supprimée, une erreur est survenue', [static::ENTITY, $entity]));
        }
        return $this->redirectToRoute($this->getCrudRoute('index'), [], Response::HTTP_SEE_OTHER);
    }

}