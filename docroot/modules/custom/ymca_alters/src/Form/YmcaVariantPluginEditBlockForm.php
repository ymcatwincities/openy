<?php

/**
 * @file
 * Contains \Drupal\ymca_alters\Form\YmcaVariantPluginEditBlockForm.
 */

namespace Drupal\ymca_alters\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\page_manager\PageVariantInterface;
use Drupal\page_manager_ui\Form\VariantPluginEditBlockForm;
use Drupal\Core\Url;
use Drupal\Core\Link;

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
    $uuid = $this->block->getDerivativeId();
    $block_content = \Drupal::entityManager()->loadEntityByUuid('block_content', $uuid);
    $block_content_id = $block_content->id();

    $edit_link_url = Url::fromUri('internal:/block/' . $block_content_id);
    $edit_link_url->setOption('attributes', array('target' => '_blank'));
    /* @var Link $edit_link */
    $edit_link = new Link($form['settings']['admin_label']['#plain_text'], $edit_link_url);
    $link = $edit_link->toRenderable();
    $form['settings']['admin_label']['#markup'] = render($link);
    unset($form['settings']['admin_label']['#plain_text']);

    return $form;
  }
}
