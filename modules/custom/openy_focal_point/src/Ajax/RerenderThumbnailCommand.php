<?php

namespace Drupal\openy_focal_point\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Defines an AJAX command that closes the current active dialog.
 *
 * @ingroup ajax
 */
class RerenderThumbnailCommand implements CommandInterface {

  /**
   * A CSS selector string of the dialog to close.
   *
   * @var string
   */
  protected $selector;

  /**
   * Constructs a CloseDialogCommand object.
   *
   * @param string $selector
   *   A CSS selector string of the dialog to close.
   * @param bool $persist
   *   (optional) Whether to persist the dialog in the DOM or not.
   */
  public function __construct($selector) {
    $this->selector = $selector;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'rerenderThumbnail',
      'selector' => $this->selector,
    ];
  }

}
