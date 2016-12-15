<?php

namespace Drupal\purge\Plugin\Purge\Queue;

use Drupal\Core\DestructableInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\purge\Plugin\Purge\Queue\MemoryQueue;
use Drupal\purge\Plugin\Purge\Queue\QueueInterface;

/**
 * A \Drupal\purge\Plugin\Purge\Queue\QueueInterface compliant file-based queue.
 *
 * @PurgeQueue(
 *   id = "file",
 *   label = @Translation("File"),
 *   description = @Translation("A file-based queue for fast I/O systems."),
 * )
 */
class FileQueue extends MemoryQueue implements QueueInterface, DestructableInterface {

  /**
   * The file under public:// to which the queue buffer gets written to.
   */
  protected $file = 'purge-file.queue';

  /**
   * The separator string to split columns with.
   */
  const SEPARATOR = '|';

  /**
   * Constructs a \Drupal\purge\Plugin\Purge\Queue\File object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->file = DRUPAL_ROOT . '/' . PublicStream::basePath() . '/' . $this->file;
    $this->bufferInitialize();
  }

  /**
   * {@inheritdoc}
   */
  private function bufferInitialize() {
    if (!$this->bufferInitialized) {
      $this->bufferInitialized = TRUE;
      $this->buffer = [];

      // Open and parse the queue file, if it wasn't there during initialization
      // it will automatically get written at some point.
      if (file_exists($this->file)) {
        foreach (file($this->file) as $line) {
          $line = explode(self::SEPARATOR, str_replace("\n", '', $line));
          $item_id = (int) array_shift($line);
          $line[self::EXPIRE] = (int) $line[self::EXPIRE];
          $line[self::CREATED] = (int) $line[self::CREATED];
          $this->buffer[$item_id] = $line;
        }
      }
    }
  }

  /**
   * Commit the buffer to disk.
   */
  public function bufferCommit() {
    $ob = '';
    if (!file_exists($path = dirname($this->file))) {
      if (!mkdir($path, 0777, TRUE)) {
        throw new \Exception("Failed recursive mkdir() to create missing '$path'!");
      }
      if (!file_exists($path)) {
        throw new \Exception("Path '$path' still does not exist after trying mkdir()!");
      }
    }
    if (!$fh = fopen($this->file, 'w')) {
      throw new \Exception('Unable to open file resource to ' . $this->file);
    }
    foreach ($this->buffer as $item_id => $line) {
      $ob .= $item_id . SELF::SEPARATOR . $line[SELF::DATA] . SELF::SEPARATOR
        . $line[SELF::EXPIRE] . SELF::SEPARATOR . $line[SELF::CREATED] . "\n";
    }
    fwrite($fh, $ob);
    fclose($fh);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteQueue() {
    if (file_exists($this->file)) {
      unlink($this->file);
    }
    parent::deleteQueue();
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\purge\Plugin\Purge\Queue\QueueService::reload()
   */
  public function destruct() {
    if ($this->bufferInitialized) {
      $this->bufferCommit();
    }
  }

  /**
   * Trigger a disk commit when the object is destructed.
   */
  public function __destruct() {
    if ($this->bufferInitialized) {
      $this->bufferCommit();
    }
  }

}
