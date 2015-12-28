<?php

/**
 * @file
 * Contains YMCA office hours widget.
 */

namespace Drupal\webforms\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormState;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\ymca_field_office_hours\Plugin\Field\FieldType\YmcaOfficeHoursItem;

/**
 * Plugin implementation of the 'ymca_office_hours' widget.
 *
 * @FieldWidget(
 *   id = "options_email_default",
 *   label = @Translation("Options email"),
 *   field_types = {
 *     "options_email_item"
 *   }
 * )
 */
class OptionsEmailWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    /** @var YmcaOfficeHoursItem $item */
    $item = $items->get($delta);
    if (!$this->isDefaultValueWidget($form_state)) {
      $def = $this->fieldDefinition->getDefaultValue($item->getEntity());
      $options = [];
      foreach ($def as $id => $item_data) {
        $options[$id] = $item_data['option_name'];
      }

      $element['option_emails'] = array(
        '#type' => 'select',
        '#title' => $this->t('Options'),
        '#options' => $options,
        // @todo '#default_value' => 1,
        '#attributes' => array('class' => array('langcode-input')),
      );

      // Add our custom validator.
      $element['#element_validate'][] = array(get_class($this), 'validateElement');

      return $element;

    }

    /** @var FieldConfig $definition */
    $definition = $item->getFieldDefinition();

    // Add field title.
    $element['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#default_value' => isset($item->title) ? $item->title : '',
      '#value' => $definition->label(),
    ];

    $element['option_name'] = [
      '#title' => t('Option name'),
      '#type' => 'textfield',
      '#default_value' => isset($item->option_name) ? $item->option_name : '',
      '#required' => FALSE,
    ];
    $element['option_emails'] = [
      '#title' => t('Emails'),
      '#type' => 'textfield',
      '#default_value' => isset($item->option_emails) ? $item->option_emails : '',
      '#required' => FALSE,
    ];
    $element['option_select'] = [
      '#type' => 'checkbox',
      '#default_value' => isset($item->option_select) ? $item->option_select : '',
      '#required' => FALSE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {

    // We should display only single value for non default settings form.
    if (!$this->isDefaultValueWidget($form_state)) {
      $get_delta = 0;
    }
    return parent::form($items, $form, $form_state, $get_delta);
  }

  /**
   * Custom element validation.
   *
   * @param array $element
   *   Element to be validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state at the time of validation.
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    // Getting field name.
    $field_name = array_shift($element['#parents']);
    // Getting index of select item.
    $loc_index = $form_state->getValue($field_name);
    // Form build info.
    $bi = $form_state->getBuildInfo();
    /** @var \Drupal\contact\MessageForm $callback */
    $callback = $bi['callback_object'];
    /** @var \Drupal\contact\Entity\Message $message */
    $message = $callback->getEntity();
    /** @var \Drupal\contact\Entity\ContactForm $contact_form */
    $contact_form = $message->getContactForm();
    /** @var \Drupal\Core\Field\FieldItemList $add_recipients */
    $add_recipients = $message->get($field_name);
    /** @var \Drupal\webforms\Plugin\Field\FieldType\OptionsEmailItem $email_item */
    $email_item = $add_recipients->get($loc_index[0]['option_emails']);
    // Get recipients from field data. @todo use ContactForm validation.
    $recipients = array_map('trim', explode(',', $email_item->get('option_emails')->getValue()));
    // Get recipients from contact form entity.
    $form_recipients = $contact_form->getRecipients();
    // Merge recipients, based on user selection.
    $new_recipients = array_unique(array_filter(array_merge($form_recipients, $recipients)));
    // Set recipients for sending out emails.
    $contact_form->setRecipients($new_recipients);
  }

  /**
   * Special handling to create form elements for multiple values.
   *
   * Handles generic features for multiple fields:
   * - number of widgets
   * - AHAH-'add more' button
   * - table display and drag-n-drop value reordering.
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $parents = $form['#parents'];

    // Determine the number of widgets to display.
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
        $field_state = static::getWidgetState($parents, $field_name, $form_state);
        $max = $field_state['items_count'];
        $is_multiple = TRUE;
        break;

      default:
        $max = $cardinality - 1;
        $is_multiple = ($cardinality > 1);
        break;
    }

    $title = $this->fieldDefinition->getLabel();
    $description = FieldFilteredMarkup::create(\Drupal::token()->replace($this->fieldDefinition->getDescription()));

    $elements = array();

    for ($delta = 0; $delta <= $max; $delta++) {
      // Add a new empty item if it doesn't exist yet at this delta.
      if (!isset($items[$delta])) {
        $items->appendItem();
      }

      // For multiple fields, title and description are handled by the wrapping
      // table.
      if ($is_multiple) {
        $element = [
          '#title' => $this->t('@title (value @number)', ['@title' => $title, '@number' => $delta + 1]),
          '#title_display' => 'invisible',
          '#description' => '',
        ];
      }
      else {
        $element = [
          '#title' => $title,
          '#title_display' => 'before',
          '#description' => $description,
        ];
      }

      $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

      if ($element) {
        // Input field for the delta (drag-n-drop reordering).
        if ($is_multiple) {
          // We name the element '_weight' to avoid clashing with elements
          // defined by widget.
          $element['_weight'] = array(
            '#type' => 'weight',
            '#title' => $this->t('Weight for row @number', array('@number' => $delta + 1)),
            '#title_display' => 'invisible',
            // Note: this 'delta' is the FAPI #type 'weight' element's property.
            '#delta' => $max,
            '#default_value' => $items[$delta]->_weight ?: $delta,
            '#weight' => 100,
          );
        }

        $elements[$delta] = $element;
      }
    }

    if ($elements) {
      $elements += array(
        '#theme' => 'field_multiple_value_form',
        '#field_name' => $field_name,
        '#cardinality' => $cardinality,
        '#cardinality_multiple' => $this->fieldDefinition->getFieldStorageDefinition()->isMultiple(),
        '#required' => $this->fieldDefinition->isRequired(),
        '#title' => $title,
        '#description' => $description,
        '#max_delta' => $max,
      );

      // Add 'add more' button, if not working with a programmed form.
      if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && !$form_state->isProgrammed()) {
        $id_prefix = implode('-', array_merge($parents, array($field_name)));
        $wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper');
        $elements['#prefix'] = '<div id="' . $wrapper_id . '">';
        $elements['#suffix'] = '</div>';

        $elements['add_more'] = array(
          '#type' => 'submit',
          '#name' => strtr($id_prefix, '-', '_') . '_add_more',
          '#value' => t('Add another item'),
          '#attributes' => array('class' => array('field-add-more-submit')),
          '#limit_validation_errors' => array(array_merge($parents, array($field_name))),
          '#submit' => array(array(get_class($this), 'addMoreSubmit')),
          '#ajax' => array(
            'callback' => array(get_class($this), 'addMoreAjax'),
            'wrapper' => $wrapper_id,
            'effect' => 'fade',
          ),
        );
        // @todo place this form element to default form
        $elements['prepopulate_locations'] = [
          '#type' => 'submit',
          '#value' => t('Pre-populate with Locations'),
          '#name' => strtr($id_prefix, '-', '_') . '_add_more',
          '#attributes' => array('class' => array('field-add-more-submit')),
          '#limit_validation_errors' => array(array_merge($parents, array($field_name))),
          '#ajax' => [
            'callback' => [get_class($this), 'addLocationsAjax'],
            'wrapper' => $wrapper_id,
            'effect' => 'fade',
          ],
          '#submit' => [
            'callback' => [get_class($this), 'addLocationsSubmit']
          ]
        ];
        $elements['remove_items'] = [
          '#type' => 'submit',
          '#value' => t('Remove selected items'),
          '#name' => strtr($id_prefix, '-', '_') . '_remove_items',
          '#attributes' => array('class' => array('field-remove-items-submit')),
          '#limit_validation_errors' => array(array_merge($parents, array($field_name))),
          '#ajax' => [
            'callback' => [get_class($this), 'removeItemsAjax'],
            'wrapper' => $wrapper_id,
            'effect' => 'fade',
          ],
          '#submit' => [
            'callback' => [get_class($this), 'removeItemsSubmit']
          ]
        ];
      }
    }

    return $elements;
  }

  /**
   * Generates the form element for a single copy of the widget.
   */
  protected function formSingleElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    if ($items->getName() == 'field_what_is_your_preferred_y_l') {
      if ($form_state->getValue('remove_items') == TRUE) {
        $items_to_be_removed = $form_state->getValue('items_to_be_removed');

        $values = $form_state->getValue('default_value_input');
        $values['field_what_is_your_preferred_y_l'] = array_intersect_key($values['field_what_is_your_preferred_y_l'], array_flip(array_filter(array_keys($values['field_what_is_your_preferred_y_l']), 'is_numeric')));

        if (in_array($delta, $items_to_be_removed)) {
          $item = $items->get($delta);
          $items->set($delta, array());
        }
      }

      if ($form_state->getValue('locations') == TRUE) {
        // Get all locations from form_state.
        $location_entities = $form_state->getValue('location_entities');
        $location_entities = array_values($location_entities);
        $values = $form_state->getValue('default_value_input');
        $values['field_what_is_your_preferred_y_l'] = array_intersect_key($values['field_what_is_your_preferred_y_l'], array_flip(array_filter(array_keys($values['field_what_is_your_preferred_y_l']), 'is_numeric')));
        $existed_items_count = count($values['field_what_is_your_preferred_y_l']);
        $delta >= $existed_items_count ? $id = $delta - $existed_items_count : '';
        if (isset($location_entities[$id])) {
          $title = $location_entities[$id]->getTitle();
          $item_values = $items->getValue();
          $item_values[$delta] = array(
            'option_name' => $title,
            'option_emails' => '',
            'option_select' => FALSE,
          );
          $items->setValue($item_values);
        }
      }
    }
    $entity = $items->getEntity();

    $element += array(
      '#field_parents' => $form['#parents'],
      // Only the first widget should be required.
      '#required' => $delta == 0 && $this->fieldDefinition->isRequired(),
      '#delta' => $delta,
      '#weight' => $delta,
    );
    $element = $this->formElement($items, $delta, $element, $form, $form_state);

    if ($element) {
      // Allow modules to alter the field widget form element.
      $context = array(
        'form' => $form,
        'widget' => $this,
        'items' => $items,
        'delta' => $delta,
        'default' => $this->isDefaultValueWidget($form_state),
      );
      \Drupal::moduleHandler()->alter(array('field_widget_form', 'field_widget_' . $this->getPluginId() . '_form'), $element, $form_state, $context);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();

    // Extract the values from $form_state->getValues().
    $path = array_merge($form['#parents'], array($field_name));
    $key_exists = NULL;
    $values = NestedArray::getValue($form_state->getValues(), $path, $key_exists);

    if ($key_exists) {
      // Account for drag-and-drop reordering if needed.
      if (!$this->handlesMultipleValues()) {
        // Remove the 'value' of the 'add more' button.
        unset($values['add_more']);
        unset($values['prepopulate_locations']);
        unset($values['remove_items']);

        // The original delta, before drag-and-drop reordering, is needed to
        // route errors to the correct form element.
        foreach ($values as $delta => &$value) {
          $value['_original_delta'] = $delta;
        }

        usort($values, function ($a, $b) {
          return SortArray::sortByKeyInt($a, $b, '_weight');
        });
      }

      // Let the widget massage the submitted values.
      $values = $this->massageFormValues($values, $form, $form_state);

      // Assign the values and remove the empty ones.
      $items->setValue($values);
      $items->filterEmptyItems();

      // Put delta mapping in $form_state, so that flagErrors() can use it.
      $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
      foreach ($items as $delta => $item) {
        $field_state['original_deltas'][$delta] = isset($item->_original_delta) ? $item->_original_delta : $delta;
        unset($item->_original_delta, $item->_weight);
      }
      static::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
    }
  }

  /**
   * Ajax callback for the "Pre-populate with Locations" button.
   *
   * This returns the new page content to replace the page content made obsolete
   * by the form submission.
   */
  public function addLocationsAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));

    // Ensure the widget allows adding additional items.
    if ($element['#cardinality'] != FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      return;
    }

    // Add a DIV around the delta receiving the Ajax effect.
    $delta = $element['#max_delta'];
    $element[$delta]['#prefix'] = '<div class="ajax-new-content">' . (isset($element[$delta]['#prefix']) ? $element[$delta]['#prefix'] : '');
    $element[$delta]['#suffix'] = (isset($element[$delta]['#suffix']) ? $element[$delta]['#suffix'] : '') . '</div>';
    $form_state->setRebuild();

    return $element;
  }

  /**
   * Ajax callback for the "Pre-populate with Locations" button.
   *
   * This returns the new page content to replace the page content made obsolete
   * by the form submission.
   */
  public function addLocationsSubmit(array $form, FormStateInterface $form_state) {
    $location_ids = \Drupal::entityQuery('node')
      ->condition('type', 'location')
      ->execute();
    $location_entities = \Drupal::entityManager()->getStorage(
      'node'
    )->loadMultiple($location_ids);
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    // Increment the items count.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);

    $values = $form_state->getValue('default_value_input');
    $values['field_what_is_your_preferred_y_l'] = array_intersect_key($values['field_what_is_your_preferred_y_l'], array_flip(array_filter(array_keys($values['field_what_is_your_preferred_y_l']), 'is_numeric')));

    // Skip item which already have locations from the list.
    foreach ($location_entities as $key => $entity) {
      foreach ($values['field_what_is_your_preferred_y_l'] as $value) {
        if ($entity->getTitle() == $value['option_name']) {
          unset($location_entities[$key]);
        }
      }
    }

    $form_state->setValue('locations', TRUE);
    $form_state->setValue('location_entities', $location_entities);
    $items_count = count($location_entities);

    $field_state['items_count'] += $items_count;
    static::setWidgetState($parents, $field_name, $form_state, $field_state);

    $form_state->setRebuild();
  }

  /**
   * Ajax callback for the "Remove selected items" button.
   *
   * This returns the new page content to replace the page content made obsolete
   * by the form submission.
   */
  public function removeItemsAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));

    // Ensure the widget allows adding additional items.
    if ($element['#cardinality'] != FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      return;
    }

    // Add a DIV around the delta receiving the Ajax effect.
    $delta = $element['#max_delta'];
    $element[$delta]['#prefix'] = '<div class="ajax-new-content">' . (isset($element[$delta]['#prefix']) ? $element[$delta]['#prefix'] : '');
    $element[$delta]['#suffix'] = (isset($element[$delta]['#suffix']) ? $element[$delta]['#suffix'] : '') . '</div>';
    $form_state->setRebuild();

    return $element;
  }

  /**
   * Ajax callback for the "Remove selected items" button.
   *
   * This returns the new page content to replace the page content made obsolete
   * by the form submission.
   */
  public function removeItemsSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    // Increment the items count.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);

    $items_to_be_removed = array();
    $values = $form_state->getValue('default_value_input');
    $values['field_what_is_your_preferred_y_l'] = array_intersect_key($values['field_what_is_your_preferred_y_l'], array_flip(array_filter(array_keys($values['field_what_is_your_preferred_y_l']), 'is_numeric')));
    foreach ($values['field_what_is_your_preferred_y_l'] as $key => $item) {
      if ($item['option_select']) {
        $items_to_be_removed[] = $key;
      }
    }
    if (!empty($items_to_be_removed)) {
      $form_state->setValue('remove_items', TRUE);
      $form_state->setValue('items_to_be_removed', $items_to_be_removed);
    }

    $field_state['items_count'] -= count($items_to_be_removed);
    static::setWidgetState($parents, $field_name, $form_state, $field_state);

    $form_state->setRebuild();
  }

}
