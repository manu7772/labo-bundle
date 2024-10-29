<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Security\Voter\WebpageVoter;
use Aequation\LaboBundle\Controller\Admin\Base\BaseCrudController;
use Aequation\LaboBundle\Form\Type\PhotoType;
use Aequation\LaboBundle\Repository\EcollectionRepository;
use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;
use Aequation\LaboBundle\Service\Interface\WebpageServiceInterface;
use Aequation\LaboBundle\Service\Tools\Classes;
use Aequation\LaboBundle\Service\Tools\Strings;
use Aequation\LaboBundle\Field\ThumbnailField;
Use Aequation\LaboBundle\Model\Interface\LaboUserInterface;

use App\Entity\Webpage;
use App\Repository\CategoryRepository;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
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
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\RelationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Markup;

#[IsGranted('ROLE_COLLABORATOR')]
class WebpageCrudController extends BaseCrudController
{
    public const ENTITY = Webpage::class;
    public const VOTER = WebpageVoter::class;

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name'))
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        $this->checkGrants($pageName);
        // $info = $this->getContextInfo();
        /** @var LaboUserInterface $user */
        $user = $this->getUser();
        $timezone = $this->getParameter('timezone');
        $current_tz = $timezone !== $user->getTimezone() ? $user->getTimezone() : $timezone;
        switch ($pageName) {
            case Crud::PAGE_DETAIL:
                yield IdField::new('id');
                yield AssociationField::new('owner', 'Propriétaire');
                yield TextField::new('name', 'Nom');
                yield TextField::new('slug', 'Slug');
                yield BooleanField::new('prefered', 'Page principale');
                yield TextField::new('title', 'Titre de la page');
                yield TextareaField::new('linktitle', 'Titre de lien externe')->formatValue(fn ($value) => Strings::markup($value));
                yield AssociationField::new('mainmenu', 'Menu intégré');
                yield TextField::new('twigfileName', 'Nom du modèle');
                yield TextField::new('twigfile', 'Chemin du modèle')->setPermission('ROLE_SUPER_ADMIN');
                yield TextField::new('content', 'Texte de la page')->renderAsHtml();
                yield ArrayField::new('items', 'Sections de pages');
                yield AssociationField::new('slider', 'Diaporama');
                yield ArrayField::new('categorys', 'Catégories');
                yield TextareaField::new('relationOrderDetails', '[Rel.order info]')->setPermission('ROLE_SUPER_ADMIN');
                yield ThumbnailField::new('photo', 'Photo')->setBasePath($this->getParameter('vich_dirs.item_photo'));
                yield BooleanField::new('enabled', 'Activée');
                yield BooleanField::new('softdeleted', 'Supprimée')->setPermission('ROLE_SUPER_ADMIN');
                yield DateTimeField::new('createdAt', 'Création')->setFormat('dd/MM/Y - HH:mm')->setTimezone($current_tz);
                yield DateTimeField::new('updatedAt', 'Modification')->setFormat('dd/MM/Y - HH:mm')->setTimezone($current_tz);
                break;
            case Crud::PAGE_NEW:
                yield TextField::new('name', 'Nom de la page')
                    ->setHelp('Utilisez un nom simple et pas trop long. <strong>Ce nom est uniquement utilisé pour l\'administration et n\'est pas affiché dans le contenu la page web</strong>.')
                    ->setColumns(6)
                    ;
                yield SlugField::new('slug', 'Slug')->setTargetFieldName('name')->setColumns(6)->setHelp('<strong>Le mieux est de laisser le serveur définir automatiquement ce champ.</strong><br>Sinon utilisez un nom simple et pas trop long. Il est préférable de ne jamais changer ce slug une fois défini, car il changera l\'URL de la page et cela réduit l\'efficacité du référencement du site.');
                yield ChoiceField::new('twigfile', 'Modèle de mise en page')
                    ->setChoices(fn (?Webpage $webpage): array => $webpage?->getTwigfileChoices() ?: [])
                    ->escapeHtml(false)
                    ->setColumns(6)
                    ;
                yield BooleanField::new('prefered', 'Page principale')->setPermission('ROLE_SUPER_ADMIN')->setColumns(3)->setHelp('Définir comme page principale du site. Si ce choix est activé, les utilisateurs arriveront directement sur cette page désormais définie comme page d\'accueil.');
                yield TextField::new('title', 'Titre de la page')->setColumns(8);
                yield AssociationField::new('categorys')->setQueryBuilder(fn (QueryBuilder $qb): QueryBuilder => CategoryRepository::QB_CategoryChoices($qb, Webpage::class))
                    ->autocomplete()
                    ->setSortProperty('name')
                    ->setFormTypeOptions(['by_reference' => false])
                    ->setColumns(4);
                yield AssociationField::new('items', 'Sections de pages')
                    ->autocomplete()
                    ->setQueryBuilder(fn (QueryBuilder $qb): QueryBuilder => EcollectionRepository::QB_collectionChoices($qb, Webpage::class, 'items'))
                    ->setSortProperty('sectiontype')
                    ->setFormTypeOptions(['by_reference' => false])
                    ->setColumns(4)
                    ->setRequired(!$this->isGranted('ROLE_SUPER_ADMIN'));
                yield AssociationField::new('slider', 'Diaporama')->setColumns(4);
                yield AssociationField::new('mainmenu', 'Menu intégré')->setColumns(4);
                yield TextareaField::new('linktitle', 'Titre de lien externe')
                    ->setHelp('Entrez ici le texte pour les liens qui dirigeront vers cette page web. Optionel : si non renseigné, le Titre de la page sera utilisé.')
                    ->setColumns(4);
                yield TextEditorField::new('content', 'Texte de la page')
                    ->formatValue(fn ($value) => Strings::markup($value))
                    ->setColumns(8)
                    ;
                yield TextField::new('photo', 'Photo')
                    ->setFormType(PhotoType::class)
                    ->setColumns(6);
                yield BooleanField::new('enabled', 'Activée');
                yield BooleanField::new('softdeleted', 'Supprimée')->setPermission('ROLE_SUPER_ADMIN');
                yield AssociationField::new('owner', 'Propriétaire')->autocomplete()->setColumns(6)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);
                break;
            case Crud::PAGE_EDIT:

                yield FormField::addTab('Informations de base')
                    ->setIcon('info')
                    // ->addCssClass('optional')
                    ->setHelp('Informations pour l\'administration. Ces informations ne sont pas visibles sur le site public.');

                    yield TextField::new('name', 'Nom de la page')
                        ->setHelp('Utilisez un nom simple et pas trop long. <strong>Ce nom est uniquement utilisé pour l\'administration et n\'est pas affiché dans le contenu la page web</strong>.')
                        ->setColumns(6)
                        ;
                    yield SlugField::new('slug', 'Slug')->setTargetFieldName('name')->setColumns(3)->setHelp('Utilisez un nom simple et pas trop long. <strong>Il est préférable de ne jamais changer ce slug</strong> car il changera l\'URL de la page et cela réduit l\'efficacité du référencement du site.');
                    yield BooleanField::new('updateSlug')->setLabel('Mettre à jour le slug')->setColumns(2)->setHelp('Il est recommandé d\'éviter de changer le slug car il est indexé par les moteurs de recherche. Faites-le uniquement si le nom du slug n\'a plus aucun rapport avec le contenu de ce que vous êtes en train d\'éditer.');
                    yield ChoiceField::new('twigfile', 'Modèle de mise en page')
                        ->setChoices(fn (Webpage $webpage): array => $webpage->getTwigfileChoices() ?: [])
                        ->escapeHtml(false)
                        ->setColumns(6)
                        ;
                    yield BooleanField::new('prefered', 'Page principale')->setColumns(2)->setHelp('Définir comme page principale du site. Si ce choix est activé, les utilisateurs arriveront directement sur cette page désormais définie comme page d\'accueil.');

                yield FormField::addTab('Contenu de la page')
                    ->setIcon('globe')
                    // ->addCssClass('optional')
                    ->setHelp('Contenu textes et médias de la page');

                    yield FormField::addColumn('col-md-8');

                        yield TextField::new('title', 'Titre de la page');
                        yield AssociationField::new('categorys')->setQueryBuilder(fn (QueryBuilder $qb): QueryBuilder => CategoryRepository::QB_CategoryChoices($qb, Webpage::class))
                            ->autocomplete()
                            ->setSortProperty('name')
                            ->setFormTypeOptions(['by_reference' => false]);
                        yield AssociationField::new('items', 'Sections de pages')
                            ->autocomplete()
                            ->setQueryBuilder(fn (QueryBuilder $qb): QueryBuilder => EcollectionRepository::QB_collectionChoices($qb, Webpage::class, 'items'))
                            ->setSortProperty('sectiontype')
                            ->setFormTypeOptions(['by_reference' => false])
                            ->setRequired(!$this->isGranted('ROLE_SUPER_ADMIN'));
                        yield AssociationField::new('mainmenu', 'Menu intégré');
                        yield TextareaField::new('linktitle', 'Titre de lien externe')
                            ->setHelp('Entrez ici le texte pour les liens qui dirigeront vers cette page web. Optionel : si non renseigné, le Titre de la page sera utilisé.');
                        yield TextEditorField::new('content', 'Texte de la page')
                            ->formatValue(fn ($value) => Strings::markup($value));

                    yield FormField::addColumn('col-md-4');

                        yield TextField::new('photo', 'Photo')
                            ->setFormType(PhotoType::class);
                        yield AssociationField::new('slider', 'Diaporama');

                yield FormField::addTab('Statut')
                    ->setIcon('lock');
    
                    yield BooleanField::new('enabled', 'Activée');
                    yield BooleanField::new('softdeleted', 'Supprimée')->setPermission('ROLE_SUPER_ADMIN');
                    yield AssociationField::new('owner', 'Propriétaire')->autocomplete()->setColumns(6)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);
                break;
            default:
                yield IdField::new('id')->setPermission('ROLE_SUPER_ADMIN');
                yield TextField::new('name', 'Nom');
                yield ThumbnailField::new('photo', 'Photo')
                    ->setBasePath($this->getParameter('vich_dirs.item_photo'))
                    ->setTextAlign('center')
                    ->setSortable(false);
                // yield TextField::new('title', 'Titre');
                yield BooleanField::new('prefered', 'Page principale')->setTextAlign('center');
                // yield TextField::new('slug', 'Slug');
                yield TextField::new('twigfileName', 'Modèle')->setTextAlign('center');
                // yield TextField::new('content', 'Texte de la page')->formatValue(fn ($value) => Strings::markup($value))->setSortable(false);
                // yield AssociationField::new('items', 'Sections de pages')->setTextAlign('center');
                // yield AssociationField::new('mainmenu', 'Menu intégré')->setTextAlign('center');
                yield BooleanField::new('enabled', 'Activée')->setTextAlign('center');
                yield AssociationField::new('owner', 'Propriétaire');
                yield DateTimeField::new('createdAt', 'Création')->setFormat('dd/MM/Y - HH:mm')->setTimezone($current_tz);
                break;
        }
    }

}