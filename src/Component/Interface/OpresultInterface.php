<?php
namespace Aequation\LaboBundle\Component\Interface;

use Twig\Markup;

interface OpresultInterface
{

    public const ACTION_SUCCESS =     'success';      // success -- success
    public const ACTION_UNDONE =      'undone';       // undone -- operation was unecessary
    public const ACTION_WARNING =     'warning';      // warning -- operation failed but not critical
    public const ACTION_DANGER =      'danger';       // error -- operation failed
    // messages
    public const MESSAGE_INFO =     'info';         // info message
    public const MESSAGE_DEV =      'dev';          // information for DEVELOPPERS

    public function isContainerValid(): bool;
    public function getContainer(): array;
    public function getJsonContainer(): string;

}