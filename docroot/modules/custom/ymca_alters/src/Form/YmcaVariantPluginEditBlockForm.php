<?php

namespace Drupal\ymca_alters\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\page_manager\PageVariantInterface;
use Drupal\page_manager_ui\Form\VariantPluginEditBlockForm;

/**
 * Provides a form for editing a block plugin of a variant.
 */
class YmcaVariantPluginEditBlockForm extends VariantPluginEditBlockForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PageVariantInterface $page_variant = NULL, $block_id = NULL) {
    $form = parent::buildForm($form, $form_state, $page_variant, $block_id);

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
