<?php

namespace Drupal\openy_addthis\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form removing addthis field data before module uninstall.
 */
class PrepareUninstallForm extends ConfirmFormBase {

  /**
   * The entity_type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * Constructs a new PrepareUninstallForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_addthis.prepare_module_uninstall';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t(
      'Are you sure you want to delete all addthis field values?'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action cannot be undone.<br />Make a backup of your database if you want to be able to restore these items.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete all addthis field values');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('system.modules_uninstall');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tables_to_update = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      if ($entity_type->id() == 'node') {
        $tables_to_update[] = $entity_type->getDataTable();
        $tables_to_update[] = $entity_type->getRevisionDataTable();
      }
    }

    foreach ($tables_to_update as $table) {
      if ($table && $this->connection->schema()->fieldExists($table, 'addthis')) {
        $this->connection->update($table)
          ->fields(['addthis' => NULL])
          ->execute();
      }
    }
    drupal_set_message(t('All values have been deleted.'));

    $form_state->setRedirect('system.modules_uninstall');
  }

}
