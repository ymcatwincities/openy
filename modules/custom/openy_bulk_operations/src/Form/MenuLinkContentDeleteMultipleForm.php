<?php

namespace Drupal\bulk_actions\Form;

use Drupal\block_content\BlockContentInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a menu_link_content deletion confirmation form.
 *
 * @see \Drupal\node\Form\DeleteMultiple.
 */
class MenuLinkContentDeleteMultipleForm extends ConfirmFormBase {

  /**
   * View route.
   */
  const VIEW_ROUTE = 'view.bulk_actions_menu_link_content.page_1';

  /**
   * The array of links to delete.
   *
   * @var \Drupal\menu_link_content\MenuLinkContentInterface[]
   */
  protected $items = array();

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $tempStore;

  /**
   * The file storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $manager;

  /**
   * Constructor.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityManagerInterface $manager) {
    $this->tempStore = $temp_store_factory->get('menu_link_content_multiple_delete_confirm');
    $this->storage = $manager->getStorage('menu_link_content');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'menu_link_content_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return \Drupal::translation()->formatPlural(
      count($this->items),
      'Are you sure you want to delete this menu item?',
      'Are you sure you want to delete these menu items?'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url(self::VIEW_ROUTE);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->items = $this->tempStore->get('delete');
    if (empty($this->items)) {
      $form_state->setRedirect(self::VIEW_ROUTE);
    }

    $form['items'] = array(
      '#theme' => 'item_list',
      '#items' => array_map(function (MenuLinkContentInterface $item) {
        return SafeMarkup::checkPlain($item->label());
      }, $this->items),
    );
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->items)) {
      $this->storage->delete($this->items);
      $this->tempStore->delete('delete');
    }
    $form_state->setRedirect(self::VIEW_ROUTE);
  }

}
