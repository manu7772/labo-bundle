<?php
namespace Aequation\LaboBundle\Controller\Admin;

use App\Entity\User;
use App\Entity\Entreprise;
use Doctrine\ORM\QueryBuilder;
use Aequation\LaboBundle\Field\ThumbnailField;
use Aequation\LaboBundle\Form\Type\PortraitType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Aequation\LaboBundle\Security\Voter\UserVoter;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;

use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
// use Symfony\Component\Translation\TranslatableMessage;
use function Symfony\Component\Translation\t;
use Symfony\Contracts\Translation\TranslatorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use Aequation\LaboBundle\Model\Final\FinalUserInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimezoneField;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use Aequation\LaboBundle\Model\Interface\LaboUserInterface;
use Aequation\LaboBundle\Repository\LaboCategoryRepository;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Aequation\LaboBundle\Service\Interface\LaboUserServiceInterface;

#[IsGranted('ROLE_COLLABORATOR')]
class UserCrudController extends LaboUserCrudController
{

    public const ENTITY = User::class;
    public const VOTER = UserVoter::class;
    public const DEFAULT_SORT = ['lastLogin' => 'DESC', 'createdAt' => 'DESC'];

    public function configureFields(string $pageName): iterable
    {
        /** @var LaboUserServiceInterface */
        $manager = $this->manager;
        $this->checkGrants($pageName);
        $info = $this->getContextInfo();
        /** @var LaboUserInterface $user */
        $user = $info['entity'];
        $timezone = $this->getParameter('timezone');
        $current_tz = $timezone !== $user->getTimezone() ? $user->getTimezone() : $timezone;
        switch ($pageName) {
            case Crud::PAGE_DETAIL:
                if(!$manager->isLoggable($user)) {
                    $this->addFlash('error', t('Cet utilisateur ne peut actuellement pas se connecter à son compte (compte expiré, désactivé ou autre raison).'));
                }
                // ------------------------------------------------- Sécurité
                yield FormField::addColumn('col-md-12 col-lg-6');
                yield FormField::addPanel(label: 'Sécurité', icon: 'fa6-solid:lock');
                yield IdField::new('id');
                yield EmailField::new('email');
                yield TextField::new('fonction', 'Fonction');
                yield BooleanField::new('admin', 'Administrateur du site');
                // yield TextField::new('password', 'Mot de passe (codé)')->setPermission('ROLE_SUPER_ADMIN');
                yield BooleanField::new('mainentreprise', 'Membre de l\'association');
                yield TextField::new('higherRole')
                    ->setCssClass('text-warning')
                    ->setHelp('<h5>Tous les roles<br>pour cet utilisateur&nbsp;:</h5><div><ul><li>'.implode('</li><li>', $this->translate($user->getReachableRoles())).'</li></ul></div>')
                    ->formatValue(fn ($value) => $this->translate($value));
                yield ArrayField::new('roles')->formatValue(fn ($value) => $this->translate($value));
                yield ArrayField::new('inferiorRoles')->formatValue(fn ($value) => $this->translate($value))->setPermission('ROLE_SUPER_ADMIN');
                yield ArrayField::new('reachableRoles')->setPermission('ROLE_SUPER_ADMIN');
                yield BooleanField::new('enabled', 'Activé');
                yield BooleanField::new('isVerified', 'Vérifié');
                yield BooleanField::new('softdeleted', 'Supprimé')->setPermission('ROLE_SUPER_ADMIN');
                // ------------------------------------------------- Autres informations
                yield FormField::addColumn('col-md-12 col-lg-6');
                yield FormField::addPanel(label: 'Autres informations', icon: 'fa6-solid:user')->setHelp('Informations supplémentaires');
                yield TextField::new('firstname', 'Nom');
                yield TextField::new('lastname', 'Prénom');
                yield ArrayField::new('entreprises', 'Entreprises');
                yield ThumbnailField::new('portrait', 'Photo')
                    ->setBasePath($this->getParameter('vich_dirs.user_portrait'));
                yield CollectionField::new('categorys', 'Catégories');
                yield TextField::new('timezone');
                yield DateTimeField::new('createdAt')->setFormat('dd/MM/Y - HH:mm:ss')->setTimezone($current_tz);
                if($timezone !== $user->getTimezone()) yield DateTimeField::new('createdAt')->setFormat('dd/MM/Y - HH:mm:ss')->setCssClass('text-bg-primary')->setTimezone($user->getTimezone());
                yield DateTimeField::new('updatedAt')->setFormat('dd/MM/Y - HH:mm:ss')->setTimezone($current_tz);
                if($timezone !== $user->getTimezone() && $user->getUpdatedAt()) yield DateTimeField::new('updatedAt')->setFormat('dd/MM/Y - HH:mm:ss')->setCssClass('text-bg-primary')->setTimezone($user->getTimezone());
                yield DateTimeField::new('lastLogin', 'Dern.log')->setFormat('dd/MM/Y - HH:mm:ss')->setTimezone($current_tz);
                if($timezone !== $user->getTimezone() && $user->getLastLogin()) yield DateTimeField::new('lastLogin', 'Dern.log')->setFormat('dd/MM/Y - HH:mm:ss')->setCssClass('text-bg-primary')->setTimezone($user->getTimezone());
                if($user->getExpiresAt()) {
                    yield DateTimeField::new('expiresAt', 'Expiration')->setFormat('dd/MM/Y - HH:mm:ss')->setTimezone($current_tz);
                    if($timezone !== $user->getTimezone()) yield DateTimeField::new('expiresAt', 'Expiration')->setFormat('dd/MM/Y - HH:mm:ss')->setCssClass('text-bg-primary')->setTimezone($user->getTimezone());
                }
                yield BooleanField::new('darkmode')->setHelp('Interface graphique en mode sombre');
                break;
            case Crud::PAGE_NEW:
                // ------------------------------------------------- Sécurité
                yield FormField::AddTab(label: 'Sécurité', icon: 'fa6-solid:lock');
                yield EmailField::new('email')->setColumns($this->isGranted('ROLE_ADMIN') ? 4 : 6)->setHelp('Le mail doit être unique : l\'enregistrement sera rejeté si une autre personne utilise le mail sur le même site.');
                yield TextField::new('plainPassword', 'Mot de passe')->setRequired(true)->setColumns($this->isGranted('ROLE_ADMIN') ? 4 : 6)->setHelp('Utilisez des lettres, des signes et des chiffres, et au moins 12 caractères.');
                yield ChoiceField::new('roles')->setChoices(function(?LaboUserInterface $user): array {
                    /** @var LaboUserInterface $user */
                    return $user->getRolesChoices($this->getUser());
                })->setColumns(4)->allowMultipleChoices(true)->setHelp('Les roles déterminent les niveaux d\'accès à l\'administration du site.')->setPermission('ROLE_ADMIN');
                yield DateTimeField::new('expiresAt', 'Expiration')->setColumns(3)->setPermission('ROLE_ADMIN')->setTimezone($current_tz);
                yield BooleanField::new('enabled', 'Activé')->setColumns(2)->setPermission('ROLE_ADMIN');
                yield BooleanField::new('isVerified', 'Vérifié')->setColumns(2)->setHelp('Compte vérifié')->setPermission('ROLE_ADMIN');
                yield BooleanField::new('darkmode')->setColumns(2)->setHelp('Interface graphique en mode sombre');
                yield BooleanField::new('softdeleted', 'Supprimé')->setFormTypeOption('attr', ['class' => 'border-danger text-bg-danger'])->setColumns(2)->setPermission('ROLE_SUPER_ADMIN');
                // ------------------------------------------------- Autres informations
                yield FormField::AddTab(label: 'Autres informations', icon: 'fa6-solid:user')->setHelp('Informations supplémentaires');
                yield TextField::new('firstname', 'Nom')->setColumns(6);
                yield TextField::new('lastname', 'Prénom')->setColumns(6);
                yield TextField::new('fonction', 'Fonction')->setColumns(6);
                yield AssociationField::new('categorys', 'Catégories')->setQueryBuilder(static fn (QueryBuilder $qb): QueryBuilder => LaboCategoryRepository::QB_CategoryChoices($qb, User::class))
                    // ->autocomplete()
                    ->setSortProperty('name')
                    ->setFormTypeOptions(['by_reference' => false])
                    ->setColumns(6);
                yield TextField::new('portrait', 'Photo')
                    ->setFormType(PortraitType::class)
                    ->setColumns(6);
                yield TimezoneField::new('timezone')->setColumns(4);
                yield FormField::AddTab(label: 'Entreprises', icon: 'fa6-solid:industry')->setHelp('Entreprises intégrées')->setPermission('ROLE_ADMIN');
                yield AssociationField::new('entreprises', 'Entreprises')
                    // ->autocomplete()
                    ->setSortProperty('firstname')
                    ->setFormTypeOptions(['by_reference' => false])
                    ->setPermission('ROLE_ADMIN');
                break;
            case Crud::PAGE_EDIT:
                if(!$manager->isLoggable($user)) {
                    $this->addFlash('info', t('Cet utilisateur ne peut actuellement pas se connecter à son compte (compte expiré, désactivé ou autre raison).'));
                }
                // ------------------------------------------------- Actions
                yield FormField::AddTab(label: 'Actions', icon: 'fa fa-cog')->setHelp('Actions concernant cet utilisateur')->setPermission('ROLE_ADMIN');
                yield BooleanField::new('mainentreprise', 'Membre de l\'association')->setColumns(6)->setHelp('En plaçant cet utilisateur "membre de l\'association", il sera :<ul><li>visible dans l\'équipe sur le site</li><li>sera ajouté aux ADMIN du site</li><li>se verra attribué la catégorie correspondante</li></ul>À l\'inverse :<ul><li>ne sera plus visible dans l\'équipe sur le site</li><li>sera retiré des ADMIN du site</li><li>se verra retirer la catégorie correspondante</li></ul>')->setColumns(12)->setPermission('ROLE_ADMIN');
                // ------------------------------------------------- Sécurité
                yield FormField::AddTab(label: 'Sécurité', icon: 'fa6-solid:lock');
                yield EmailField::new('email')->setColumns($this->isGranted('ROLE_ADMIN') ? 4 : 6)->setHelp('Le mail doit être unique : l\'enregistrement sera rejeté si une autre personne utilise le mail sur le même site.');
                yield TextField::new('plainPassword', 'Mot de passe', 'Nouveau mot de passe')->setColumns($this->isGranted('ROLE_ADMIN') ? 4 : 6)->setHelp('<strong class="text-danger">ATTENTION</strong> : ne remplissez ce champ QUE SI vous souhaitez changer votre mot de passe. <strong>Dans ce cas, pensez à bien le noter !</strong>');
                yield ChoiceField::new('roles')->setChoices(function(?User $user): array { return $user->getRolesChoices($this->getUser()); })->setColumns(4)->allowMultipleChoices(true)->setHelp('Les roles déterminent les niveaux d\'accès à l\'administration du site.')->setPermission('ROLE_ADMIN')->renderAsBadges();
                yield DateTimeField::new('expiresAt', 'Expiration')->setColumns(3)->setPermission('ROLE_ADMIN')->setTimezone($current_tz);
                yield DateTimeField::new('lastLogin', 'Dern.log')->setColumns(3)->setPermission('ROLE_SUPER_ADMIN')->setTimezone($current_tz);
                yield BooleanField::new('enabled', 'Activé')->setColumns(2)->setPermission('ROLE_ADMIN');
                yield BooleanField::new('isVerified', 'Vérifié')->setColumns(2)->setHelp('Compte vérifié')->setPermission('ROLE_ADMIN');
                yield BooleanField::new('softdeleted', 'Supprimé')->setFormTypeOption('attr', ['class' => 'border-danger text-bg-danger'])->setColumns(2)->setPermission('ROLE_SUPER_ADMIN');
                // ------------------------------------------------- Autres informations
                yield FormField::AddTab(label: 'Autres informations', icon: 'fa6-solid:user')->setHelp('Informations supplémentaires');
                yield TextField::new('firstname', 'Nom')->setColumns(6);
                yield TextField::new('lastname', 'Prénom')->setColumns(6);
                yield TextField::new('fonction', 'Fonction')->setColumns(6);
                yield AssociationField::new('categorys', 'Catégories')->setQueryBuilder(static fn (QueryBuilder $qb): QueryBuilder => LaboCategoryRepository::QB_CategoryChoices($qb, User::class))
                    // ->autocomplete()
                    ->setSortProperty('name')
                    ->setFormTypeOptions(['by_reference' => false])
                    ->setColumns(6);
                yield TextField::new('portrait', 'Photo')
                    ->setFormType(PortraitType::class)
                    // ->setFormTypeOption('allow_delete', false)
                    ->setColumns(6);
                yield TimezoneField::new('timezone')->setColumns(4);
                yield BooleanField::new('darkmode')->setColumns(3)->setHelp('Interface graphique en mode sombre');
                // ------------------------------------------------- Entreprises
                yield FormField::AddTab(label: 'Entreprises', icon: 'fa6-solid:industry')->setHelp('Entreprises intégrées')->setPermission('ROLE_ADMIN');
                yield AssociationField::new('entreprises', 'Entreprises')
                    // ->autocomplete()
                    ->setSortProperty('firstname')
                    ->setFormTypeOptions(['by_reference' => false])
                    ->setPermission('ROLE_ADMIN');
                break;
            default:
                yield IdField::new('id')->setPermission('ROLE_SUPER_ADMIN');
                yield EmailField::new('email');
                yield TextField::new('firstname', 'Nom')->setTextAlign('center');
                yield ThumbnailField::new('portrait', 'Photo')
                    ->setBasePath($this->getParameter('vich_dirs.user_portrait'))
                    ->setTextAlign('center')
                    ->setSortable(false);
                yield TextField::new('higherRole', 'Statut')->setTextAlign('center')->setCssClass('text-muted italic')->formatValue(fn ($value) => $this->translate($value));
                // yield DateTimeField::new('createdAt')->setFormat('dd/MM/Y - HH:mm')->setTimezone($current_tz);
                // yield BooleanField::new('darkmode')->setTextAlign('center');
                yield AssociationField::new('entreprises', 'Entreprises')->setTextAlign('center');
                // yield BooleanField::new('mainentreprise', 'Membre asso')->setTextAlign('center')->setPermission('ROLE_ADMIN');
                yield BooleanField::new('enabled', 'Activé')->setTextAlign('center')->setPermission('ROLE_ADMIN');
                yield BooleanField::new('isVerified', 'Vérifié')->setTextAlign('center');
                yield BooleanField::new('softdeleted', 'Supprimé')->setTextAlign('center')->setPermission('ROLE_SUPER_ADMIN');
                // yield TimezoneField::new('timezone')->setTextAlign('center');
                yield DateTimeField::new('lastLogin', 'Dern.log')->setFormat('dd/MM/YY HH:mm')->setTimezone($current_tz);
                break;
        }
    }
}