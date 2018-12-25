<?php

namespace Drupal\purge\Plugin\Purge\Queue;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Queue\DatabaseQueue as CoreDatabaseQueue;
use Drupal\purge\Plugin\Purge\Queue\QueueInterface;
use Drupal\purge\Plugin\Purge\Queue\QueueBasePageTrait;
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
class DatabaseQueue extends CoreDatabaseQueue implements QueueInterface {
  use QueueBasePageTrait;

  /**
   * The active Drupal database connection object.
   */
  const TABLE_NAME = 'purge_queue';

  /**
   * Static boolean to determine if we've checked for table installation.
   *
   * @var bool
   */
  protected $table_exists = FALSE;

  /**
   * Constructs a \Drupal\purge\Plugin\Purge\Queue\Database object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object containing the key-value tables.
   */
  public function __construct(Connection $connection) {
    parent::__construct('purge', $connection);
    $this->ensureTableExists();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($container->get('database'));
  }

  /**
   * {@inheritdoc}
   */
  public function createItem($data) {
    $query = $this->connection->insert(static::TABLE_NAME)
      ->fields(array(
        'data' => serialize($data),
        'created' => time(),
      ));
    if ($id = $query->execute()) {
      return (int) $id;
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
        'data' => serialize($data),
        'created' => $time,
      ];
    }

    // Insert all of them using just one multi-row query.
    $query = db_insert(static::TABLE_NAME)->fields(['data', 'created']);
    foreach ($records as $record) {
      $query->values($record);
    }

    // Execute the query and finish the call.
    if ($id = $query->execute()) {
      $id = (int) $id;

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
    return (int) $this->connection->query('SELECT COUNT(item_id) FROM {' . static::TABLE_NAME . '}')
      ->fetchField();
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
      $conditions = [':now' => time()];
      $item = $this->connection->queryRange('SELECT * FROM {' . static::TABLE_NAME . '} q WHERE ((expire = 0) OR (:now > expire)) ORDER BY created, item_id ASC', 0, 1, $conditions)->fetchObject();
      if ($item) {
        $item->item_id = (int) $item->item_id;
        $item->expire = (int) $item->expire;

        // Try to update the item. Only one thread can succeed in UPDATEing the
        // same row. We cannot rely on REQUEST_TIME because items might be
        // claimed by a single consumer which runs longer than 1 second. If we
        // continue to use REQUEST_TIME instead of the current time(), we steal
        // time from the lease, and will tend to reset items before the lease
        // should really expire.
        $update = $this->connection->update(static::TABLE_NAME)
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
    $conditions = [':now' => time()];
    $items = $this->connection->queryRange('SELECT * FROM {' . static::TABLE_NAME . '} q WHERE ((expire = 0) OR (:now > expire)) ORDER BY created, item_id ASC', 0, $claims, $conditions);

    // Iterate all returned items and unpack them.
    foreach ($items as $item) {
      if (!$item) continue;
      $item_ids[] = $item->item_id;
      $item->item_id = (int) $item->item_id;
      $item->expire = (int) $item->expire;
      $item->data = unserialize($item->data);
      $returned_items[] = $item;
    }

    // Update the items (marking them claimed) in one query.
    if (count($returned_items)) {
      $this->connection->update(static::TABLE_NAME)
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
    return $this->connection->update(static::TABLE_NAME)
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
      ->select(static::TABLE_NAME, 'q')
      ->fields('q', ['item_id', 'data'])
      ->condition('item_id', array_keys($items_data), 'IN')
      ->execute();
    foreach ($originals as $original) {
      $item_id = intval($original->item_id);
      if ($original->data !== $items_data[$item_id]) {
        $this->connection->update(static::TABLE_NAME)
          ->fields(['data' => $items_data[$item_id]])
          ->condition('item_id', $item_id)
          ->execute();
      }
    }

    // Update the lease time in one single query and resolve what to return.
    $update = $this->connection->update(static::TABLE_NAME)
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
    return parent::deleteItem($item);
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
      ->delete(static::TABLE_NAME)
      ->condition('item_id', $item_ids, 'IN')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function createQueue() {
    // All tasks are stored in a single database table (which is created when
    // this class instantiates) so there is nothing we need to do to create
    // a new queue.
  }

  /**
   * {@inheritdoc}
   */
  public function deleteQueue() {
    $this->connection->delete(static::TABLE_NAME)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function ensureTableExists() {
    // Wrap ::ensureTableExists() to prevent expensive duplicate code paths.
    if (!$this->table_exists) {
      if (parent::ensureTableExists()) {
        $this->table_exists = TRUE;
        return TRUE;
      }
    }
    return $this->table_exists;
  }

  /**
   * {@inheritdoc}
   */
  public function schemaDefinition() {
    // Reuse core's schema as was around Drupal 8.1.7. However, we are in no way
    // fully depending on core and can - when required - hardcode the full
    // schema if core decided to change it significantly.
    $schema = parent::schemaDefinition();
    unset($schema['fields']['name']);
    unset($schema['indexes']['name_created']);
    $schema['description'] = "Queue items for the purge database queue plugin.";
    $schema['indexes']['created'] = ['created'];
    return $schema;
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
      ->select(static::TABLE_NAME, 'q')
      ->fields('q', ['item_id', 'expire', 'data'])
      ->orderBy('q.created', 'DESC')
      ->range((($page - 1) * $limit), $limit)
      ->execute();
    foreach ($resultset as $item) {
      if (!$item) continue;
      $item->item_id = (int) $item->item_id;
      $item->expire = (int) $item->expire;
      $item->data = unserialize($item->data);
      $items[] = $item;
    }
    return $items;
  }

}
