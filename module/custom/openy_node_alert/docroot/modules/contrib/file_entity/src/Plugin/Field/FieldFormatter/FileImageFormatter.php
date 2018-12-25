<?php

namespace Drupal\file_entity\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implementation of the 'image' formatter for the file_entity files.
 *
 * @FieldFormatter(
 *   id = "file_image",
 *   label = @Translation("File Image"),
 *   field_types = {
 *     "uri"
 *   }
 * )
 */
class FileImageFormatter extends ImageFormatter {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs on FileImageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style entity storage class.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage);
    $this->entityFieldManager = $entity_field_manager;
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
      $container->get('current_user'),
      $container->get('entity.manager')->getStorage('image_style'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'title' => 'field_image_title_text',
      'alt' => 'field_image_alt_text',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if ($this->getSetting('title') == '_none') {
      $summary[] = $this->t('Title attribute is hidden.');
    } else {
      $summary[] = $this->t('Field used for the image title attribute: @title', ['@title' => $this->getSetting('title')]);
    }
    if ($this->getSetting('alt') == '_none') {
      $summary[] = $this->t('Alt attribute is hidden.');
    } else {
      $summary[] = $this->t('Field used for the image alt attribute: @alt', ['@alt' => $this->getSetting('alt')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $file = $items->getEntity();

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $cache_tags = $image_style->getCacheTags();
    }
    $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());

    if (isset($image_style)) {
      $elements[0] = [
        '#theme' => 'image_style',
        '#style_name' => $image_style_setting,
      ];
    }
    else {
      $elements[0] = [
        '#theme' => 'image',
      ];
    }
    $elements[0] += [
      '#uri' => $file->getFileUri(),
      '#cache' => [
        'tags' => $cache_tags,
      ],
    ];
    foreach (['title', 'alt'] as $element_name) {
      $field_name = $this->getSetting($element_name);
      if ($field_name !== '_none' && $file->hasField($field_name)) {
        $elements[0]['#' . $element_name] = $file->$field_name->value;
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities_items) {}

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    unset($element['image_link']);
    $available_fields = $this->entityFieldManager->getFieldDefinitions(
      $form['#entity_type'],
      $form['#bundle']
    );
    $options = [];
    foreach ($available_fields as $field_name => $field_definition) {
      if ($field_definition instanceof FieldConfigInterface && $field_definition->getType() == 'string') {
        $options[$field_name] = $field_definition->label();
      }
    }
    $element['title'] = [
      '#title' => $this->t('Image title field'),
      '#description' => $this->t('The field that is used as source for the image title attribute.'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $this->getSetting('title'),
      '#empty_option' => $this->t('No title attribute'),
      '#empty_value' => '_none',
    ];
    $element['alt'] = [
      '#title' => $this->t('Image alt field'),
      '#description' => $this->t('The field that is used as source for the image alt attribute.'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $this->getSetting('alt'),
      '#empty_option' => $this->t('No alt attribute'),
      '#empty_value' => '_none',
    ];
    return $element;
  }

}
