<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Entity\Crudvoter;
use Aequation\LaboBundle\Entity\Image;
use Aequation\LaboBundle\Entity\Pdf;
use Aequation\LaboBundle\Security\Voter\CategoryVoter;
use Aequation\LaboBundle\Security\Voter\CrudvoterVoter;
use Aequation\LaboBundle\Security\Voter\ImageVoter;
use Aequation\LaboBundle\Security\Voter\MenuVoter;
use Aequation\LaboBundle\Security\Voter\SiteparamsVoter;
use Aequation\LaboBundle\Security\Voter\SliderVoter;
use Aequation\LaboBundle\Security\Voter\SlideVoter;
use Aequation\LaboBundle\Security\Voter\UserVoter;
use Aequation\LaboBundle\Security\Voter\WebpageVoter;
use Aequation\LaboBundle\Entity\Siteparams;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Repository\LaboUserRepository;
use Aequation\LaboBundle\Security\Voter\EntrepriseVoter;
use Aequation\LaboBundle\Security\Voter\PdfVoter;
use Aequation\LaboBundle\Security\Voter\WebsectionVoter;

use App\Entity\Category;
use App\Entity\Entreprise;
use App\Entity\Menu;
use App\Entity\Slide;
use App\Entity\Slider;
use App\Entity\Webpage;
use App\Entity\Websection;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Symfony\Component\Security\Core\User\UserInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Override temlates
 * @see https://symfonycasts.com/screencast/easyadminbundle/override-template
 * Templates/Views @link vendor/easycorp/easyadmin-bundle/src/Resources/views
 */
#[Route(path: '/admin', name: 'admin_')]
#[IsGranted('ROLE_COLLABORATOR')]
class DashboardController extends AbstractDashboardController
{
    public const ADMIN_HOMEPAGE = false;

    public function __construct(
        private TranslatorInterface $translator,
        private LaboUserRepository $userRepository,
        private AdminUrlGenerator $adminUrlGenerator,
    )
    {
        // 
    }

    #[Route('', name: 'home')]
    public function index(): Response
    {
        // dump($this->isGranted(WebpageVoter::ADMIN_ACTION_LIST, Webpage::class));
        // Admin granted page
        if(!static::ADMIN_HOMEPAGE && $this->isGranted(WebpageVoter::ADMIN_ACTION_LIST, Webpage::class)) {
            // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
            return $this->redirect($this->adminUrlGenerator->setController(WebpageCrudController::class)->generateUrl());
        }
        // Admin default homepage
        /** @see https://symfony.com/bundles/EasyAdminBundle/current/dashboards.html#customizing-the-dashboard-contents */
        // return $this->render('bundles/EasyAdminBundle/dashboard.html.twig', []);
        return parent::index();
    }

    #[Route(path: '/connexion', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('admin_home');
        }
        $data = [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'title' => 'Admin login',
        ];
        return $this->render('@AequationLabo/security/login.html.twig', $data);
    }

    #[Route(path: '/deconnexion', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle($this->translator->trans(id: 'Administration', locale: 'fr_FR'))
            ->setFaviconPath('images/logo_AEW_contour.svg')
            // ->setTranslationDomain('admin')
            ;
    }

    public function configureMenuItems(): iterable
    {
        // try {
            // $route_logout = $this->generateUrl('admin_logout');
        // } catch (\Throwable $th) {
            $route_logout = $this->generateUrl('app_logout');
        // }
        // 1. PUBLIC HOMEPAGE
        $color = 'text-success-emphasis';
        yield MenuItem::section('Site public')->setCssClass($color);
        yield MenuItem::linkToUrl(label: 'Retour au site', icon: 'fas fa-fw fa-home '.$color, url: $this->generateUrl('app_home'));
        yield MenuItem::linkToUrl(label: 'Quitter', icon: 'fas fa-fw fa-unlock '.$color, url: $route_logout);
        yield MenuItem::linkToUrl(label: 'Labo', icon: 'fas fa-fw fa-cog text-danger', url: $this->generateUrl('aequation_labo_home'))->setPermission('ROLE_ADMIN');
        // yield MenuItem::linkToUrl(label: 'Sadmin', icon: 'fas fa-fw fa-lock text-danger', url: $this->generateUrl('sadmin_home'))->setPermission('ROLE_SUPER_ADMIN');

        // 2. MANAGER
        $color = 'text-primary-emphasis';
        $webmanage = [];
        if($this->isGranted(WebpageVoter::ADMIN_ACTION_LIST, Webpage::class)) $webmanage['Webpage'] = MenuItem::linkToCrud(label: 'Pages web', icon: 'fas fa-fw fa-'.Webpage::getIcon(false).' '.$color, entityFqcn: Webpage::class);
        if($this->isGranted(WebsectionVoter::ADMIN_ACTION_LIST, Websection::class)) $webmanage['Websection'] = MenuItem::linkToCrud(label: 'Sections web', icon: 'fas fa-fw fa-'.Websection::getIcon(false).' '.$color, entityFqcn: Websection::class);
        if($this->isGranted(MenuVoter::ADMIN_ACTION_LIST, Menu::class)) $webmanage['Menu'] = MenuItem::linkToCrud(label: 'Menus', icon: 'fas fa-fw fa-'.Menu::getIcon(false).' '.$color, entityFqcn: Menu::class);
        if(count($webmanage)) {
            yield MenuItem::section('Contenu du site')->setCssClass($color);
            foreach ($webmanage as $menuItem) yield $menuItem;
        }

        // 3. MEDIAS
        $color = 'text-info';
        $medias = [];
        if($this->isGranted(SliderVoter::ADMIN_ACTION_LIST, Slider::class)) $medias['Slider'] = MenuItem::linkToCrud(label: 'Diaporamas', icon: 'fas fa-fw fa-'.Slider::getIcon(false).' '.$color, entityFqcn: Slider::class);
        if($this->isGranted(SlideVoter::ADMIN_ACTION_LIST, Slide::class)) $medias['Slide'] = MenuItem::linkToCrud(label: 'Diapositives', icon: 'fas fa-fw fa-'.Slide::getIcon(false).' '.$color, entityFqcn: Slide::class);
        if($this->isGranted(PdfVoter::ADMIN_ACTION_LIST, Pdf::class)) $medias['Pdf'] = MenuItem::linkToCrud(label: 'Fichiers PDF', icon: 'fas fa-fw fa-'.Pdf::getIcon(false).' '.$color, entityFqcn: Pdf::class);
        if($this->isGranted(CategoryVoter::ADMIN_ACTION_LIST, Category::class)) $medias['Category'] = MenuItem::linkToCrud(label: 'Categories', icon: 'fas fa-fw fa-'.Category::getIcon(false).' '.$color, entityFqcn: Category::class);
        if(count($medias)) {
            yield MenuItem::section('Médias & tags')->setCssClass($color);
            foreach ($medias as $menuItem) yield $menuItem;
        }

        // 4. USERS
        $color = 'text-info-emphasis';
        $users = [];
        if($this->isGranted(UserVoter::ADMIN_ACTION_LIST, User::class)) $users['User'] = MenuItem::linkToCrud(label: 'Utilisateurs', icon: 'fas fa-fw fa-'.User::getIcon(false).' '.$color, entityFqcn: User::class);
        if($this->isGranted(EntrepriseVoter::ADMIN_ACTION_LIST, Entreprise::class)) $users['Entreprise'] = MenuItem::linkToCrud(label: 'entreprises', icon: 'fas fa-fw fa-'.Entreprise::getIcon(false).' '.$color, entityFqcn: Entreprise::class);
        if(count($users)) {
            yield MenuItem::section('Utilisateurs')->setCssClass($color);
            foreach ($users as $menuItem) yield $menuItem;
        }

        // 5. SUPER_ADMIN
        $color = 'text-warning';
        $sadmin = [];
        if($this->isGranted(CrudvoterVoter::ADMIN_ACTION_LIST, Crudvoter::class)) $sadmin['Crudvoter'] = MenuItem::linkToCrud(label: 'Autorisations', icon: 'fas fa-fw fa-'.Crudvoter::getIcon(false).' '.$color, entityFqcn: Crudvoter::class);
        if($this->isGranted(SiteparamsVoter::ADMIN_ACTION_LIST, Siteparams::class)) $sadmin['Siteparams'] = MenuItem::linkToCrud(label: 'Paramètres', icon: 'fas fa-fw fa-'.Siteparams::getIcon(false).' '.$color, entityFqcn: Siteparams::class);
        if(count($sadmin)) {
            yield MenuItem::section('Super Admin')->setCssClass($color);
            foreach ($sadmin as $menuItem) yield $menuItem;
        }
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        /** @var LaboUserInterface $user */
        $profil_url = $this->adminUrlGenerator
            ->setController(UserCrudController::class)
            ->setEntityId($user->getId())
            ->setAction(Crud::PAGE_DETAIL)
            ->set('detail_option', 'user_profile')
            ->generateUrl();
        /** @var LaboUserInterface $user */
        // Usually it's better to call the parent method because that gives you a
        // user menu with some menu items already created ("sign out", "exit impersonation", etc.)
        // if you prefer to create the user menu from scratch, use: return UserMenu::new()->...
        return parent::configureUserMenu($user)
            // use the given $user object to get the user name
            ->setName($user->getFirstname())
            // use this method if you don't want to display the name of the user
            // ->displayUserName(false)

            // you can return an URL with the avatar image
            // ->setAvatarUrl('https://...')
            // ->setAvatarUrl($user->getProfileImageUrl())
            // use this method if you don't want to display the user image
            ->displayUserAvatar(false)
            // you can also pass an email address to use gravatar's service
            ->setGravatarEmail($user->getEmail())

            // you can use any type of menu item, except submenus
            ->addMenuItems([
                MenuItem::linkToRoute('Retour au site', 'fa fa-home', 'app_home'),
                MenuItem::linkToUrl('Mon profil', 'fa-'.User::FA_ICON, $profil_url),
                // MenuItem::linkToRoute('Settings', 'fa fa-user-cog', '...', ['...' => '...']),
                // MenuItem::section(),
                // MenuItem::linkToLogout('Logout', 'fa fa-sign-out'),
            ]);
    }

}