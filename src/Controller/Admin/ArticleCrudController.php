<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Controller\Admin\Base\BaseCrudController;
use Aequation\LaboBundle\Field\CKEditorField;
use Aequation\LaboBundle\Field\ThumbnailField;
use Aequation\LaboBundle\Form\Type\PdfType;
use Aequation\LaboBundle\Form\Type\PhotoType;
use Aequation\LaboBundle\Model\Final\FinalArticleInterface;
use Aequation\LaboBundle\Security\Voter\ArticleVoter;
use Aequation\LaboBundle\Service\Tools\Strings;
use App\Controller\Admin\VideolinkCrudController;
use App\Entity\Videolink;
use App\Entity\Article;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COLLABORATOR')]
class ArticleCrudController extends BaseCrudController
{
    public const ENTITY = Article::class;
    public const VOTER = ArticleVoter::class;

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name'))
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        $this->checkGrants($pageName);
        switch ($pageName) {
            case Crud::PAGE_DETAIL:
                yield FormField::addTab(label: 'Article', icon: $this->getLaboContext()->getInstance()::ICON);

                yield FormField::addColumn('col-md-12 col-lg-6');
                    yield FormField::addPanel(label: 'Article', icon: Article::ICON);
                        yield IdField::new('id');
                        yield AssociationField::new('owner', 'Propriétaire')->setCrudController(UserCrudController::class);
                        yield TextField::new('name', 'Nom');
                        yield DateTimeField::new('publication', 'Publication')->setFormat('dd/MM/Y - HH:mm')->setTimezone($this->getLaboContext()->getTimezone());
                        yield IntegerField::new('orderitem', 'Priorité')->setHelp('Ordre d\'affichage de la page dans les listes.');
                        yield TextField::new('title', 'Titre de la section');
                        yield TextField::new('content', 'Texte de la section')->renderAsHtml();
                
                yield FormField::addColumn('col-md-12 col-lg-6');
                    yield FormField::addPanel(label: 'Médias associés', icon: 'fa6-solid:link');
                        yield CollectionField::new('categorys');
                        yield ArrayField::new('pdfiles', 'Fichiers PDF');
                        yield ArrayField::new('relinks', 'Urls');
                        yield ArrayField::new('videolinks', 'Vidéos');
                        yield AssociationField::new('slider', 'Diaporama');
                        yield ThumbnailField::new('photo', 'Photo')
                            ->setBasePath($this->getParameter('vich_dirs.item_photo'));

                    yield FormField::addPanel(label: 'Autres', icon: 'fa6-solid:info');
                        yield BooleanField::new('prefered', 'Article important');
                        yield BooleanField::new('enabled', 'Activé');
                        yield DateTimeField::new('createdAt', 'Création')->setFormat('dd/MM/Y - HH:mm')->setTimezone($this->getLaboContext()->getTimezone());
                        yield DateTimeField::new('updatedAt', 'Modification')->setFormat('dd/MM/Y - HH:mm')->setTimezone($this->getLaboContext()->getTimezone());

                yield FormField::addTab(label: 'Super admin', icon: 'tabler:lock-filled')->setPermission('ROLE_SUPER_ADMIN');
                    yield TextField::new('euid', 'Euid')->setPermission('ROLE_SUPER_ADMIN')->setHelp('Identifiant unique de la section');
                    yield ArrayField::new('parents', 'Parents')->setPermission('ROLE_SUPER_ADMIN')->setHelp('Liste des pages web utilisant cette section');
                    yield TextareaField::new('content', 'Texte compilé')
                        ->formatValue(fn ($value) => $this->getLaboContext()->getInstance()->dumpContent())
                        ->setColumns(12)->setPermission('ROLE_SUPER_ADMIN')
                        ->setHelp('Texte compilé avec les variables twig. Utile pour le débogage.')
                        ;
                    yield TextareaField::new('relationOrderDetails', 'Rel.order info')->setPermission('ROLE_SUPER_ADMIN');
                    yield BooleanField::new('softdeleted', 'Supprimé')->setPermission('ROLE_SUPER_ADMIN');
                break;
            case Crud::PAGE_NEW:
            case Crud::PAGE_EDIT:
                yield TextField::new('name', 'Nom de l\'article')
                    ->setHelp('Utilisez un nom simple et pas trop long. <strong>Ce nom est uniquement utilisé pour l\'administration et n\'est pas affiché dans le contenu la page web</strong>.')
                    ->setColumns(6);
                yield BooleanField::new('prefered', 'Article important')->setColumns(2);
                yield DateTimeField::new('publication', 'Publication')
                    ->setFormat('dd/MM/Y - HH:mm')
                    ->setTimezone($this->getLaboContext()->getTimezone())
                    ->setColumns(4);
                // TITLE
                yield TextareaField::new('title', 'Titre de l\'article')
                    ->setNumOfRows(1)
                    ->setColumns(6)
                    ->setRequired(true);
                yield AssociationField::new('videolinks', 'Vidéos')
                    ->setFormTypeOptions(['by_reference' => false])
                    ->setColumns(6)
                    ->setCrudController(VideolinkCrudController::class)
                    ;
                yield TextField::new('photo', 'Photo')
                    ->setFormType(PhotoType::class)
                    // ->setFormTypeOptions(['allow_delete' => false])
                    ->setColumns(6);
                // CONTENT
                yield CKEditorField::new('content', 'Texte de l\'article')
                    ->formatValue(fn ($value) => Strings::markup($value))
                    ->setRequired(true)
                    ->setColumns(12);
                // SLIDER
                yield AssociationField::new('slider', 'Diaporama')
                    ->setHelp('Diaporama de l\'article.')
                    ->setColumns(6);
                yield CollectionField::new('pdfiles', 'Fichiers PDF')
                    ->setEntryType(PdfType::class)
                    ->setColumns(6);
                yield BooleanField::new('enabled', 'Activé');
                yield BooleanField::new('softdeleted', 'Supprimé')->setPermission('ROLE_SUPER_ADMIN');
                yield AssociationField::new('owner', 'Propriétaire')->setColumns(6)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);
                yield IntegerField::new('orderitem', 'Priorité')->setHelp('Ordre d\'affichage dans les listes.')->setColumns(3);
                break;
            default:
                // yield IdField::new('id')->setPermission('ROLE_SUPER_ADMIN');
                yield ThumbnailField::new('photo', 'Photo')
                    ->setBasePath($this->getParameter('vich_dirs.item_photo'))
                    ->setTextAlign('center')
                    ->setSortable(false);
                yield TextField::new('name', 'Nom de l\'article');
                yield BooleanField::new('prefered', 'Article par défaut')->setTextAlign('center');
                // yield AssociationField::new('pdfiles', 'PDF')->setTextAlign('center')->setSortable(false);
                yield BooleanField::new('enabled', 'Activé')->setTextAlign('center');
                // yield DateTimeField::new('createdAt', 'Création')->setFormat('dd/MM/Y - HH:mm')->setTimezone($this->getLaboContext()->getTimezone());
                break;
        }
    }

    public function createEntity(
        string $entityFqcn,
        bool $checkGrant = true,
    ): object
    {
        // if($checkGrant) $this->checkGrants(Crud::PAGE_NEW);
        /** @var FinalArticleInterface $entity */
        $entity = $this->entityService->getNew();
        if($type = $this->getQueryValue('type')) {
            $entity->setSectiontype($type);
        }
        return $entity;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        if($type = $this->getQueryValue('type')) {
            $queryBuilder->andWhere('entity.sectiontype = :sectiontype')
                ->setParameter('sectiontype', $type);
        }
        return $queryBuilder;
    }

}