<?php

namespace Drupal\ymca_frontend\Controller;

use Drupal\Core\Render\Element;
use Drupal\node\NodeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Controller for Scheduler view mode of Location CT.
 */
class YMCALocationSchedulesController {

  use StringTranslationTrait;

  /**
   * Set Markup.
   */
  public function content(NodeInterface $node) {
    $node_view = node_view($node, 'schedules');
    $markup = render($node_view);

    return array(
      '#markup' => $markup,
    );
  }

  /**
   * Set Title.
   */
  public function setTitle(NodeInterface $node) {
    return $this->t('Schedules');
  }

}
