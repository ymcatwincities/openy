<?php

/**
 * @file
 * Contains YMCA office hours widget.
 */

namespace Drupal\webforms\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
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

}
