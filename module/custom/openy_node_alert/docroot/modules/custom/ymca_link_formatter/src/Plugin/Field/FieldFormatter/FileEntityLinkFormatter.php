<?php

namespace Drupal\ymca_link_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\file\Plugin\Field\FieldFormatter\BaseFieldFileFormatterBase;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Formatter for a text field on a file entity that links the field to the file.
 *
 * @FieldFormatter(
 *   id = "file_entity_link",
 *   label = @Translation("File Entity link"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class FileEntityLinkFormatter extends BaseFieldFileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['link_to_file'] = TRUE;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // We don't call the parent in order to bypass the link to file form.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function viewValue(FieldItemInterface $item) {
    $fieldItemList = $item->getParent();
    $entityAdapter = $fieldItemList->getParent();
    $entity = $entityAdapter->getValue();
    if (!$entity instanceof FileInterface) {
      return $item->value;
    }
    if (!$entity->isNew()) {
      $uri = 'file/' . $entity->id() . '/edit';
    }
    if (isset($uri) && !$entity->isNew()) {
      $file_entity_link = [
        '#type' => 'link',
        '#title' => new FormattableMarkup($entity->label(), []),
        '#url' => Url::fromUri(file_create_url($uri)),
        '#attributes' => [
          'target' => '_blank',
        ],
      ];
    }
    else {
      $file_entity_link = array('#plain_text' => $entity->label);
    }
    $file_entity_link['#cache']['tags'] = $entity->getCacheTags();

    return $file_entity_link;
  }

}
