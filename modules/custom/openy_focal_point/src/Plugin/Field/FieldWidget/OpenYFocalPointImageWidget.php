<?php

namespace Drupal\openy_focal_point\Plugin\Field\FieldWidget;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\focal_point\Plugin\Field\FieldWidget\FocalPointImageWidget;
use Drupal\Core\Url;

/**
 * The difference with FocalPointImageWidget is in createPreviewLink() method
 * we use our custom route for Preview and pass image styles to be used in
 * preview dialog.
 */

/**
 * Plugin implementation of the 'openy_image_focal_point' widget.
 *
 * @FieldWidget(
 *   id = "openy_image_focal_point",
 *   label = @Translation("OpenY Image (Focal Point)"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class OpenYFocalPointImageWidget extends FocalPointImageWidget {

  /**
   * Create the preview link form element.
   *
   * @param int $fid
   *   The fid of the image file.
   * @param array $element_selectors
   *   The element selectors to ultimately be used by javascript.
   * @param string $default_focal_point_value
   *   The default focal point value in the form x,y.
   * @param string $image_style
   *   The image style to use in a crop preview.
   *
   * @return array
   *   The preview link form element.
   */
  private static function createCropLink($fid, array $element_selectors, $default_focal_point_value, $image_style) {
    // Replace comma (,) with an x to make javascript handling easier.
    $preview_focal_point_value = str_replace(',', 'x', $default_focal_point_value);

    // Create a token to be used during an access check on the preview page.
    $token = self::getPreviewToken();

    $preview_link = [
      '#type' => 'link',
      '#title' => new TranslatableMarkup('Crop Image'),
      '#url' => new Url('openy_focal_point.preview',
        [
          'fid' => $fid,
          'focal_point_value' => $preview_focal_point_value,
        ],
        [
          'query' => ['focal_point_token' => $token, 'image_style' => $image_style],
        ]),
      '#attached' => [
        'library' => ['core/drupal.dialog.ajax'],
      ],
      '#attributes' => [
        'class' => ['use-ajax'],
        'data-selector' => $element_selectors['focal_point'],
        'data-dialog-type' => 'modal',
        'target' => '_blank',
      ],
    ];

    return $preview_link;
  }

  /**
   * Create a link to open Dialog with editing Focal Point.
   *
   * @param int $fid
   *   The fid of the image file.
   * @param array $element_selectors
   *   The element selectors to ultimately be used by javascript.
   * @param string $default_focal_point_value
   *   The default focal point value in the form x,y.
   * @param string $image_style
   *   The image style to use in a focal point preview.
   *
   * @return array
   *   The preview link form element.
   */
  private static function createFocalPointEditLink($fid, array $element_selectors, $default_focal_point_value, $image_style) {
    // Replace comma (,) with an x to make javascript handling easier.
    $preview_focal_point_value = str_replace(',', 'x', $default_focal_point_value);

    // Create a token to be used during an access check on the preview page.
    $token = self::getPreviewToken();

    $preview_link = [
      '#type' => 'link',
      '#title' => new TranslatableMarkup('Set Focal Point'),
      '#url' => new Url('openy_focal_point.edit_focal_point',
        [
          'fid' => $fid,
          'focal_point_value' => $preview_focal_point_value,
        ],
        [
          'query' => ['focal_point_token' => $token, 'image_style' => $image_style],
        ]),
      '#attached' => [
        'library' => ['core/drupal.dialog.ajax'],
      ],
      '#attributes' => [
        'class' => ['use-ajax'],
        'data-selector' => $element_selectors['focal_point'],
        'data-dialog-type' => 'modal',
        'target' => '_blank',
      ],
    ];

    return $preview_link;
  }

  /**
   * Loads the entity view display entity and prepare list of all image styles
   * used (to be passed with preview link).
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $element = parent::process($element, $form_state, $form);

    if (isset($element['focal_point'])) {
      // It is important to unset 'focal_point' so field doesn't have values
      // otherwise focal point values will be overridden on file save.
      // @see focal_point_entity_update()
      unset($element['focal_point']);
      $element['preview']['indicator']['#access'] = FALSE;
    }

    if (isset($element['preview']['preview_link'])) {
      $item = $element['#value'];
      $fid = $item['fids'][0];
      $element_selectors = [
        'focal_point' => 'focal-point-' . implode('-', $element['#parents']),
      ];
      $default_focal_point_value = isset($item['focal_point']) ? $item['focal_point'] : $element['#focal_point']['offsets'];

      $paragraph_type = \Drupal::request()->query->get('paragraph_type');
      $field_name = \Drupal::request()->query->get('field_name');

      $display_storage = \Drupal::entityTypeManager()->getStorage('entity_view_display');
      // We are assuming that only default view mode is used for paragraph
      // images OpenY focal point styled output.
      $paragraph_display = $display_storage->load('paragraph.' . $paragraph_type . '.default');
      if ($paragraph_display) {
        $image_field_component = $paragraph_display->getComponent($field_name);
        $display = $display_storage->load('media.image.' . $image_field_component['settings']['view_mode']);
      }
      if (isset($display)) {
        $image_component = $display->getComponent($element['#field_name']);
        $image_style = $image_component['settings']['image_style'];

        $element['preview']['preview_link'] = [
          '#type' => 'inline_template',
          '#template' => '<div class="form-item__description"><div>{{ focal_point_link }}  |  {{ crop_image_link }}</div><div>{{ note }}</div></div>',
          '#context' => [
            'focal_point_link' => self::createFocalPointEditLink($fid, $element_selectors, $default_focal_point_value, $image_style),
            'crop_image_link' => self::createCropLink($fid, $element_selectors, $default_focal_point_value, $image_style),
            'note' => new TranslatableMarkup('Note: Focal Point and Crop Image cannot both be applied to the same image.'),
          ],
        ];
      }
    }

    return $element;
  }

}
