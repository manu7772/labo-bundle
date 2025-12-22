<?php
namespace Aequation\LaboBundle\Service\Tools;

use Aequation\LaboBundle\Service\Base\BaseService;

use Attribute;
use ReflectionAttribute;

class LaboReflectionAttribute extends BaseService
{
    public readonly bool $target_class;
    public readonly bool $target_function;
    public readonly bool $target_method;
    public readonly bool $target_property;
    public readonly bool $target_class_constant;
    public readonly bool $target_parameter;
    public readonly bool $target_all;
    public readonly bool $is_repeatable;
    private readonly ?object $instance;
    public readonly int|false $target;
    public readonly bool $isAttribute;
    protected array $errors = [];

    public function __construct(
        public readonly ReflectionAttribute $reflectionAttribute
    )
    {
        try {
            $this->instance = $this->reflectionAttribute->newInstance();
        } catch (\Throwable $th) {
            $this->addError($th->getMessage());
            $this->instance = null;
        }
        $this->isAttribute = true;
        $this->target = $this->reflectionAttribute->getTarget();
        $this->target_class = ($this->target & Attribute::TARGET_CLASS) === Attribute::TARGET_CLASS;
        $this->target_function = ($this->target & Attribute::TARGET_FUNCTION) === Attribute::TARGET_FUNCTION;
        $this->target_method = ($this->target & Attribute::TARGET_METHOD) === Attribute::TARGET_METHOD;
        $this->target_property = ($this->target & Attribute::TARGET_PROPERTY) === Attribute::TARGET_PROPERTY;
        $this->target_class_constant = ($this->target & Attribute::TARGET_CLASS_CONSTANT) === Attribute::TARGET_CLASS_CONSTANT;
        $this->target_parameter = ($this->target & Attribute::TARGET_PARAMETER) === Attribute::TARGET_PARAMETER;
        $this->target_all = ($this->target & Attribute::TARGET_ALL) === Attribute::TARGET_ALL;
        $this->is_repeatable = ($this->target & Attribute::IS_REPEATABLE) === Attribute::IS_REPEATABLE;
    }
    // native functions
    public function getArguments(): array { return $this->reflectionAttribute->getArguments(); }
    public function getName(): string { return $this->reflectionAttribute->getName(); }
    public function getTarget(): int { return $this->reflectionAttribute->getTarget(); }
    public function isRepeated(): bool { return $this->reflectionAttribute->isRepeated(); }
    public function newInstance(): ?object { return $this->instance; }
    // Added functions
    public function isTargetClass(): bool { return $this->target_class; }
    public function isTargetFunction(): bool { return $this->target_function; }
    public function isTargetMethod(): bool { return $this->target_method; }
    public function isTargetProperty(): bool { return $this->target_property; }
    public function isTargetClassConstant(): bool { return $this->target_class_constant; }
    public function isTargetParameter(): bool { return $this->target_parameter; }
    public function isTargetAll(): bool { return $this->target_all; }
    public function isRepeatable(): bool { return $this->is_repeatable; }
    // Valid
    public function isValid(): bool { return empty($this->errors); }
    public function getErrors(): array { return $this->errors; }
    protected function addError(string $error_message): void { $this->errors[] = $error_message; }

    public function __debugInfo(): array
    {
        return [
            'isAttribute' => $this->isAttribute ?? null,
            'target' => $this->target ?? null,
            'target_class' => $this->target_class ?? null,
            'target_function' => $this->target_function ?? null,
            'target_method' => $this->target_method ?? null,
            'target_property' => $this->target_property ?? null,
            'target_class_constant' => $this->target_class_constant ?? null,
            'target_parameter' => $this->target_parameter ?? null,
            'target_all' => $this->target_all ?? null,
            'is_repeatable' => $this->is_repeatable ?? null,
        ];
    }

}

