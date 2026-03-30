<?php
namespace Aequation\LaboBundle\Component;

use Aequation\LaboBundle\Component\Interface\ImageOperationsInterface;
// Symfony
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\File\File;
// PHP
use GdImage;
use InvalidArgumentException;

class ImageOperations implements ImageOperationsInterface
{
    public const MAKE_EXCEPTION_ON_FAILURE = false; // Set to true to throw exceptions on operation failure, false to return false

    public const IMAGE_OPERATIONS = [
        'rotate' => [
            'icon' => 'tabler:rotate',
            'method' => 'rotate',
            'type' => ChoiceType::class,
            // 'default' => 0,
            'options' => [
                'label' => 'Rotation',
                'required' => true,
                'choices' => ['0°' => 0, '90°' => 90, '180°' => 180, '270°' => 270],
            ],
        ],
        'flip' => [
            'icon' => 'tabler:flip-vertical',
            'method' => 'flip',
            'type' => ChoiceType::class,
            'options' => [
                'label' => 'Retournement',
                'required' => true,
                'choices' => ['aucun' => 'NULL', 'horizontal' => 'H', 'vertical' => 'V'],
            ],
        ],
    ];

    protected ?File $image = null;
    protected GdImage|false $imageResource = false;

    protected array $operationsApplied = [];

    public function __construct(
        protected array $operations = []
    ) {
        $this->setOperations($operations);
    }

    public static function getAllOperations(): array
    {
        return static::IMAGE_OPERATIONS;
    }
    public static function getAllOperationNames(): array
    {
        return array_keys(static::IMAGE_OPERATIONS);
    }

    public static function operationExists(string $name): bool
    {
        return array_key_exists($name, static::IMAGE_OPERATIONS);
    }

    public static function getOperationDefaultvalue(string $name): mixed
    {
        switch (static::IMAGE_OPERATIONS[$name]['type']) {
            case ChoiceType::class:
                if(isset(static::IMAGE_OPERATIONS[$name]['default'])) {
                    return static::IMAGE_OPERATIONS[$name]['default'];
                }
                $choices = static::IMAGE_OPERATIONS[$name]['options']['choices'];
                return static::IMAGE_OPERATIONS[$name]['options']['required'] ? reset($choices) : null;
                break;
            default:
                throw new InvalidArgumentException(sprintf("Unsupported form field type \"%s\" for operation \"%s\".", static::IMAGE_OPERATIONS[$name]['type'], $name));
                break;
        }
    }

    public function jsonSerialize(): mixed
    {
        return $this->operations;
    }

    protected function initOperationsApplied(): void
    {
        foreach (static::getAllOperationNames() as $name) {
            $this->operationsApplied[$name] = false;
        }
    }

    public function setImage(string|File $image): static
    {
        $old_image = $this->image;
        $this->image = is_string($image) ? new File($image) : $image;
        if($this->image !== $old_image) {
            $this->initOperationsApplied();
        }
        return $this;
    }

    public function getImage(): ?File
    {
        return $this->image;
    }

    public function resetOperations(array $operations = []): static
    {
        $this->operations = $operations;
        foreach (static::getAllOperationNames() as $name) {
            if(!array_key_exists($name, $this->operations)) {
                $this->operations[$name] = static::getOperationDefaultvalue($name);
            }
        }
        $this->initOperationsApplied();
        return $this;
    }

    public function hasOperation(string $name): bool
    {
        return array_key_exists($name, $this->operations);
    }

    public function getOperations(): array
    {
        return $this->operations;
    }

    public function setOperations(array $operations): static
    {
        $this->resetOperations($operations);
        return $this;
    }

    public function apply(null|string|array $names = null): bool
    {
        $names = (array) $names;
        $result = true;
        // Validate operation names
        if(static::MAKE_EXCEPTION_ON_FAILURE) {
            foreach ($names as $name) {
                if(!static::operationExists($name)) {
                    // Operation not found
                    throw new InvalidArgumentException(sprintf("Operation \"%s\" does not exist.", $name));
                }
            }
        }
        foreach ($names as $name) {
            if($this->operationsApplied[$name] ?? false) {
                // Apply the operation
                $method = static::IMAGE_OPERATIONS[$name]['method'] ?? null;
                $option = $this->operations[$name]['option'];
                if($this->$method($option)) {
                    $this->operationsApplied[$name] = true;
                } else {
                    // Operation failed
                    if(static::MAKE_EXCEPTION_ON_FAILURE) {
                        throw new InvalidArgumentException(sprintf("Failed to apply operation \"%s\" with option \"%s\".", $name, $option));
                    } else {
                        $result = false;
                    }
                }
            }
        }
        return $result;
    }

    protected function getImageResource(): GdImage|false
    {
        if(!$this->image) {
            if(static::MAKE_EXCEPTION_ON_FAILURE) {
                throw new InvalidArgumentException("No image set to apply the operation.");
            }
            return false;
        }
        return $this->imageResource = imagecreatefromstring(file_get_contents($this->image->getPath()));
    }

    /**************************************************************/
    // Operation methods
    /**************************************************************/

    protected function rotate(int $option): bool
    {
        if($this->getImageResource()) {
            if($imageResource = imagerotate($this->imageResource, $option, 0)) {
                $this->imageResource = $imageResource;
                return true;
            } else {
                return false;
                // throw new InvalidArgumentException(sprintf("Failed to rotate the image by %d degrees.", $option));
            }
        } else {
            return false;
            // throw new InvalidArgumentException("Failed to create image resource from the provided image.");
        }
        return false;
    }

    protected function flip(string $option): bool
    {
        if($this->getImageResource()) {
            switch ($option) {
                case 'horizontal':
                    return imageflip($this->imageResource, IMG_FLIP_HORIZONTAL);
                    break;
                case 'vertical':
                    return imageflip($this->imageResource, IMG_FLIP_VERTICAL);
                    break;
                default:
                    return false;
                    // throw new InvalidArgumentException(sprintf("Invalid flip mode: %s", $option));
                    break;
            }
        } else {
            return false;
            // throw new InvalidArgumentException("Failed to create image resource from the provided image.");
        }
    }

    public function __isset(string $name)
    {
        return array_key_exists($name, $this->operations);
    }

    public function __get(string $name)
    {
        if($this->hasOperation($name)) {
            return $this->operations[$name] ?? static::getOperationDefaultvalue($name);
        }
        return null;
    }

    public function __set(string $name, mixed $value): void
    {
        if(static::operationExists($name)) {
            $this->operations[$name] = $value;
        }
    }

}