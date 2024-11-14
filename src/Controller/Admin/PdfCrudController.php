<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Entity\Pdf;
use Aequation\LaboBundle\Security\Voter\PdfVoter;

use Aequation\LaboBundle\Controller\Admin\Base\BaseCrudController;
use Aequation\LaboBundle\Field\VichFileField;
use Aequation\LaboBundle\Service\Interface\PdfServiceInterface;
use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Vich\UploaderBundle\Form\Type\VichImageType;

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
        $info = $this->getContextInfo();
        switch ($pageName) {
            case Crud::PAGE_DETAIL:
                yield IdField::new('id');
                yield AssociationField::new('owner', 'Propriétaire');
                yield TextField::new('name');
                yield TextField::new('slug');
                yield TextField::new('originalname');
                // yield TextField::new('filename');
                yield TextField::new('mime');
                yield TextareaField::new('description');
                yield IntegerField::new('size')->formatValue(function ($value) { return intval($value/1024).'Ko'; });
                yield DateTimeField::new('createdAt', 'Date création')->setFormat('dd/MM/Y - HH:mm');
                break;
            case Crud::PAGE_NEW:
                yield TextField::new('name')->setColumns(6)->setRequired(true);
                // yield VichFileField::new('file')->setColumns(6);
                yield TextField::new('file', 'Fichier PDF')
                    ->setTemplatePath('')
                    ->setFormType(VichFileType::class)
                    ->setRequired(false)
                    ->setFormTypeOptions([
                        'allow_delete' => false,
                        // 'accept' => 'application/pdf',
                    ]);
                // yield ImageField::new('file', 'Fichier PDF')
                //     ->setFormType(FileUploadType::class)
                //     ->setBasePath('uploads/pdf/') //see documentation about ImageField to understand the difference beetwen setBasePath and setUploadDir
                //     ->setUploadDir('public/uploads/pdf/')
                //     ->setColumns(6)
                //     ->setFormTypeOptions(['attr' => [
                //             'accept' => 'application/pdf'
                //         ]
                //     ]);
                yield TextareaField::new('description')->setColumns(6);
                yield TextEditorField::new('content')->setColumns(6)->setCssClass('editorjs')->setHelp('Contenu du document : si vous avez désigné un fichier PDF, ce contenu sera ignoré');
                yield AssociationField::new('owner', 'Propriétaire')->autocomplete()->setColumns(6)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);
                break;
            case Crud::PAGE_EDIT:
                yield TextField::new('name')->setColumns(6)->setRequired(true);
                yield TextField::new('file', 'Fichier PDF')
                    ->setTemplatePath('')
                    ->setFormType(VichFileType::class)
                    ->setRequired(false)
                    ->setFormTypeOptions([
                        'allow_delete' => false,
                        // 'accept' => 'application/pdf',
                    ]);
                yield BooleanField::new('updateSlug')->setLabel('Mettre à jour le slug')->setColumns(6);
                yield SlugField::new('slug')->setTargetFieldName('name')->setColumns(6);
                yield TextareaField::new('description')->setColumns(6);
                yield TextEditorField::new('content')->setColumns(6)->setCssClass('editorjs')->setHelp('Contenu du document : si vous avez désigné un fichier PDF, ce contenu sera ignoré');
                yield AssociationField::new('owner', 'Propriétaire')->autocomplete()->setColumns(6)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);
                break;
            default:
                yield IdField::new('id')->setPermission('ROLE_SUPER_ADMIN');
                yield TextField::new('name', 'Nom du document');
                yield TextField::new('originalname', 'Nom du fichier');
                yield TextField::new('filepathname', 'Consulter')->setTemplatePath('@EasyAdmin/crud/field/pdf_link.html.twig');
                yield IntegerField::new('size')->setTextAlign('right')->formatValue(function ($value) { return intval($value/1024).'Ko'; });
                yield AssociationField::new('owner', 'Propriétaire');
                yield DateTimeField::new('createdAt', 'Date création')->setTextAlign('center')->setFormat('dd/MM/Y - HH:mm');
                break;
        }
    }

}