<?php

namespace Drupal\webforms;

use Drupal\contact\Entity\Message;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\file_entity\Entity\FileEntity;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Class WebformsSubmissionsArchiver
 * @package Drupal\webforms
 */
class WebformsSubmissionsArchiver {

  /**
   * Query Factory to work with.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  private $queryFactory;

  /**
   * Entity Type Manager to work with.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $entityTypeManager;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  private $logger;

  /**
   * Configs.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  private $config;

  public function __construct(
    QueryFactory $queryFactory,
    EntityTypeManager $entityTypeManager,
    LoggerChannel $logger,
    ConfigFactory $configFactory
  ) {
    $this->queryFactory = $queryFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
    $this->config = $configFactory;
  }

  /**
   * Archiving loop, should be run from cron.
   */
  public function archive() {
    $tz = $this->config->get('system.date')->get('timezone')['default'];
    // Get first from list of contact_storage entities.
    $end_of_month = new \DateTime(
      date('Y-m-d', strtotime(date('Y-m'))),
      new \DateTimeZone($tz)
    );
    $end_time = $end_of_month->getTimeStamp() - 1;

    $message_ids = \Drupal::entityQuery('contact_message')
      ->condition('created', $end_time, '<=')
      ->sort('created', 'ASC')
      ->range(0, 1)
      ->execute();
    // Loop through all of them to find the data, older than a month.
    $entities = $this->entityTypeManager->getStorage('contact_message')
      ->loadMultiple($message_ids);
    /** @var Message $entity */
    $entity = array_shift($entities);
    $form_name = $entity->bundle();
    $created = (int) $entity->created->get(0)->getValue()['value'];
    $created_month = date('m', $created);
    $created_year = date('Y', $created);

    $end = new \DateTime(
      date(
        'Y-m-d',
        strtotime($created_year . '-' . $created_month . ' +1 month')
      ), new \DateTimeZone($tz)
    );
    $end = $end->getTimeStamp() - 1;
    $start = new \DateTime(
      date('Y-m-d', strtotime($created_year . '-' . $created_month)),
      new \DateTimeZone($tz)
    );
    $start = $start->getTimeStamp();

    $month_ids = \Drupal::entityQuery('contact_message')
      ->condition('created', [$start, $end], 'BETWEEN')
      ->condition('contact_form', $form_name)
      ->execute();

    // Archive a single month data, store to local Archive entity.
    // We have up to 1000 entities per month.
    $month_entities = $this->entityTypeManager->getStorage('contact_message')
      ->loadMultiple($month_ids);
    if (count($month_entities) > 0) {
      /** @var ViewExecutable $get_views */
      $get_views = Views::getView('cm_archive_csv');
      $get_views->setArguments(
        [$form_name, implode(',', array_keys($month_ids))]
      );
      $out = $get_views->render('rest_export_1');
      /** @var ViewsRenderPipelineMarkup $markup */
      $markup = $out['#markup'];
      // $compressed = gzcompress($out['#markup']->__toString(), 3);
      $file = FileEntity::create(['bundle' => 'archive', 'type' => 'archive']);
      $filename = $form_name . '_' . date('Y_m_d', $start) . '_to_' . date(
          'Y_m_d',
          $end
        ) . '.csv.gz';
      $file->setFilename($filename);
      $file->setFileUri("public://$filename");
      $file->setMimeType('application/x-gzip');
      $file->setPermanent();
      $fp = gzopen($file->getFileUri(), 'w3');
      gzwrite($fp, $out['#markup']->__toString());
      gzclose($fp);
      $file->save();

      // Check if the file is greater than a zero, remove archived data.
      if ($file->getSize() != 0) {
        $this->logger->debug(
          'Processed into archive: %count entities of type: %type. First entity created: %created. Timeframe: %start, %end',
          [
            '%created' => date('Y/m/d', $created),
            '%start' => date('Y/m/d', $start),
            '%end' => date('Y/m/d', $end),
            '%count' => count($month_ids),
            '%type' => $form_name
          ]
        );
        $this->entityTypeManager->getStorage('contact_message')->delete(
          $month_entities
        );
      }
    }

  }
}
