<?php

namespace Drupal\purge_queuer_coretags\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\purge_ui\Form\QueuerConfigFormBase;

/**
 * Configuration form for the Core Tags queuer.
 */
class ConfigurationForm extends QueuerConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['purge_queuer_coretags.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'purge_queuer_coretags.configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('purge_queuer_coretags.settings');

    /**
     * Blacklist form elements (and ajax 'add more' logic).
     */
    $form['blacklist'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Tag blacklist'),
      '#description' => $this->t('You can exclude tags that Drupal invalidated by listing them here, only change this <b>if you know what you are doing!</b> The strings are matched as prefixes, so for example <code>config:</code> will match tags as <code>config:core.extension</code> and <code>config:block_list</code>.'),
    ];

    // Retrieve the existing blacklist and initiatlize the counter.
    $blacklist = $config->get('blacklist');
    if (is_null($form_state->get('blacklist_items_count'))) {
      if (empty($blacklist)) {
        $form_state->set('blacklist_items_count', 1);
      }
      else {
        $form_state->set('blacklist_items_count', count($blacklist));
      }
    }

    // Define the fields based on whats stored in form state.
    $max = $form_state->get('blacklist_items_count');
    $form['blacklist']['blacklist'] = [
      '#tree' => TRUE,
      '#prefix' => '<div id="blacklist-wrapper">',
      '#suffix' => '</div>',
    ];
    for ($delta = 0; $delta < $max; $delta++) {
      if (!isset($form['blacklist']['blacklist'][$delta])) {
        $element = [
          '#type' => 'textfield',
          '#default_value' => isset($blacklist[$delta]) ? $blacklist[$delta] : '',
        ];
        $form['blacklist']['blacklist'][$delta] = $element;
      }
    }

    // Define the add button.
    $form['blacklist']['add'] = [
      '#type' => 'submit',
      '#name' => 'add',
      '#value' => t('Add prefix'),
      '#submit' => [[$this, 'addMoreSubmit']],
      '#ajax' => [
        'callback' => [$this, 'addMoreCallback'],
        'wrapper' => 'blacklist-wrapper',
        'effect' => 'fade',
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Let the form rebuild the blacklist textfields.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function addMoreSubmit(array &$form, FormStateInterface $form_state) {
    $count = $form_state->get('blacklist_items_count');
    $count++;
    $form_state->set('blacklist_items_count', $count);
    $form_state->setRebuild();
  }

  /**
   * Adds more textfields to the blacklist fieldset.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function addMoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['blacklist']['blacklist'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Remove empty values from the blacklist so this doesn't cause issues.
    $blacklist = [];
    foreach ($form_state->getValue('blacklist') as $prefix) {
      if (!empty(trim($prefix))) {
        $blacklist[] = $prefix;
      }
    }
    $form_state->setValue('blacklist', $blacklist);
  }

  /**
   * {@inheritdoc}
   */
  public function submitFormSuccess(array &$form, FormStateInterface $form_state) {
    $this->config('purge_queuer_coretags.settings')
      ->set('blacklist', $form_state->getValue('blacklist'))
      ->save();
  }

}
