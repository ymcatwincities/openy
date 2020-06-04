<?php

namespace Drupal\openy_upgrade_tool\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogInterface;
use Drupal\openy_upgrade_tool\OpenyUpgradeLogManagerInterface;
use Drupal\openy_upgrade_tool\OpenYUpgradeToolFeatureStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\openy_upgrade_tool\OpenyUpgradeLogManager;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Diff\DiffFormatter;

/**
 * Class OpenyUpgradeLogDiff.
 */
class OpenyUpgradeLogDiff extends FormBase {

  const DEFAULT_DIFF_TARGET = 'openy';

  /**
   * Drupal\openy_upgrade_tool\OpenyUpgradeLogManager definition.
   *
   * @var \Drupal\openy_upgrade_tool\OpenyUpgradeLogManagerInterface
   */
  protected $upgradeLogManager;

  /**
   * Drupal\Core\Config\StorageInterface definition.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The source storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configRevisionStorage;

  /**
   * Drupal\Core\Config\ConfigManagerInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * Drupal\Core\Diff\DiffFormatter definition.
   *
   * @var \Drupal\Core\Diff\DiffFormatter
   */
  protected $diffFormatter;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * OpenyUpgradeLog entity.
   *
   * @var \Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogInterface
   */
  protected $entity;

  /**
   * Constructs a new OpenyUpgradeLogDiff object.
   *
   * @param \Drupal\openy_upgrade_tool\OpenyUpgradeLogManagerInterface $openy_upgrade_log_manager
   *   OpenyUpgradeLogManager.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   Config storage.
   * @param \Drupal\Core\Config\StorageInterface $config_revision_storage
   *   Config revision storage.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   Config Manager.
   * @param \Drupal\Core\Diff\DiffFormatter $diff_formatter
   *   The diff formatter.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(
    OpenyUpgradeLogManagerInterface $openy_upgrade_log_manager,
    StorageInterface $config_storage,
    StorageInterface $config_revision_storage,
    ConfigManagerInterface $config_manager,
    DiffFormatter $diff_formatter,
    DateFormatterInterface $date_formatter
  ) {
    $this->upgradeLogManager = $openy_upgrade_log_manager;
    $this->configStorage = $config_storage;
    $this->configRevisionStorage = $config_revision_storage;
    $this->configManager = $config_manager;
    $this->diffFormatter = $diff_formatter;
    $this->dateFormatter = $date_formatter;

    $this->entity = $this->getRouteMatch()->getParameters()->get('openy_upgrade_log');
    if (!($this->entity instanceof OpenyUpgradeLogInterface)) {
      $this->entity = $this->upgradeLogManager->load($this->entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openy_upgrade_log.manager'),
      $container->get('openy_upgrade_tool.config.storage'),
      $container->get('openy_upgrade_tool.config.storage.upgrade_log.revision'),
      $container->get('config.manager'),
      $container->get('diff.formatter'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_upgrade_log_diff';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $ajax_wrapper = 'openy-diff-ajax-wrapper';
    $form['#prefix'] = "<div id='$ajax_wrapper'";
    $form['#suffix'] = '</div>';

    if (!$this->entity) {
      $form['invalid']['#markup'] = $this->t("Can't find openy_upgrade_log item!");
      return $form;
    }

    // Get compare_with value for diff calculating.
    $compare_with = $form_state->getValue('compare_with', self::DEFAULT_DIFF_TARGET);

    $options = [];
    $revisions = [];
    $this->loadRevisions($revisions, $options);

    $form['compare_with'] = [
      '#type' => 'select',
      '#title' => $this->t('Compare with'),
      '#options' => $options,
      '#default_value' => self::DEFAULT_DIFF_TARGET,
      '#ajax' => [
        'callback' => '::diffTargetChange',
        'event' => 'change',
        'wrapper' => $ajax_wrapper,
      ],
    ];

    $form['diff_content'] = [
      '#type' => 'container',
    ];

    $description = $this->t('Diff between current config version (active config) and config version from Open Y feature file.');
    if ($compare_with != self::DEFAULT_DIFF_TARGET) {
      $description = $this->t('Diff between current config version (active config) and config version from logger entity revision with message: @msg', [
        '@msg' => $revisions[$compare_with]->getRevisionLogMessage(),
      ]);
    }
    $form['diff_content']['description'] = [
      '#markup' => $description,
    ];

    $form['diff_content']['diff'] = [
      '#type' => 'table',
      '#attributes' => ['class' => ['diff']],
      '#header' => [
        ['data' => $this->t('Current config version'), 'colspan' => '1'],
        ['data' => $options[$compare_with], 'colspan' => '1'],
      ],
      '#rows' => $this->getDiffDataRows($compare_with),
    ];

    if (empty($form['diff_content']['diff']['#rows'])) {
      $form['diff_content']['empty'] = [
        '#markup' => $this->t('There is no difference between configs versions.'),
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    if (!$this->entity->getStatus()) {
      // Show apply_current button if user doesn't confirmed changes.
      $form['actions']['apply_current'] = [
        '#type' => 'submit',
        '#value' => $this->t('Apply current config'),
        '#button_type' => 'primary',
        '#submit' => ['::applyCurrentActiveVersion'],
        '#ajax' => [
          'callback' => '::closeModal',
          'wrapper' => $ajax_wrapper,
        ],
      ];
    }

    $form['actions']['apply_selected'] = [
      '#type' => 'submit',
      '#name' => 'apply_selected',
      '#value' => $this->t('Apply @option', [
        '@option' => $options[$compare_with],
      ]),
      '#submit' => ['::applySelectedConfigVersion'],
    ];
    $form['actions']['manual_merge'] = Link::fromTextAndUrl(
      $this->t('Merge manually'),
      Url::fromRoute('openy_upgrade_tool.log.manual_merge', [
        'openy_upgrade_log' => $this->entity->id(),
        'target' => $compare_with,
      ])
    )->toRenderable();
    $form['actions']['manual_merge']['#attributes'] = [
      'class' => ['use-ajax', 'button', 'button--danger'],
      'data-dialog-type' => 'modal',
      'data-dialog-options' => json_encode([
        'width' => OpenyUpgradeLogManager::MODAL_WIDTH,
      ]),
    ];

    // Add the CSS for the inline diff.
    $form['#attached']['library'][] = 'system/diff';

    return $form;
  }

  /**
   * Helper function for data load.
   */
  private function loadRevisions(array &$revisions, array &$options) {
    $options[self::DEFAULT_DIFF_TARGET] = $this->t('Open Y version');
    $revision_ids = $this->upgradeLogManager
      ->loggerEntityStorage
      ->revisionIds($this->entity);

    foreach ($revision_ids as $key => $revision_id) {
      $revision = $this->upgradeLogManager
        ->loggerEntityStorage
        ->loadRevision($revision_id);
      $options[$revision_id] = $this->t('Revision: @time', [
        '@time' => $this->dateFormatter->format($revision->getRevisionCreationTime()),
      ]);
      if ($revision->isDefaultRevision()) {
        $options[$revision_id] = $this->t('Current Revision');
      }
      $revisions[$revision_id] = $revision;
    }
  }

  /**
   * Get render array for diff table.
   */
  private function getDiffDataRows($compare_with) {
    $source_name = $this->entity->getName();

    if ($compare_with == self::DEFAULT_DIFF_TARGET) {
      // Target storage is FileStorage, load config from file.
      $target_storage = new OpenYUpgradeToolFeatureStorage($this->configStorage);
      $target_name = $source_name;
    }
    else {
      // Target storage is OpenyUpgradeLog entity revision, load config from
      // revision data field.
      $target_storage = $this->configRevisionStorage;
      $target_name = $this->configRevisionStorage->encode([
        'name' => $source_name,
        'revision_id' => $compare_with,
      ]);
    }

    $diff = $this->configManager->diff($this->configStorage, $target_storage, $source_name, $target_name, NULL);
    $this->diffFormatter->show_header = FALSE;
    $rows = $this->diffFormatter->format($diff);

    // Fix formatting and add whitespaces to diff.
    foreach ($rows as $key => $row) {
      foreach ($row as $row_data_key => $row_data) {
        if (is_array($row_data) && isset($row_data['data']['#markup'])) {
          $rows[$key][$row_data_key]['data']['#prefix'] = '<pre>';
          $rows[$key][$row_data_key]['data']['#suffix'] = '</pre>';
        }
        if ($row_data_key === 0 || $row_data_key === 2) {
          // Remove 0 and 2 columns with symbols "+" and "-".
          // Use only columns with data highlighted by color.
          unset($rows[$key][$row_data_key]);
        }
      }
    }
    return $rows;
  }

  /**
   * Rebuild form on target change.
   */
  public function diffTargetChange(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Close modal and redirect to dashboard.
   */
  public function closeModal(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $dashboard_url = \Drupal::service('url_generator')
      ->generateFromRoute(OpenyUpgradeLogManager::DASHBOARD);
    $response->addCommand(new RedirectCommand($dashboard_url));
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

  /**
   * Set status TRUE to selected logger entity (conflict resolved).
   */
  public function applyCurrentActiveVersion(array &$form, FormStateInterface $form_state) {
    $messenger = \Drupal::messenger();
    $this->entity->applyCurrentActiveVersion();
    $messenger->addMessage($this->t('Conflict resolved for "@name" config, confirmed current config version from active storage and left unchanged.', [
      '@name' => $this->entity->getName(),
    ]));
  }

  /**
   * Restore config from selected target item (openy or revision ID).
   */
  public function applySelectedConfigVersion(array &$form, FormStateInterface $form_state) {
    $compare_with = $form_state->getValue('compare_with', self::DEFAULT_DIFF_TARGET);

    if ($compare_with == self::DEFAULT_DIFF_TARGET) {
      // Restore config from file system (Open Y feature).
      $this->upgradeLogManager->applyOpenyVersion($this->entity->getName());
    }
    else {
      // Restore config from revision data.
      $revision = $this->upgradeLogManager
        ->loggerEntityStorage
        ->loadRevision($compare_with);
      $revision->applyConfigVersionFromData();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    $form_state->setRedirect(OpenyUpgradeLogManager::DASHBOARD);
  }

}
