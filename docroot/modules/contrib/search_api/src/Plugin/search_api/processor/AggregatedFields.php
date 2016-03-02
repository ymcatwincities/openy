<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\search_api\processor\AggregatedFields.
 */

namespace Drupal\search_api\Plugin\search_api\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Property\BasicProperty;
use Drupal\search_api\Utility;

/**
 * @SearchApiProcessor(
 *   id = "aggregated_field",
 *   label = @Translation("Aggregated fields"),
 *   description = @Translation("Add customized aggregations of existing fields to the index."),
 *   stages = {
 *     "preprocess_index" = -25
 *   }
 * )
 */
class AggregatedFields extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'fields' => array(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';
    $form['description'] = array(
      '#markup' => $this->t('This data alteration lets you define additional fields that will be added to this index. Each of these new fields will be an aggregation of one or more existing fields.<br />To add a new aggregated field, click the "Add new field" button and then fill out the form.<br />To remove a previously defined field, click the "Remove field" button.<br />You can also change the names or contained fields of existing aggregated fields.'),
    );

    $this->buildFieldsForm($form, $form_state);

    $form['actions']['#type'] = 'actions';
    $form['actions'] = array(
      '#type' => 'actions',
      'add' => array(
        '#type' => 'submit',
        '#value' => $this->t('Add new Field'),
        '#submit' => array(array($this, 'submitAjaxFieldButton')),
        '#limit_validation_errors' => array(),
        '#name' => 'add_aggregation_field',
        '#ajax' => array(
          'callback' => array($this, 'buildAjaxAddFieldButton'),
          'wrapper' => 'search-api-alter-add-aggregation-field-settings',
        ),
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  // @todo Make sure this works both with and without Javascript.
  public function buildFieldsForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->has('fields')) {
      $form_state->set('fields', $this->configuration['fields']);
    }
    $form_state_fields = $form_state->get('fields');

    // Check if we need to add a new field, or remove one.
    $triggering_element = $form_state->getTriggeringElement();
    if (isset($triggering_element['#name'])) {
      drupal_set_message(t('Changes in this form will not be saved until the %button button at the form bottom is clicked.', array('%button' => t('Save'))), 'warning');
      $button_name = $triggering_element['#name'];
      if ($button_name == 'add_aggregation_field') {
        // Increment $i until the corresponding field is not set, then create
        // the field with that number as suffix.
        for ($i = 1; isset($form_state_fields['search_api_aggregation_' . $i]); ++$i) {
        }
        $form_state_fields['search_api_aggregation_' . $i] = array(
          'label' => '',
          'type' => 'union',
          'fields' => array(),
        );
      }
      else {
        // Get the field ID from the button name.
        $field_id = substr($button_name, 25);
        unset($form_state_fields[$field_id]);
      }
      $form_state->set('fields', $form_state_fields);
    }

    // Get index type descriptions.
    $type_descriptions = $this->getTypeDescriptions();
    $types = $this->getTypes();

    // Get the available fields for this index.
    $fields = $this->index->getFields(FALSE);
    $field_options = array();
    $field_properties = array();

    // Annotate them so we can show them cleanly in the UI.
    // @todo Use option groups to group fields by datasource?
    /** @var \Drupal\search_api\Item\FieldInterface[] $fields */
    foreach ($fields as $field_id => $field) {
      $field_options[$field_id] = $field->getPrefixedLabel();
      $field_properties[$field_id] = array(
        '#attributes' => array('title' => $field_id),
        '#description' => $field->getDescription(),
      );
    }
    ksort($field_options);

    $form['fields'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'id' => 'search-api-alter-add-aggregation-field-settings',
      ),
      '#tree' => TRUE,
    );

    foreach ($form_state_fields as $field_id => $field) {
      $new = !$field['label'];
      $form['fields'][$field_id] = array(
        '#type' => 'details',
        '#title' => $new ? $this->t('New field') : $field['label'],
        '#open' => $new,
      );
      $form['fields'][$field_id]['label'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('New field name'),
        '#default_value' => $field['label'],
        '#required' => TRUE,
      );
      $form['fields'][$field_id]['type'] = array(
        '#type' => 'select',
        '#title' => $this->t('Aggregation type'),
        '#options' => $types,
        '#default_value' => $field['type'],
        '#required' => TRUE,
      );

      $form['fields'][$field_id]['type_descriptions'] = $type_descriptions;
      foreach (array_keys($types) as $type) {
        // @todo This shouldn't rely on undocumented form array structure.
        $form['fields'][$field_id]['type_descriptions'][$type]['#states']['visible'][':input[name="processors[aggregated_field][settings][fields][' . $field_id . '][type]"]']['value'] = $type;
      }

      // @todo Order checked fields first in list?
      $form['fields'][$field_id]['fields'] = array_merge($field_properties, array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Contained fields'),
        '#options' => $field_options,
        '#default_value' => $field['fields'],
        '#attributes' => array('class' => array('search-api-checkboxes-list')),
        '#required' => TRUE,
      ));

      $form['fields'][$field_id]['actions'] = array(
        '#type' => 'actions',
        'remove' => array(
          '#type' => 'submit',
          '#value' => $this->t('Remove field'),
          '#submit' => array(array($this, 'submitAjaxFieldButton')),
          '#limit_validation_errors' => array(),
          '#name' => 'remove_aggregation_field_' . $field_id,
          '#ajax' => array(
            'callback' => array($this, 'buildAjaxAddFieldButton'),
            'wrapper' => 'search-api-alter-add-aggregation-field-settings',
          ),
        ),
      );
    }
  }

  /**
   * Retrieves form elements with the descriptions of all aggregation types.
   *
   * @return array
   *   An array containing form elements with the descriptions of all
   *   aggregation types.
   */
  protected function getTypeDescriptions() {
    $form = array();
    foreach ($this->getTypes('description') as $type => $description) {
      $form[$type] = array(
        '#type' => 'item',
        '#description' => $description,
      );
    }
    return $form;
  }

  /**
   * Retrieves information about available aggregation types.
   *
   * @param string $info
   *   (optional) One of "label", "type" or "description", to indicate what
   *   values should be returned for the types.
   *
   * @return array
   *   An array of the identifiers of the available types mapped to, depending
   *   on $info, their labels, their data types or their descriptions.
   */
  protected function getTypes($info = 'label') {
    switch ($info) {
      case 'label':
        return array(
          'union' => $this->t('Union'),
          'concat' => $this->t('Concatenation'),
          'sum' => $this->t('Sum'),
          'count' => $this->t('Count'),
          'max' => $this->t('Maximum'),
          'min' => $this->t('Minimum'),
          'first' => $this->t('First'),
        );
      case 'type':
        return array(
          'union' => 'string',
          'concat' => 'string',
          'sum' => 'integer',
          'count' => 'integer',
          'max' => 'integer',
          'min' => 'integer',
          'first' => 'string',
        );
      case 'description':
        return array(
          'union' => $this->t('The Union aggregation does an union operation of all the values of the field. 2 fields with 2 values each become 1 field with 4 values.'),
          'concat' => $this->t('The Concatenation aggregation concatenates the text data of all contained fields.'),
          'sum' => $this->t('The Sum aggregation adds the values of all contained fields numerically.'),
          'count' => $this->t('The Count aggregation takes the total number of contained field values as the aggregated field value.'),
          'max' => $this->t('The Maximum aggregation computes the numerically largest contained field value.'),
          'min' => $this->t('The Minimum aggregation computes the numerically smallest contained field value.'),
          'first' => $this->t('The First aggregation will simply keep the first encountered field value.'),
        );
    }
    return array();
  }

  /**
   * Form submission handler for this processor form's AJAX buttons.
   */
  public static function submitAjaxFieldButton(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * Handles adding or removing of aggregated fields via AJAX.
   */
  public static function buildAjaxAddFieldButton(array $form, FormStateInterface $form_state) {
    return $form['settings']['aggregated_field']['fields'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (empty($values['fields'])) {
      return;
    }
    foreach ($values['fields'] as $field_id => &$field) {
      if ($field['label'] && !$field['fields']) {
        $error_message = $this->t('You have to select at least one field to aggregate.');
        $form_state->setError($form['fields'][$field_id]['fields'], $error_message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Remove the unnecessary form_state values, so no overhead is stored.
    unset($values['actions']);
    foreach ($values['fields'] as &$field_definition) {
      unset($field_definition['type_descriptions'], $field_definition['actions']);
      $field_definition['fields'] = array_values(array_filter($field_definition['fields']));
    }

    $form_state->setValues($values);
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array &$items) {
    if (!$items) {
      return;
    }
    if (isset($this->configuration['fields'])) {
      /** @var \Drupal\search_api\Item\ItemInterface[] $items */
      foreach ($items as $item) {
        foreach ($this->configuration['fields'] as $aggregated_field_id => $aggregated_field) {
          if ($aggregated_field['label']) {
            if (!$item->getField($aggregated_field_id, FALSE)) {
              continue;
            }
            // Extract the selected fields to aggregate from the settings.
            $required_fields = array();
            // @todo Don't do this once for every item, compute fields per
            //   datasource right away.
            foreach ($aggregated_field['fields'] as $field_id_to_aggregate) {
              // Only include valid and selected fields to aggregate.
              if (!isset($required_fields[$field_id_to_aggregate]) && !empty($field_id_to_aggregate)) {
                // Make sure we only get fields from the datasource of the
                // current item.
                list($datasource_id) = Utility::splitCombinedId($field_id_to_aggregate);
                if (!$datasource_id || $datasource_id == $item->getDatasourceId()) {
                  $required_fields[$field_id_to_aggregate] = $field_id_to_aggregate;
                }
              }
            }

            // Get all the available field values.
            $given_fields = array();
            foreach ($required_fields as $required_field_id) {
              $field = $item->getField($required_field_id);
              if ($field && $field->getValues()) {
                $given_fields[$required_field_id] = $field;
                unset($required_fields[$required_field_id]);
              }
            }

            $missing_fields = array();
            // Get all the missing field values.
            foreach ($required_fields as $required_field_id) {
              $field = Utility::createField($this->index, $required_field_id);
              $missing_fields[$field->getPropertyPath()] = $field;
            }
            // Get the value from the original objects in to the fields
            if ($missing_fields) {
              Utility::extractFields($item->getOriginalObject(), $missing_fields);
            }

            $fields = array_merge($given_fields, $missing_fields);

            $values = array();
            /** @var \Drupal\search_api\Item\FieldInterface[] $fields */
            foreach ($fields as $field) {
              $values = array_merge($values, $field->getValues());
            }

            switch ($aggregated_field['type']) {
              case 'concat':
                $values = array(implode("\n\n", $values));
                break;
              case 'sum':
                $values = array(array_sum($values));
                break;
              case 'count':
                $values = array(count($values));
                break;
              case 'max':
                $values = array(max($values));
                break;
              case 'min':
                $values = array(min($values));
                break;
              case 'first':
                if ($values) {
                  $values = array(reset($values));
                }
                break;
            }

            $item->getField($aggregated_field_id)->setValues($values);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterPropertyDefinitions(array &$properties, DatasourceInterface $datasource = NULL) {
    if ($datasource) {
      return;
    }
    $types = $this->getTypes('type');
    if (isset($this->configuration['fields'])) {
      $index_fields = $this->index->getFields(FALSE);
      foreach ($this->configuration['fields'] as $field_id => $field) {
        $definition = array(
          'label' => $field['label'],
          'description' => $this->fieldDescription($field, $index_fields),
          'type' => $types[$field['type']],
        );
        $properties[$field_id] = BasicProperty::createFromDefinition($definition)
          ->setIndexedLocked();
      }
    }
  }

  /**
   * Creates a description for an aggregated field.
   *
   * @param array $field
   *   The settings of the aggregated field.
   * @param \Drupal\search_api\Item\FieldInterface[] $index_fields
   *   The index's fields.
   *
   * @return string
   *   A description for the given aggregated field.
   */
  protected function fieldDescription(array $field, array $index_fields) {
    $fields = array();
    foreach ($field['fields'] as $f) {
      $fields[] = isset($index_fields[$f]) ? $index_fields[$f]->getPrefixedLabel() : $f;
    }
    $type = $this->getTypes();
    $type = $type[$field['type']];
    return $this->t('A @type aggregation of the following fields: @fields.', array('@type' => $type, '@fields' => implode(', ', $fields)));
  }

}
