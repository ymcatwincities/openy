<?php

/**
 * @file
 * Contains Drupal\ymca_link_formatter\Plugin\Field\FieldFormatter\LinkAdvancedFormatter.
 */

namespace Drupal\ymca_link_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Plugin implementation of the 'link_advanced_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "link_advanced_formatter",
 *   label = @Translation("Link advanced formatter"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkAdvancedFormatter extends LinkFormatter {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'classes' => ''
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['classes'] = array(
      '#type' => 'textfield',
      '#title' => t('CSS classes'),
      '#default_value' => $this->getSetting('classes'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $settings = $this->getSettings();

    if (!empty($settings['classes'])) {
      $summary[] = t('Additional classes: <em>@classes</em>', ['@classes' => $settings['classes']]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    foreach ($items as &$item) {
      if (!empty($item->_attributes['class'])) {
        $item->_attributes['class'][] = $this->getSetting('classes');
      }
      else {
        $item->_attributes += ['class' => [$this->getSetting('classes')]];
      }
    }

    return parent::viewElements($items, $langcode);
  }

}
