<?php

namespace Drupal\openy_campaign\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class TeamMemberUIController to show page with UI for Team members.
 */
class TeamMemberUIController extends ControllerBase {

  /**
   * Render view block to show all members table.
   *
   * @return array Render array
   */
  public function showMembers() {
    $build = [
      'view' => views_embed_view('campaign_members', 'members_list_block'),
    ];

    return $build;
  }

}