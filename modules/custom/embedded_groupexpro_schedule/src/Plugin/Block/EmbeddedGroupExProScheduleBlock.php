<?php

namespace Drupal\embedded_groupexpro_schedule\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;

/**
 * Provides a block with Embedded GroupEx Pro Schedule.
 *
 * @Block(
 *   id = "embedded_groupexpro_schedule_block",
 *   admin_label = @Translation("Embedded GroupEx Pro Schedule"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class EmbeddedGroupExProScheduleBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $account = \Drupal::config('embedded_groupexpro_schedule.settings')->get('account');

    if (empty($account)) {
      // If the account id is not set, set an error and return empty.
      $link = Link::createFromRoute(t('Set the id'), 'embeddedgroupexpro.settings');
      drupal_set_message(t('The GroupEx Pro account id is not set. @link.', ['@link' => $link->toString()]), 'error', TRUE);
      return [];
    }

    return [
      '#theme' => 'embedded_groupexpro_schedule',
      '#account' => $account,
    ];
  }

}
