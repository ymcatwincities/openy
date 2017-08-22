<?php

namespace Drupal\openy_campaign\Controller;

use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\node\Entity\Node;

/**
 * Class CampaignMenuController.
 */
class CampaignMenuController extends ControllerBase {

  /**
   * Show needed content on Campaign node
   *
   * @param int $landing_page_id Landing page node ID to get new content for replacement.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function showPageContent($landing_page_id) {
    $response = new AjaxResponse();

    $node = Node::load($landing_page_id);
    $viewBuilder = \Drupal::entityTypeManager()->getViewBuilder('node');

    // Replace field_content in Landing page.
    $fieldContent = $node->get('field_content');
    $fieldView = $viewBuilder->viewField($fieldContent, 'full');

    // TODO Remove one extra after viewField
    $response->addCommand(new ReplaceCommand('.node__content > .container .wrapper-field-content', render($fieldView)));

    // Set 'active' class to menu link.
    $response->addCommand(new InvokeCommand('.campaign-menu a', 'removeClass', ['active']));
    $response->addCommand(new InvokeCommand('.campaign-menu a.node-' . $landing_page_id, 'addClass', ['active']));

    return $response;
  }

}