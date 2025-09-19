<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Component\AppEntityInfo;
use Aequation\LaboBundle\Model\Attribute\Slugable;
use Aequation\LaboBundle\Model\Interface\AppEntityInterface;
use Aequation\LaboBundle\Service\Base\BaseService;
use Aequation\LaboBundle\Model\Interface\SlugInterface;
use Aequation\LaboBundle\Service\Tools\Classes;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

use Exception;

class SlugService extends BaseService
{

    protected ?array $controls = [];

    public function __construct(
        protected SluggerInterface $slugger,
        protected AppEntityManager $appEntityManager,
        // protected EntityManagerInterface $em
    ) {}

    public function resetControls(): void
    {
        $this->controls = [];
    }

    /**
     * Search if slug exists in same class of entity
     * @param string $slug
     * @param SlugInterface|string $entity // --> if $entity is object, exclude this $entity from search
     * @return boolean
     */
    public function slugExists(
        SlugInterface|string $entity,
        string $slug = null,
    ): bool
    {
        $classname = is_object($entity) ? $entity->getClassname() : $entity;
        if(!is_object($entity)) $entity = null;
        if(empty($entity) && empty($slug)) throw new Exception(vsprintf("Error %s line %d: %s or %s must be defined.", [__METHOD__, __LINE__, '$entity', '$slug']));
        if(empty($slug ??= $entity->getSlug())) throw new Exception(vsprintf("Error %s line %d: %s must be defined.", [__METHOD__, __LINE__, '$slug']));
        $filter = function(AppEntityInterface $test) use ($entity, $classname, $slug) {
            return (empty($entity) || $test !== $entity)
                && is_a($test, $classname)
                && $test instanceof SlugInterface
                && $test->getSlug() === $slug;
        };
        // New hydratateds
        $ydrateds = $this->appEntityManager->getNewHydrateds($filter);
        if(!empty($ydrateds)) return true;
        // Inserts
        $inserts = $this->appEntityManager->getScheduledForInsert($filter);
        if(!empty($inserts)) return true;
        // Updates
        $updates = $this->appEntityManager->getScheduledForUpdate($filter);
        if(!empty($updates)) return true;
        // In database
        /** @var ServiceEntityRepository */
        $repo = $this->appEntityManager->getRepository($classname, 'slug');
        foreach ($repo->findBy(['slug' => $slug]) as $test) {
            if(empty($entity) || $test !== $entity) return true;
        }
        return false;
    }

    public function getSlugFromString(
        string $string
    ): string
    {
        return (string) $this->slugger->slug($string)->lower();
    }

    public function getSlugableAttribute(
        string|SlugInterface $objectOrClass
    ): Slugable
    {
        $attributes = Classes::getClassAttributes($objectOrClass, Slugable::class, true);
        if(empty($attributes)) throw new Exception(vsprintf("Error %s line %d: SlugInterface %s does not contains a class attribute %s.", [__METHOD__, __LINE__, is_string($objectOrClass) ? $objectOrClass : $objectOrClass::class, Slugable::class]));
        $slugable = reset($attributes);
        return $slugable;
    }

    /**
     * Generate new SLUG for entity if needed.
     * Returns true if slug has changed
     * @param SlugInterface $entity
     * @return boolean
     */
    public function computeUniqueSlug(
        SlugInterface $entity
    ): bool
    {
        $orign = $entity->getSlug();
        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
        $classname = $entity->getClassName();
        if($entity->isUpdateSlug()) {
            $slugAttr = $this->getSlugableAttribute($classname);
            $base = $propertyAccessor->getValue($entity, $slugAttr->property->name);
            if(empty($base)) {
                dump($entity->_isModel(), $entity);
                throw new Exception(vsprintf("Error %s line %d: could not update slug for %s (named %s) with an empty value %s.", [__METHOD__, __LINE__, $entity::class, $entity, json_encode($base)]));
            }
            $addon = 1;
            $entity->setSlug($this->getSlugFromString($base));
            while ($this->slugExists($entity)) {
                $entity->setSlug($this->getSlugFromString($base.'-'.$addon++));
            }
        }
        return $orign !== $entity->getSlug();
    }

    // /**
    //  * Generate new SLUG for entity if needed.
    //  * Returns true if slug has changed
    //  * @param SlugInterface $entity
    //  * @return boolean
    //  */
    // public function computeUniqueSlug(
    //     SlugInterface $entity
    // ): bool
    // {
    //     $orign = $entity->getSlug();
    //     $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
    //     /** @var ServiceEntityRepository */
    //     $repo = $this->appEntityManager->getRepository($entity->getClassname(), 'slug');
    //     // if(!($repo instanceof ServiceEntityRepository)) throw new Exception(vsprintf("Could not retrieve any repository for %s with property %s.", [$entity::class, json_encode('slug')]));
    //     $classname = $repo->getClassName();
    //     // $traits = class_uses($repo, true);
    //     // if(!in_array(SlugRepo::class, $traits)) throw new Exception(vsprintf("This repository %s for %s (from %s) with property %s need trait %s.", [$repo::class, $classname, $entity::class, json_encode('slug'), SlugRepo::class]));
    //     $this->controls[$classname] ??= [];
    //     $control_id = $entity->_appManaged->isNew() ? spl_object_hash($entity) : $entity->getId();
    //     $this->controls[$classname] = array_replace($this->controls[$classname], $repo->findAllSlugs());
    //     if($entity->isUpdateSlug()) {
    //         $slugAttr = $this->getSlugableAttribute($classname);
    //         // dd($slugAttr);
    //         $base = $propertyAccessor->getValue($entity, $slugAttr->property);
    //         if(empty($base)) throw new Exception(vsprintf("Error %s line %d: could not update slug for %s with an empty value %s.", [__METHOD__, __LINE__, $entity::class, json_encode($base)]));
    //         $new_slug = $this->getSlugFromString($base);
    //         $list = array_filter($this->controls[$classname], function($key) use ($control_id) { return $key !== $control_id; }, ARRAY_FILTER_USE_KEY);
    //         $addon = 1;
    //         while (in_array($new_slug, $list)) {
    //             $new_slug = $this->getSlugFromString($base.'-'.$addon++);
    //         }
    //         $entity->setSlug($new_slug);
    //         // if($entity->getSlug() === 'image') throw new Exception(vsprintf("Could not set slug for %s with value %s.", [$entity::class, json_encode($entity->getSlug())]));
    //         if($orign !== $entity->getSlug() && in_array($entity->getSlug(), $list)) throw new Exception(vsprintf("Error %s line %d: could not set slug for %s (%s) with value %s (already in folowing values: %s).", [__METHOD__, __LINE__, $entity::class, $control_id, json_encode($entity->getSlug()), json_encode($list)]));
    //     }
    //     $this->controls[$classname][$control_id] = $entity->getSlug();
    //     return $orign !== $entity->getSlug();
    // }


}