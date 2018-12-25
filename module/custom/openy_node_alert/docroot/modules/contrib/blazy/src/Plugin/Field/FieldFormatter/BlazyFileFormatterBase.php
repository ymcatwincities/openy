<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\blazy\BlazyFormatterManager;
use Drupal\blazy\Dejavu\BlazyDefault;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for blazy/slick image, and file ER formatters.
 *
 * Defines one base class to extend for both image and file ER formatters as
 * otherwise different base classes: ImageFormatterBase or FileFormatterBase.
 *
 * @see Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFormatter.
 * @see Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFileFormatter.
 * @see Drupal\slick\Plugin\Field\FieldFormatter\SlickImageFormatter.
 * @see Drupal\slick\Plugin\Field\FieldFormatter\SlickFileFormatter.
 */
abstract class BlazyFileFormatterBase extends FileFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyFormatterManager
   */
  protected $blazyManager;

  /**
   * Constructs a BlazyFormatter object.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, BlazyFormatterManager $blazy_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->blazyManager = $blazy_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('blazy.formatter.manager')
    );
  }

  /**
   * Returns the blazy manager.
   */
  public function blazyManager() {
    return $this->blazyManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return BlazyDefault::imageSettings() + BlazyDefault::gridSettings();
  }

  /**
   * Builds the settings.
   */
  public function buildSettings() {
    $settings              = $this->getSettings();
    $settings['plugin_id'] = $this->getPluginId();

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element    = [];
    $definition = $this->getScopedFormElements();

    $definition['_views'] = isset($form['field_api_classes']);
    if (!empty($definition['_views'])) {
      $view = $form_state->get('view');
      // Disables problematic options for GridStack plugin since GridStack
      // will not work with Responsive image, and has its own breakpoints.
      if ($view->getExecutable()->getStyle()->getPluginId() == 'gridstack') {
        $definition['breakpoints'] = $definition['ratio'] = FALSE;
        $definition['responsive_image'] = FALSE;
        $definition['no_ratio'] = TRUE;
      }
    }

    $this->admin()->buildSettingsForm($element, $definition);

    return $element;
  }

  /**
   * Defines the scope for the form elements.
   */
  public function getScopedFormElements() {
    $field       = $this->fieldDefinition;
    $entity_type = $field->getTargetEntityTypeId();
    $target_type = $this->getFieldSetting('target_type');
    $multiple    = $field->getFieldStorageDefinition()->isMultiple();

    return [
      'background'        => TRUE,
      'box_captions'      => TRUE,
      'breakpoints'       => BlazyDefault::getConstantBreakpoints(),
      'captions'          => ['title' => $this->t('Title'), 'alt' => $this->t('Alt')],
      'current_view_mode' => $this->viewMode,
      'entity_type'       => $entity_type,
      'field_name'        => $field->getName(),
      'field_type'        => $field->getType(),
      'grid_form'         => $multiple,
      'image_style_form'  => TRUE,
      'media_switch_form' => TRUE,
      'namespace'         => 'blazy',
      'plugin_id'         => $this->getPluginId(),
      'settings'          => $this->getSettings(),
      'style'             => $multiple,
      'target_type'       => $target_type,
      'thumbnail_style'   => TRUE,
    ];
  }

  /**
   * Overrides parent::needsEntityLoad().
   *
   * One step back to have both image and file ER plugins extend this, because
   * EntityReferenceItem::isDisplayed() doesn't exist, except for ImageItem
   * which is always TRUE anyway for type image and file ER.
   */
  protected function needsEntityLoad(EntityReferenceItem $item) {
    return !$item->hasNewEntity();
  }

  /**
   * {@inheritdoc}
   *
   * A clone of Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase so
   * to have one base class to extend for both image and file ER formatters.
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    // Add the default image if the type is image.
    if ($items->isEmpty() && $this->fieldDefinition->getType() === 'image') {
      $default_image = $this->getFieldSetting('default_image');
      // If we are dealing with a configurable field, look in both
      // instance-level and field-level settings.
      if (empty($default_image['uuid']) && $this->fieldDefinition instanceof FieldConfigInterface) {
        $default_image = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('default_image');
      }
      if (!empty($default_image['uuid']) && $file = \Drupal::entityManager()->loadEntityByUuid('file', $default_image['uuid'])) {
        // Clone the FieldItemList into a runtime-only object for the formatter,
        // so that the fallback image can be rendered without affecting the
        // field values in the entity being rendered.
        $items = clone $items;
        $items->setValue(array(
          'target_id' => $file->id(),
          'alt' => $default_image['alt'],
          'title' => $default_image['title'],
          'width' => $default_image['width'],
          'height' => $default_image['height'],
          'entity' => $file,
          '_loaded' => TRUE,
          '_is_default' => TRUE,
        ));
        $file->_referringItem = $items[0];
      }
    }

    return parent::getEntitiesToView($items, $langcode);
  }

}
