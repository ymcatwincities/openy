<?php

namespace Drupal\panelizer\Wizard;

use Drupal\Core\Form\FormStateInterface;
use Drupal\panelizer\Form\PanelizerWizardContentForm;
use Drupal\panelizer\Form\PanelizerWizardContextForm;
use Drupal\panelizer\Form\PanelizerWizardGeneralForm;

class PanelizerAddWizard extends PanelizerWizardBase {

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return 'panelizer.wizard.add.step';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL, $view_mode_name = NULL) {
    if ($entity_type_id && $bundle && $view_mode_name) {
      $form_state->set('machine_name_prefix', "{$entity_type_id}__{$bundle}__{$view_mode_name}");
    }
    $form = parent::buildForm($form, $form_state);
    $cached_values = $form_state->getTemporaryValue('wizard');
    $cached_values['id'] = $this->getMachineName();
    // Some variants like PanelsDisplayVariant need this. Set it to empty.
    $cached_values['access'] = [];
    $form_state->setTemporaryValue('wizard', $cached_values);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $operations = array_map('strval', [
      $this->getNextOp(),
      $this->t('Update'),
      $this->t('Update and save'),
      $this->t('Save'),
    ]);

    if (in_array($form_state->getValue('op'), $operations)) {
      $cached_values = $form_state->getTemporaryValue('wizard');
      if ($form_state->hasValue('label')) {
        $config = $cached_values['plugin']->getConfiguration();
        $config['label'] = $form_state->getValue('label');
        $cached_values['plugin']->setConfiguration($config);
      }
      if ($form_state->hasValue('id')) {
        $cached_values['id'] = $form_state->getValue('id');
        /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $plugin */
        $plugin = $cached_values['plugin'];
        $plugin->setStorage($plugin->getStorageType(), $cached_values['id']);
      }
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function finish(array &$form, FormStateInterface $form_state) {
    parent::finish($form, $form_state);
    $cached_values = $form_state->getTemporaryValue('wizard');
    $form_state->setRedirect('panelizer.wizard.edit', ['machine_name' => $cached_values['id'], 'step' => 'content']);
  }

}
