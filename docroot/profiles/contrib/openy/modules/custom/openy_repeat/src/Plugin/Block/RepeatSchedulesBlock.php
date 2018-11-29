<?php

namespace Drupal\openy_repeat\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Provides a 'Repeat Schedules' block.
 *
 * @Block(
 *   id = "repeat_schedules_block",
 *   admin_label = @Translation("Repeat Schedules Block"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class RepeatSchedulesBlock extends BlockBase {

  /**
   * Return Location from "Session" node type.
   *
   * @return array
   */
  public function getLocations() {
    $sql = "SELECT DISTINCT nd.title as location 
            FROM {node} n
            INNER JOIN node__field_session_location l ON n.nid = l.entity_id AND l.bundle = 'session'
            INNER JOIN node_field_data nd ON l.field_session_location_target_id = nd.nid
            WHERE n.type = 'session'
            ORDER BY location ASC";

    $connection = \Drupal::database();
    $query = $connection->query($sql);

    $result = $query->fetchCol();
    natsort($result);
    return $result;
  }

  /**
   * Return Categories from chain "Session" -> "Class" -> "Activity" -> "Program sub-category".
   *
   * @return array
   */
  public function getCategories() {
    $sql = "SELECT title 
            FROM {node_field_data} n
            WHERE n.type = 'activity'
            AND n.status = '1'
            ORDER BY title ASC";

    $connection = \Drupal::database();
    $query = $connection->query($sql);
    $result = $query->fetchCol();
    natsort($result);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $request_stack = \Drupal::service('request_stack');
    $query = $request_stack->getCurrentRequest()->query;
    $locations = $query->get('locations');
    $categories = $query->get('categories');
    $checked_categories = [];
    if (!empty($categories)) {
      $checked_categories = explode(',', $categories);
    }
    $checked_locations = [];
    if (!empty($locations)) {
      $checked_locations = explode(',', $locations);
    }
    // Find repeat_schedules paragraph.
    $node = \Drupal::routeMatch()->getParameter('node');
    $paragraphs = $node->field_content->referencedEntities();
    foreach ($paragraphs as $p) {
      if ($p->bundle() == 'repeat_schedules') {
        $pdf_only = !$p->field_prgf_rs_pdf_only_view->isEmpty() ? $p->field_prgf_rs_pdf_only_view->getValue()[0]['value'] : '';
        // Setup redirect to PDF generation route if pdf only option is enabled.
        if ($pdf_only) {
          $p_categories = $p->field_prgf_repeat_schedule_categ->referencedEntities();
          $q_p_categories = [];
          foreach ($p_categories as $p_category) {
            $q_p_categories[] = $p_category->getTitle();
          }
          $query = [
            'category' => implode(',', $q_p_categories),
            'mode' => 'day',
          ];
          $path = Url::fromRoute('openy_repeat.pdf', [], ['query' => $query])->toString();
          $response = new RedirectResponse($path, 302);
          $response->send();
        }
      }
    }
    return [
      '#theme' => 'openy_repeat_schedule_dashboard',
      '#locations' => $this->getLocations(),
      '#categories' => $this->getCategories(),
      '#checked_locations' => $checked_locations,
      '#checked_categories' => $checked_categories,
    ];
  }

}
