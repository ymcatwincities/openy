<?php

namespace Drupal\queue_ui;

use Drupal\Core\Database\Database;

class QueueUIDatabaseQueue implements QueueUIInterface {

  public $inspect;

  public function __construct() {
    $this->inspect = TRUE;
  }

  /**
   * SystemQueue implements all default QueueUI methods.
   *
   * @return array
   *  An array of available QueueUI methods. Array key is system name of the
   *  operation, array key value is the display name.
   */
  public function getOperations() {
    return [
      'view' => t('View'),
      'release' => t('Release'),
      'delete' => t('Delete'),
    ];
  }

  /**
   * View the queue items in a queue and expose additional methods for inspection.
   *
   * @param string $queue_name
   * @return string
   */
  public function inspect($queue_name) {
    $query = Database::getConnection('default')->select('queue', 'q');
    $query->addField('q', 'item_id');
    $query->addField('q', 'expire');
    $query->addField('q', 'created');
    $query->condition('q.name', $queue_name);
    $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query = $query->limit(25);
    $result = $query->execute();

    $header = [
      t('Item ID'),
      t('Expires'),
      t('Created'),
      ['data' => t('Operations'), 'colspan' => '3'],
    ];

    $rows = [];

    foreach ($result as $item) {
      $row = [];
      $row[] = $item->item_id;
      $row[] = ($item->expire ? date(DATE_RSS, $item->expire) : $item->expire);
      $row[] = date(DATE_RSS, $item->created);

      foreach ($this->getOperations() as $op => $title) {
        $row[] = l($title, QUEUE_UI_BASE . "/$queue_name/$op/$item->item_id");
      }

      $rows[] = ['data' => $row];
    }

    $theme_table = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows
    ];
    $table = \Drupal::service('renderer')->render($theme_table);

    $theme_pager = [
      '#theme' => 'pager'
    ];
    $pager = \Drupal::service('renderer')->render($theme_pager);

    return $table . $pager;
  }

  /**
   * View the item data for a specified queue item.
   *
   * @param int $item_id
   * @return string
   */
  public function view($item_id) {
    $queue_item = $this->loadItem($item_id);

    $rows[] = [
      'data' => [
        'header' => t('Item ID'),
        'data' => $queue_item->item_id,
      ],
    ];
    $rows[] = [
      'data' => [
        'header' => t('Queue name'),
        'data' => $queue_item->name,
      ],
    ];
    $rows[] = [
      'data' => [
        'header' => t('Expire'),
        'data' => ($queue_item->expire ? date(DATE_RSS, $queue_item->expire) : $queue_item->expire),
      ],
    ];
    $rows[] = [
      'data' => [
        'header' => t('Created'),
        'data' => date(DATE_RSS, $queue_item->created),
      ],
    ];
    $rows[] = [
      'data' => [
        'header' => ['data' => t('Data'), 'style' => 'vertical-align:top'],
        'data' => '<pre>' . print_r(unserialize($queue_item->data), TRUE) . '</pre>',
        // @TODO - should probably do something nicer than print_r here...
      ],
    ];

    $table = [
      '#theme' => 'table',
      '#rows' => $rows
    ];
    return \Drupal::service('renderer')->render($table);
  }

  public function delete($item_id) {
    // @TODO - try... catch...
    drupal_set_message("Deleted queue item " . $item_id);

    Database::getConnection('default')->delete('queue')
      ->condition('item_id', $item_id)
      ->execute();

    return TRUE;
  }

  public function release($item_id) {
    // @TODO - try... catch...
    drupal_set_message("Released queue item " . $item_id);

    Database::getConnection('default')->update('queue')
      ->condition('item_id', $item_id)
      ->fields(['expire' => 0])
      ->execute();

    return TRUE;
  }

  /**
   * Load a specified SystemQueue queue item from the database.
   *
   * @param $item_id
   *  The item id to load
   * @return
   *  Result of the database query loading the queue item.
   */
  private function loadItem($item_id) {
    // Load the specified queue item from the queue table.
    $query = Database::getConnection('default')->select('queue', 'q')
      ->fields('q', ['item_id', 'name', 'data', 'expire', 'created'])
      ->condition('q.item_id', $item_id)
      ->range(0, 1) // item id should be unique
      ->execute();

    foreach ($query as $record) {
      $result[] = $record;
    }

    return $result[0];
  }
}
