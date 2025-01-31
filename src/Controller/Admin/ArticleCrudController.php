<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Controller\Admin\Base\BaseCrudController;
use Aequation\LaboBundle\Service\Interface\LaboArticleServiceInterface;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COLLABORATOR')]
abstract class ArticleCrudController extends BaseCrudController
{

    public function configureFilters(Filters $filters): Filters
    {
        $filters
            ->add(TextFilter::new('name', 'Nom'))
            ->add(TextFilter::new('content', 'Texte'))
            ;
        return $filters;
    }

    public function configureFields(string $pageName): iterable
    {
        /** @var LaboArticleServiceInterface */
        $manager = $this->manager;
        /** @var BaseCrudController $this */
        $this->checkGrants($pageName);
        $info = $this->getContextInfo();
        /** @var LaboUserInterface $user */
        $user = $this->getUser();
        $timezone = $this->getParameter('timezone');
        $current_tz = $timezone !== $user->getTimezone() ? $user->getTimezone() : $timezone;
        // $info = $this->getContextInfo();
        switch ($pageName) {
            case Crud::PAGE_DETAIL:
                yield IdField::new('id')->setPermission('ROLE_SUPER_ADMIN');
                yield TextField::new('name', 'Nom');
                yield TextField::new('title', 'Titre');
                yield TextField::new('slug', 'Slug');
                yield TextEditorField::new('content', 'Texte');
                yield DateTimeField::new('start', 'Début');
                yield DateTimeField::new('end', 'Fin');
                yield TextField::new('timezone');
                yield DateTimeField::new('createdAt')->setFormat('dd/MM/Y - HH:mm:ss')->setTimezone($current_tz);
                if($timezone !== $user->getTimezone()) yield DateTimeField::new('createdAt')->setFormat('dd/MM/Y - HH:mm:ss')->setCssClass('text-bg-primary')->setTimezone($user->getTimezone());
                yield DateTimeField::new('updatedAt')->setFormat('dd/MM/Y - HH:mm:ss')->setTimezone($current_tz);
                if($timezone !== $user->getTimezone() && $user->getUpdatedAt()) yield DateTimeField::new('updatedAt')->setFormat('dd/MM/Y - HH:mm:ss')->setCssClass('text-bg-primary')->setTimezone($user->getTimezone());
                break;
            case Crud::PAGE_NEW:
                yield TextField::new('name', 'Nom')
                    ->setColumns(6);
                yield SlugField::new('slug', 'Slug')
                    ->setTargetFieldName('name')
                    ->setColumns(6)
                    ->setHelp('Utilisez un nom simple et pas trop long. <strong>Il est préférable de ne jamais changer ce slug</strong> car il changera l\'URL de la page et cela réduit l\'efficacité du référencement du site.');
                yield DateTimeField::new('start', 'Début')->setColumns(6);
                yield DateTimeField::new('end', 'Fin')->setColumns(6);
                yield TextField::new('title', 'Titre');
                yield TextEditorField::new('content', 'Texte')
                    ->setColumns(6)
                    ->setHelp('Contenu texte');
                break;
            case Crud::PAGE_EDIT:
                yield TextField::new('name', 'Nom')
                    ->setColumns(6);
                yield SlugField::new('slug', 'Slug')->setTargetFieldName('name')->setColumns(3)->setHelp('Utilisez un nom simple et pas trop long. <strong>Il est préférable de ne jamais changer ce slug</strong> car il changera l\'URL de la page et cela réduit l\'efficacité du référencement du site.');
                yield BooleanField::new('updateSlug')->setLabel('Mettre à jour le slug')->setColumns(2)->setHelp('Il est recommandé d\'éviter de changer le slug car il est indexé par les moteurs de recherche. Faites-le uniquement si le nom du slug n\'a plus aucun rapport avec le contenu de ce que vous êtes en train d\'éditer.');
                yield DateTimeField::new('start', 'Début')->setColumns(6);
                yield DateTimeField::new('end', 'Fin')->setColumns(6);
                yield TextField::new('title', 'Titre');
                yield TextEditorField::new('content', 'Texte')
                    ->setColumns(6)
                    ->setHelp('Contenu texte');
                break;
            default:
                yield IdField::new('id')->setPermission('ROLE_SUPER_ADMIN');
                yield TextField::new('name', 'Nom');
                yield TextField::new('slug', 'Slug');
                yield TextField::new('content', 'Texte');
                yield DateTimeField::new('start', 'Début');
                yield DateTimeField::new('end', 'Fin');
                break;
        }
    }

}