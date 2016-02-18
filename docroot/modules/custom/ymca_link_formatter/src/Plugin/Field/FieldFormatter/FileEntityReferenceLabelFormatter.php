<?php

namespace Drupal\ymca_link_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'entity reference label' formatter.
 *
 * @FieldFormatter(
 *   id = "file_entity_reference_label_url",
 *   label = @Translation("File Item Route URL"),
 *   description = @Translation("Display the label of the referenced file entities."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class FileEntityReferenceLabelFormatter extends EntityReferenceFormatterBase {

  /**
   * Entity has been processed.
   *
   * @var \Drupal\file_entity\Entity\FileEntity
   */
  private $entity;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'file_link' => TRUE,
      'file_title' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['file_link'] = array(
      '#title' => t('Link label to the file link'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('file_link'),
    );
    $elements['file_title'] = array(
      '#title' => t('Title for the file link'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('file_title'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = $this->getSetting('file_link') ? t(
      'Link to the referenced file entity'
    ) : t('No link');
    $summary[] = ($this->getSetting('file_title') == '') ? t(
      'File title for the file link'
    ) : t('Custom title for the file link');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $output_as_link = $this->getSetting('file_link');

    foreach ($this->getEntitiesToView(
      $items,
      $langcode
    ) as $delta => $this->entity) {
      $label = ($this->getSetting('file_title') == '') ? $this->entity->label(
      ) : $this->getSetting('file_title');
      // If the link is to be displayed and the entity has a uri, display a
      // link.
      if ($output_as_link && !$this->entity->isNew()) {
        try {
          $uri = $this->entity->getFileUri();
        }
        catch (UndefinedLinkTemplateException $e) {
          // This exception is thrown by \Drupal\Core\Entity\Entity::urlInfo()
          // and it means that the entity type doesn't have a link template nor
          // a valid "uri_callback", so don't bother trying to output a link for
          // the rest of the referenced entities.
          $output_as_link = FALSE;
        }
      }

      if ($output_as_link && isset($uri) && !$this->entity->isNew()) {
        $elements[$delta] = [
          '#type' => 'link',
          '#title' => new FormattableMarkup($label, []),
          '#url' => Url::fromUri(file_create_url($uri)),
        ];

        if (!empty($items[$delta]->_attributes)) {
          $elements[$delta]['#options'] += array('attributes' => array());
          $elements[$delta]['#options']['attributes'] += $items[$delta]->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and shouldn't be rendered in the field template.
          unset($items[$delta]->_attributes);
        }
      }
      else {
        $elements[$delta] = array('#plain_text' => $label);
      }
      $elements[$delta]['#cache']['tags'] = $this->entity->getCacheTags();
    }

    return $elements;
  }

}
