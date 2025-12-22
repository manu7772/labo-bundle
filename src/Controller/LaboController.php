<?php
namespace Aequation\LaboBundle\Controller;

use Aequation\LaboBundle\Component\CssManager;
use Aequation\LaboBundle\Controller\Base\CommonController;
use Aequation\LaboBundle\Service\Interface\LaboBundleServiceInterface;
use Aequation\LaboBundle\Service\AppEntityManager;
use Aequation\LaboBundle\Service\Interface\AppServiceInterface;
use Aequation\LaboBundle\Service\Interface\CssDeclarationInterface;
use Aequation\LaboBundle\Service\LaboSiteparamsService;
use Aequation\LaboBundle\Service\Tools\Files;
use Aequation\LaboBundle\Service\Tools\Strings;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfonycasts\TailwindBundle\TailwindBuilder;

#[Route(path: '/ae-labo', name: 'aequation_labo_')]
// #[IsGranted('ROLE_ADMIN')]
class LaboController extends CommonController
{

    #[Route(path: '', name: 'home')]
    #[Template('@AequationLabo/labo/home.html.twig')]
    public function home(): array
    {
        $data = [];
        return $data;
    }

    #[Route(path: '/services', name: 'services')]
    #[Template('@AequationLabo/labo/services.html.twig')]
    public function services(
        AppServiceInterface $appService,
    ): array
    {
        /** @var AppEntityManager $manager */
        $data = [];
        return $data;
    }

    /**
     * @see file Symfonycasts\TailwindBundle\Command\TailwindBuildCommand for example of use of TailwindBuilder
     */
    #[Route(path: '/css/{action?}/{data?}', name: 'css', methods: ["get","post"])]
    public function css(
        LaboBundleServiceInterface $laboService,
        CssDeclarationInterface $cssDeclaration,
        #[Autowire(service: 'tailwind.builder')]
        TailwindBuilder $tailwindBuilder,
        Request $request,
        ?string $action = null,
        ?string $data = null,
    ): Response
    {
        $action_info = [];
        $need_tw_compile = $request->getSession()->get('need_tw_compile', 0);
        if($need_tw_compile > 3) {
            $need_tw_compile = 0;
            $request->getSession()->remove('need_tw_compile');
        } else if($need_tw_compile > 0) {
            $request->getSession()->set('need_tw_compile', $need_tw_compile + 1);
        }
        if ($request->isMethod('POST')) {
            $action = 'formcss';
        }
        if(!empty($action)) $action_info['action_name'] = $action;
        $cssManager = new CssManager();
        $inputFiles = $tailwindBuilder->getInputCssPaths();
        switch ($action) {
            case 'removecss':
                $action_info['removedcss'] = $cssDeclaration->removeClasses($data);
                $action_info[$action] = $action_info['removedcss'] > 0 && $cssDeclaration->saveClasses();
                if($action_info[$action]) {
                    $request->getSession()->set('need_tw_compile', 1);
                    $this->addFlash('success', 'Nombre de classes supprimées : '.$action_info['removedcss']);
                } else {
                    $this->addFlash('warning', 'Aucune classe n\'a été supprimée. Il se peut que les classes indiquées n\'existent pas dans la liste, ou bien ce sont des classes qui ne peuvent pas être supprimées.');
                }
                return $this->redirectToRoute('aequation_labo_css');
                break;
            case 'formcss':
                $form = $cssDeclaration->getCssForm();
                /**
                 * @see https://symfony.com/doc/current/form/direct_submit.html
                 */
                // $formName = $form->getName();
                // dd($formName, $request->getPayload()->get($formName));
                // $form->submit($request->request->get($formName));
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    // perform some action...
                    $cssManager = $form->getData();
                    $action_info['addedcss'] = $cssDeclaration->addClasses($cssManager->getCssClasses());
                    $action_info[$action] = $action_info['addedcss'] > 0 && $cssDeclaration->saveClasses();
                    // dd($cssManager->getCssClasses());
                    if($action_info[$action]) {
                        $request->getSession()->set('need_tw_compile', 1);
                        $this->addFlash('success', 'Nombre de classes ajoutées : '.$action_info['addedcss']);
                    } else {
                        $this->addFlash('warning', 'Aucune classe n\'a été ajoutée. Il se peut que les classes indiquées existent déjà dans la liste, ou que le nom de la classe soit invalide.');
                    }
                } else {
                    $this->addFlash('warning', 'Les classes ajoutées sont invalides.');
                }
                return $this->redirectToRoute('aequation_labo_css');
                break;
            case 'addcss':
                $action_info['post_data'] = $request->request->all();
                $action_info['addedcss'] = $cssDeclaration->addClasses($action_info['post_data']['cssclasses'] ?? '');
                $action_info[$action] = $action_info['addedcss'] > 0 && $cssDeclaration->saveClasses();
                if($action_info[$action]) {
                    $request->getSession()->set('need_tw_compile', 1);
                    $this->addFlash('success', 'Nombre de classes ajoutées : '.$action_info['addedcss']);
                } else {
                    $this->addFlash('warning', 'Aucune classe n\'a été ajoutée. Il se peut que les classes indiquées existent déjà dans la liste, ou que le nom de la classe soit invalide.');
                }
                return $this->redirectToRoute('aequation_labo_css');
            break;
            case 'reset':
                $action_info[$action] = $cssDeclaration->resetAll();
                if($action_info[$action]) {
                    $cssDeclaration->buildTailwindCss(false, false, $laboService->isProd(), function($type, $buffer) use (&$action_info) {
                        $action_info['process_tailwind_actions'][$type] ??= [];
                        $action_info['process_tailwind_actions'][$type][] = $buffer;
                    });
                    if($action_info[$action]) {
                        $request->getSession()->remove('need_tw_compile');
                        $this->addFlash('success', 'La réinitialisation a été effectuée !');
                    } else {
                        $this->addFlash('error', 'La réinitialisation a échoué !');
                    }
                }
                return $this->redirectToRoute('aequation_labo_css');
                break;
            case 'refresh':
                $action_info[$action] = $cssDeclaration->refreshClasses(true);
                if($action_info[$action]) {
                    $cssDeclaration->buildTailwindCss(false, false, $laboService->isProd(), function($type, $buffer) use (&$action_info) {
                        $action_info['process_tailwind_actions'][$type] ??= [];
                        $action_info['process_tailwind_actions'][$type][] = $buffer;
                    });
                    if($action_info[$action]) {
                        $request->getSession()->remove('need_tw_compile');
                        $this->addFlash('success', 'La génération a été effectuée !');
                    } else {
                        $this->addFlash('error', 'La génération a échoué !');
                    }
                }
                return $this->redirectToRoute('aequation_labo_css');
                break;
            case 'show_tw_css':
                $action_info['css_contents'] = [];
                foreach ($inputFiles as $inputFile) {
                    $action_info['css_contents'][$inputFile.' > '.$tailwindBuilder->getInternalOutputCssPath($inputFile)] = $tailwindBuilder->getOutputCssContent($inputFile);
                }
                $action_info[$action] = !empty($action_info['css_contents']);
                if(!$action_info[$action]) {
                    $this->addFlash('error', 'Aucun fichier n\'a été trouvé, désolé !');
                }
            break;
            default:
                break;
        }
        $tailwind_params = [
            'Input css paths' => [],
            'Config file' => $tailwindBuilder->getConfigFilePath(),
        ];
        foreach ($inputFiles as $file) {
            $tailwind_params['Input css paths'][$file] = $tailwindBuilder->getInternalOutputCssPath($file);
        }
        $data = [
            'cssDeclaration' => $cssDeclaration,
            'action' => $action,
            'action_info' => $action_info,
            'tailwind_params' => $tailwind_params,
            'cssForm' => $cssDeclaration->getCssForm($cssManager),
        ];
        return $this->render('@AequationLabo/labo/css.html.twig', $data);
    }

    #[Route(path: '/siteparams', name: 'siteparams')]
    #[Template('@AequationLabo/labo/siteparams.html.twig')]
    public function siteparams(
        ParameterBagInterface $parameterBag,
    ): array
    {
        /** @var LaboSiteparamsService $manager */
        $data = [
            'parameters' => $parameterBag,
        ];
        return $data;
    }

    #[Route(path: '/testmodale', name: 'testmodale')]
    #[Template('@AequationLabo/labo/testmodale.html.twig')]
    public function testmodale(
        LaboBundleServiceInterface $laboService,
    ): array
    {
        $data = [];
        return $data;
    }

    #[Route(path: '/documentation/{rubrique<\w+>?home}', name: 'documentation')]
    public function documentation(
        ?string $rubrique = null,
    ): Response
    {
        $submenu = ['Documentation' => ['route' => 'aequation_labo_documentation', 'params' => ['rubrique' => 'home']]];
        $files = $this->container->get('Tool:Files')->listFiles(dirname(__DIR__).'/../templates/documentation/rubriques/');
        $sub = '';
        foreach ($files as $file) {
            // $name = Strings::getBefore($file->getBasename('.'.$file->getExtension()), '.');
            $name = Strings::getBefore($file->getBasename(), '.');
            if($rubrique === $name) $sub = 'rubriques/';
            $submenu[ucfirst($name)] = ['route' => 'aequation_labo_documentation', 'params' => ['rubrique' => $name]];
        }
        return $this->render("@AequationLabo/documentation/$sub$rubrique.html.twig", [
            'submenu' => $submenu,
            'rubrique' => $rubrique,
        ]);
    }

}