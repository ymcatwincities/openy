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

    // Replace field_content in Landing page.
    $node = Node::load($landing_page_id);

    /** @var \Drupal\Core\Entity\EntityInterface $fieldEntity */
    $fieldEntity = $node->get('field_content')->entity;

    $viewBuilder = \Drupal::entityTypeManager()->getViewBuilder($fieldEntity->getEntityTypeId());
    $fieldView = $viewBuilder->view($fieldEntity, 'default');

    $renderField =  \Drupal::service('renderer')->renderRoot($fieldView);

    // TODO Remove one extra div after $renderField
    // Paragraph type previous landing page must be the same as new one.
    $replacementClass = '.' . $fieldEntity->getEntityTypeId() . '--type--' . str_replace('_', '-', $fieldEntity->bundle());
    $response->addCommand(new ReplaceCommand('.node__content > .container .wrapper-field-content ' . $replacementClass, $renderField));

    // Set 'active' class to menu link.
    $response->addCommand(new InvokeCommand('.campaign-menu a', 'removeClass', ['active']));
    $response->addCommand(new InvokeCommand('.campaign-menu a.node-' . $landing_page_id, 'addClass', ['active']));

    return $response;
  }

}