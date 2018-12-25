<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\VariantPluginAddBlockForm.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\page_manager\PageVariantInterface;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a form for adding a block plugin to a variant.
 */
class VariantPluginAddBlockForm extends VariantPluginConfigureBlockFormBase {

  /**
   * The block manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $blockManager;

  /**
   * Constructs a new VariantPluginFormBase.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $block_manager
   *   The block manager.
   */
  public function __construct(SharedTempStoreFactory $tempstore, PluginManagerInterface $block_manager) {
    parent::__construct($tempstore);
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.shared_tempstore'),
      $container->get('plugin.manager.block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_manager_variant_add_block_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareBlock($plugin_id) {
    $block = $this->blockManager->createInstance($plugin_id);
    $block_id = $this->getVariantPlugin()->addBlock($block->getConfiguration());
    return $this->getVariantPlugin()->getBlock($block_id);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL, $block_display = NULL, $block_id = NULL) {
    $form = parent::buildForm($form, $form_state, $block_display, $block_id);
    $form['region']['#default_value'] = $request->query->get('region');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function submitText() {
    return $this->t('Add block');
  }

}
