<?php
namespace Aequation\LaboBundle\Doctrine\Type;

use Aequation\LaboBundle\Component\ArrayTextUtil;
// Symfony
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * ArrayTextType Type
 * - copied from Doctrine\DBAL\Types\JsonType
 * 
 * @see https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html#custom-mapping-types
 * @see https://symfony.com/doc/current/doctrine/dbal.html#registering-custom-mapping-types
 * 
 */
class ArrayTextType extends JsonType
{
    public const NAME = 'arraytext';

    // public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    // {
    //     // return $platform->getClobTypeDeclarationSQL($column);
    //     return $platform->getJsonTypeDeclarationSQL($column);
    // }

    /**
     * Converts a value from its PHP representation to its database representation
     * of this type.
     *
     * @param mixed            $value    The value to convert.
     * @param AbstractPlatform $platform The currently used database platform.
     *
     * @return mixed The database representation of the value.
     *
     * @throws ConversionException
     */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value instanceof ArrayTextUtil ? $value->jsonSerialize() : null;
    }

    /**
     * Converts a value from its database representation to its PHP representation
     * of this type.
     *
     * @param mixed            $value    The value to convert.
     * @param AbstractPlatform $platform The currently used database platform.
     *
     * @return mixed The PHP representation of the value.
     *
     * @throws ConversionException
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        // if(is_resource($value)) $value = stream_get_contents($value);
        // if(empty($value)) return new ArrayTextUtil();
        return new ArrayTextUtil(is_resource($value) ? stream_get_contents($value) : $value);
    }

    /**
     * Get the name of this type
     *
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

}