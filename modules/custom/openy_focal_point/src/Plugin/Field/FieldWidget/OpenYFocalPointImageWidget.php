<?php

namespace Drupal\openy_focal_point\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\crop\Entity\Crop;
use Drupal\Core\Form\FormStateInterface;
use Drupal\focal_point\Plugin\Field\FieldWidget\FocalPointImageWidget;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;
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
   * @param string $field_name
   *   The name of the field element for the image field.
   * @param array $element_selectors
   *   The element selectors to ultimately be used by javascript.
   * @param string $default_focal_point_value
   *   The default focal point value in the form x,y.
   *
   * @return array
   *   The preview link form element.
   */
  private static function createCropLink($fid, $field_name, array $element_selectors, $default_focal_point_value, $image_styles) {
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
          'field_name' => $field_name,
        ],
        [
          'query' => ['focal_point_token' => $token, 'image_styles' => implode(':', $image_styles)],
        ]),
      '#attached' => [
        'library' => ['core/drupal.dialog.ajax'],
      ],
      '#attributes' => [
        'class' => ['use-ajax'],
        'data-selector' => $element_selectors['focal_point'],
        'data-field-name' => $field_name,
        'data-dialog-type' => 'modal',
        'target' => '_blank',
      ],
    ];

    return $preview_link;
  }

  /**
   * Create a link to open Dialog with editing Focal Point.
   */
  private static function createFocalPointEditLink($fid, $field_name, array $element_selectors, $default_focal_point_value, $image_styles) {
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
          'field_name' => $field_name,
        ],
        [
          'query' => ['focal_point_token' => $token, 'image_styles' => implode(':', $image_styles)],
        ]),
      '#attached' => [
        'library' => ['core/drupal.dialog.ajax'],
      ],
      '#attributes' => [
        'class' => ['use-ajax'],
        'data-selector' => $element_selectors['focal_point'],
        'data-field-name' => $field_name,
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
      $element['focal_point']['#access'] = FALSE;
      $element['preview']['indicator']['#access'] = FALSE;
    }

    if (isset($element['preview']['preview_link'])) {
      $item = $element['#value'];
      $fid = $item['fids'][0];
      $element_selectors = [
        'focal_point' => 'focal-point-' . implode('-', $element['#parents']),
      ];
      $default_focal_point_value = isset($item['focal_point']) ? $item['focal_point'] : $element['#focal_point']['offsets'];

      // Search through $form_state['input'] for target_id => 'media:XX' element. If found
      // use "entity_browser_widget" right next to it to get id that can be used
      // to load image styles for the paragraph.
      $paragraph_type = self::getParagraphInfo($fid, $form_state);

      // Another big assumption here. We assume that Media has view modes named
      // "prgf_<paragraph_name>".
      $display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->load('media.image.prgf_' . $paragraph_type);

      if ($display) {
        $components = $display->getComponents();
        // We assume that view mode displays only single field -- image.
        $image_component = reset($components);

        $used_breakpoints = [
          $image_component['settings']['image_style'],
        ];

        $element['preview']['preview_link'] = [
          '#type' => 'inline_template',
          '#template' => '{{ focal_point_link }}  |  {{ crop_image_link }}',
          '#context' => [
            'focal_point_link' => self::createFocalPointEditLink($fid, $element['#field_name'], $element_selectors, $default_focal_point_value, $used_breakpoints),
            'crop_image_link' => self::createCropLink($fid, $element['#field_name'], $element_selectors, $default_focal_point_value, $used_breakpoints),
          ],
        ];

        $element['preview']['preview_link_note'] = [
          '#type' => 'inline_template',
          '#template' => '<div class="focal-point-preview-link-note">{{ note }}</div>',
          '#context' => [
            'note' => new TranslatableMarkup('Note: Focal Point and Crop Image cannot both be applied to the same image.'),
          ]
        ];
      }

    }

    return $element;
  }

  /**
   * Gets paragraph info data. We do a few assumptions here. We assume that
   * there is a top level fields (references to paragraphs) like field_content,
   * field_header_content etc. Also there should be subforms inside of the
   * paragraphs. So our data is located in structure like:
   * $form_state->getValues()['field_content'][0]['subform']['field_prgf_image']['entity_browser_widget_paragraph_info'];
   *
   * @see OpenYFocalPointEntityReferenceBrowserWidget::formElement() where we
   * create a hidden field 'entity_browser_widget_paragraph_info'.
   */
  protected static function getParagraphInfo($fid, FormStateInterface $form_state) {
    $query = \Drupal::entityTypeManager()->getStorage('media')->getQuery();
    $query->condition('field_media_image', $fid);
    $results = $query->execute();

    $media_names = [];
    foreach ($results as $media_id) {
      $media_names[] = 'media:' . $media_id;
    }

    $form_state_values = $form_state->getUserInput();
    foreach ($form_state_values as $top_level_key => $top_level_item) {
      if (!is_array($top_level_item)) {
        continue;
      }

      foreach ($top_level_item as $second_level_key => $second_level_item) {
        if (!isset($second_level_item['subform'])) {
          continue;
        }

        foreach ($second_level_item['subform'] as $subform_field_name => $subform_field_item) {
          if (isset($subform_field_item['target_id'])
            && in_array($subform_field_item['target_id'], $media_names)
            && isset($subform_field_item['entity_browser_widget_paragraph_info'])) {
            return $subform_field_item['entity_browser_widget_paragraph_info'];
          }
        }
      }
    }
  }

}
