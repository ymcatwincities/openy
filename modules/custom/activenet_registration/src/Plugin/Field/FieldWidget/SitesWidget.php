<?php

namespace Drupal\activenet_registration\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\activenet_registration\DataStorage;
use Drupal\activenet_registration\DataStorageInterface;

/**
 * Plugin implementation of the 'activenet_registration' widget
 *
 *@FieldWidget(
 *   id = "activenet_sites_widget",
 *   label = @Translation("Activenet Sites Selection"),
 *   field_types = {
 *      "activenet_sites"
 *   }
 *)
 */

class SitesWidget extends WidgetBase {

  /**
   * Storage cache.
   *
   * @var \Drupal\activenet_registration\DataStorageInterface
   */
  protected $storage;

  /**
   * Constructs a Active Registration object
   * 
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition, or maybe a FieldConfig?
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * 
   */
  public function __construct($plugin_id, $plugin_definition, $field_definition, array $settings, array $third_party_settings) {
    $this->storage =  \Drupal::service('activenet_registration.datastorage');
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }
  
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items->get($delta);

    $element['site'] = [
      '#title' => t('Site'),
      '#type' => 'select',
      '#empty_option' => '- Select Site -',
      '#default_value' => isset($item->site) ? $item->site : '',
      '#options' => $this->sitesSelect($this->storage->getSites()),
    ];
    return $element;
  }

  private function sitesSelect($sites) {
    foreach($sites as $site) {
      $select[$site->site_id] = $site->site_name;
    }
    return $select;
  }
 
}
