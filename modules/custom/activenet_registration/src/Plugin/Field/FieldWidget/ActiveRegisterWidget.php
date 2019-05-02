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
 *   id = "activenet_registration_widget",
 *   label = @Translation("FlexReg Programs Registration"),
 *   field_types = {
 *      "activenet_registration"
 *   }
 *)
 */

class ActiveRegisterWidget extends WidgetBase {

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

    $element['activity_flex'] = [
      '#title' => t('Activity/flex'),
      '#type' => 'select',
      '#empty_option' => '- Select Type -',
      '#default_value' => isset($item->activity_flex) ? $item->activity_flex : '',
      '#options' => [
        'activity' => 'Activity',
        'flex' => 'Flex Reg Program',
      ],
    ];

    $element['site'] = [
      '#title' => t('Site'),
      '#type' => 'select',
      '#empty_option' => '- Select Site -',
      '#default_value' => isset($item->site) ? $item->site : '',
      '#options' => $this->sitesSelect($this->storage->getSites()),
    ];

    $element['activity_name'] = [
      '#title' => t('Activity/Program Name'),
      '#type' => 'textfield',
      '#default_value' => isset($item->activity_name) ? $item->activity_name : '',
      '#size' => 60,
      '#maxlength' => 255,
      '#description' => 'Names that fully or partially match the entered text',
    ];

    $element['program_type'] = [
      '#title' => t('Program type'),
      '#type' => 'select',
      '#empty_option' => '- Select Program Type -',
      '#default_value' => isset($item->program_type) ? $item->program_type : '',
      '#options' => $this->programSelect($this->storage->getProgramTypes()),
      '#description' => 'Only used with Flex Registration Programs',
    ];

    $element['activity_type'] = [
      '#title' => t('Activity type'),
      '#type' => 'select',
      '#empty_option' => '- Select Activity Type -',
      '#default_value' => isset($item->activity_type) ? $item->activity_type : '',
      '#options' => $this->activitySelect($this->storage->getActivityTypes()),
      '#description' => 'Only used with Activity registrations',
    ];

    $element['category'] = [
      '#title' => t("Category"),
      '#type' => 'select',
      '#empty_option' => '- Select Category -',
      '#default_value' => isset($item->category) ? $item->category : '',
      '#options' => $this->categorySelect($this->storage->getCategories()),
    ];

    $element['other_category'] = [
      '#title' => t("Other Category"),
      '#type' => 'select',
      '#empty_option' => '- Select Category -',
      '#default_value' => isset($item->other_category) ? $item->other_category : '',
      '#options' => $this->otherCategorySelect($this->storage->getOtherCategories()),
    ];

    $element['gender'] = [
      '#title' => t("Gender"),
      '#type' => 'select',
      '#default_value' => isset($item->gender) ? $item->gender : 12,
      '#options' => [
        12 => '- Select Gender -', //workaround for 0 being a valid gender selection
        0 => 'Coed',
        1 => 'Male',
        2 => 'Female',
      ],
    ];

    return $element;
  }

  private function sitesSelect($sites) {
    foreach($sites as $site) {
      $select[$site->site_id] = $site->site_name;
    }
    return $select;
  }

  private function programSelect($programs) {
    foreach($programs as $program) {
      $select[$program->program_type_id] = $program->program_type_name;
    }
    return $select;
  }

  private function activitySelect($activities) {
    foreach($activities as $activity) {
      $select[$activity->activity_type_id] = $activity->activity_type_name;
    }
    return $select;
  }

  private function categorySelect($categories) {
    foreach($categories as $category) {
      $select[$category->category_id] = $category->activity_category_name;
    }
    return $select;
  }

  private function otherCategorySelect($categories) {
    foreach($categories as $category) {
      $select[$category->other_category_id] = $category->other_category_name;
    }
    return $select;
  }

}
