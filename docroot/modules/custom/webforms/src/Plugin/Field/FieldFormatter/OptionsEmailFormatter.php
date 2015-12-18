<?php

/**
 * @file
 * Contains Ymca office hours formatter.
 */

namespace Drupal\webforms\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ymca_office_hours' formatter.
 *
 * @FieldFormatter(
 *   id = "options_emails_formatter",
 *   label = @Translation("Options email formatter"),
 *   field_types = {
 *     "options_email_item"
 *   }
 * )
 */
class OptionsEmailFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

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
      $configuration['third_party_settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

      $elements[$delta] = [
        '#title' => t('Option name'),
        'type' => 'textfield',
        '#required' => TRUE,
      ];

    return $elements;
  }

}
