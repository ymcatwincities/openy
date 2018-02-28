<?php

/**
 * @file
 * Contains \Drupal\video\Plugin\Field\FieldFormatter\VidePlayerFormatter.
 */

namespace Drupal\video\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Plugin implementation of the 'video_player' formatter.
 *
 * @FieldFormatter(
 *   id = "video_player",
 *   label = @Translation("HTML5 Video Player"),
 *   field_types = {
 *     "video"
 *   }
 * )
 */

class VideoPlayerFormatter extends VideoPlayerFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs an VideoPlayerFormatter object.
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
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
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
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'width' => '854',
      'height' => '480',
      'controls' => TRUE,
      'autoplay' => FALSE,
      'loop' => FALSE,
      'muted' => FALSE,
      'preload' => 'none'
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['width'] = [
      '#title' => t('Width'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('width'),
      '#required' => TRUE,
    ];
    $element['height'] = [
      '#title' => t('Height'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('height'),
      '#required' => TRUE,
    ];
    $element['controls'] = [
      '#title' => t('Show controls'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('controls'),
    ];
    $element['autoplay'] = [
      '#title' => t('Autoplay'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('autoplay'),
    ];
    $element['loop'] = [
      '#title' => t('Loop'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('loop'),
    ];
    $element['muted'] = [
      '#title' => t('Muted'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('muted'),
    ];
    $element['preload'] = [
      '#title' => t('Preload'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('preload'),
      '#options' => array(
        'none' =>'none',
        'metadata' => 'metadata',
        'auto' => 'auto'
      ),
      '#description' => t('Hint to the browser about whether optimistic downloading of the video itself or its metadata is considered worthwhile.')
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = t('HTML5 Video (@widthx@height@controls@autoplay@loop@muted).', [
      '@width' => $this->getSetting('width'),
      '@height' => $this->getSetting('height'),
      '@controls' => $this->getSetting('controls') ? t(', controls') : '' ,
      '@autoplay' => $this->getSetting('autoplay') ? t(', autoplaying') : '' ,
      '@loop' => $this->getSetting('loop') ? t(', looping') : '' ,
      '@muted' => $this->getSetting('muted') ? t(', muted') : '',
    ]);
    return $summary;
  }
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    // Collect cache tags to be added for each item in the field.
    foreach ($files as $delta => $file) {
      $video_uri = $file->getFileUri();
      $elements[$delta] = array(
        '#theme' => 'video_player_formatter',
        '#items' => array(Url::fromUri(file_create_url($video_uri))),
        '#player_attributes' => $this->getSettings(),
      );
    }
    return $elements;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if(empty($field_definition->getTargetBundle()) && !$field_definition->isList()){
      return TRUE;
    }
    else{
      $entity_form_display = entity_get_form_display($field_definition->getTargetEntityTypeId(), $field_definition->getTargetBundle(), 'default');
      $widget = $entity_form_display->getRenderer($field_definition->getName());
      $widget_id = $widget->getBaseId();
      if(!$field_definition->isList() && $widget_id == 'video_upload'){
        return TRUE;
      }
    }
    return FALSE;
  }
}