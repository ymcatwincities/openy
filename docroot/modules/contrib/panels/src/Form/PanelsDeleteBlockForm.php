<?php

namespace Drupal\panels\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\panels\CachedValuesGetterTrait;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting an access condition.
 */
class PanelsDeleteBlockForm extends ConfirmFormBase {

  use CachedValuesGetterTrait;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * The tempstore id.
   *
   * @var string
   */
  protected $tempstore_id;

  /**
   * @var \Drupal\ctools\Plugin\BlockVariantInterface
   */
  protected $plugin;

  /**
   * The plugin being configured.
   *
   * @var \Drupal\Core\Block\BlockPluginInterface
   */
  protected $block;

  /**
   * PanelsDeleteBlockForm constructor.
   *
   * @param \Drupal\user\SharedTempStoreFactory $tempstore
   *   The tempstore factory.
   */
  public function __construct(SharedTempStoreFactory $tempstore) {
    $this->tempstore = $tempstore;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.shared_tempstore')
    );
  }

  /**
   * Get the tempstore id.
   *
   * @return string
   */
  protected function getTempstoreId() {
    return $this->tempstore_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'panels_delete_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the block %label?', ['%label' => $this->block->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getRequest()->attributes->get('destination');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $tempstore_id = NULL, $machine_name = NULL, $block_id = NULL) {
    $this->tempstore_id = $tempstore_id;
    $cached_values = $this->getCachedValues($this->tempstore, $tempstore_id, $machine_name);
    $this->plugin = $cached_values['plugin'];
    $this->block = $this->plugin->getBlock($block_id);
    $form['block_display'] = [
      '#type' => 'value',
      '#value' => $machine_name
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->plugin->removeBlock($this->block->getConfiguration()['uuid']);
    $cached_values = $this->getCachedValues($this->tempstore, $this->getTempstoreId(), $form_state->getValue('block_display'));
    $cached_values['plugin'] = $this->plugin;
    // PageManager specific handling.
    if (isset($cached_values['page_variant'])) {
      $cached_values['page_variant']->getVariantPlugin()->setConfiguration($cached_values['plugin']->getConfiguration());
    }
    $this->tempstore->get($this->getTempstoreId())->set($cached_values['id'], $cached_values);
    drupal_set_message($this->t('The block %label has been removed.', ['%label' => $this->block->label()]));
  }

}
