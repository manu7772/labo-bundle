<?php
namespace Aequation\LaboBundle\Field;

use Aequation\LaboBundle\Controller\Admin\WebsectionCrudController;
use Aequation\LaboBundle\Entity\Item;
use App\Entity\Websection;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

final class WebsectionsField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_AUTOCOMPLETE = 'autocomplete';
    public const OPTION_EMBEDDED_CRUD_FORM_CONTROLLER = 'crudControllerFqcn';
    /** @deprecated since easycorp/easyadmin-bundle 4.4.3 use static::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER */
    public const OPTION_CRUD_CONTROLLER = self::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER;
    public const OPTION_WIDGET = 'widget';
    public const OPTION_QUERY_BUILDER_CALLABLE = 'queryBuilderCallable';
    /** @internal this option is intended for internal use only */
    public const OPTION_RELATED_URL = 'relatedUrl';
    /** @internal this option is intended for internal use only */
    public const OPTION_DOCTRINE_ASSOCIATION_TYPE = 'associationType';

    public const WIDGET_AUTOCOMPLETE = 'autocomplete';
    public const WIDGET_NATIVE = 'native';

    /** @internal this option is intended for internal use only */
    public const PARAM_AUTOCOMPLETE_CONTEXT = 'autocompleteContext';

    /** @internal this option is intended for internal use only */
    public const OPTION_RENDER_AS_EMBEDDED_FORM = 'renderAsEmbeddedForm';

    public const OPTION_EMBEDDED_CRUD_FORM_NEW_PAGE_NAME = 'crudNewPageName';
    public const OPTION_EMBEDDED_CRUD_FORM_EDIT_PAGE_NAME = 'crudEditPageName';
    // the name of the property in the associated entity used to sort the results (only for *-To-One associations)
    public const OPTION_SORT_PROPERTY = 'sortProperty';
    public const OPTION_ESCAPE_HTML_CONTENTS = 'escapeHtml';
    // Other opations
    public const OPTION_USE_JAVASCRIPT = 'useJavascript';
    public const OPTION_PARENT_FIELD_NAME = 'parentFieldName';

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty('items')
            ->setLabel($label)
            ->setTemplatePath('@EasyAdmin/crud/field/websections.html.twig')
            // ->setTemplateName('crud/field/association')
            ->setFormTypeOptions([
                'class' => Websection::class,
                'multiple' => true,
            ])
            ->setFormType(EntityType::class)
            ->addCssClass('field-association')
            ->setDefaultColumns('col-md-7 col-xxl-6')
            ->setCustomOption(self::OPTION_AUTOCOMPLETE, false)
            ->setCustomOption(self::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER, null)
            ->setCustomOption(self::OPTION_WIDGET, self::WIDGET_AUTOCOMPLETE)
            ->setCustomOption(self::OPTION_QUERY_BUILDER_CALLABLE, null)
            ->setCustomOption(self::OPTION_RELATED_URL, null)
            ->setCustomOption(self::OPTION_DOCTRINE_ASSOCIATION_TYPE, null)
            ->setCustomOption(self::OPTION_RENDER_AS_EMBEDDED_FORM, false)
            ->setCustomOption(self::OPTION_EMBEDDED_CRUD_FORM_NEW_PAGE_NAME, null)
            ->setCustomOption(self::OPTION_EMBEDDED_CRUD_FORM_EDIT_PAGE_NAME, null)
            ->setCustomOption(self::OPTION_ESCAPE_HTML_CONTENTS, true)
            ->setCustomOption(self::OPTION_USE_JAVASCRIPT, false)
            ->setCustomOption(self::OPTION_PARENT_FIELD_NAME, $propertyName)
            ->setCrudController(WebsectionCrudController::class)
            ->addJsFiles('js/websections.js')
            ;
    }

    public function setUseJavascript(bool $useJavascript = true): self
    {
        $this->setCustomOption(static::OPTION_USE_JAVASCRIPT, $useJavascript);
        return $this;
    }

    public function setParentFieldName(
        string $parentFieldName
    ): self
    {
        $this->setCustomOption(static::OPTION_PARENT_FIELD_NAME, $parentFieldName);
        return $this;
    }

    public function autocomplete(): self
    {
        $this->setCustomOption(static::OPTION_AUTOCOMPLETE, true);

        return $this;
    }

    public function renderAsNativeWidget(bool $asNative = true): self
    {
        $this->setCustomOption(static::OPTION_WIDGET, $asNative ? static::WIDGET_NATIVE : static::WIDGET_AUTOCOMPLETE);

        return $this;
    }

    public function setCrudController(string $crudControllerFqcn): self
    {
        $this->setCustomOption(static::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER, $crudControllerFqcn);

        return $this;
    }

    public function setQueryBuilder(\Closure $queryBuilderCallable): self
    {
        $this->setCustomOption(static::OPTION_QUERY_BUILDER_CALLABLE, $queryBuilderCallable);

        return $this;
    }

    public function renderAsEmbeddedForm(?string $crudControllerFqcn = null, ?string $crudNewPageName = null, ?string $crudEditPageName = null): self
    {
        $this->setCustomOption(static::OPTION_RENDER_AS_EMBEDDED_FORM, true);
        $this->setCustomOption(static::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER, $crudControllerFqcn);
        $this->setCustomOption(static::OPTION_EMBEDDED_CRUD_FORM_NEW_PAGE_NAME, $crudNewPageName);
        $this->setCustomOption(static::OPTION_EMBEDDED_CRUD_FORM_EDIT_PAGE_NAME, $crudEditPageName);

        return $this;
    }

    public function setSortProperty(string $orderProperty): self
    {
        $this->setCustomOption(static::OPTION_SORT_PROPERTY, $orderProperty);

        return $this;
    }

    public function renderAsHtml(bool $asHtml = true): self
    {
        $this->setCustomOption(static::OPTION_ESCAPE_HTML_CONTENTS, !$asHtml);

        return $this;
    }

}