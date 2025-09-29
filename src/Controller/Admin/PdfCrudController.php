<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Entity\Pdf;
use Aequation\LaboBundle\Field\CKEditorField;

use Aequation\LaboBundle\Field\VichFileField;
use Aequation\LaboBundle\Form\Type\PhotoType;
use Aequation\LaboBundle\Field\ThumbnailField;
use Aequation\LaboBundle\Service\Tools\Strings;
use Vich\UploaderBundle\Form\Type\VichFileType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Aequation\LaboBundle\Security\Voter\PdfVoter;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use Aequation\LaboBundle\Service\Interface\PdfServiceInterface;
use Aequation\LaboBundle\Controller\Admin\Base\BaseCrudController;
use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;

#[IsGranted('ROLE_COLLABORATOR')]
class PdfCrudController extends BaseCrudController
{

    public const ENTITY = Pdf::class;
    public const VOTER = PdfVoter::class;

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name'))
            ->add(TextFilter::new('filename'))
            // ->add(NumericFilter::new('size'))
            ->add(DateTimeFilter::new('createdAt'))
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        $this->checkGrants($pageName);
        switch ($pageName) {
            case Crud::PAGE_DETAIL:
                yield IdField::new('id');
                yield AssociationField::new('owner', 'Propriétaire');
                yield TextField::new('name');
                yield TextField::new('slug');
                yield TextField::new('euid', 'URL EUID')
                    ->setPermission('ROLE_SUPER_ADMIN')
                    ->formatValue(function ($value) { return urlencode($value); })
                    ;
                yield TextField::new('originalname');
                yield TextField::new('filepathname', 'Consulter')->setTemplatePath('@EasyAdmin/crud/field/pdf_link.html.twig');
                // yield TextField::new('filename');
                yield TextField::new('mime');
                yield TextareaField::new('description');
                yield BooleanField::new('enabled', 'Visible sur le site');
                yield IntegerField::new('size')->formatValue(function ($value) { return intval($value/1024).'Ko'; });
                yield DateTimeField::new('createdAt', 'Date création')->setFormat('dd/MM/Y - HH:mm');
                break;
            case Crud::PAGE_NEW:
                yield FormField::addTab(label: 'Informations', icon: 'fa6-solid:info');
                yield TextField::new('name')->setColumns(6)->setRequired(true);
                yield SlugField::new('slug')->setTargetFieldName('name')->setColumns(6);
                yield ChoiceField::new('sourcetype', 'Type de source')->setChoices(Pdf::getSourcetypeChoices())->setColumns(6)->setPermission('ROLE_SUPER_ADMIN');
                yield AssociationField::new('owner', 'Propriétaire')->setColumns(6)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);
                yield TextareaField::new('description', 'Description du contenu du PDF')->setColumns(12);
                yield BooleanField::new('enabled', 'Visible sur le site');
                // Source: PDF file
                yield FormField::addTab(label: 'Fichier source', icon: 'fa6-solid:file-pdf')->setHelp('Vous pouvez choisir un fichier PDF');
                // yield VichFileField::new('file')->setColumns(6);
                yield TextField::new('file', 'Fichier PDF')
                    ->setColumns(3)
                    // ->setTemplatePath('')
                    ->setFormType(VichFileType::class)
                    ->setRequired(false)
                    ->setFormTypeOptions([
                        'allow_delete' => false,
                        // 'accept' => 'application/pdf',
                    ]);
                // Source: content
                yield FormField::addTab(label: 'Contenu source', icon: 'fa6-solid:pencil')->setHelp('Vous pouvez saisir le contenu du document PDF ici.<br><strong>Si vous avez désigné un fichier source PDF, tout ce contenu sera ignoré</strong>');
                yield ChoiceField::new('paper', 'Format document')->setChoices(Pdf::getPaperChoices())->setColumns(6);
                yield ChoiceField::new('orientation', 'Orientation document')->setFormTypeOption('expanded', true)->setChoices(Pdf::getOrientationChoices())->setColumns(6);
                yield CKEditorField::new('content', 'Contenu du fichier PDF')
                    ->setColumns(12)
                    ->setHelp('Contenu du document PDF : si vous avez désigné un fichier source PDF, ce contenu sera ignoré')
                    ->formatValue(fn ($value) => Strings::markup($value));
                yield FormField::addTab(label: 'Vignette', icon: 'fa7-regular:image')->setHelp('Choisissez une image pour illustrer le document PDF (optionnel)');
                yield TextField::new('photo', 'Photo')
                    ->setFormType(PhotoType::class)
                    ->setColumns(6);
                break;
            case Crud::PAGE_EDIT:
                yield FormField::addTab(label: 'Informations', icon: 'fa6-solid:info');
                yield TextField::new('name')->setColumns(6)->setRequired(true);
                yield SlugField::new('slug')->setTargetFieldName('name')->setColumns(6);
                yield ChoiceField::new('sourcetype', 'Type de source')->setChoices(Pdf::getSourcetypeChoices())->setColumns(6)->setPermission('ROLE_SUPER_ADMIN');
                yield AssociationField::new('owner', 'Propriétaire')->setColumns(6)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);
                yield TextareaField::new('description', 'Description du contenu du PDF')->setColumns(12);
                yield BooleanField::new('enabled', 'Visible sur le site');
                switch ($this->getLaboContext()->getInstance()->getSourcetype()) {
                    case 2:
                        # file
                        // Source: PDF file
                        yield FormField::addTab(label: 'Fichier source', icon: 'fa6-solid:file-pdf')->setHelp('Vous pouvez choisir un fichier PDF');
                        // yield VichFileField::new('file')->setColumns(6);
                        yield TextField::new('file', 'Fichier PDF')
                            ->setColumns(3)
                            // ->setTemplatePath('')
                            ->setFormType(VichFileType::class)
                            ->setRequired(false)
                            ->setFormTypeOptions([
                                'allow_delete' => false,
                                // 'accept' => 'application/pdf',
                            ]);
                        break;
                    default:
                        # document
                        // Source: content
                        yield FormField::addTab(label: 'Contenu source', icon: 'fa6-solid:pencil')->setHelp('Vous pouvez saisir le contenu du document PDF ici.<br><strong>Si vous avez désigné un fichier source PDF, tout ce contenu sera ignoré</strong>');
                        yield ChoiceField::new('paper', 'Format document')->setChoices(Pdf::getPaperChoices())->setColumns(6)->setRequired(false);
                        yield ChoiceField::new('orientation', 'Orientation document')->setFormTypeOption('expanded', true)->setChoices(Pdf::getOrientationChoices())->setColumns(6)->setRequired(false);
                        yield CKEditorField::new('content', 'Contenu du fichier PDF')
                            ->setColumns(12)
                            ->setHelp('Contenu du document PDF : si vous avez désigné un fichier source PDF, ce contenu sera ignoré')
                            ->formatValue(fn ($value) => Strings::markup($value));
                        break;
                }
                // yield TextField::new('name')->setColumns(6)->setRequired(true);
                // yield TextField::new('file', 'Fichier PDF')
                //     ->setTemplatePath('')
                //     ->setFormType(VichFileType::class)
                //     ->setRequired(false)
                //     ->setFormTypeOptions([
                //         'allow_delete' => false,
                //         // 'accept' => 'application/pdf',
                //     ]);
                // yield BooleanField::new('updateSlug')->setLabel('Mettre à jour le slug')->setColumns(6)->setHelp('Si vous cochez cette case, le slug sera mis à jour avec le nom du document.<br><strong>Il n\'est pas recommandé de modifier le slug</strong> car cela change le lien URL du document.');
                // yield SlugField::new('slug')->setTargetFieldName('name')->setColumns(6);
                // yield TextEditorField::new('content', 'Contenu du fichier PDF')->setColumns(12)->setNumOfRows(20)->setHelp('Contenu du document : si vous avez désigné un fichier PDF, ce contenu sera ignoré');
                // yield TextareaField::new('description', 'Description du contenu du PDF')->setColumns(6);
                // yield AssociationField::new('owner', 'Propriétaire')->setColumns(6)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);
                yield FormField::addTab(label: 'Vignette', icon: 'fa7-regular:image')->setHelp('Choisissez une image pour illustrer le document PDF (optionnel)');
                yield TextField::new('photo', 'Photo')
                    ->setFormType(PhotoType::class)
                    ->setColumns(6);
                break;
            default:
                yield IdField::new('id')->setPermission('ROLE_SUPER_ADMIN');
                yield TextField::new('name', 'Nom du document');
                // yield TextField::new('slug');
                // yield TextField::new('filename', 'Nom du fichier');
                yield ThumbnailField::new('photo', 'Photo')
                    ->setBasePath($this->getParameter('vich_dirs.item_photo'))
                    ->setTextAlign('center')
                    ->setSortable(false);
                yield TextField::new('sourcetypeName', 'Type')->setTextAlign('center');
                yield TextField::new('filepathname', 'Consulter')->setTextAlign('center')->setTemplatePath('@EasyAdmin/crud/field/pdf_link.html.twig');
                yield IntegerField::new('size')->setTextAlign('right')->formatValue(function ($value) { return intval($value/1024).'Ko'; })->setTextAlign('center');
                yield BooleanField::new('enabled', 'Visible')->setTextAlign('center');
                yield AssociationField::new('owner', 'Propr.')->setTextAlign('center');
                yield AssociationField::new('pdfowner', 'Attach.')->setTextAlign('center');
                // yield DateTimeField::new('createdAt', 'Date création')->setTextAlign('center')->setFormat('dd/MM/Y - HH:mm');
                break;
        }
    }

}