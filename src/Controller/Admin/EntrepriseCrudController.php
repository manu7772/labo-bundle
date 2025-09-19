<?php
namespace Aequation\LaboBundle\Controller\Admin;

use App\Controller\Admin\AddresslinkCrudController;
use App\Controller\Admin\PhonelinkCrudController;
use App\Controller\Admin\EmailinkCrudController;

use Aequation\LaboBundle\Security\Voter\EntrepriseVoter;
use Aequation\LaboBundle\Controller\Admin\Base\BaseCrudController;
use Aequation\LaboBundle\Field\CKEditorField;
use Aequation\LaboBundle\Form\Type\PortraitType;
use Aequation\LaboBundle\Repository\LaboCategoryRepository;

use App\Entity\Entreprise;
use App\Service\Interface\EntrepriseServiceInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimezoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ArrayFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

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
        $info = $this->getContextInfo();
        /** @var Entreprise $entreprise */
        $entreprise = $info['entity'];
        $timezone = $this->getParameter('timezone');
        $current_tz = $timezone !== $entreprise->getTimezone() ? $entreprise->getTimezone() : $timezone;
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
                yield DateTimeField::new('createdAt', 'Création')->setFormat('dd/MM/Y - HH:mm:ss')->setTimezone($current_tz);
                if($timezone !== $entreprise->getTimezone()) yield DateTimeField::new('createdAt', 'Création')->setFormat('dd/MM/Y - HH:mm:ss')->setCssClass('text-bg-primary')->setTimezone($entreprise->getTimezone());
                yield DateTimeField::new('updatedAt', 'Modification')->setFormat('dd/MM/Y - HH:mm:ss')->setTimezone($current_tz);
                if($timezone !== $entreprise->getTimezone() && $entreprise->getUpdatedAt()) yield DateTimeField::new('updatedAt', 'Modification')->setFormat('dd/MM/Y - HH:mm:ss')->setCssClass('text-bg-primary')->setTimezone($entreprise->getTimezone());
                // yield DateTimeField::new('lastLogin')->setFormat('dd/MM/Y - HH:mm:ss')->setTimezone($current_tz)->setPermission('ROLE_SUPER_ADMIN');
                // if($timezone !== $entreprise->getTimezone() && $entreprise->getLastLogin()) yield DateTimeField::new('lastLogin')->setFormat('dd/MM/Y - HH:mm:ss')->setCssClass('text-bg-primary')->setTimezone($entreprise->getTimezone())->setPermission('ROLE_SUPER_ADMIN');
                if($entreprise->getExpiresAt()) {
                    yield DateTimeField::new('expiresAt', 'Expiration')->setFormat('dd/MM/Y - HH:mm:ss')->setTimezone($current_tz);
                    if($timezone !== $entreprise->getTimezone()) yield DateTimeField::new('expiresAt', 'Expiration')->setFormat('dd/MM/Y - HH:mm:ss')->setCssClass('text-bg-primary')->setTimezone($entreprise->getTimezone());
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
                yield FormField::addPanel(label: 'Sécurité', icon: 'tabler:lock-filled');
                yield EmailField::new('email')->setColumns($this->isGranted('ROLE_ADMIN') ? 4 : 6)->setHelp('Le mail doit être unique : l\'enregistrement sera rejeté si une autre personne utilise le mail sur le même site.');
                yield TextField::new('plainPassword', 'Mot de passe')->setRequired(true)->setColumns($this->isGranted('ROLE_ADMIN') ? 4 : 6)->setHelp('Utilisez des lettres, des signes et des chiffres, et au moins 12 caractères.');
                // yield ChoiceField::new('roles')->setChoices(function(?Entreprise $entreprise): array { return $entreprise->getRolesChoices($this->getUser()); })->setColumns(4)->allowMultipleChoices(true)->setHelp('Les roles déterminent les niveaux d\'accès à l\'administration du site.')->setPermission('ROLE_SUPER_ADMIN');
                yield FormField::addPanel(label: 'Autres informations', icon: 'entreprise')->setHelp('Informations supplémentaires');
                yield TextField::new('firstname', 'Nom')->setColumns(6);
                // yield TextField::new('lastname', 'Prénom')->setColumns(6);
                yield TextField::new('fonction', 'Secteur activité')->setColumns(6);
                yield AssociationField::new('categorys', 'Catégories')->setQueryBuilder(static fn (QueryBuilder $qb): QueryBuilder => LaboCategoryRepository::QB_CategoryChoices($qb, Entreprise::class))
                    // ->autocomplete()
                    ->setSortProperty('name')
                    ->setFormTypeOptions(['by_reference' => false])
                    ->setColumns(6);
                yield TextField::new('portrait', 'Photo')
                    ->setFormType(PortraitType::class)
                    ->setColumns(6);
                yield AssociationField::new('members', 'Membres')
                    // ->autocomplete()
                    ->setSortProperty('firstname')
                    ->setFormTypeOptions(['by_reference' => false])
                    ;
                yield FormField::addTab(label: false, icon: 'tabler:address-book');
                yield CollectionField::new('addresses', 'Adresses')->useEntryCrudForm(AddresslinkCrudController::class)->setColumns(12);
                yield FormField::addTab(label: false, icon: 'tabler:phone');
                yield CollectionField::new('phones', 'Téléphones')->useEntryCrudForm(PhonelinkCrudController::class)->setColumns(12)->setEmptyData(fn (FormInterface $form) => $this->appEntityManager->getNew(PhonelinkCrudController::ENTITY));
                yield FormField::addTab(label: false, icon: 'tabler:mail');
                yield CollectionField::new('emails', 'Emails')->useEntryCrudForm(EmailinkCrudController::class)->setColumns(12);

                yield TimezoneField::new('timezone', 'Fuseau horaire')->setColumns(4);
                yield DateTimeField::new('expiresAt', 'Expiration')->setColumns(3)->setPermission('ROLE_ADMIN')->setTimezone($current_tz);
                yield BooleanField::new('enabled', 'Active')->setColumns(3)->setPermission('ROLE_ADMIN');
                // yield BooleanField::new('isVerified')->setColumns(3)->setHelp('Compte vérifié')->setPermission('ROLE_ADMIN');
                // yield BooleanField::new('darkmode')->setColumns(3)->setHelp('Interface graphique en mode sombre')->setPermission('ROLE_SUPER_ADMIN');
                yield BooleanField::new('softdeleted')->setFormTypeOption('attr', ['class' => 'border-danger text-bg-danger'])->setColumns(3)->setPermission('ROLE_SUPER_ADMIN');
                break;
            case Crud::PAGE_EDIT:
                yield FormField::addTab(label: 'Sécurité', icon: 'tabler:lock-filled');
                yield EmailField::new('email')->setColumns($this->isGranted('ROLE_ADMIN') ? 4 : 6)->setHelp('Le mail doit être unique : l\'enregistrement sera rejeté si une autre personne utilise le mail sur le même site.');
                // yield TextField::new('plainPassword', 'Mot de passe', 'Nouveau mot de passe')->setColumns($this->isGranted('ROLE_ADMIN') ? 4 : 6)->setHelp('<strong class="text-danger">ATTENTION</strong> : ne remplissez ce champ QUE SI vous souhaitez changer votre mot de passe. <strong>Dans ce cas, pensez à bien le noter !</strong>');
                // yield ChoiceField::new('roles')->setChoices(function(?Entreprise $entreprise): array { return $entreprise->getRolesChoices($this->getUser()); })->setColumns(4)->allowMultipleChoices(true)->setHelp('Les roles déterminent les niveaux d\'accès à l\'administration du site.')->setPermission('ROLE_SUPER_ADMIN')->renderAsBadges();
                yield FormField::addTab(label: 'Autres informations', icon: 'tabler:building-factory-2')->setHelp('Informations supplémentaires');
                yield TextField::new('firstname', 'Nom')->setColumns(6);
                // yield TextField::new('lastname', 'Prénom')->setColumns(6);
                yield TextField::new('fonction', 'Secteur activité')->setColumns(6);
                yield AssociationField::new('categorys', 'Catégories')->setQueryBuilder(static fn (QueryBuilder $qb): QueryBuilder => LaboCategoryRepository::QB_CategoryChoices($qb, Entreprise::class))
                    // ->autocomplete()
                    ->setSortProperty('name')
                    ->setFormTypeOptions(['by_reference' => false])
                    ->setColumns(6);
                // yield CKEditorField::new('description', 'Description')->setColumns(6);
                yield TimezoneField::new('timezone', 'Fuseau horaire')->setColumns(4);
                yield DateTimeField::new('expiresAt', 'Expiration')->setColumns(3)->setPermission('ROLE_ADMIN')->setTimezone($current_tz);
                // yield DateTimeField::new('lastLogin')->setColumns(3)->setPermission('ROLE_SUPER_ADMIN')->setTimezone($current_tz);
                yield BooleanField::new('enabled', 'Active')->setColumns(3)->setPermission('ROLE_ADMIN');
                // yield BooleanField::new('isVerified')->setColumns(3)->setHelp('Compte vérifié')->setPermission('ROLE_ADMIN');
                // yield BooleanField::new('darkmode')->setColumns(3)->setHelp('Interface graphique en mode sombre')->setPermission('ROLE_SUPER_ADMIN');
                yield BooleanField::new('softdeleted')->setFormTypeOption('attr', ['class' => 'border-danger text-bg-danger'])->setColumns(3)->setPermission('ROLE_SUPER_ADMIN');
                yield TextField::new('portrait', 'Photo')
                    ->setFormType(PortraitType::class)
                    // ->setFormTypeOption('allow_delete', false)
                    ->setColumns(6);
                yield FormField::addTab(label: false, icon: 'tabler:address-book');
                yield CollectionField::new('addresses', 'Adresses')->useEntryCrudForm(AddresslinkCrudController::class)->setColumns(12);
                yield FormField::addTab(label: false, icon: 'tabler:phone');
                yield CollectionField::new('phones', 'Téléphones')->useEntryCrudForm(PhonelinkCrudController::class)->setColumns(12)->setEmptyData(fn (FormInterface $form) => $this->appEntityManager->getNew(PhonelinkCrudController::ENTITY));
                yield FormField::addTab(label: false, icon: 'tabler:mail');
                yield CollectionField::new('emails', 'Emails')->useEntryCrudForm(EmailinkCrudController::class)->setColumns(12);
                // yield FormField::addTab(label: 'Membres', icon: 'fa6-solid:users')->setHelp('Membres de l\'entreprise');
                // yield AssociationField::new('members', 'Membres')
                //     // ->autocomplete()
                //     ->setSortProperty('firstname')
                //     ->setFormTypeOptions(['by_reference' => false]);
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
                // yield DateTimeField::new('createdAt', 'Création')->setFormat('dd/MM/Y - HH:mm')->setTimezone($current_tz);
                // yield BooleanField::new('darkmode')->setTextAlign('center');
                // yield BooleanField::new('enabled', 'Active')->setTextAlign('center')->setPermission('ROLE_ADMIN');
                // yield BooleanField::new('isVerified')->setTextAlign('center');
                // yield BooleanField::new('softdeleted')->setTextAlign('center')->setPermission('ROLE_SUPER_ADMIN');
                // yield TimezoneField::new('timezone', 'Fuseau horaire')->setTextAlign('center');
                // yield DateTimeField::new('lastLogin')->setFormat('dd/MM/YY HH:mm')->setTimezone($current_tz)->setPermission('ROLE_SUPER_ADMIN');
                break;
        }
    }
}