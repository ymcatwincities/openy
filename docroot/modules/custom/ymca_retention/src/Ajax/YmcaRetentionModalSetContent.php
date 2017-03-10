<?php

namespace Drupal\ymca_retention\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Defines an AJAX command to set YMCA Retention modal content.
 *
 * @ingroup ajax
 */
class YmcaRetentionModalSetContent implements CommandInterface {

  /**
   * The target element identifier.
   *
   * @var string
   */
  protected $targetId;

  /**
   * Construct YmcaRetentionModalSetContent object.
   *
   * @param string $target_id
   *   The target element identifier.
   */
  public function __construct($target_id) {
    $this->targetId = $target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'ymcaRetentionModalSetContent',
      'arguments' => ['targetId' => $this->targetId],
    ];
  }

}
