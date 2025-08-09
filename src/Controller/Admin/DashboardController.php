<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Entity\Crudvoter;
use Aequation\LaboBundle\Entity\Image;
use Aequation\LaboBundle\Entity\LaboRelink;
use Aequation\LaboBundle\Entity\Pdf;
use Aequation\LaboBundle\Security\Voter\CategoryVoter;
use Aequation\LaboBundle\Security\Voter\CrudvoterVoter;
use Aequation\LaboBundle\Security\Voter\ImageVoter;
use Aequation\LaboBundle\Security\Voter\MenuVoter;
use Aequation\LaboBundle\Security\Voter\SiteparamsVoter;
use Aequation\LaboBundle\Security\Voter\AdvertVoter;
use Aequation\LaboBundle\Security\Voter\SliderVoter;
use Aequation\LaboBundle\Security\Voter\SlideVoter;
use Aequation\LaboBundle\Security\Voter\UserVoter;
use Aequation\LaboBundle\Security\Voter\WebpageVoter;
use Aequation\LaboBundle\Entity\Siteparams;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Repository\LaboUserRepository;
use Aequation\LaboBundle\Security\Voter\AddresslinkVoter;
use Aequation\LaboBundle\Security\Voter\EmailinkVoter;
use Aequation\LaboBundle\Security\Voter\EntrepriseVoter;
use Aequation\LaboBundle\Security\Voter\PdfVoter;
use Aequation\LaboBundle\Security\Voter\PhonelinkVoter;
use Aequation\LaboBundle\Security\Voter\UrlinkVoter;
use Aequation\LaboBundle\Security\Voter\WebsectionVoter;
use Aequation\LaboBundle\Service\Interface\AppEntityManagerInterface;
use Aequation\LaboBundle\Service\Interface\LaboCategoryServiceInterface;
use Aequation\LaboBundle\Service\Interface\LaboRelinkServiceInterface;
use Aequation\LaboBundle\Service\Tools\Classes;
use App\Entity\Advert;
use App\Entity\Prixthese;
use App\Security\Voter\PrixtheseVoter;
use App\Entity\Category;
use App\Entity\Addresslink;
use App\Entity\Emailink;
use App\Entity\Phonelink;
use App\Entity\Urlink;
use App\Entity\Entreprise;
use App\Entity\Menu;
use App\Entity\Slide;
use App\Entity\Slider;
use App\Entity\Webpage;
use App\Entity\Websection;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
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
#[IsGranted('ROLE_COLLABORATOR')]
#[AdminDashboard(routePath: 'easyadmin', routeName: 'easyadmin')]
class DashboardController extends AbstractDashboardController
{
    public const ADMIN_HOMEPAGE = false;

    public function __construct(
        protected AppEntityManagerInterface $manager,
        private LaboUserRepository $userRepository,
        private TranslatorInterface $translator,
        private AdminUrlGenerator $adminUrlGenerator,
    )
    {
        // 
    }

    #[Route(path: '/easyadmin', name: 'easyadmin')]
    public function index(): Response
    {
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

    public function configureAssets(): Assets
    {
        return Assets::new()->useCustomIconSet();
    }

    protected function translate(
        mixed $data,
        array $parameters = [],
        string $domain = 'EasyAdminBundle',
        string $locale = null,
    ): mixed
    {
        switch (true) {
            case is_string($data):
                $trans = $this->translator->trans($data, $parameters, $domain, $locale);
                return in_array($trans, ['names', 'name'])
                    ? ucfirst($domain)
                    : $trans;
                break;
            case is_array($data):
                return array_map(function($value) use ($parameters, $domain, $locale) { return $this->translate($value, $parameters, $domain, $locale); }, $data);
                break;
            default:
                return $data;
                break;
        }
        // throw new Exception(vsprintf('Erreur %s ligne %d: la traduction ne peut s\'appliquer qu\'à un texte ou un tableau de textes.'))
    }

    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('easyadmin');
        }
        $data = [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'title' => 'Admin login',
        ];
        return $this->render('@AequationLabo/security/login.html.twig', $data);
    }

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
        yield MenuItem::linkToUrl(label: 'Retour au site', icon: 'tabler:home-filled', url: $this->generateUrl('app_home'));
        yield MenuItem::linkToUrl(label: 'Quitter', icon: 'tabler:lock-filled', url: $route_logout);
        yield MenuItem::linkToUrl(label: 'Labo', icon: 'tabler:brand-symfony', url: $this->generateUrl('aequation_labo_home'))->setPermission('ROLE_SUPER_ADMIN');

        // 2. MANAGER
        $color = 'text-primary-emphasis';
        $webmanage = [];
        if($this->isGranted(WebpageVoter::ADMIN_ACTION_LIST, Webpage::class)) $webmanage['Webpage'] = MenuItem::linkToCrud(label: 'Pages web', icon: Webpage::ICON, entityFqcn: Webpage::class);
        if($this->isGranted(WebsectionVoter::ADMIN_ACTION_LIST, Websection::class)) $webmanage['Websection'] = MenuItem::linkToCrud(label: 'Sections web', icon: Websection::ICON, entityFqcn: Websection::class);
        if($this->isGranted(MenuVoter::ADMIN_ACTION_LIST, Menu::class)) $webmanage['Menu'] = MenuItem::linkToCrud(label: 'Menus', icon: Menu::ICON, entityFqcn: Menu::class);
        if(count($webmanage)) {
            yield MenuItem::section('Contenu du site')->setCssClass($color);
            foreach ($webmanage as $menuItem) yield $menuItem;
        }

        // 3. MEDIAS
        $color = 'text-info';
        $medias = [];
        $sub_medias = [];
        if($this->isGranted(PrixtheseVoter::ADMIN_ACTION_LIST, Prixthese::class)) $medias['Prixthese'] = MenuItem::linkToCrud(label: 'Prix de thèses', icon: Prixthese::ICON, entityFqcn: Prixthese::class);
        if($this->isGranted(AdvertVoter::ADMIN_ACTION_LIST, Advert::class)) $medias['Advert'] = MenuItem::linkToCrud(label: 'Annonces', icon: Advert::ICON, entityFqcn: Advert::class);
        if($this->isGranted(SliderVoter::ADMIN_ACTION_LIST, Slider::class)) $medias['Slider'] = MenuItem::linkToCrud(label: 'Diaporamas', icon: Slider::ICON, entityFqcn: Slider::class);
        if($this->isGranted(SlideVoter::ADMIN_ACTION_LIST, Slide::class)) $medias['Slide'] = MenuItem::linkToCrud(label: 'Diapositives', icon: Slide::ICON, entityFqcn: Slide::class);
        if($this->isGranted(PdfVoter::ADMIN_ACTION_LIST, Pdf::class)) $medias['Pdf'] = MenuItem::linkToCrud(label: 'Fichiers PDF', icon: Pdf::ICON, entityFqcn: Pdf::class);
        $sub_medias = [];
        if($this->isGranted(CategoryVoter::ADMIN_ACTION_LIST, Category::class)) {
            /** @var LaboCategoryServiceInterface */
            $entService = $this->manager->getEntityService(Category::class);
            $categoryTypes = $entService->getCategoryTypeChoices();
            foreach ($categoryTypes as $type) {
                $name = $this->translator->trans('names', [], Classes::getShortname($type));
                if($name === 'names') $name = Classes::getShortname($type);
                $url = $this->generateUrl('easyadmin_category_index', ['type' => Classes::getShortname($type)]);
                $sub_medias[$type] = MenuItem::linkToUrl(label: 'Type '.ucfirst($this->translate('names', [], Classes::getShortname($type))), icon: Category::ICON, url: $url);
            }
            if(count($sub_medias)) {
                $medias['Category'] = MenuItem::subMenu(label: 'Categories', icon: Category::ICON)->setSubItems($sub_medias);
            }
        }
        $sub_medias = [];
        if($this->isGranted(AddresslinkVoter::ADMIN_ACTION_LIST, Addresslink::class)) $sub_medias[$type] = MenuItem::linkToCrud(label: ucfirst($this->translate('names', [], Classes::getShortname(Addresslink::class))), icon: Addresslink::ICON, entityFqcn: Addresslink::class);
        if($this->isGranted(PhonelinkVoter::ADMIN_ACTION_LIST, Phonelink::class)) $sub_medias[$type] = MenuItem::linkToCrud(label: ucfirst($this->translate('names', [], Classes::getShortname(Phonelink::class))), icon: Phonelink::ICON, entityFqcn: Phonelink::class);
        if($this->isGranted(EmailinkVoter::ADMIN_ACTION_LIST, Emailink::class)) $sub_medias[$type] = MenuItem::linkToCrud(label: ucfirst($this->translate('names', [], Classes::getShortname(Emailink::class))), icon: Emailink::ICON, entityFqcn: Emailink::class);
        if($this->isGranted(UrlinkVoter::ADMIN_ACTION_LIST, Urlink::class)) $sub_medias[$type] = MenuItem::linkToCrud(label: ucfirst($this->translate('names', [], Classes::getShortname(Urlink::class))), icon: Urlink::ICON, entityFqcn: Urlink::class);
        if(count($sub_medias)) {
            $medias['Urlinks'] = MenuItem::subMenu(label: 'Contacts', icon: LaboRelink::ICON)->setSubItems($sub_medias);
        }
        if(count($medias)) {
            yield MenuItem::section('Médias & liens')->setCssClass($color);
            foreach ($medias as $menuItem) yield $menuItem;
        }

        // 4. USERS
        $color = 'text-info-emphasis';
        $users = [];
        $sub_users = [];
        if($this->isGranted(UserVoter::ADMIN_ACTION_LIST, User::class)) $users['User'] = MenuItem::linkToCrud(label: $this->translate('names', [], Classes::getShortname(User::class)), icon: User::ICON, entityFqcn: User::class);
        if($this->isGranted(EntrepriseVoter::ADMIN_ACTION_LIST, Entreprise::class)) $users['Entreprise'] = MenuItem::linkToCrud(label: $this->translate('names', [], Classes::getShortname(Entreprise::class)), icon: Entreprise::ICON, entityFqcn: Entreprise::class);
        if(count($users)) {
            yield MenuItem::section('Utilisateurs')->setCssClass($color);
            foreach ($users as $menuItem) yield $menuItem;
        }

        // 5. SUPER_ADMIN
        $color = 'text-warning';
        $sadmin = [];
        $sub_sadmin = [];
        if($this->isGranted(CrudvoterVoter::ADMIN_ACTION_LIST, Crudvoter::class)) $sadmin['Crudvoter'] = MenuItem::linkToCrud(label: 'Autorisations', icon: Crudvoter::ICON, entityFqcn: Crudvoter::class);
        if($this->isGranted(SiteparamsVoter::ADMIN_ACTION_LIST, Siteparams::class)) $sadmin['Siteparams'] = MenuItem::linkToCrud(label: 'Paramètres', icon: Siteparams::ICON, entityFqcn: Siteparams::class);
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
                MenuItem::linkToRoute('Retour au site', 'tabler:home-filled', 'app_home'),
                MenuItem::linkToUrl('Mon profil', User::ICON, $profil_url),
                // MenuItem::linkToRoute('Settings', 'fa fa-user-cog', '...', ['...' => '...']),
                // MenuItem::section(),
                // MenuItem::linkToLogout('Logout', 'fa fa-sign-out'),
            ]);
    }

}