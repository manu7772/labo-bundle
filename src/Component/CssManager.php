<?php
namespace Aequation\LaboBundle\Component;

use Aequation\LaboBundle\Service\Tools\Iterables;

use Symfony\Component\Validator\Constraints as Assert;
use Exception;

class CssManager
{

    private array $cssClasses = [];

    public static function separateClasses(
        string|array $cssClasses
    ): array
    {
        if(is_array($cssClasses)) $cssClasses = static::unifyClasses($cssClasses);
        $classes = [];
        foreach (Iterables::toClassList($cssClasses) as $class) {
            $classes[$class] = $class;
        }
        return array_values($classes);
    }

    public static function unifyClasses(
        string|array $cssClasses
    ): string
    {
        if(is_string($cssClasses)) $cssClasses = static::separateClasses($cssClasses);
        $cssClasses = empty($cssClasses) ? '' : implode(' ', $cssClasses);
        return preg_replace('/\s+/', ' ', $cssClasses);
    }

    public function getCssClasses(): array
    {
        return array_values($this->cssClasses);
    }

    #[Assert\NotBlank]
    public function getStringCssClasses(): string
    {
        return static::unifyClasses($this->cssClasses);
    }

    public function setStringCssClasses(
        string $cssClasses
    ): static
    {
        foreach (static::separateClasses($cssClasses) as $class) {
            $this->addCssClass($class);
        }
        return $this;
    }

    public function addCssClass(
        string $cssClass
    ): static
    {
        $cssClass = trim($cssClass);
        if(empty($cssClass) || !preg_match('/^[\w_-]+$/', $cssClass)) {
            throw new Exception(vsprintf('Error %s line %d: class "%s" is invalid!', [__METHOD__, __LINE__, $cssClass]));
            // return $this->setStringCssClasses($cssClass);
        }
        $this->cssClasses[$cssClass] = $cssClass;
        return $this;
    }

    public function removeCssClass(
        string|array $cssClass
    ): static
    {
        $cssClass = static::separateClasses($cssClass);
        $this->cssClasses = array_filter($this->cssClasses, function($class) use ($cssClass) {
            return !in_array($class, $cssClass);
        });
        return $this;
    }

}