<?php

/**
 * @file
 * Contains \Drupal\file_browser\Plugin\Field\FieldFormatter\EntityReferenceImageFormatter.
 */

namespace Drupal\file_browser\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Renders any entity reference field as rendered images.
 *
 * Most of this code is copied from the original ImageFormatter class.
 *
 * @FieldFormatter(
 *   id = "entity_reference_image",
 *   label = @Translation("Rendered file entity as Image"),
 *   description = @Translation("Display the referenced file entities as images."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceImageFormatter extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'image_style' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);

    \Drupal::configFactory()->getEditable('entity_browser.browser.browse_files')->getRawData();

    $link_generator = \Drupal::service('link_generator');
    $user = \Drupal::currentUser();

    $element['image_style'] = array(
      '#title' => t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
      '#description' => array(
        '#markup' => $link_generator->generate($this->t('Configure Image Styles'), new Url('entity.image_style.collection')),
        '#access' => $user->hasPermission('administer image styles'),
      ),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $image_styles = image_style_options(FALSE);

    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $this->getSetting('image_style');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = t('Image style: @style', array('@style' => $image_styles[$image_style_setting]));
    }
    else {
      $summary[] = t('Original image');
    }

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

    // Get the current image style
    $image_style_setting = $this->getSetting('image_style');

    foreach ($files as $delta => $file) {
      // Confirm that this file is an image
      $image_factory = \Drupal::service('image.factory');
      $image = $image_factory->get($file->getFileUri());

      if ($image->isValid()) {
        // Fake an ImageItem object as we're not using that field type
        $item = new \stdClass();
        $item->width = $image->getWidth();
        $item->height = $image->getHeight();
        $item->alt = '';
        $item->title = $file->getFilename();
        $item->entity = $file;

        $elements[$delta] = array(
          '#theme' => 'image_formatter',
          '#item' => $item,
          '#item_attributes' => [],
          '#image_style' => $image_style_setting,
        );
      }
    }

    return $elements;
  }
}
