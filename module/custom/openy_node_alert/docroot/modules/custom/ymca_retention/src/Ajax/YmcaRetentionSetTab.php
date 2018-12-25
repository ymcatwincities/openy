<?php

namespace Drupal\ymca_retention\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Defines an AJAX command to set YMCA Retention tab.
 *
 * @ingroup ajax
 */
class YmcaRetentionSetTab implements CommandInterface {

  /**
   * The tab identifier.
   *
   * @var string
   */
  protected $tabId;

  /**
   * Construct YmcaRetentionSetTab object.
   *
   * @param string $tab_id
   *   The tab/accordion item identifier.
   */
  public function __construct($tab_id = NULL) {
    if (!$tab_id) {
      $tab_id = 'tab_1';
    }

    $this->tabId = $tab_id;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'ymcaRetentionSetTab',
      'arguments' => ['tabId' => $this->tabId],
    ];
  }

}
