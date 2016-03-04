<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\Queue\DatabaseQueue.
 */

namespace Drupal\purge\Plugin\Purge\Queue;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Queue\QueueDatabaseFactory;
use Drupal\purge\Plugin\Purge\Queue\QueueInterface;
use Drupal\purge\Plugin\Purge\Queue\QueueBase;

/**
 * A \Drupal\purge\Plugin\Purge\Queue\QueueInterface compliant database backed queue.
 *
 * @PurgeQueue(
 *   id = "database",
 *   label = @Translation("Database"),
 *   description = @Translation("A scalable database backed queue."),
 * )
 */
class DatabaseQueue extends QueueBase implements QueueInterface {

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * @var \Drupal\Core\Queue\QueueDatabaseFactory
   */
  protected $queueDatabase;

  /**
   * Holds the 'queue.database' queue retrieved from Drupal.
   */
  protected $dbqueue;

  /**
   * The name of the queue this instance is working with.
   *
   * @var string
   */
  protected $name;

  /**
   * Constructs a \Drupal\purge\Plugin\Purge\Queue\Database object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   The active database connection.
   * @param \Drupal\Core\Queue\QueueDatabaseFactory $queue_database
   *   The 'queue.database' service creating database queue objects.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection, QueueDatabaseFactory $queue_database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->connection = $connection;
    $this->queueDatabase = $queue_database;

    // The name of the database queue we are storing items in.
    $this->name = 'purge';

    // Instantiate the database queue using the factory.
    $this->dbqueue = $this->queueDatabase->get($this->name);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('queue.database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function createItem($data) {
    if ($item_id = $this->dbqueue->createItem($data)) {
      return (int) $item_id;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function createItemMultiple(array $items) {
    $item_ids = $records = [];

    // Build a array with all exactly records as they should turn into rows.
    $time = time();
    foreach ($items as $data) {
      $records[] = [
        'name' => $this->name,
        'data' => serialize($data),
        'created' => $time,
      ];
    }

    // Insert all of them using just one multi-row query.
    $query = db_insert('queue')->fields(['name', 'data', 'created']);
    foreach ($records as $record) {
      $query->values($record);
    }

    // Execute the query and finish the call.
    if ($id = $query->execute()) {
      $id = (int)$id;

      // A multiple row-insert doesn't give back all the individual IDs, so
      // calculate them back by applying subtraction.
      for ($i = 1; $i <= count($records); $i++) {
        $item_ids[] = $id;
        $id++;
      }
      return $item_ids;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function numberOfItems() {
    return (int)$this->dbqueue->numberOfItems();
  }

  /**
   * {@inheritdoc}
   *
   * @todo
   *   \Drupal\Core\Queue\DatabaseQueue::claimItem() doesn't included expired
   *   items in its query which means that its essentially broken and makes our
   *   tests fail. Therefore we overload the implementation with one that does
   *   it accurately. However, this should flow back to core.
   */
  public function claimItem($lease_time = 3600) {

    // Claim an item by updating its expire fields. If claim is not successful
    // another thread may have claimed the item in the meantime. Therefore loop
    // until an item is successfully claimed or we are reasonably sure there
    // are no unclaimed items left.
    while (TRUE) {
      $conditions = [':name' => $this->name, ':now' => time()];
      $item = $this->connection->queryRange('SELECT * FROM {queue} q WHERE name = :name AND ((expire = 0) OR (:now > expire)) ORDER BY created, item_id ASC', 0, 1, $conditions)->fetchObject();
      if ($item) {
        $item->item_id = (int)$item->item_id;
        $item->expire = (int)$item->expire;

        // Try to update the item. Only one thread can succeed in UPDATEing the
        // same row. We cannot rely on REQUEST_TIME because items might be
        // claimed by a single consumer which runs longer than 1 second. If we
        // continue to use REQUEST_TIME instead of the current time(), we steal
        // time from the lease, and will tend to reset items before the lease
        // should really expire.
        $update = $this->connection->update('queue')
          ->fields([
            'expire' => time() + $lease_time,
          ])
          ->condition('item_id', $item->item_id);

        // If there are affected rows, this update succeeded.
        if ($update->execute()) {
          $item->data = unserialize($item->data);
          return $item;
        }
      }
      else {
        // No items currently available to claim.
        return FALSE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function claimItemMultiple($claims = 10, $lease_time = 3600) {
    $returned_items = $item_ids = [];

    // Retrieve all items in one query.
    $conditions = [':name' => $this->name, ':now' => time()];
    $items = $this->connection->queryRange('SELECT * FROM {queue} q WHERE name = :name AND ((expire = 0) OR (:now > expire)) ORDER BY created, item_id ASC', 0, $claims, $conditions);

    // Iterate all returned items and unpack them.
    foreach ($items as $item) {
      if (!$item) continue;
      $item_ids[] = $item->item_id;
      $item->item_id = (int)$item->item_id;
      $item->expire = (int)$item->expire;
      $item->data = unserialize($item->data);
      $returned_items[] = $item;
    }

    // Update the items (marking them claimed) in one query.
    if (count($returned_items)) {
      $this->connection->update('queue')
        ->fields([
          'expire' => time() + $lease_time,
        ])
        ->condition('item_id', $item_ids, 'IN')
        ->execute();
    }

    // Return the generated items, whether its empty or not.
    return $returned_items;
  }

  /**
   * Implements \Drupal\Core\Queue\QueueInterface::releaseItem().
   */
  public function releaseItem($item) {
    return $this->connection->update('queue')
      ->fields([
        'expire' => 0,
        'data' => serialize($item->data),
      ])
      ->condition('item_id', $item->item_id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function releaseItemMultiple(array $items) {
    // Extract item IDs and serialized data so comparing becomes easier.
    $items_data = [];
    foreach ($items as $item) {
      $items_data[intval($item->item_id)] = serialize($item->data);
    }

    // Figure out which items have changed their data and update just those.
    $originals = $this->connection
      ->select('queue', 'q')
      ->fields('q', ['item_id', 'data'])
      ->condition('item_id', array_keys($items_data), 'IN')
      ->execute();
    foreach ($originals as $original) {
      $item_id = intval($original->item_id);
      if ($original->data !== $items_data[$item_id]) {
        $this->connection->update('queue')
          ->fields(['data' => $items_data[$item_id]])
          ->condition('item_id', $item_id)
          ->execute();
      }
    }

    // Update the lease time in one single query and resolve what to return.
    $update = $this->connection->update('queue')
      ->fields(['expire' => 0])
      ->condition('item_id', array_keys($items_data), 'IN')
      ->execute();
    if ($update) {
      return [];
    }
    else {
      return $items;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItem($item) {
    return $this->dbqueue->deleteItem($item);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItemMultiple(array $items) {
    $item_ids = [];
    foreach ($items as $item) {
      $item_ids[] = $item->item_id;
    }
    $this->connection
      ->delete('queue')
      ->condition('item_id', $item_ids, 'IN')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function createQueue() {
    // All tasks are stored in a single database table (which is created when
    // Drupal is first installed) so there is nothing we need to do to create
    // a new queue.
    return $this->dbqueue->createQueue();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteQueue() {
    return $this->dbqueue->deleteQueue();
  }

  /**
   * {@inheritdoc}
   */
  public function selectPage($page = 1) {
    if (($page < 1) || !is_int($page)) {
      throw new \LogicException('Parameter $page has to be a positive integer.');
    }

    $items = [];
    $limit = $this->selectPageLimit();
    $resultset = $this->connection
      ->select('queue', 'q')
      ->fields('q', ['item_id', 'expire', 'data'])
      ->orderBy('q.created', 'DESC')
      ->condition('name', $this->name)
      ->range((($page - 1) * $limit), $limit)
      ->execute();
    foreach ($resultset as $item) {
      if (!$item) continue;
      $item->item_id = (int)$item->item_id;
      $item->expire = (int)$item->expire;
      $item->data = unserialize($item->data);
      $items[] = $item;
    }
    return $items;
  }

}
