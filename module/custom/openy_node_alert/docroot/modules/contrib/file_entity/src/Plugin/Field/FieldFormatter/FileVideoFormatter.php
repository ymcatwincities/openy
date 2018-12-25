<?php

namespace Drupal\file_entity\Plugin\Field\FieldFormatter;


use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Template\Attribute;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'file_video' formatter.
 *
 * @FieldFormatter(
 *   id = "file_video",
 *   label = @Translation("Video"),
 *   description = @Translation("Render the file using an HTML5 video tag."),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileVideoFormatter extends FileFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a FileVideoFormatter instance.
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
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param Drupal\Core\Render\RendererInterface $renderer
   *   The rendered service
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, RendererInterface $renderer) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->renderer = $renderer;
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
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {

    return array(
      'controls' => TRUE,
      'autoplay' => FALSE,
      'loop' => FALSE,
      'muted' => FALSE,
      'width' => NULL,
      'height' => NULL,
      'multiple_file_behavior' => 'tags',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['controls'] = array(
      '#title' => t('Show video controls'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('controls'),
    );
    $element['autoplay'] = array(
      '#title' => t('Autoplay'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('autoplay'),
    );
    $element['loop'] = array(
      '#title' => t('Loop'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('loop'),
    );
    $element['muted'] = array(
      '#title' => t('Muted'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('muted'),
    );
    $element['width'] = array(
      '#type' => 'textfield',
      '#title' => t('Width'),
      '#default_value' => $this->getSetting('width'),
      '#size' => 5,
      '#maxlength' => 5,
      '#field_suffix' => t('pixels'),
    );
    $element['height'] = array(
      '#type' => 'textfield',
      '#title' => t('Height'),
      '#default_value' => $this->getSetting('height'),
      '#size' => 5,
      '#maxlength' => 5,
      '#field_suffix' => t('pixels'),
    );
    $element['multiple_file_behavior'] = array(
      '#title' => t('Display of multiple files'),
      '#type' => 'radios',
      '#options' => array(
        'tags' => t('Use multiple @tag tags, each with a single source.', array('@tag' => '<video>')),
        'sources' => t('Use multiple sources within a single @tag tag.', array('@tag' => '<video>')),
      ),
      '#default_value' => $this->getSetting('multiple_file_behavior'),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = t('Controls: %controls', array('%controls' => $this->getSetting('controls') ? 'visible' : 'hidden'));
    $summary[] = t('Autoplay: %autoplay', array('%autoplay' => $this->getSetting('autoplay') ? t('yes') : t('no')));
    $summary[] = t('Loop: %loop', array('%loop' => $this->getSetting('loop') ? t('yes') : t('no')));
    $summary[] = t('Muted: %muted', array('%muted' => $this->getSetting('muted') ? t('yes') : t('no')));
    $width = $this->getSetting('width');
    $height = $this->getSetting('height');
    if ($width && $height) {
      $summary[] = t('Size: %width x %height pixels', array(
        '%width' => $this->getSetting('width'),
        '%height' => $this->getSetting('height')
      ));
    }
    $summary[] = t('Multiple files: %multiple', array('%multiple' => $this->getSetting('multiple_file_behavior')));
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $multiple_file_behavior = $this->getSetting('multiple_file_behavior');
    $source_files = array();
    // Because we can have the files grouped in a single video tag, we do a
    // grouping in case the multiple file behavior is not 'tags'.
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      if ($file->getMimeTypeType() == 'video') {
        $source_attributes = new Attribute();
        $source_attributes->setAttribute('src', file_create_url($file->getFileUri()));
        $source_attributes->setAttribute('type', $file->getMimeType());
        if ($multiple_file_behavior == 'tags') {
          $source_files[$delta] = array(array('file' => $file, 'source_attributes' => $source_attributes));
        }
        else {
          $source_files[0][] = array('file' => $file, 'source_attributes' => $source_attributes);
        }
      }
    }

    if (!empty($source_files)) {
      // Prepare the video attributes according to the settings.
      $video_attributes = new Attribute();
      foreach (array('controls', 'autoplay', 'loop', 'muted') as $attribute) {
        if ($this->getSetting($attribute)) {
          $video_attributes->setAttribute($attribute, $attribute);
        }
      }
      $width = $this->getSetting('width');
      $height = $this->getSetting('height');
      if ($width && $height) {
        $video_attributes->setAttribute('width', $width);
        $video_attributes->setAttribute('height', $height);
      }
      foreach ($source_files as $delta => $files) {
        $elements[$delta] = array(
          '#theme' => 'file_entity_video',
          '#attributes' => $video_attributes,
          '#files' => $files,
        );
        foreach ($files as $file) {
          $this->renderer->addCacheableDependency($elements[$delta], $file['file']);
        }
      }
    }

    return $elements;
  }
}
