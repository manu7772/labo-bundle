<?php
namespace Aequation\LaboBundle\Component;

use Aequation\LaboBundle\Model\Interface\WebsectionInterface;
use Aequation\LaboBundle\Service\Interface\ExpressionServiceInterface;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;

use Exception;
use Symfony\Component\ExpressionLanguage\Expression;

class TwigfileMetadata
{

    public readonly ?array $models;
    public readonly ?array $sectiontypes;
    public ?string $defaultSectiontype;
    protected PropertyAccessor $propertyAccessor;

    public function __construct(
        public readonly WebsectionInterface $websection,
        public readonly string|array|null $paths = null
    )
    {
        $this->sectiontypes = $this->websection->_service->getSectiontypes();
        $this->models = $this->websection->_service->listWebsectionModels(false, null, $paths);
        $this->propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->enableMagicCall()->enableExceptionOnInvalidIndex()->getPropertyAccessor();
    }

    public function getModelData(
        ?string $twigfile = null
    ): ?array
    {
        $twigfile ??= $this->websection->getTwigfile();
        // if(empty($twigfile)) return null;
        foreach ($this->models as $data) {
            if($data['choice_value'] === $twigfile) return $data;
        }
        return null;
    }

    public function listWebsectionModels(
        bool $asChoiceList = false,
        array|string|null $filter_types = null,
        string|array|null $paths = null
    ): array
    {
        return $this->websection->_service->listWebsectionModels($asChoiceList, $filter_types, $paths);
    }

    public function getSectiontypeChoices(): array
    {
        return $this->sectiontypes;
    }

    public function getDefaultSectiontype(): string
    {
        if(!isset($this->defaultSectiontype)) {
            $sectiontypes = $this->getSectiontypeChoices();
            $this->defaultSectiontype = isset($sectiontypes['section'])
                ? $sectiontypes['section']
                : reset($sectiontypes);
        }
        return $this->defaultSectiontype;
    }

    /**
     * Get new field type
     * if bool returned:
     * - true: let form builder generate the form field
     * - false: form field should NOT been generated
     * @param string $fieldname
     * @param string $context
     * @return FieldInterface|boolean
     */
    public function getEasyadminField(
        string $fieldname,
        string $context = 'default'
    ): FieldInterface|bool
    {
        $expression = $this->websection->_service->getAppService()->get(ExpressionServiceInterface::class);
        $data = $this->getModelData();
        $default = $data['form']['default'][$fieldname] ?? [];
        $field = $data['form'][$context][$fieldname] ?? [];
        $field = array_merge($field, $default);
        if(!empty($field)) {
            if(!($field['show'] ?? true)) return false;
            if(!isset($field['type'])) return true;
            if(!class_exists($field['type']) || !is_a($field['type'], FieldInterface::class, true)) {
                throw new Exception(vsprintf('Error %s line %d: %s is not instance of %s', [__METHOD__, __LINE__, json_encode($field['type']), FieldInterface::class]));
            }
            $type = $field['type'];
            $newField = $type::new($fieldname, $field['label'] ?? null);
            foreach ($field['properties'] ?? [] as $attr => $value) {
                if(is_string($value)) $value = $expression->evaluate(new Expression($value), ['websection' => $this->websection], true);
                $this->propertyAccessor->setValue($newField, $attr, $value);
            }
            foreach ($field['setters'] ?? [] as $method => $value) {
                if(is_string($value)) $value = $expression->evaluate(new Expression($value), ['websection' => $this->websection], true);
                $newField->$method($value);
            }
            return $newField;
        }
        return true;
    }


}