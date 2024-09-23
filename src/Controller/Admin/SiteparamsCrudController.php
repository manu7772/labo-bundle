<?php
namespace Aequation\LaboBundle\Controller\Admin;

use Aequation\LaboBundle\Security\Voter\SiteparamsVoter;

use Aequation\LaboBundle\Controller\Admin\Base\BaseCrudController;
use Aequation\LaboBundle\Service\Interface\SiteparamsServiceInterface;
use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;
use Aequation\LaboBundle\Service\Tools\Strings;
use Aequation\LaboBundle\Entity\Siteparams;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class SiteparamsCrudController extends BaseCrudController
{
    public const ENTITY = Siteparams::class;
    public const VOTER = SiteparamsVoter::class;

    public function configureFilters(Filters $filters): Filters
    {
        /** @var SiteparamsServiceInterface $manager */
        $manager = $this->manager;
        return $filters
            ->add(TextFilter::new('name'))
            ->add(ChoiceFilter::new('typevalue', 'Type')->setChoices(Siteparams::getTypevalueChoices()))
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        // /** @var SiteparamsServiceInterface $manager */
        // $manager = $this->manager;
        $this->checkGrants($pageName);
        $info = $this->getContextInfo();
        switch ($pageName) {
            case Crud::PAGE_DETAIL:
                yield IdField::new('id');
                yield TextField::new('name', 'Nom');
                yield TextField::new('typevalue', 'Type');
                yield BooleanField::new('dispatch');
                yield TextareaField::new('oneStringLineParam', 'Valeur')->formatValue(function ($value) use ($info) {
                    return $info['entity']->dumpParam();
                });
                // yield TextareaField::new('paramvalue', 'Valeur brute')->setPermission('ROLE_SUPER_ADMIN');
                break;
            case Crud::PAGE_NEW:
                yield TextField::new('name', 'Nom du paramètre')
                    ->setHelp('Le nom sera modifié pour être compatible avec un nom de paramètre. Veuillez donc bien vérifier le nom avant l\'utilisation de ce paramètre. Il doit contenir au minimum 1 caractère alphabétique [a-z] et d\'une longueur de 3 caractères.')
                    ->setColumns(5)
                    ;
                yield ChoiceField::new('typevalue', 'Type')
                    ->setChoices(Siteparams::getTypevalueChoices())
                    ->setRequired(true)
                    ->setColumns(4)
                    ;
                yield BooleanField::new('dispatch')->setColumns(3)->setHelp('Décompose le nom du paramètre (uniquement pour les paramètres de type array).');
                yield TextareaField::new('formvalue', 'Valeur')
                    ->setFormTypeOptions(['by_reference' => false])
                    ->setHelp('Pour une valeur de type "array" (tableau de données), un retour à la ligne correspond à une nouvelle entrée.')
                    ->setColumns(12)
                    ;
                yield TextareaField::new('description', 'Description du paramètre')
                    ->setHelp('Indiquez une description de l\'utilité de ce paramètre, afin de bien comprendre à quoi il sert.')
                    ->setColumns(12)
                    ;
                break;
            case Crud::PAGE_EDIT:
                yield TextField::new('name', 'Nom du paramètre')
                    ->setHelp('Le nom sera modifié pour être compatible avec un nom de paramètre. Veuillez donc bien vérifier le nom avant l\'utilisation de ce paramètre. Il doit contenir au minimum 1 caractère alphabétique [a-z] et d\'une longueur de 3 caractères.')
                    ->setColumns(5)
                    ;
                yield ChoiceField::new('typevalue', 'Type')
                    ->setChoices(Siteparams::getTypevalueChoices())
                    ->setRequired(true)
                    ->setDisabled(!$this->isGranted('ROLE_SUPER_ADMIN'))
                    ->setColumns(4)
                    ;
                if($info['entity']->getTypevalue() === 'array') yield BooleanField::new('dispatch')->setColumns(3)->setHelp('Décompose le nom du paramètre (uniquement pour les paramètres de type array).');
                switch ($info['entity']->getTypevalue()) {
                    case 'boolean':
                        yield BooleanField::new('formvalue', 'On/Off')
                            ->setFormTypeOptions(['by_reference' => false])
                            // ->setHelp('Pour une valeur de type "array" (tableau de données), un retour à la ligne correspond à une nouvelle entrée.')
                            ->setColumns(12)
                            ;
                        break;
                    case 'array':
                        yield TextareaField::new('formvalue', 'Valeur')
                            ->setFormTypeOptions(['by_reference' => false])
                            ->setHtmlAttribute('data-controller', 'JsonInTextarea')
                            ->setHelp('Pour une valeur de type "array" (tableau de données), entrée les valeurs au format JSON.')
                            ->setColumns(12)
                            ;
                        break;
                    default:
                        yield TextareaField::new('formvalue', 'Valeur')
                            ->setFormTypeOptions(['by_reference' => false])
                            ->setColumns(12)
                            ;
                        break;
                }
                yield TextareaField::new('description', 'Description du paramètre')
                    ->setHelp('Indiquez une description de l\'utilité de ce paramètre, afin de bien comprendre à quoi il sert.')
                    ->setColumns(12)
                    ;
                break;
            default:
                yield IdField::new('id')->setPermission('ROLE_SUPER_ADMIN');
                yield TextField::new('name', 'Nom');
                yield TextField::new('typevalue', 'Type')->setTextAlign('right');
                yield TextareaField::new('oneStringLineParam', 'Valeur')->formatValue(fn ($value) => Strings::cutAt($value, 50, true))->setTextAlign('left');
                yield BooleanField::new('dispatch')->setTextAlign('center');
                break;
        }
    }

}