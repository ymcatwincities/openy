<?php

namespace Drupal\openy_upgrade_tool\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a Openy upgrade log revision.
 *
 * @ingroup openy_upgrade_tool
 */
class OpenyUpgradeLogRevisionRevertForm extends ConfirmFormBase {


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
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new OpenyUpgradeLogRevisionRevertForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The Openy upgrade log storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityStorageInterface $entity_storage, DateFormatterInterface $date_formatter) {
    $this->OpenyUpgradeLogStorage = $entity_storage;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('openy_upgrade_log'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_upgrade_log_revision_revert_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to revert to the revision from %revision-date?', ['%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime())]);
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
    return t('Revert');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
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
    // The revision timestamp will be updated when the revision is saved. Keep
    // the original one for the confirmation message.
    $original_revision_timestamp = $this->revision->getRevisionCreationTime();

    $this->revision = $this->prepareRevertedRevision($this->revision, $form_state);
    $this->revision->revision_log = t('Copy of the revision from %date.', ['%date' => $this->dateFormatter->format($original_revision_timestamp)]);
    $this->revision->save();

    $this->logger('content')->notice('Openy upgrade log: reverted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $messenger->addMessage(t('Openy upgrade log %title has been reverted to the revision from %revision-date.', ['%title' => $this->revision->label(), '%revision-date' => $this->dateFormatter->format($original_revision_timestamp)]));
    $form_state->setRedirect(
      'entity.openy_upgrade_log.version_history',
      ['openy_upgrade_log' => $this->revision->id()]
    );
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogInterface $revision
   *   The revision to be reverted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(OpenyUpgradeLogInterface $revision, FormStateInterface $form_state) {
    $request_time = \Drupal::time()->getRequestTime();
    $revision->setNewRevision();
    $revision->isDefaultRevision();
    $revision->setRevisionCreationTime($request_time);

    return $revision;
  }

}
