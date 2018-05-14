<?php

namespace Drupal\media_entity_video\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Video Player (HTML5)' formatter.
 *
 * @FieldFormatter(
 *   id = "video_player_html5",
 *   label = @Translation("Video Player (HTML5)"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class VideoPlayerHTML5 extends VideoPlayerBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings['provide_download_link'] = TRUE;
    $settings['video_attributes'] = '';

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['provide_download_link'] = [
      '#title' => $this->t('Provide Download Link'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('provide_download_link'),
    ];

    $form['video_attributes'] = [
      '#title' => $this->t('Video Tag Attributes'),
      '#type' => 'textfield',
      '#description' => $this->t('Give values Like controls preload="auto" loop.'),
      '#default_value' => $this->getSetting('video_attributes'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $provide_download_link = $this->getSetting('provide_download_link');
    $video_attributes = $this->getSetting('video_attributes');

    if ($provide_download_link) {
      $summary[] = $this->t('Download link provided.');
    }

    if ($video_attributes) {
      $summary[] = $this->t('Video tag attributes: @tags.', [
        '@tags' => $video_attributes,
      ]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $provide_download_link = $this->getSetting('provide_download_link');
    $video_attributes = $this->getSetting('video_attributes');
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $item = $file->_referringItem;
      $elements[$delta] = array(
        '#theme' => 'media_video_file_formatter',
        '#file' => $file,
        '#description' => $item->description,
        '#value' => $provide_download_link,
        '#extravalue' => $video_attributes,
        '#cache' => array(
          'tags' => $file->getCacheTags(),
        ),
      );
      // Pass field item attributes to the theme function.
      if (isset($item->_attributes)) {
        $elements[$delta] += array('#attributes' => array());
        $elements[$delta]['#attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }
    return $elements;
  }

}
