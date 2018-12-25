<?php

namespace Drupal\ymca_alters\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\panels\Form\PanelsEditBlockForm;

/**
 * Provides a form for editing a block plugin.
 */
class YmcaPanelsEditBlockForm extends PanelsEditBlockForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $tempstore_id = NULL, $machine_name = NULL, $block_id = NULL) {
    $form = parent::buildForm($form, $form_state, $tempstore_id, $machine_name, $block_id);

    // Add link to edit block to Page Manager block edit modal.
    if ($this->block->getBaseId() != 'block_content' || !$uuid = $this->block->getDerivativeId()) {
      return $form;
    }

    $block_content = \Drupal::entityManager()->loadEntityByUuid('block_content', $uuid);
    $edit_link = $block_content->toLink(NULL, 'edit-form', ['attributes' => ['target' => '_blank']]);
    $link = $edit_link->toRenderable();
    $form['settings']['admin_label']['#markup'] = render($link);
    unset($form['settings']['admin_label']['#plain_text']);

    return $form;
  }

}
