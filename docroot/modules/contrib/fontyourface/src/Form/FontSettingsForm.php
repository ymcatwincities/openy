<?php

namespace Drupal\fontyourface\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\fontyourface\Entity\Font;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Class FontSettingsForm.
 *
 * @package Drupal\fontyourface\Form
 *
 * @ingroup fontyourface
 */
class FontSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['fontyourface.settings'];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'Font_settings';
  }

  /**
   * Defines the settings form for Font entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fontyourface.settings');
    $form['Font_settings']['#markup'] = 'Settings form for @font-your-face. Support modules can use this form for settings or to import fonts.';
    $form['load_all_enabled_fonts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load all enabled fonts'),
      '#default_value' => (int) $config->get('load_all_enabled_fonts'),
      '#description' => $this->t('This will load all fonts that have been enabled regardless of theme. Warning: this may add considerable download weight to your pages depending on the number of enabled fonts'),
    ];
    $form['imports'] = [
      '#type' => 'fieldset',
      '#title' => 'Import',
      '#collapsible' => FALSE,
    ];
    // Set the module weight. There is some general Drupal funk around module weights.
    module_set_weight('fontyourface', 1);
    foreach (\Drupal::moduleHandler()->getImplementations('fontyourface_api') as $module_name) {
      module_set_weight($module_name, 10);
    }
    foreach (\Drupal::moduleHandler()->getImplementations('fontyourface_import') as $module_name) {
      $form['imports']['import_' . $module_name] = [
        '#type' => 'submit',
        '#value' => $this->t('Import from @module', ['@module' => $module_name]),
        '#attributes' => [
          'style' => 'margin: 10px;',
        ],
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ];
    }

    $form['imports']['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import all fonts'),
      '#weight' => 10,
    ];
    return parent::buildForm($form, $form_state);;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $op = (string) $values['op'];

    $batch = [
      'title' => $this->t('Importing...'),
      'operations' => [],
      'finished' => '\Drupal\fontyourface\Form\FontSettingsForm::importFinished',
    ];
    foreach (\Drupal::moduleHandler()->getImplementations('fontyourface_import') as $module_name) {
      if ($op == $this->t('Import all fonts') || $op == $this->t('Import from @module', ['@module' => $module_name])) {
        $batch['operations'][] = [
          '\Drupal\fontyourface\Form\FontSettingsForm::importFromProvider',
          [
            $module_name,
          ],
        ];
      }
    }
    if (!empty($batch['operations'])) {
      batch_set($batch);
    }

    if ($op == $this->t('Save configuration')) {
      $config = $this->config('fontyourface.settings')
        ->set('load_all_enabled_fonts', $values['load_all_enabled_fonts'])
        ->save();
      parent::submitForm($form, $form_state);
    }

    // Resave enabled fonts.
    $fonts = Font::loadActivatedFonts();
    foreach ($fonts as $font) {
      $font->activate();
    }
  }

  /**
   * Imports fonts from provider. Batch operation handler.
   *
   * @param string $module
   *   Module name that is providing fonts.
   * @param array $context
   *   Context batch array.
   */
  public static function importFromProvider($module, array &$context) {
    $context['message'] = new TranslatableMarkup('Importing from @module', ['@module' => $module]);
    $module_handler = \Drupal::moduleHandler();
    $new_context = $module_handler->invoke($module, 'fontyourface_import', [$context]);
    if (!empty($new_context)) {
      $context = $new_context;
    }
  }

  /**
   * Imports fonts from provider. Batch completion handler.
   *
   * @param bool $success
   *   Boolean if operations were successful.
   * @param array $results
   *   Results of batch operations.
   * @param array $operations
   *   List of batch operations run.
   */
  public static function importFinished($success, array $results, array $operations) {
    drupal_set_message(new TranslatableMarkup('Finished importing fonts.'));
  }

}
