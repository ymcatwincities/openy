<?php

namespace Drupal\file_browser\Plugin\Block;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Provides the "Image Embed" block.
 *
 * @Block(
 *   id = "image_embed",
 *   admin_label = @Translation("Image Embed"),
 *   category = @Translation("Embed")
 * )
 */
class ImageEmbedBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'image_style' => '',
      'files' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $entities = $form_state->getValue([
      'settings',
      'selection',
      'fids',
      'entities',
    ], []);
    $table = $form_state->getValue([
      'settings',
      'selection',
      'table',
    ], []);
    $files = [];
    foreach ($entities as $entity) {
      $settings = isset($table[$entity->id()]) ? $table[$entity->id()] : [];
      $settings['fid'] = $entity->id();
      $files[] = $settings;
    }
    if (empty($files)) {
      $files = $this->configuration['files'];
    }

    $form['selection'] = $this->browserForm($files);

    $form['image_style'] = [
      '#type' => 'select',
      '#options' => image_style_options(),
      '#title' => $this->t('Image style'),
      '#default_value' => $this->configuration['image_style'],
    ];

    return $form;
  }

  /**
   * Constructs parts of the form needed to use Entity Browser.
   *
   * @param array $files
   *   An array representing the current configuration + form state.
   *
   * @return array
   *   A render array representing Entity Browser components.
   */
  public function browserForm($files) {
    $selection = [
      '#type' => 'container',
      '#attributes' => ['id' => 'image-embed-block-browser'],
    ];

    $selection['fids'] = [
      '#type' => 'entity_browser',
      '#entity_browser' => 'browse_files_modal',
      '#entity_browser_validators' => [
        'entity_type' => ['type' => 'file'],
      ],
      '#process' => [
        [
          '\Drupal\entity_browser\Element\EntityBrowserElement',
          'processEntityBrowser',
        ],
        [get_called_class(), 'processEntityBrowser'],
      ],
    ];

    $order_class = 'image-embed-block-delta-order';

    $selection['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Preview'),
        $this->t('Filename'),
        $this->t('Metadata'),
        $this->t('Order', [], ['context' => 'Sort order']),
      ],
      '#empty' => $this->t('No files yet'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => $order_class,
        ],
      ],
    ];

    $delta = 0;

    foreach ($files as $info) {
      $file = File::load($info['fid']);
      $uri = $file->getFileUri();
      $image = \Drupal::service('image.factory')->get($uri);
      if ($image->isValid()) {
        $width = $image->getWidth();
        $height = $image->getHeight();
      }
      else {
        $width = $height = NULL;
      }

      $display = [
        '#theme' => 'image_style',
        '#width' => $width,
        '#height' => $height,
        '#style_name' => 'file_entity_browser_small',
        '#uri' => $uri,
      ];

      $fid = $file->id();
      $selection['table'][$fid] = [
        '#attributes' => [
          'class' => ['draggable'],
          'data-entity-id' => $file->getEntityTypeId() . ':' . $fid,
        ],
        'display' => $display,
        'filename' => ['#markup' => $file->label()],
        'alt' => [
          '#type' => 'textfield',
          '#title' => $this->t('Alternative text'),
          '#default_value' => isset($info['settings']['alt']) ? $info['settings']['alt'] : '',
          '#size' => 45,
          '#maxlength' => 512,
          '#description' => $this->t('This text will be used by screen readers, search engines, or when the image cannot be loaded.'),
        ],
        '_weight' => [
          '#type' => 'weight',
          '#title' => $this->t('Weight for row @number', ['@number' => $delta + 1]),
          '#title_display' => 'invisible',
          '#delta' => count($files),
          '#default_value' => $delta,
          '#attributes' => ['class' => [$order_class]],
        ],
      ];

      $delta++;
    }

    return $selection;
  }

  /**
   * AJAX callback: Re-renders the Entity Browser button/table.
   */
  public static function updateCallback(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $parents = array_slice($trigger['#array_parents'], 0, -2);
    $selection = NestedArray::getValue($form, $parents);
    return $selection;
  }

  /**
   * Render API callback: Processes the entity browser element.
   */
  public static function processEntityBrowser(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['entity_ids']['#ajax'] = [
      'callback' => [get_called_class(), 'updateCallback'],
      'wrapper' => 'image-embed-block-browser',
      'event' => 'entity_browser_value_updated',
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['files'] = [];
    foreach ($form_state->getValue(['selection', 'table'], []) as $fid => $settings) {
      $this->configuration['files'][] = [
        'fid' => $fid,
        'settings' => $settings,
      ];
    }
    $this->configuration['image_style'] = $form_state->getValue('image_style');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    foreach ($this->configuration['files'] as $info) {
      /** @var \Drupal\file\Entity\File $file */
      $file = File::load($info['fid']);
      if ($file && $file->access('view')) {
        $uri = $file->getFileUri();
        $image = \Drupal::service('image.factory')->get($uri);
        if ($image->isValid()) {
          $width = $image->getWidth();
          $height = $image->getHeight();
        }
        else {
          $width = $height = NULL;
        }

        $current = [
          '#theme' => 'image',
          '#width' => $width,
          '#height' => $height,
          '#alt' => isset($info['settings']['alt']) ? $this->t($info['settings']['alt']) : '',
          '#uri' => $uri,
        ];

        if ($this->configuration['image_style']) {
          $current['#theme'] = 'image_style';
          $current['#style_name'] = $this->configuration['image_style'];
        }

        $build[] = $current;
      }
    }

    return $build;
  }

}
