<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Twig\Markup;
use App\Entity\Menu;
use Doctrine\ORM\QueryBuilder;
use App\Repository\MenuRepository;
use Aequation\LaboBundle\Entity\Item;
use App\Repository\WebpageRepository;
use App\Repository\CategoryRepository;
use Aequation\LaboBundle\Field\CKEditorField;
use Aequation\LaboBundle\Form\Type\PhotoType;

use Aequation\LaboBundle\Field\ThumbnailField;
use Symfony\Component\HttpFoundation\Response;
use Aequation\LaboBundle\Service\Tools\Classes;
use Aequation\LaboBundle\Service\Tools\Strings;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Aequation\LaboBundle\Security\Voter\MenuVoter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use Aequation\LaboBundle\Repository\ItemRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Translation\TranslatableMessage;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Aequation\LaboBundle\Service\Interface\MenuServiceInterface;
use Aequation\LaboBundle\Controller\Admin\Base\BaseCrudController;
use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;

#[IsGranted('ROLE_COLLABORATOR')]
class MenuCrudController extends BaseCrudController
{
    public const ENTITY = Menu::class;
    public const VOTER = MenuVoter::class;

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
                yield IntegerField::new('orderitem', 'Priorité')->setHelp('Ordre d\'affichage de la page dans les listes.');
                yield TextField::new('title', 'Titre du menu');
                yield TextField::new('linktitle', 'Titre de lien externe');
                yield ArrayField::new('items', 'Éléments du menu');
                yield ArrayField::new('relationOrderNames', 'Éléments order')->setPermission('ROLE_SUPER_ADMIN');
                yield BooleanField::new('prefered', 'Menu principal');
                yield CollectionField::new('categorys', 'Catégories');
                yield TextField::new('content', 'Texte de la page')->renderAsHtml();
                yield ThumbnailField::new('photo', 'Photo')->setBasePath($this->getParameter('vich_dirs.item_photo'));
                yield BooleanField::new('enabled', 'Activé');
                yield BooleanField::new('softdeleted', 'Supprimé')->setPermission('ROLE_SUPER_ADMIN');
                yield DateTimeField::new('createdAt', 'Création')->setFormat('dd/MM/Y - HH:mm')->setTimezone($current_tz);
                yield DateTimeField::new('updatedAt', 'Modification')->setFormat('dd/MM/Y - HH:mm')->setTimezone($current_tz);
                break;
            case Crud::PAGE_NEW:
                yield TextField::new('name', 'Nom du menu')->setColumns(6);
                yield TextField::new('title', 'Titre du menu')->setColumns(6);
                yield TextField::new('linktitle', 'Titre de lien externe')->setColumns(6);
                yield AssociationField::new('items', 'Éléments du menu')
                    ->setQueryBuilder(fn (QueryBuilder $qb): QueryBuilder => ItemRepository::getQB_orderedChoicesList($qb, Menu::class, 'items'))
                    ->setSortProperty('name')
                    // ->autocomplete()
                    ->setFormTypeOptions(['by_reference' => false])
                    ->setColumns(6);
                yield AssociationField::new('webpage', 'Page web')
                    ->setSortProperty('name')
                    // ->autocomplete()
                    ->setFormTypeOptions(['by_reference' => false])
                    ->setColumns(6);
                yield CKEditorField::new('content', 'Texte de la page')
                    ->formatValue(fn ($value) => Strings::markup($value));
                yield AssociationField::new('categorys', 'Catégories')
                    ->setQueryBuilder(static fn (QueryBuilder $qb): QueryBuilder => CategoryRepository::QB_CategoryChoices($qb, Menu::class))
                    // ->autocomplete()
                    ->setSortProperty('name')
                    ->setFormTypeOptions(['by_reference' => false])
                    ->setColumns(6);
                yield TextField::new('photo', 'Photo')
                    ->setFormType(PhotoType::class)
                    ->setColumns(6);                yield AssociationField::new('owner', 'Propriétaire')->setColumns(6)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);
                yield BooleanField::new('enabled', 'Activé')->setColumns(6);
                yield SlugField::new('slug')->setTargetFieldName('name')->setColumns(6);
                yield BooleanField::new('softdeleted', 'Supprimé')->setPermission('ROLE_SUPER_ADMIN');
                break;
            case Crud::PAGE_EDIT:
                yield FormField::addColumn('col-md-6');
                    yield TextField::new('name', 'Nom du menu');
                    yield TextField::new('title', 'Titre du menu');
                    yield TextField::new('linktitle', 'Titre de lien externe');
                    yield AssociationField::new('items', 'Éléments du menu')
                        ->setQueryBuilder(function (QueryBuilder $qb) {
                            return ItemRepository::getQB_orderedChoicesList($qb, Menu::class, 'items', []);
                        })
                        // ->autocomplete()
                        ->setFormTypeOption('by_reference', false);
                    yield AssociationField::new('webpage', 'Page web')
                        ->setSortProperty('name');
                    yield CKEditorField::new('content', 'Texte de la page')
                        ->formatValue(fn ($value) => Strings::markup($value));
                        // ->autocomplete()
                        // ->setFormTypeOptions(['by_reference' => false])
                    yield BooleanField::new('prefered', 'Menu principal');
                    yield BooleanField::new('enabled', 'Activé');
                    yield BooleanField::new('softdeleted', 'Supprimé')->setPermission('ROLE_SUPER_ADMIN');
                    
                yield FormField::addColumn('col-md-6');
                    yield AssociationField::new('categorys', 'Catégories')
                        ->setQueryBuilder(static fn (QueryBuilder $qb): QueryBuilder => CategoryRepository::QB_CategoryChoices($qb, Menu::class))
                        ->setSortProperty('name')
                        // ->autocomplete()
                        ->setFormTypeOptions(['by_reference' => false]);
                    yield AssociationField::new('owner', 'Propriétaire')->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);
                    yield TextField::new('photo', 'Photo')->setFormType(PhotoType::class);
                    yield BooleanField::new('updateSlug')->setLabel('Mettre à jour le slug')->setHelp('Si vous cochez cette case, le slug sera mis à jour avec le nom du menu.<br><strong>Il n\'est pas recommandé de modifier le slug</strong> car cela change le lien URL du document.');
                    yield SlugField::new('slug')->setTargetFieldName('name');
                    yield IntegerField::new('orderitem', 'Priorité')->setHelp('Ordre d\'affichage de la page dans les listes.')->setColumns(3);
                break;
            default:
                yield IdField::new('id')->setPermission('ROLE_SUPER_ADMIN');
                yield TextField::new('name', 'Nom');
                yield TextField::new('slug');
                yield ThumbnailField::new('photo', 'Photo')
                    ->setBasePath($this->getParameter('vich_dirs.item_photo'))
                    ->setTextAlign('center')
                    ->setSortable(false);
                yield AssociationField::new('webpage', 'Page web');
                yield AssociationField::new('items', 'Éléments du menu')->setTextAlign('center');
                yield IntegerField::new('orderitem', 'Ord.');
                yield BooleanField::new('prefered', 'Menu principal')->setTextAlign('center');
                yield BooleanField::new('enabled', 'Activé')->setTextAlign('center');
                yield AssociationField::new('owner', 'Propriétaire');
                yield DateTimeField::new('createdAt', 'Création')->setFormat('dd/MM/Y - HH:mm')->setTimezone($current_tz)->setTextAlign('right');
                break;
        }
    }

}