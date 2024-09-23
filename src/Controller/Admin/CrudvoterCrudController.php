<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Security\Voter\CrudvoterVoter;
use Aequation\LaboBundle\Controller\Admin\Base\BaseCrudController;
use Aequation\LaboBundle\Entity\Crudvoter;
use Aequation\LaboBundle\Service\Interface\CrudvoterServiceInterface;
use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
class CrudvoterCrudController extends BaseCrudController
{
    public const ENTITY = Crudvoter::class;
    public const VOTER = CrudvoterVoter::class;
    
    public function configureFilters(Filters $filters): Filters
    {
        /** @var CrudvoterServiceInterface $manager */
        $manager = $this->manager;
        return $filters
            ->add(TextFilter::new('voterclass', 'Nom du VOTER'))
            ->add(ChoiceFilter::new('entityclass', 'Classe d\'entité')->setChoices($manager->getEntityNamesChoices(false)))
            // ->add(TextFilter::new('entityshort', 'Nom d\'entité'))
            ->add(ChoiceFilter::new('firewall', 'Pare-feu')->setChoices($manager->getFirewallChoices(true)))
            ->add(TextFilter::new('attribute', 'Attribut'))
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        $this->checkGrants($pageName);
        /** @var CrudvoterServiceInterface $manager */
        $manager = $this->manager;
        // $info = $this->getContextInfo();
        switch ($pageName) {
            case Crud::PAGE_DETAIL:
                yield IdField::new('id');
                yield TextField::new('voterclass', 'Nom du VOTER');
                yield TextField::new('entityclass', 'Classe d\'entité');
                yield TextField::new('entityshort', 'Nom d\'entité');
                yield IntegerField::new('entity', 'Entité/Objet');
                yield TextField::new('firewall', 'Pare-feu');
                yield TextField::new('attribute', 'Attribut');
                yield ArrayField::new('users', 'Utilisateurs');
                yield TextareaField::new('voter', 'Algorithme de vote');
                break;
            case Crud::PAGE_NEW:
                yield TextField::new('voterclass', 'Nom du VOTER')->setColumns(6);
                yield ChoiceField::new('entityclass', 'Classe d\'entité')
                    ->setChoices($this->manager->getEntityNamesChoices(true))
                    ->escapeHtml(false)
                    ->setColumns(6)
                    ;
                // yield TextField::new('entityshort', 'Nom d\'entité')->setColumns(6);
                yield ChoiceField::new('firewall', 'Pare-feu')
                    ->setChoices($manager->getFirewallChoices(true))
                    ->setRequired(false)
                    ->setColumns(6)
                    ;
                yield IntegerField::new('entity', 'Entité/Objet')->setColumns(6);
                yield ChoiceField::new('users', 'Utilisateurs')
                    // ->setFormTypeOption('data_class', User::class)
                    ->setChoices(fn (): array => $this->userService->getRepository()->getChoicesForType(field: 'email'))
                    ->allowMultipleChoices(true)
                    ->autocomplete()
                    // ->setVirtual(true)
                    // ->setSortable(true)
                    ->setColumns(6)
                    ;
                yield TextField::new('attribute', 'Attribut')->setColumns(6);
                yield TextareaField::new('voter', 'Algorithme de vote')->setColumns(12);
                break;
            case Crud::PAGE_EDIT:
                yield TextField::new('voterclass', 'Nom du VOTER')->setColumns(6);
                yield ChoiceField::new('entityclass', 'Classe d\'entité')
                    ->setChoices($this->manager->getEntityNamesChoices(true))
                    ->escapeHtml(false)
                    ->setColumns(6)
                    ;
                // yield TextField::new('entityshort', 'Nom d\'entité')->setColumns(6);
                yield ChoiceField::new('firewall', 'Pare-feu')
                    ->setChoices($manager->getFirewallChoices(true))
                    ->setRequired(false)
                    ->setColumns(6)
                    ;
                yield IntegerField::new('entity', 'Entité/Objet')->setColumns(6);
                yield ChoiceField::new('users', 'Utilisateurs')
                    // ->setFormTypeOption('data_class', User::class)
                    ->setChoices(fn (): array => $this->userService->getRepository()->getChoicesForType(field: 'email'))
                    ->allowMultipleChoices(true)
                    ->autocomplete()
                    // ->setVirtual(true)
                    // ->setSortable(true)
                    ->setColumns(6)
                    ;
                yield TextField::new('attribute', 'Attribut')->setColumns(6);
                yield TextareaField::new('voter', 'Algorithme de vote')->setColumns(12);
                break;
            default:
                yield IdField::new('id')->setPermission('ROLE_SUPER_ADMIN');
                // yield TextField::new('voterclass', 'Nom du VOTER');
                // yield TextField::new('entityclass', 'Classe d\'entité');
                yield TextField::new('entityshort', 'Nom d\'entité');
                // yield IntegerField::new('entity', 'Objet');
                yield TextField::new('firewall', 'Pare-feu');
                yield TextField::new('attribute', 'Attribut');
                yield ArrayField::new('users', 'Utilisateurs');
                yield TextareaField::new('voter', 'Algorithme de vote');
                break;
        }
    }

}