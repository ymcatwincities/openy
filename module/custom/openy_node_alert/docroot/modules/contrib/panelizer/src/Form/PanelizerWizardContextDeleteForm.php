<?php

namespace Drupal\panelizer\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ctools\Form\ContextDelete;

/**
 * Provides a form for deleting a context.
 */
class PanelizerWizardContextDeleteForm extends ContextDelete {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'panelizer_wizard_context_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $cached_values = $this->getTempstore();
    $context = $cached_values['contexts'][$this->context_id];
    return $this->t('Are you sure you want to delete the context @label?', ['@label' => $context['label']]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $cached_values = $this->getTempstore();

    return new Url('panelizer.wizard.add.step', [
      'machine_name' => $cached_values['id'],
      'step' => 'contexts',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $this->getTempstore();
    $context = $cached_values['contexts'][$this->context_id];
    drupal_set_message($this->t('The static context %label has been removed.', ['%label' => $context['label']]));
    unset($cached_values['contexts'][$this->context_id]);
    $this->setTempstore($cached_values);
    parent::submitForm($form, $form_state);
  }

}
