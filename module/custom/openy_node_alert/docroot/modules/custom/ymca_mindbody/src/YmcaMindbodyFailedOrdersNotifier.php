<?php

namespace Drupal\ymca_mindbody;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\ymca_errors\ErrorManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Link;
use Drupal\Core\Render\Renderer;

/**
 * Class YmcaMindbodyFailedOrdersNotifier.
 *
 * @package Drupal\ymca_mindbody
 */
class YmcaMindbodyFailedOrdersNotifier implements YmcaMindbodyFailedOrdersNotifierInterface {

  /**
   * Interval in seconds for first run.
   */
  const FIRST_RUN_INTERVAL = 604800;

  /**
   * Interval in hours how often notifier should be triggered.
   */
  const RUN_INTERVAL = 24;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Config factory.
   *
   * @var ConfigFactory
   */
  protected $configFactory;

  /**
   * Error Manager.
   *
   * @var ErrorManager
   */
  protected $errorManager;

  /**
   * Mail manager.
   *
   * @var MailManagerInterface
   */
  protected $mailManager;

  /**
   * Language manager.
   *
   * @var LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Query manager.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * MindbodyResultsController constructor.
   *
   * @param LoggerChannelFactoryInterface $logger_factory
   *   Logger factory.
   * @param ConfigFactory $config_factory
   *   The Config Factory.
   * @param ErrorManager $error_manager
   *   The Error manager.
   * @param MailManagerInterface $mail_manager
   *   Mail manager.
   * @param LanguageManagerInterface $language_manager
   *   Language manager.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param QueryFactory $entity_query
   *   The entity query factory.
   * @param Renderer $renderer
   *   The renderer.
   */
  public function __construct(
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactory $config_factory,
    ErrorManager $error_manager,
    MailManagerInterface $mail_manager,
    LanguageManagerInterface $language_manager,
    EntityTypeManagerInterface $entity_type_manager,
    QueryFactory $entity_query,
    Renderer $renderer
  ) {
    $this->logger = $logger_factory->get('ymca_mindbody');
    $this->configFactory = $config_factory;
    $this->errorManager = $error_manager;
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityQuery = $entity_query;
    $this->renderer = $renderer;
    $this->personifyMindbodyCacheEntityStorage = $this->entityTypeManager->getStorage('personify_mindbody_cache');
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed() {
    $config = $this->configFactory->get('ymca_mindbody.settings');
    $last_run_time = $config->get('failed_orders_notifier_last_run');
    if (empty($last_run_time)) {
      return TRUE;
    }
    $last_run = new \DateTime();
    $last_run->setTimestamp($last_run_time);
    $diff = $last_run->diff(new \DateTime());
    $diff_hrs = $diff->d * self::RUN_INTERVAL + $diff->h;
    // Check if cron was run less then 24 hrs ago.
    if ($diff_hrs < self::RUN_INTERVAL) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    if (!$this->isAllowed()) {
      return;
    }
    $config = $this->configFactory->get('ymca_mindbody.settings');
    $last_run_time = $config->get('failed_orders_notifier_last_run');
    if (empty($last_run_time)) {
      $last_run_time = time() - self::FIRST_RUN_INTERVAL;
    }
    $ids = $this->entityQuery->get('personify_mindbody_cache')->execute();
    $ids = array_chunk($ids, 100, TRUE);

    $new_failed_orders = [];
    foreach ($ids as $chunk) {
      $entities = $this->personifyMindbodyCacheEntityStorage->loadMultiple($chunk);
      foreach ($entities as $entity) {
        // Select failed entities after last notifier run.
        if ($entity->field_pmc_status->value !== 'Order: Success' && $entity->getCreatedTime() >= $last_run_time) {
          // Select fields to be send in email.
          $data = unserialize($entity->field_pmc_prs_data->value);
          $branch_id = explode('_', $data->ProductCode);
          $branch_id = $branch_id[0];
          $new_failed_orders[] = [
            'Date' => date('m/d/Y g:ia', $entity->field_pmc_ord_date->value),
            'OrderNo' => $entity->field_pmc_order_num->value,
            'OrderLineNo' => $entity->field_pmc_ord_l_num->value,
            'CustomerID' => $entity->field_pmc_user_id->value,
            'PersonifyBranchId' => $branch_id,
            'ProductCode' => $data->ProductCode,
            'ErrorMessage' => $entity->field_pmc_status->value,
            'EditLink' => Link::createFromRoute(
              t('Edit'),
              'entity.personify_mindbody_cache.edit_form',
              ['personify_mindbody_cache' => $entity->id()],
              [
                'absolute' => TRUE,
                'attributes' => [
                  'target' => '_blank',
                ],
              ]
            ),
          ];
        }
      }
    }

    if (empty($new_failed_orders)) {
      return;
    }

    // Prepare render array with data.
    $table = [
      '#type' => 'table',
      '#header' => [
        t('Date'),
        t('Order No'),
        t('OrderLine No'),
        t('Customer ID'),
        t('Personify Branch Id'),
        t('Product Code'),
        t('Error Message'),
        t('Edit'),
      ],
      '#rows' => $new_failed_orders,
    ];
    $rendered_data = $this->renderer->renderRoot($table)->__toString();

    $tokens = [
      'data' => $rendered_data,
      'last_run_date' => date('m/d/Y', $last_run_time),
    ];
    try {
      // Send notifications.
      if (!empty($to = $this->configFactory->get('ymca_mindbody.settings')->get('failed_orders_recipients'))) {
        $lang = $this->languageManager->getCurrentLanguage()->getId();
        $this->mailManager->mail('ymca_mindbody', 'notify_of_failed_orders', $to, $lang, $tokens);
        // Update last run time.
        $this->configFactory->getEditable('ymca_mindbody.settings')->set('failed_orders_notifier_last_run', time())->save();
      }
    }
    catch (\Exception $e) {
      $msg = 'Failed to send email notification. Error: %error';
      $this->logger->critical($msg, ['%error' => $e->getMessage()]);
    }
  }

}
