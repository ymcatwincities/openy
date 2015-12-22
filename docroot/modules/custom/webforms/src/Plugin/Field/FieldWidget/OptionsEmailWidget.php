<?php

/**
 * @file
 * Contains YMCA office hours widget.
 */

namespace Drupal\webforms\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
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
   * - table display and drag-n-drop value reordering
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
      }
    }

    return $elements;
  }

  /**
   * Ajax callback for the "Pre-populate with Locations" button.
   *
   * This returns the new page content to replace the page content made obsolete
   * by the form submission.
   */
  public function addLocationsAjax(array $form, FormStateInterface $form_state) {
    // @todo wrapper replacement code here, @see addMoreAjax as example
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

    // @todo inject locations list to a form. @see addMoreSubmit as example

  }

}
