<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Entity\Image;
use Aequation\LaboBundle\Security\Voter\ImageVoter;

use Aequation\LaboBundle\Controller\Admin\Base\BaseCrudController;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Vich\UploaderBundle\Form\Type\VichImageType;

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
        $info = $this->getContextInfo();
        switch ($pageName) {
            case Crud::PAGE_DETAIL:
                yield IdField::new('id');
                yield AssociationField::new('owner', 'Propriétaire');
                yield TextField::new('name');
                yield TextField::new('slug');
                yield TextField::new('filename');
                yield TextField::new('mime');
                yield TextField::new('originalname');
                yield TextEditorField::new('description');
                yield TextField::new('dimensions');
                yield ImageField::new('filename', 'Photo')
                    ->setBasePath($this->getParameter($info['entity']->_shortname(type: 'snake')));
                yield IntegerField::new('size')->formatValue(function ($value) { return intval($value/1024).'Ko'; });
                yield DateTimeField::new('createdAt')->setFormat('dd/MM/Y - HH:mm');
                break;
            case Crud::PAGE_NEW:
                yield TextField::new('name')->setColumns(6)->setRequired(true);
                yield TextField::new('file')
                    ->setFormType(VichImageType::class)
                    ->setColumns(6);
                yield TextEditorField::new('description')->setColumns(6);
                yield AssociationField::new('owner', 'Propriétaire')->autocomplete()->setColumns(6)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);
                break;
            case Crud::PAGE_EDIT:
                yield TextField::new('name')->setColumns(6)->setRequired(true);
                yield TextField::new('file')
                    ->setFormType(VichImageType::class)
                    ->setColumns(6);
                yield BooleanField::new('updateSlug')->setLabel('Mettre à jour le slug')->setColumns(6);
                yield SlugField::new('slug')->setTargetFieldName('name')->setColumns(6);
                yield TextEditorField::new('description')->setColumns(12);
                yield AssociationField::new('owner', 'Propriétaire')->autocomplete()->setColumns(6)->setPermission('ROLE_ADMIN')->setCrudController(UserCrudController::class);
                break;
            default:
                yield IdField::new('id')->setPermission('ROLE_SUPER_ADMIN');
                yield TextField::new('name');
                // yield TextField::new('filename');
                yield ImageField::new('filename', 'Photo')
                    ->setBasePath($this->getParameter($info['entity']->_shortname(type: 'snake')))
                    ->setTextAlign('center')
                    ->setSortable(false);
                yield IntegerField::new('size')->setTextAlign('center')->formatValue(function ($value) { return intval($value/1024).'Ko'; });
                yield AssociationField::new('owner', 'Propriétaire');
                yield DateTimeField::new('createdAt')->setFormat('dd/MM/Y - HH:mm');
                break;
        }
    }

}