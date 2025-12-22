<?php
namespace Aequation\LaboBundle\Controller\Admin;

use App\Entity\Urlink;
use App\Entity\Emailink;
use App\Entity\Phonelink;
use App\Entity\Videolink;
use App\Entity\Entreprise;

use App\Entity\Addresslink;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormInterface;
use Aequation\LaboBundle\Field\CKEditorField;
use Aequation\LaboBundle\Service\Tools\Classes;
use Aequation\LaboBundle\Form\Type\PortraitType;
use App\Controller\Admin\UrlinkCrudController;
use App\Controller\Admin\VideolinkCrudController;
use Aequation\LaboBundle\Model\Interface\LaboRelinkInterface;
use App\Controller\Admin\EmailinkCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

use App\Controller\Admin\PhonelinkCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use App\Controller\Admin\AddresslinkCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ArrayFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimezoneField;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use Aequation\LaboBundle\Repository\LaboCategoryRepository;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

#[IsGranted('ROLE_COLLABORATOR')]
abstract class EntrepriseCrudController extends LaboUserCrudController
{

    // public const ENTITY = Entreprise::class;
    // public const VOTER = EntrepriseVoter::class;
    public const DEFAULT_SORT = ['lastLogin' => 'DESC', 'createdAt' => 'DESC'];

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('firstname', 'Nom'))
            ->add(TextFilter::new('fonction', 'Secteur activité'))
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        $this->checkGrants($pageName);
        /** @var Entreprise $entreprise */
        $entreprise = $this->getLaboContext()->getInstance();
        switch ($pageName) {
            case Crud::PAGE_DETAIL:
                yield FormField::addPanel(label: 'Sécurité', icon: 'tabler:lock-filled');
                yield IdField::new('id');
                yield EmailField::new('email');
                yield TextField::new('fonction', 'Secteur activité');
                // yield TextField::new('password', 'Mot de passe (codé)')->setPermission('ROLE_SUPER_ADMIN');
                // yield TextField::new('higherRole')
                //     ->setCssClass('text-warning')
                //     ->setHelp('<h5>Tous les roles<br>pour cet utilisateur&nbsp;:</h5><div><ul><li>'.implode('</li><li>', $this->translate($entreprise->getReachableRoles())).'</li></ul></div>')
                //     ->formatValue(fn ($value) => $this->translate($value))
                //     ->setPermission('ROLE_SUPER_ADMIN');
                // yield ArrayField::new('roles')->formatValue(fn ($value) => $this->translate($value))->setPermission('ROLE_SUPER_ADMIN');
                // yield ArrayField::new('inferiorRoles')->formatValue(fn ($value) => $this->translate($value))->setPermission('ROLE_SUPER_ADMIN');
                // yield ArrayField::new('reachableRoles');
                yield FormField::addPanel(label: 'Autres informations', icon: 'tabler:building-factory-2')->setHelp('Informations supplémentaires');
                yield TextField::new('firstname', 'Nom');
                // yield TextField::new('lastname', 'Prénom');
                yield ArrayField::new('members', 'Membres');
                yield ImageField::new('portrait', 'Photo')
                    ->setFormType(PortraitType::class)
                    ->setBasePath($this->getParameter('vich_dirs.user_portrait'));
                yield CollectionField::new('categorys', 'Catégories');
                yield TextField::new('timezone', 'Fuseau horaire');
                yield DateTimeField::new('createdAt', 'Création')->setFormat('dd/MM/Y - HH:mm:ss')->setTimezone($this->getLaboContext()->getTimezone());
                if($this->getParameter('timezone') !== $entreprise->getTimezone()) yield DateTimeField::new('createdAt', 'Création')->setFormat('dd/MM/Y - HH:mm:ss')->setCssClass('text-bg-primary')->setTimezone($entreprise->getTimezone());
                yield DateTimeField::new('updatedAt', 'Modification')->setFormat('dd/MM/Y - HH:mm:ss')->setTimezone($this->getLaboContext()->getTimezone());
                if($this->getParameter('timezone') !== $entreprise->getTimezone() && $entreprise->getUpdatedAt()) yield DateTimeField::new('updatedAt', 'Modification')->setFormat('dd/MM/Y - HH:mm:ss')->setCssClass('text-bg-primary')->setTimezone($entreprise->getTimezone());
                // yield DateTimeField::new('lastLogin')->setFormat('dd/MM/Y - HH:mm:ss')->setTimezone($this->getLaboContext()->getTimezone())->setPermission('ROLE_SUPER_ADMIN');
                // if($this->getParameter('timezone') !== $entreprise->getTimezone() && $entreprise->getLastLogin()) yield DateTimeField::new('lastLogin')->setFormat('dd/MM/Y - HH:mm:ss')->setCssClass('text-bg-primary')->setTimezone($entreprise->getTimezone())->setPermission('ROLE_SUPER_ADMIN');
                if($entreprise->getExpiresAt()) {
                    yield DateTimeField::new('expiresAt', 'Expiration')->setFormat('dd/MM/Y - HH:mm:ss')->setTimezone($this->getLaboContext()->getTimezone());
                    if($this->getParameter('timezone') !== $entreprise->getTimezone()) yield DateTimeField::new('expiresAt', 'Expiration')->setFormat('dd/MM/Y - HH:mm:ss')->setCssClass('text-bg-primary')->setTimezone($entreprise->getTimezone());
                }
                // yield BooleanField::new('darkmode')->setPermission('ROLE_SUPER_ADMIN');
                yield BooleanField::new('enabled', 'Active');
                // yield BooleanField::new('isVerified');
                yield BooleanField::new('softdeleted')->setPermission('ROLE_SUPER_ADMIN');
                yield FormField::addPanel(label: 'Contacts', icon: 'tabler:address-book')->setHelp('Informations de contact');
                yield CollectionField::new('relinks', 'Urls');
                yield CollectionField::new('addresses', 'Adresses');
                yield CollectionField::new('emails', 'Emails');
                yield CollectionField::new('phones', 'Téléphones');
                break;
            case Crud::PAGE_NEW:
            case Crud::PAGE_EDIT:
                yield FormField::addTab(label: $this->translate('name', domain: Classes::getShortname(Entreprise::class)), icon: Entreprise::ICON);
                yield EmailField::new('email')->setColumns(6)->setHelp('Le mail doit être unique : l\'enregistrement sera rejeté si une autre personne utilise le mail sur le même site.');
                yield TextField::new('firstname', 'Nom')->setColumns(6);
                // yield TextField::new('lastname', 'Prénom')->setColumns(6);
                yield TextField::new('fonction', 'Secteur activité')->setColumns(6);
                // yield AssociationField::new('categorys', 'Catégories')->setQueryBuilder(static fn (QueryBuilder $qb): QueryBuilder => LaboCategoryRepository::QB_CategoryChoices($qb, Entreprise::class))
                //     // ->autocomplete()
                //     ->setSortProperty('name')
                //     ->setFormTypeOptions(['by_reference' => false])
                //     ->setColumns(6);
                // yield CKEditorField::new('description', 'Description')->setColumns(6);
                // yield TimezoneField::new('timezone', 'Fuseau horaire')->setColumns(4);
                // yield DateTimeField::new('expiresAt', 'Expiration')->setColumns(3)->setPermission('ROLE_ADMIN')->setTimezone($this->getLaboContext()->getTimezone());
                yield BooleanField::new('enabled', 'Active')->setColumns(3)->setPermission('ROLE_ADMIN');
                // yield BooleanField::new('isVerified')->setColumns(3)->setHelp('Compte vérifié')->setPermission('ROLE_ADMIN');
                // yield BooleanField::new('darkmode')->setColumns(3)->setHelp('Interface graphique en mode sombre')->setPermission('ROLE_SUPER_ADMIN');
                yield BooleanField::new('softdeleted')->setFormTypeOption('attr', ['class' => 'border-danger text-bg-danger'])->setColumns(3)->setPermission('ROLE_SUPER_ADMIN');
                yield TextField::new('portrait', 'Photo')
                    ->setFormType(PortraitType::class)
                    // ->setFormTypeOption('allow_delete', false)
                    ->setColumns(6);
                yield FormField::addTab(label: false, icon: Addresslink::ICON);
                yield CollectionField::new('addresses', 'Adresses')
                    ->useEntryCrudForm(AddresslinkCrudController::class)
                    ->setEntryToStringMethod(fn (?LaboRelinkInterface $entity) => $entity ? (empty($entity->getName()) ? $entity->getLinktitle() : $entity->getName()) : '---')
                    ->setColumns(12)
                    ->setEntryIsComplex(true)
                    ->setFormTypeOption('by_reference', false)

                    // ->setEmptyData(fn (FormInterface $form) => $this->appEntityManager->getNew(AddresslinkCrudController::ENTITY))
                    ;
                yield FormField::addTab(label: false, icon: Phonelink::ICON);
                yield CollectionField::new('phones', 'Téléphones')
                    ->useEntryCrudForm(PhonelinkCrudController::class)
                    ->setEntryToStringMethod(fn (?LaboRelinkInterface $entity) => $entity ? (empty($entity->getName()) ? $entity->getLinktitle() : $entity->getName()) : '---')
                    ->setColumns(12)
                    ->setEntryIsComplex(true)
                    ->setFormTypeOption('by_reference', false)
                    // ->setEmptyData(fn (FormInterface $form) => $this->appEntityManager->getNew(PhonelinkCrudController::ENTITY))
                    ;
                yield FormField::addTab(label: false, icon: Emailink::ICON);
                yield CollectionField::new('emails', 'Emails')
                    ->useEntryCrudForm(EmailinkCrudController::class)
                    ->setEntryToStringMethod(fn (?LaboRelinkInterface $entity) => $entity ? (empty($entity->getName()) ? $entity->getLinktitle() : $entity->getName()) : '---')
                    ->setColumns(12)
                    ->setEntryIsComplex(true)
                    ->setFormTypeOption('by_reference', false)
                    // ->setEmptyData(fn (FormInterface $form) => $this->appEntityManager->getNew(EmailinkCrudController::ENTITY))
                    ;
                yield FormField::addTab(label: false, icon: Urlink::ICON);
                yield CollectionField::new('relinks', 'Urls')
                    ->useEntryCrudForm(UrlinkCrudController::class)
                    ->setEntryToStringMethod(fn (?LaboRelinkInterface $entity) => $entity ? (empty($entity->getName()) ? $entity->getLinktitle() : $entity->getName()) : '---')
                    ->setColumns(12)
                    ->setEntryIsComplex(true)
                    ->setFormTypeOption('by_reference', false)
                    // ->setEmptyData(fn (FormInterface $form) => $this->appEntityManager->getNew(UrlinkCrudController::ENTITY))
                    ;
                yield FormField::addTab(label: false, icon: Videolink::ICON);
                yield CollectionField::new('videolinks', 'Vidéos')
                    ->useEntryCrudForm(VideolinkCrudController::class)
                    ->setEntryToStringMethod(fn (?LaboRelinkInterface $entity) => $entity ? (empty($entity->getName()) ? $entity->getLinktitle() : $entity->getName()) : '---')
                    ->setColumns(12)
                    ->setEntryIsComplex(true)
                    ->setFormTypeOption('by_reference', false)
                    // ->setEmptyData(fn (FormInterface $form) => $this->appEntityManager->getNew(VideolinkCrudController::ENTITY))
                    ;
                break;
            default:
                yield IdField::new('id')->setPermission('ROLE_SUPER_ADMIN');
                yield TextField::new('firstname', 'Nom');
                yield EmailField::new('email');
                yield TextField::new('fonction', 'Secteur activité');
                yield ImageField::new('portrait', 'Photo')
                    ->setBasePath($this->getParameter('vich_dirs.user_portrait'))
                    ->setTextAlign('center')
                    ->setSortable(false);
                // yield TextField::new('higherRole')->setTextAlign('center')->formatValue(fn ($value) => '<small class="text-muted"><i>'.$this->translate($value).'</i></small>');
                // yield DateTimeField::new('createdAt', 'Création')->setFormat('dd/MM/Y - HH:mm')->setTimezone($this->getLaboContext()->getTimezone());
                // yield BooleanField::new('darkmode')->setTextAlign('center');
                // yield BooleanField::new('enabled', 'Active')->setTextAlign('center')->setPermission('ROLE_ADMIN');
                // yield BooleanField::new('isVerified')->setTextAlign('center');
                // yield BooleanField::new('softdeleted')->setTextAlign('center')->setPermission('ROLE_SUPER_ADMIN');
                // yield TimezoneField::new('timezone', 'Fuseau horaire')->setTextAlign('center');
                // yield DateTimeField::new('lastLogin')->setFormat('dd/MM/YY HH:mm')->setTimezone($this->getLaboContext()->getTimezone())->setPermission('ROLE_SUPER_ADMIN');
                break;
        }
    }
}