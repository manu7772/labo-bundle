<?php
namespace Aequation\LaboBundle\Entity;

use Aequation\LaboBundle\Service\Tools\Strings;
use Aequation\LaboBundle\Component\TypedCollection;
use Aequation\LaboBundle\Component\Interface\TextContentsInterface;
use Aequation\LaboBundle\Component\Interface\TypedCollectionInterface;
// Symfony
use Doctrine\ORM\Mapping as ORM;
use Twig\Markup;
// PHP
use Stringable;

#[ORM\Embeddable()]
class TextContents extends TypedCollection implements TextContentsInterface
{

    #[ORM\Column(type: 'json')]
    protected array $elements = [];

    public function __construct(...$texts)
    {
        $this->setTexts($texts);
    }

    public function __toString(): string
    {
        return implode("\n", $this->elements);
    }

    protected function createFrom(array $elements): TypedCollectionInterface
    {
        return new static(...$elements);
    }

    public function getElements(): array
    {
        return $this->toArray();
    }

    public function getRaw(): static
    {
        return $this->map(fn($text) => Strings::markup($text));
    }

    public function getRaws(string $decorator_element = 'div'): Markup
    {
        $markups = $this->map(fn($text) => Strings::markup($text))->toArray();
        return in_array($decorator_element, ['br', 'hr'])
            ? Strings::markup(implode(sprintf('<%s>', $decorator_element), $markups))
            : Strings::markup(sprintf('<%s>', $decorator_element).implode(sprintf('</%s><%s>', $decorator_element, $decorator_element), $markups).sprintf("</%s>", $decorator_element));
    }

    public function setTexts(array $texts): void
    {
        $this->elements = [];
        foreach ($texts as $text) {
            $this->addText($text);
        }
    }

    public function addText(null|string|Stringable $text): static
    {
        if ($this->toValidText($text)) {
            $this->elements[] = $text;
        }
        return $this;
    }

    public function getText(int $index): ?string
    {
        return $this->elements[$index] ?? null;
    }

    public function set(string|int $key, mixed $value): void
    {
        if($this->toValidText($value)) {
            $this->elements[$key] = $value;
        }
    }

    public function add(mixed $element): void
    {
        if($this->toValidText($element)) {
            $this->addText($element);
        }
    }

    protected function toValidText(mixed &$element): bool
    {
        if(is_string($element) || $element instanceof Stringable) {
            $text = (string) $element;
            if(!empty($text)) {
                $element = $text;
                return true;
            }
        }
        return false;
    }

}