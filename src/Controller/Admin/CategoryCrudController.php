<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Controller\Admin\Base\BaseCrudController;
use Aequation\LaboBundle\Service\Interface\LaboCategoryServiceInterface;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COLLABORATOR')]
abstract class CategoryCrudController extends BaseCrudController
{

    public function configureFilters(Filters $filters): Filters
    {
        /** @var LaboCategoryServiceInterface */
        $manager = $this->manager;
        $filters
            ->add(TextFilter::new('name', 'Nom'))
            ->add(TextFilter::new('description', 'Information'))
            ;
        $typeChoices = $manager->getCategoryTypeChoices(false);
        if(!empty($typeChoices)) $filters->add(ChoiceFilter::new('type', 'Classe d\'entité')->setChoices($typeChoices));
        return $filters;
    }

    public function configureFields(string $pageName): iterable
    {
        /** @var LaboCategoryServiceInterface */
        $manager = $this->manager;
        /** @var BaseCrudController $this */
        $this->checkGrants($pageName);
        /** @var LaboUserInterface $user */
        $user = $this->getUser();
        switch ($pageName) {
            case Crud::PAGE_DETAIL:
                yield IdField::new('id')->setPermission('ROLE_SUPER_ADMIN');
                yield TextField::new('name', 'Nom');
                yield TextField::new('slug', 'Slug');
                yield TextField::new('description', 'Information');
                yield TextField::new('longTypeAsHtml', 'Classe d\'entité')->renderAsHtml(true);
                yield TextField::new('timezone');
                yield DateTimeField::new('createdAt')->setFormat('dd/MM/Y - HH:mm:ss')->setTimezone($$this->getLaboContext()->getTimezone());
                if($this->getParameter('timezone') !== $user->getTimezone()) yield DateTimeField::new('createdAt')->setFormat('dd/MM/Y - HH:mm:ss')->setCssClass('text-bg-primary')->setTimezone($user->getTimezone());
                yield DateTimeField::new('updatedAt')->setFormat('dd/MM/Y - HH:mm:ss')->setTimezone($this->getLaboContext()->getTimezone());
                if($this->getParameter('timezone') !== $user->getTimezone() && $user->getUpdatedAt()) yield DateTimeField::new('updatedAt')->setFormat('dd/MM/Y - HH:mm:ss')->setCssClass('text-bg-primary')->setTimezone($user->getTimezone());
                break;
            case Crud::PAGE_NEW:
                yield TextField::new('name', 'Nom')
                    ->setColumns(6)
                    ->setHelp('Nom de la catégorie : maximum 64 lettres');
                yield SlugField::new('slug', 'Slug')->setTargetFieldName('name')->setColumns(6)->setHelp('Utilisez un nom simple et pas trop long. <strong>Il est préférable de ne jamais changer ce slug</strong> car il changera l\'URL de la page et cela réduit l\'efficacité du référencement du site.');
                yield TextField::new('description', 'Information')
                    ->setColumns(6)
                    ->setHelp('Information succinte sur la catégorie : maximum 64 lettres');
                yield ChoiceField::new('type', 'Classe d\'entité')
                    ->setChoices($manager->getCategoryTypeChoices(true))
                    ->escapeHtml(false)
                    ->setRequired(true)
                    ->setHelp('Choisir une classe à laquelle appartient cette nouvelle catégorie')
                    ->setColumns(6);
                break;
            case Crud::PAGE_EDIT:
                yield TextField::new('name', 'Nom')
                    ->setColumns(6)
                    ->setHelp('Nom de la catégorie'.($this->isGranted('ROLE_SUPER_ADMIN') ? '' : ' <i>(non modifiable)</i>'))
                    ->setDisabled(!$this->isGranted('ROLE_SUPER_ADMIN'));
                yield SlugField::new('slug', 'Slug')->setTargetFieldName('name')->setColumns(3)->setHelp('Utilisez un nom simple et pas trop long. <strong>Il est préférable de ne jamais changer ce slug</strong> car il changera l\'URL de la page et cela réduit l\'efficacité du référencement du site.');
                yield BooleanField::new('updateSlug')->setLabel('Mettre à jour le slug')->setColumns(2)->setHelp('Il est recommandé d\'éviter de changer le slug car il est indexé par les moteurs de recherche. Faites-le uniquement si le nom du slug n\'a plus aucun rapport avec le contenu de ce que vous êtes en train d\'éditer.');
                yield TextField::new('description', 'Information')
                    ->setColumns(6)
                    ->setHelp('Information succinte sur la catégorie : maximum 64 lettres');
                yield ChoiceField::new('type', 'Classe d\'entité')
                    ->setDisabled(!$this->isGranted('ROLE_SUPER_ADMIN'))
                    ->setChoices($manager->getCategoryTypeChoices(true))
                    ->escapeHtml(false)
                    ->setRequired(true)
                    ->setHelp('Classe à laquelle appartient cette catégorie'.($this->isGranted('ROLE_SUPER_ADMIN') ? '' : ' <i>(non modifiable)</i>'))
                    ->setColumns(6);
                break;
            default:
                yield IdField::new('id')->setPermission('ROLE_SUPER_ADMIN');
                yield TextField::new('name', 'Nom');
                yield TextField::new('slug', 'Slug');
                yield TextField::new('description', 'Information');
                yield TextField::new('typeAsHtml', 'Classe d\'entité')->renderAsHtml(true)->setTextAlign('center');
                break;
        }
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $type = $this->getQueryValue('type');
        if($type && !class_exists($type)) $type = $this->manager->getClassnameByShortname($type);
        if($type) {
            $queryBuilder->andWhere('entity.type = :type')
                ->setParameter('type', $type);
        }
        return $queryBuilder;
    }

}