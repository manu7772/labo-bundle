<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Security\Voter\MenuVoter;
use Aequation\LaboBundle\Controller\Admin\Base\BaseCrudController;
use Aequation\LaboBundle\Entity\Item;
use Aequation\LaboBundle\Repository\ItemRepository;
use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;
use Aequation\LaboBundle\Service\Interface\MenuServiceInterface;
use Aequation\LaboBundle\Service\Tools\Classes;

use App\Entity\Menu;
use App\Repository\CategoryRepository;
use App\Repository\MenuRepository;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatableMessage;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Markup;

#[IsGranted('ROLE_COLLABORATOR')]
class MenuCrudController extends BaseCrudController
{
    public const ENTITY = Menu::class;
    public const VOTER = MenuVoter::class;

    public function __construct(
        MenuServiceInterface $manager,
        protected LaboUserServiceInterface $userService,
        protected AdminUrlGenerator $adminUrlGenerator,
        protected MenuRepository $menuRepository,
    ) {
        parent::__construct($manager, $userService);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', 'Nom'));
    }

    public function configureFields(string $pageName): iterable
    {
        $this->checkGrants($pageName);
        $info = $this->getContextInfo();
        /** @var User $user */
        $user = $this->getUser();
        $timezone = $this->getParameter('timezone');
        $current_tz = $timezone !== $user->getTimezone() ? $user->getTimezone() : $timezone;
        switch ($pageName) {
            case Crud::PAGE_DETAIL:
                yield IdField::new('id');
                yield AssociationField::new('owner', 'Propriétaire');
                yield TextField::new('name', 'Nom');
                yield TextField::new('title', 'Titre du menu');
                yield ArrayField::new('items', 'Éléments du menu');
                yield ArrayField::new('relationOrderNames', 'Éléments order')->setPermission('ROLE_SUPER_ADMIN');
                yield BooleanField::new('prefered', 'Menu principal');
                yield CollectionField::new('categorys', 'Catégories');
                yield BooleanField::new('enabled', 'Activé');
                yield BooleanField::new('softdeleted', 'Supprimé')->setPermission('ROLE_SUPER_ADMIN');
                yield DateTimeField::new('createdAt', 'Création')->setFormat('dd/MM/Y - HH:mm')->setTimezone($current_tz);
                yield DateTimeField::new('updatedAt', 'Modification')->setFormat('dd/MM/Y - HH:mm')->setTimezone($current_tz);
                break;
            case Crud::PAGE_NEW:
                yield TextField::new('name', 'Nom du menu')->setColumns(6);
                yield TextField::new('title', 'Titre du menu')->setColumns(6);
                yield AssociationField::new('items', 'Éléments du menu')
                    ->setQueryBuilder(fn (QueryBuilder $qb): QueryBuilder => ItemRepository::getQB_orderedChoicesList($qb, Menu::class, 'items'))
                    ->setSortProperty('name')
                    ->autocomplete()
                    ->setFormTypeOptions(['by_reference' => false])
                    ->setColumns(6);
                yield AssociationField::new('categorys', 'Catégories')
                    ->setQueryBuilder(static fn (QueryBuilder $qb): QueryBuilder => CategoryRepository::QB_CategoryChoices($qb, Menu::class))
                    ->autocomplete()
                    ->setSortProperty('name')
                    ->setFormTypeOptions(['by_reference' => false])
                    ->setColumns(6);
                yield AssociationField::new('owner', 'Propriétaire')->autocomplete()->setColumns(6)->setPermission('ROLE_ADMIN');
                yield BooleanField::new('enabled', 'Activé');
                yield BooleanField::new('softdeleted', 'Supprimé')->setPermission('ROLE_SUPER_ADMIN');
                break;
            case Crud::PAGE_EDIT:
                yield TextField::new('name', 'Nom du menu')->setColumns(6);
                yield TextField::new('title', 'Titre du menu')->setColumns(6);
                yield AssociationField::new('items', 'Éléments du menu')
                    ->setQueryBuilder(function (QueryBuilder $qb) {
                        return ItemRepository::getQB_orderedChoicesList($qb, Menu::class, 'items', []);
                    })
                    ->autocomplete()
                    ->setFormTypeOption('by_reference', false)
                    ->setColumns(6);
                yield AssociationField::new('categorys', 'Catégories')
                    ->setQueryBuilder(static fn (QueryBuilder $qb): QueryBuilder => CategoryRepository::QB_CategoryChoices($qb, Menu::class))
                    ->setSortProperty('name')
                    ->autocomplete()
                    ->setFormTypeOptions(['by_reference' => false])
                    ->setColumns(6);
                yield AssociationField::new('owner', 'Propriétaire')->autocomplete()->setColumns(6)->setPermission('ROLE_ADMIN');
                yield BooleanField::new('prefered', 'Menu principal');
                yield BooleanField::new('enabled', 'Activé');
                yield BooleanField::new('softdeleted', 'Supprimé')->setPermission('ROLE_SUPER_ADMIN');
                break;
            default:
                yield IdField::new('id')->setPermission('ROLE_SUPER_ADMIN');
                yield TextField::new('name', 'Nom');
                yield AssociationField::new('items', 'Éléments du menu')->setTextAlign('center');
                yield BooleanField::new('prefered', 'Menu principal')->setTextAlign('center');
                yield BooleanField::new('enabled', 'Activé')->setTextAlign('center');
                yield AssociationField::new('owner', 'Propriétaire');
                yield DateTimeField::new('createdAt', 'Création')->setFormat('dd/MM/Y - HH:mm')->setTimezone($current_tz)->setTextAlign('right');
                break;
        }
    }

}