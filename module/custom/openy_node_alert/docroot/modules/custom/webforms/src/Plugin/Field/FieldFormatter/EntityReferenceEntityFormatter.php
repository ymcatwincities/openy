<?php

namespace Drupal\webforms\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter as CoreEntityReferenceEntityFormatter;
use Drupal\Component\Serialization\Json;

/**
 * Plugin implementation of the 'entity reference rendered entity' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_entity_view_webform",
 *   label = @Translation("Rendered webform entity"),
 *   description = @Translation("Display the referenced entities rendered by entity_view()."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceEntityFormatter extends CoreEntityReferenceEntityFormatter implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'default_value' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $embed_settings = Json::decode($form_state->getStorage()['entity_element']['data-entity-embed-settings']);
    $entity = $form_state->getStorage()['entity'];

    $storage = \Drupal::entityTypeManager()->getStorage('contact_message');
    $message = $storage->create(['contact_form' => $entity->id()]);
    $fields_definitions = $message->getFieldDefinitions();

    // Go thru option email fields.
    foreach ($fields_definitions as $field_name => $field_definition) {
      if ($field_definition->getType() != 'options_email_item') {
        continue;
      }

      // Prepare list of options.
      $field_default_values = $field_definition->get('default_value');
      array_walk($field_default_values, function (&$item) {
        $item = $item['option_name'];
      });
      $options = $field_default_values;
      $options[-1] = $this->t('- none -');
      ksort($options);

      // Dialog form field to select default value.
      $elements[$field_name] = array(
        '#type' => 'select',
        '#title' => $this->t('Default value for %fieldname', ['%fieldname' => $field_name]),
        '#options' => $options,
        '#required' => FALSE,
      );

      if (!empty($embed_settings[$field_name])) {
        $elements[$field_name]['#default_value'] = $embed_settings[$field_name];
      }
    }

    return $elements;
  }

}
