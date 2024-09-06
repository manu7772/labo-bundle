<?php
namespace Aequation\LaboBundle\Component;

use JsonSerializable;
use Serializable;
use Stringable;

class MessagePack implements JsonSerializable, Serializable, Stringable
{

    public const DEFAUT_TYPE = 'info';

    public function __construct(
        protected string $message,
        protected array $params = [],
        protected array $options = [],
        protected ?string $type = null,
    ) {
        $this->getType();
    }

    public function getData(): array
    {
        $this->options['message'] = vsprintf($this->message, $this->params ?? []);
        $this->options['type'] ??= $this->getType();
        return $this->options;
    }

    public function __toString(): string
    {
        $data = $this->getData();
        return $data['message'];
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function setParams(array $params): static
    {
        $this->params = $params;
        return $this;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function setOptions(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function addOption(string $name, mixed $option): static
    {
        $this->options[$name] = $option;
        return $this;
    }
    
    public function removeOption(string $name): static
    {
        if(isset($this->options[$name])) unset($this->options[$name]);
        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getType(): string
    {
        return $this->type ??= static::DEFAUT_TYPE;
    }

    // /**
    //  * Get data for serialize
    //  * @return array
    //  */
    // public function __serialize(): array
    // {
    //     return $this->internals;
    // }
    
    // /**
    //  * Set properties from data
    //  * @param array $data
    //  * @return void
    //  */
    // public function __unserialize(array $data): void
    // {
    //     foreach ($data as $name => $value) {
    //         $this->internals[$name] = $value;
    //     }
    // }

    // /**
    //  * Get serialize data
    //  * @return array
    //  */
    // public function serialize(): ?string
    // {
    //     return json_encode($this->__serialize());
    // }
    
    // /**
    //  * Set data from (string) data
    //  * @param string $data
    //  * @return void
    //  */
    // public function unserialize(string $data): void
    // {
    //     $data = json_decode($data, true);
    //     $this->__unserialize($data);
    // }

    public function jsonSerialize(): mixed
    {
        return $this->getData();
    }

    public function serialize(): ?string
    {
        return serialize($this->getData());
    }

    public function unserialize(string $data)
    {
        foreach (unserialize($data) as $name => $value) {
            $this->options = [];
            switch ($name) {
                case 'message':
                    $this->setMessage($value);
                    break;
                case 'params':
                    $this->setParams($value);
                    break;
                default:
                    $this->addOption($name, $value);
                    break;
            }
        }
    }

}