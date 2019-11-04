<?php

namespace Drupal\openy_focal_point\Form;

use Drupal\Component\Utility\Random;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\crop\Entity\Crop;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\openy_focal_point\Ajax\RerenderThumbnailCommand;

/**
 * Form to create/edit crops in widget's preview popup.
 */
class OpenYFocalPointCropForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_focal_point_crop';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $build_info = $form_state->getBuildInfo();
    $file = $build_info['args'][0];
    $image_styles = $build_info['args'][1];
    $focal_point_value = $build_info['args'][2];

    $form_state->set('file', $file);

    $random = new Random();

    foreach ($image_styles as $style) {
      $style_label = $style->get('label');
      // We add random to get parameter so every time Preview popup is loaded
      // fresh images are regenerated and browser cache is bypassed. So if
      // we edit crop settings, save them and open Preview popup once again
      // images are regenerated.
      $focal_point_value .= '-' . $random->name();
      $url = $this->buildUrl($style, $file, $focal_point_value);

      $derivative_images[$style->id()] = [
        'style' => $style_label,
        'url' => $url,
        'image' => [
          '#theme' => 'image',
          '#uri' => $url,
          '#alt' => $this->t('OpenY Focal Point Preview: %label', ['%label' => $style_label]),
          '#attributes' => [
            'class' => ['focal-point-derivative-preview-image'],
          ],
        ],
      ];

      $form['openy_focal_point_preview'] = [
        '#theme' => "openy_focal_point_preview",
        '#data' => [
          'derivative_images' => $derivative_images,
        ]
      ];

      // We assume that the last effect is a manual crop.
      $effects = $style->getEffects()->getConfiguration();
      $manual = array_pop($effects);
      $crop_type = $manual['data']['crop_type'];

      $form_state->set('crop_type', $crop_type);

      $form[$style->id()] = [
        '#type' => 'image_crop',
        '#file' => $file,
        '#crop_type_list' => [$crop_type],
        '#crop_preview_image_style' => 'crop_thumbnail',
        '#show_default_crop' => FALSE,
        '#show_crop_area' => TRUE,
        '#warn_multiple_usages' => FALSE,
        '#crop_types_required' => [$crop_type],
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Crop'),
      '#ajax' => [
        'callback' => '::ajaxSave',
      ],
    ];

    $form['remove'] = [
      '#type' => 'submit',
      '#value' => $this->t('Remove cropping (use default focal point)'),
      '#ajax' => [
        'callback' => '::ajaxRemoveCropping',
      ],
    ];

    $form['close_dialog'] = [
      '#type' => 'submit',
      '#value' => $this->t('Close'),
      '#ajax' => [
        'callback' => '::closePopup',
      ],
    ];

    $form['#attached']['library'][] = 'openy_focal_point/openy_focal_point';

    return $form;
  }

  public static function ajaxSave(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\file\Entity\File $file */
    $file = $form_state->get('file');

    $crop_type = $form_state->get('crop_type');

    $input = $form_state->getUserInput();
    $crop_properties = $input['prgf_teaser']['crop_wrapper'][$crop_type]['crop_container']['values'];
    $x = (int) ($crop_properties['x'] + $crop_properties['width'] / 2);
    $y = (int) ($crop_properties['y'] + $crop_properties['height'] / 2);

    $crop = Crop::findCrop($file->getFileUri(), $crop_type);
    if ($crop) {
      if ($crop_properties['height'] == 0 && $crop_properties['width'] == 0) {
        $crop->delete();
        $crop = NULL;
      }
      else {
        $crop->setSize($crop_properties['width'], $crop_properties['height']);
        $crop->setPosition($x, $y);
      }
    }
    else {
      $crop_storage = \Drupal::entityTypeManager()->getStorage('crop');
      $crop = $crop_storage->create([
        'type' => $crop_type,
        'entity_id' => $file->id(),
        'entity_type' => 'file',
        'uri' => $file->getFileUri(),
        'x' => $x,
        'y' => $y,
        'width' => $crop_properties['width'],
        'height' => $crop_properties['height'],
      ]);
    }

    if ($crop) {
      $crop->save();
    }

    $image = \Drupal::service('image.factory')->get($file->getFileUri());
    image_path_flush($image->getSource());

    $ajax = new AjaxResponse();
    $ajax->addCommand(new RerenderThumbnailCommand('.focal-point-derivative-preview-image'));
    return $ajax;
  }

  public static function ajaxRemoveCropping(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\file\Entity\File $file */
    $file = $form_state->get('file');

    $crop_type = $form_state->get('crop_type');

    $crop = Crop::findCrop($file->getFileUri(), $crop_type);
    if ($crop) {
        $crop->delete();
    }

    $image = \Drupal::service('image.factory')->get($file->getFileUri());
    image_path_flush($image->getSource());

    $ajax = new AjaxResponse();
    $ajax->addCommand(new CloseDialogCommand());
    return $ajax;
  }

  public static function closePopup(array $form, FormStateInterface $form_state) {
    $ajax = new AjaxResponse();
    $ajax->addCommand(new CloseDialogCommand());
    return $ajax;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Create the URL for a preview image including a query parameter.
   *
   * @param \Drupal\image\Entity\ImageStyle $style
   *   The image style being previewed.
   * @param \Drupal\file\Entity\File $image
   *   The image being previewed.
   * @param string $focal_point_value
   *   The focal point being previewed in the format XxY where x and y are the
   *   left and top offsets in percentages.
   *
   * @return \Drupal\Core\GeneratedUrl|string
   *   The URL of the preview image.
   */
  protected function buildUrl(ImageStyle $style, File $image, $focal_point_value) {
    $url = $style->buildUrl($image->getFileUri());
    $url .= (strpos($url, '?') !== FALSE ? '&' : '?') . 'focal_point_preview_value=' . $focal_point_value;

    return $url;
  }

}
