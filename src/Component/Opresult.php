<?php
namespace Aequation\LaboBundle\Component;

use Aequation\LaboBundle\Component\Interface\OpresultInterface;
use Aequation\LaboBundle\Service\Tools\HttpRequest;
use Aequation\LaboBundle\Service\Tools\Iterables;
use Aequation\LaboBundle\Service\Tools\Strings;
use Exception;
use ReflectionClass;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Markup;

class Opresult implements OpresultInterface
{

    public readonly string $name;

    protected mixed $data = null;
    protected array $messages;
    protected array $actions_types = [];
    protected array $actions = [];

    public function __construct()
    {
        $this->name = 'Opresult';
        $this->resetAll();
    }

    public function __toString()
    {
        return $this->name;
    }

    public function resetAll(): static
    {
        $this->initActionTypes(true);
        $this->setData(null);
        return $this;
    }


    // STATICS

    public static function getStaticActions(): array
    {
        $rc = new ReflectionClass(static::class);
        return array_filter($rc->getConstants(), fn($action) => preg_match('/^(ACTION_)/', $action), ARRAY_FILTER_USE_KEY);
    }

    public static function getStaticMessages(): array
    {
        $rc = new ReflectionClass(static::class);
        return array_filter($rc->getConstants(), fn($action) => preg_match('/^(MESSAGE_)/', $action), ARRAY_FILTER_USE_KEY);
    }


    // RESULTS

    public function isSuccess(): bool
    {
        return $this->actions[static::ACTION_SUCCESS] > 0
            && $this->actions[static::ACTION_UNDONE] === 0
            && $this->actions[static::ACTION_WARNING] === 0
            && $this->actions[static::ACTION_DANGER] === 0
            ;
    }

    public function hasSuccess(): bool
    {
        return $this->actions[static::ACTION_SUCCESS] > 0;
    }

    public function isUndone(): bool
    {
        return $this->actions[static::ACTION_UNDONE] > 0
            && $this->actions[static::ACTION_SUCCESS] === 0
            && $this->actions[static::ACTION_WARNING] === 0
            && $this->actions[static::ACTION_DANGER] === 0
            ;
    }

    public function hasUndone(): bool
    {
        return $this->actions[static::ACTION_UNDONE] > 0;
    }

    public function isPartialSuccess(): bool
    {
        return ($this->actions[static::ACTION_SUCCESS] > 0 || $this->actions[static::ACTION_UNDONE] > 0)
            && ($this->actions[static::ACTION_WARNING] > 0 || $this->actions[static::ACTION_DANGER] > 0)
            ;
    }

    public function hasFail(): bool
    {
        return $this->actions[static::ACTION_DANGER] > 0;
    }

    public function isFail(): bool
    {
        return $this->actions[static::ACTION_SUCCESS] === 0
            && $this->actions[static::ACTION_DANGER] > 0
            ;
    }


    // CONTAINER

    public function isContainerValid(): bool
    {
        return array_sum($this->actions) > 0;
    }

    public function getContainer(): array
    {
        return [
            'result' => $this->hasSuccess(),
            'isSuccess' => $this->isSuccess(),
            'hasSuccess' => $this->hasSuccess(),
            'isUndone' => $this->isUndone(),
            'hasUndone' => $this->hasUndone(),
            'isPartialSuccess' => $this->isPartialSuccess(),
            'hasFail' => $this->hasFail(),
            'isFail' => $this->isFail(),
            'data' => $this->data,
            'messages' => $this->messages,
            'cont_valid' => $this->isContainerValid(),
        ];
    }

    public function getJsonContainer(): string
    {
        return json_encode($this->getContainer());
    }


    // RESULTS

    public function addResult(
        string $type,
        string|array $messages = null,
        int $inc = 1
    ): static
    {
        if(!array_key_exists($type, $this->actions)) throw new Exception(vsprintf('Error %s line %d: type "%s" for action is not valid (options are %s)!', [__METHOD__, __LINE__, $type, json_encode($this->getActionTypes())]));
        $this->actions[$type] = $this->actions[$type] + $inc;
        if(!empty($messages)) {
            $this->addMessage($type, $messages);
        }
        return $this;
    }

    public function addSuccess(
        string|array $messages = null,
        int $inc = 1
    ): static
    {
        return $this->addResult(static::ACTION_SUCCESS, $messages, $inc);
    }

    public function addUndone(
        string|array $messages = null,
        int $inc = 1
    ): static
    {
        return $this->addResult(static::ACTION_UNDONE, $messages, $inc);
    }

    public function addWarning(
        string|array $messages = null,
        int $inc = 1
    ): static
    {
        return $this->addResult(static::ACTION_WARNING, $messages, $inc);
    }

    public function addDanger(
        string|array $messages = null,
        int $inc = 1
    ): static
    {
        return $this->addResult(static::ACTION_DANGER, $messages, $inc);
    }


    // ACTIONS

    public function initActionTypes(
        bool $resetTypes = true
    ): static
    {
        if($resetTypes) {
            $this->actions_types = static::getStaticActions();
        }
        $this->resetActions();
        $this->resetMessages();
        return $this;
    }

    public function getActionTypes(): array
    {
        return array_keys($this->actions);
    }

    public function addActionType(
        string $type
    ): static
    {
        if(!Iterables::isArrayIndex($type) || !preg_match_all('/^\w{3,24}$/i', $type) || in_array($type, static::getStaticMessages())) throw new Exception(vsprintf('Error %s line %d: type "%s" is not valid!', [__METHOD__, __LINE__, $type]));
        if(!in_array($type, $this->actions_types)) $this->actions_types[] = $type;
        $this->checkActions();
        $this->checkMessagesTypes();
        return $this;
    }

    public function resetActions(): static
    {
        $this->actions = [];
        $this->checkActions();
        return $this;
    }

    public function checkActions(): static
    {
        foreach ($this->actions_types as $type) {
            if(!array_key_exists($type, $this->actions)) $this->actions[$type] = 0;
        }
        return $this;
    }

    public function getActions(
        string|array $types = null,
        bool $getTotal = false
    ): array|int
    {
        if(empty($types)) {
            return $getTotal
                ? array_sum($this->actions)
                : $this->actions;
        }
        $actions = 0;
        foreach ((array)$types as $type) {
            $actions += $this->actions[$type];
        }
        return $actions;
    }

    public function getTotalActions(): int
    {
        return $this->getActions(getTotal: true);
    }


    // MESSAGES

    public function addMessage(
        string $type,
        string|array $messages
    ): static
    {
        if(!array_key_exists($type, $this->messages)) throw new Exception(vsprintf('Error %s line %d: type "%s" for message is not valid!', [__METHOD__, __LINE__, $type]));
        foreach ((array)$messages as $message) {
            if(!empty($message)) $this->messages[$type][] = $message;
        }
        return $this;
    }

    public function resetMessages(): static
    {
        $this->messages = [];
        $this->checkMessagesTypes();
        return $this;
    }

    public function checkMessagesTypes(): static
    {
        foreach ($this->getActionTypes() as $type) {
            if(!array_key_exists($type, $this->messages)) $this->messages[$type] = [];
        }
        foreach (static::getStaticMessages() as $type) {
            if(!array_key_exists($type, $this->messages)) $this->messages[$type] = [];
        }
        return $this;
    }

    public function getMessageTypes(): array
    {
        return array_keys($this->messages);
    }

    public function getMessages(
        string $type = null
    ): array
    {
        return empty($type)
            ? $this->messages
            : $this->messages[$type];
    }

    public function printMessages(
        SymfonyStyle|bool $asHtmlOrIo = false,
        string|array $msgtypes = null
    ): void
    {
        $msgtypes = empty($msgtypes) ? [] : (array)$msgtypes;
        foreach ($this->messages as $type => $messages) {
            if((empty($msgtypes) || in_array($type, $msgtypes)) && count($messages) > 0) {
                if($asHtmlOrIo instanceof SymfonyStyle) {
                    switch ($type) {
                        case static::ACTION_SUCCESS:
                            $asHtmlOrIo->success($this->getMessagesAsString(false, false, $type));
                            break;
                        case static::MESSAGE_INFO:
                            $asHtmlOrIo->info($this->getMessagesAsString(false, false, $type));
                            break;
                        case static::ACTION_UNDONE:
                            $asHtmlOrIo->info($this->getMessagesAsString(false, false, $type));
                            break;
                        case static::ACTION_WARNING:
                            $asHtmlOrIo->warning($this->getMessagesAsString(false, false, $type));
                            break;
                        case static::ACTION_DANGER:
                            $asHtmlOrIo->error($this->getMessagesAsString(false, false, $type));
                            break;
                        case static::MESSAGE_DEV:
                            $asHtmlOrIo->caution($this->getMessagesAsString(false, false, $type));
                            break;
                        default:
                            $asHtmlOrIo->info($this->getMessagesAsString(false, false, $type));
                            break;
                    }
                } else if($asHtmlOrIo) {
                    // HTML
                    echo($this->getMessagesAsString(true, true, $type));
                } else {
                    // Output
                    print($this->getMessagesAsString(false, true, $type));
                }
            }
        }
    }

    public function getMessagesAsString(
        ?bool $asHtml = null,
        bool $byTypes = true,
        string|array $msgtypes = null
    ): string|Markup
    {
        if(!is_bool($asHtml)) $asHtml = !HttpRequest::isCli();
        $msgtypes = empty($msgtypes) ? [] : (array)$msgtypes;
        $string = '';
        $nl = $asHtml ? '<br>' : PHP_EOL;
        $ul_start = $asHtml ? '<ul>' : '';
        $ul_end = $asHtml ? '</ul>' : '';
        $li_start = $asHtml ? '<li>' : ' - ';
        $li_end = $asHtml ? '</li>' : ''.$nl;
        foreach ($this->messages as $type => $messages) {
            if((empty($msgtypes) || in_array($type, $msgtypes)) && count($messages) > 0) {
                if($byTypes) $string .= $asHtml ? '<div>'.$type.'</div>' : $type.$nl;
                $string .= $ul_start;
                foreach ($messages as $message) {
                    $string .= $li_start.$message.$li_end;
                }
                $string .= $ul_end;
            }
        }
        return $asHtml ? Strings::markup($string) : $string;
    }

    public function hasMessages(
        string $type = null
    ): bool
    {
        if(empty($type)) {
            foreach ($this->messages as $type => $messages) {
                if(count($messages) > 0) return true;
            }
            return false;
        }
        return count($this->messages[$type]) > 0;
    }

    public function getMessageGlobalType(): string
    {
        $type = 'success';
        if(!$this->isSuccess()) {
            if($this->isUndone()) $type = 'info';
            if($this->isPartialSuccess()) $type = 'warning';
            if($this->isFail()) $type = 'error';
        }
        return $type;
    }


    // DATA

    public function getData(string|int $index = null): mixed
    {
        if(Iterables::isArrayIndex($index)) {
            if(!is_array($this->data)) throw new Exception(vsprintf('Error %s line %d: data is not array. Can not get named data!', [__METHOD__, __LINE__]));
            if(!array_key_exists($index, $this->data)) throw new Exception(vsprintf('Error %s line %d: data does not contain "%s" index!', [__METHOD__, __LINE__, $index]));
        }
        return Iterables::isArrayIndex($index)
            ? $this->data[$index]
            : $this->data;
    }

    public function addData(string|int $index, mixed $data): static
    {
        if(empty($this->data)) $this->data = [];
        if(!is_array($this->data)) throw new Exception(vsprintf('Error %s line %d: data already exists and is not array. Can not add named data!', [__METHOD__, __LINE__]));
        $this->data[$index] = $data;
        return $this;
    }

    public function setData(mixed $data): static
    {
        $this->data = $data;
        return $this;
    }


}