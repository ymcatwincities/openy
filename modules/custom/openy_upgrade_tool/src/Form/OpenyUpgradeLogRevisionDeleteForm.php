<?php

namespace Drupal\openy_upgrade_tool\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Openy upgrade log revision.
 *
 * @ingroup openy_upgrade_tool
 */
class OpenyUpgradeLogRevisionDeleteForm extends ConfirmFormBase {


  /**
   * The Openy upgrade log revision.
   *
   * @var \Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogInterface
   */
  protected $revision;

  /**
   * The Openy upgrade log storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $OpenyUpgradeLogStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new OpenyUpgradeLogRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityStorageInterface $entity_storage, Connection $connection) {
    $this->OpenyUpgradeLogStorage = $entity_storage;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity_type.manager');
    return new static(
      $entity_manager->getStorage('openy_upgrade_log'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_upgrade_log_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the revision from %revision-date?', ['%revision-date' => \Drupal::service('date.formatter')->format($this->revision->getRevisionCreationTime())]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.openy_upgrade_log.version_history', ['openy_upgrade_log' => $this->revision->id()]);
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
  public function buildForm(array $form, FormStateInterface $form_state, $openy_upgrade_log_revision = NULL) {
    $this->revision = $this->OpenyUpgradeLogStorage->loadRevision($openy_upgrade_log_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $messenger = \Drupal::messenger();
    $this->OpenyUpgradeLogStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Openy upgrade log: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $messenger->addMessage(t('Revision from %revision-date of Openy upgrade log %title has been deleted.', ['%revision-date' => \Drupal::service('date.formatter')->format($this->revision->getRevisionCreationTime()), '%title' => $this->revision->label()]));
    $form_state->setRedirect(
      'entity.openy_upgrade_log.canonical',
       ['openy_upgrade_log' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {openy_upgrade_log_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.openy_upgrade_log.version_history',
         ['openy_upgrade_log' => $this->revision->id()]
      );
    }
  }

}
