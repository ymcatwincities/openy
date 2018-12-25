<?php

namespace Drupal\ymca_migrate_landing_page\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ymca_migrate_landing_page\MigrationImporter;
use Drupal\ymca_migrate_landing_page\MigrationImporterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends FormBase {

  /**
   * Current module settings config name.
   */
  const MODULE_SETTINGS_CONFIG_NAME = 'ymca_migrate_landing_page.settings';

  /**
   * Current module settings config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $moduleConfig;

  /**
   * Migration importer.
   *
   * @var \Drupal\ymca_migrate_landing_page\MigrationImporterInterface
   */
  protected $migrationImporter;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\ymca_migrate_landing_page\MigrationImporterInterface $importer
   *   Migration importer.
   */
  public function __construct(MigrationImporterInterface $importer) {
    $this->migrationImporter = $importer;
    $this->moduleConfig = $this->config(static::MODULE_SETTINGS_CONFIG_NAME);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ymca_migrate_landing_page_importer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ymca_migrate_landing_page_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($this->moduleConfig->get('migrate_executed')) {
      drupal_set_message('Migration has been executed already.', 'warning');
    }
    $form['messages'] = [
      '#theme' => 'status_messages',
      '#message_list' => [],
    ];

    if (!$this->moduleConfig->get('migrate_executed')) {
      $form['actions'] = [
        '#type' => 'actions',
      ];

      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Run Import'),
        '#disabled' => $this->moduleConfig->get('migrate_executed'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $batch = [
      'operations' => [
        [
          [MigrationImporter::class, 'processBatch'],
          [],
        ],
      ],
      'finished' => [MigrationImporter::class, 'finishBatch'],
      'title' => t('Migration Pages to Landing Pages'),
      'init_message' => $this->t('Starting migration Pages to Landing Pages.'),
      'progress_message' => $this->t('Migration...'),
      'error_message' => $this->t('Migration process has encountered an error.'),
    ];
    batch_set($batch);
  }

}
