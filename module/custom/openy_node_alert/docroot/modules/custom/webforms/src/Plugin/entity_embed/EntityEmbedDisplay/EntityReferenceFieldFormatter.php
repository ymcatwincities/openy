<?php

namespace Drupal\webforms\Plugin\entity_embed\EntityEmbedDisplay;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_embed\EntityEmbedDisplay\FieldFormatterEntityEmbedDisplayBase;

/**
 * Entity Embed Display reusing entity reference field formatters.
 *
 * @see \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayInterface
 *
 * @EntityEmbedDisplay(
 *   id = "entity_reference",
 *   label = @Translation("Entity Reference"),
 *   deriver = "Drupal\entity_embed\Plugin\Derivative\FieldFormatterDeriver",
 *   field_type = "entity_reference"
 * )
 */
class EntityReferenceFieldFormatter extends FieldFormatterEntityEmbedDisplayBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinition() {
    if (!isset($this->fieldDefinition)) {
      $this->fieldDefinition = parent::getFieldDefinition();
      $this->fieldDefinition->setSetting('target_type', $this->getEntityTypeFromContext());
    }
    return $this->fieldDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldValue() {
    return array('target_id' => $this->getContextValue('entity')->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatter() {
    if (!isset($this->fieldFormatter)) {
      $display = array(
        'type' => $this->getDerivativeId(),
        'settings' => $this->getConfiguration(),
        'label' => 'hidden',
      );

      // Alter webform formatter.
      $target_type = $this->getAttributeValue('data-entity-type');
      if ($target_type == 'contact_form') {
        $display['type'] = 'entity_reference_entity_view_webform';
      }

      // Create the formatter plugin. Will use the default formatter for that
      // field type if none is passed.
      $this->fieldFormatter = $this->formatterPluginManager->getInstance(
        array(
          'field_definition' => $this->getFieldDefinition(),
          'view_mode' => '_entity_embed',
          'configuration' => $display,
        )
      );
    }

    return $this->fieldFormatter;
  }

}
