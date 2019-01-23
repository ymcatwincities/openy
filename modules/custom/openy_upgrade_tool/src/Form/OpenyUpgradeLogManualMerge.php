<?php

namespace Drupal\openy_upgrade_tool\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Url;
use Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogInterface;
use Drupal\openy_upgrade_tool\OpenyUpgradeLogManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\openy_upgrade_tool\OpenyUpgradeLogManager;

/**
 * Class OpenyUpgradeLogManualMerge.
 */
class OpenyUpgradeLogManualMerge extends FormBase {

  /**
   * Drupal\openy_upgrade_tool\OpenyUpgradeLogManagerInterface definition.
   *
   * @var \Drupal\openy_upgrade_tool\OpenyUpgradeLogManager
   */
  protected $upgradeLogManager;

  /**
   * OpenyUpgradeLog entity.
   *
   * @var \Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogInterface
   */
  protected $entity;

  /**
   * Data target (openy or OpenyUpgradeLog revision ID).
   *
   * @var string
   */
  protected $compareWith;

  /**
   * Constructs a new OpenyUpgradeLogManualMerge object.
   *
   * @param \Drupal\openy_upgrade_tool\OpenyUpgradeLogManagerInterface $openy_upgrade_log_manager
   *   Open Y Upgrade Log Manager.
   */
  public function __construct(
    OpenyUpgradeLogManagerInterface $openy_upgrade_log_manager
  ) {

    $this->upgradeLogManager = $openy_upgrade_log_manager;
    $params = $this->getRouteMatch()->getParameters();
    $this->entity = $params->get('openy_upgrade_log');
    $this->compareWith = $params->get('target');
    if (!($this->entity instanceof OpenyUpgradeLogInterface)) {
      $this->entity = $this->upgradeLogManager->load($this->entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openy_upgrade_log.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_upgrade_log_manual_merge';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // TODO: show diff from both sides of text area.
    // TODO: Add custom js lib for easy diff apply.
    if ($this->compareWith !== 'openy') {
      $yml_data = $this->upgradeLogManager
        ->loggerEntityStorage
        ->loadRevision($this->compareWith)
        ->getYmlData();
    }
    else {
      $openy_config_data = $this->upgradeLogManager->featuresManager
        ->getExtensionStorages()->read($this->entity->getName());
      $yml_data = Yaml::encode($openy_config_data);
    }

    $form['data'] = [
      '#type' => 'textarea',
      '#default_value' => $yml_data,
      '#rows' => 30,
      '#title' => $this->t('Config data'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    ];
    $form['actions']['return'] = Link::fromTextAndUrl(
      $this->t('Return'),
      Url::fromRoute('openy_upgrade_tool.log.diff', ['openy_upgrade_log' => $this->entity->id()])
    )->toRenderable();
    $form['actions']['return']['#attributes'] = [
      'class' => ['use-ajax', 'button', 'button--danger'],
      'data-dialog-type' => 'modal',
      'data-dialog-options' => json_encode([
        'width' => OpenyUpgradeLogManager::MODAL_WIDTH,
      ]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $data = Yaml::decode($form_state->getValue('data'));
    $this->upgradeLogManager
      ->updateExistingConfig($this->entity->getName(), $data);
  }

}
