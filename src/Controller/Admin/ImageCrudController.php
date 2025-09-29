<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Entity\Image;
use Aequation\LaboBundle\Field\ThumbnailField;
use Aequation\LaboBundle\Service\Tools\Strings;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
// Symfony
use Vich\UploaderBundle\Form\Type\VichImageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use Aequation\LaboBundle\Security\Voter\ImageVoter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Aequation\LaboBundle\Controller\Admin\Base\BaseCrudController;

#[IsGranted('ROLE_COLLABORATOR')]
class ImageCrudController extends BaseCrudController
{

    public const ENTITY = Image::class;
    public const VOTER = ImageVoter::class;

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name'))
            ->add(TextFilter::new('filename'))
            // ->add(NumericFilter::new('totalprice'))
            ->add(NumericFilter::new('size'))
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
                yield TextField::new('filename');
                yield TextField::new('mime');
                yield TextField::new('originalname');
                yield TextEditorField::new('description')->setNumOfRows(20);
                yield TextField::new('dimensions');
                yield ThumbnailField::new('filename', 'Photo')
                    ->setBasePath($this->getParameter('vich_dirs.item_photo'))
                    ->setTextAlign('center')
                    ->setSortable(false);
                yield IntegerField::new('size')->formatValue(function ($value) { return intval($value/1024).'Ko'; });
                yield DateTimeField::new('createdAt')->setFormat('dd/MM/Y - HH:mm');
                break;
            case Crud::PAGE_NEW:
                yield TextField::new('name')->setColumns(4)->setRequired(true);
                yield TextareaField::new('description')->setColumns(4)->formatValue(fn ($value) => Strings::markup($value));
                yield AssociationField::new('owner', 'Propriétaire')->setColumns(4)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);
                yield TextField::new('file')->setFormType(VichImageType::class);
                break;
            case Crud::PAGE_EDIT:
                yield TextField::new('name')->setColumns(4)->setRequired(true);
                yield TextareaField::new('description')->setColumns(4)->formatValue(fn ($value) => Strings::markup($value));
                yield AssociationField::new('owner', 'Propriétaire')->setColumns(4)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);
                yield TextField::new('file')->setFormType(VichImageType::class)->setDisabled();
                break;
            default:
                yield IdField::new('id')->setPermission('ROLE_SUPER_ADMIN');
                yield TextField::new('name');
                yield TextField::new('filename');
                yield ThumbnailField::new('filename', 'Photo')
                    ->setBasePath($this->getParameter('vich_dirs.item_photo'))
                    ->setTextAlign('center')
                    ->setSortable(false);
                yield IntegerField::new('size')->setTextAlign('center')->formatValue(function ($value) { return intval($value/1024).'Ko'; });
                yield AssociationField::new('owner', 'Propriétaire')->setCrudController(UserCrudController::class);
                yield DateTimeField::new('createdAt')->setFormat('dd/MM/Y - HH:mm');
                break;
        }
    }

}