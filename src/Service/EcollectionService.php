<?php
namespace Aequation\LaboBundle\Service;

use Aequation\LaboBundle\Entity\Ecollection;
use Aequation\LaboBundle\Entity\Item;
use Aequation\LaboBundle\Model\Interface\EcollectionInterface;
use Aequation\LaboBundle\Model\Interface\ItemInterface;
use Aequation\LaboBundle\Service\Interface\EcollectionServiceInterface;
use Aequation\LaboBundle\Service\ItemService;
use Aequation\LaboBundle\Service\Tools\Iterables;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

#[AsAlias(EcollectionServiceInterface::class, public: true)]
class EcollectionService extends ItemService implements EcollectionServiceInterface
{
    public const ENTITY = Ecollection::class;

    public function setEcollectionItems(
        EcollectionInterface $entity,
        array $items,
        ?string $field = null
    ): EcollectionInterface
    {
        $field ??= Ecollection::RELATION_FIELDNAME;
        $setter = 'set'.ucfirst($field);
        // /** @var PropertyAccessorInterface */
        // $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->enableMagicCall()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
        /** @var EntityRepository */
        $repo = $this->getRepository(Item::class);
        $items = array_map(fn($euid) => $euid instanceof ItemInterface ? $euid : $repo->findOneByEuid($euid), $items);
        $items = array_filter($items, fn($item) => $item instanceof ItemInterface);
        // $propertyAccessor->setValue($entity, $field, $items);
        $entity->$setter($items);
        // dump($entity->getItems(), $items);
        // SAVE
        $this->em->flush();
        /** @var EntityRepository */
        $repo = $this->getRepository($entity->getClassname());
        $this->em->detach($entity);
        $id = $entity->getId();
        unset($entity);
        /** @var EcollectionInterface */
        $entity = $repo->find($id);
        return $entity;
    }

}