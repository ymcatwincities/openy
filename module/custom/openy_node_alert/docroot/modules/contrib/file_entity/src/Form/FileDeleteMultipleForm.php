<?php

namespace Drupal\file_entity\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a file deletion confirmation form.
 *
 * @see \Drupal\node\Form\DeleteMultiple.
 */
class FileDeleteMultipleForm extends ConfirmFormBase {

  /**
   * The array of files to delete.
   *
   * @var FileInterface[]
   */
  protected $files = array();

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
   * Constructs a FileDeleteMultipleForm object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityManagerInterface $manager) {
    $this->tempStore = $temp_store_factory->get('file_multiple_delete_confirm');
    $this->storage = $manager->getStorage('file');
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
    return 'file_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return \Drupal::translation()->formatPlural(
      count($this->files),
      'Are you sure you want to delete this file?',
      'Are you sure you want to delete these files?'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.file.collection');
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
    $this->files = $this->tempStore->get('delete');
    if (empty($this->files)) {
      $form_state->setRedirect('entity.file.collection');
    }

    $form['files'] = array(
      '#theme' => 'item_list',
      '#items' => array_map(function (FileInterface $file) {
        return SafeMarkup::checkPlain($file->label());
      }, $this->files),
    );
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->files)) {
      $this->storage->delete($this->files);
      $this->tempStore->delete('delete');
    }
    $form_state->setRedirect('entity.file.collection');
  }

}
