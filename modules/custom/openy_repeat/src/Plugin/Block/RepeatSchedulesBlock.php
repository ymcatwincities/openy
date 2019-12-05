<?php

namespace Drupal\openy_repeat\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\paragraphs\Entity\Paragraph;
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

    $query = \Drupal::database()->select('node' , 'n');
    $query->join('node__field_session_location', 'l', "n.nid = l.entity_id AND l.bundle = 'session'");
    $query->join('node_field_data', 'nfd', 'l.field_session_location_target_id = nfd.nid');
    $query->condition('n.type', 'session');
    $query->fields('nfd', ['title']);
    $query->orderBy('nfd.title');
    $query->addTag('repeat_schedules_block_locations');
    $result = $query->distinct()->execute()->fetchCol();

    natsort($result);
    return $result;
  }

  /**
   * Return Categories from chain "Session" -> "Class" -> "Activity" -> "Program sub-category".
   *
   * @param array $nids
   *   Array that contains categories nids to exclude.
   *
   * @return array
   */
  public function getCategories(array $nids) {

    $query = \Drupal::database()->select('node_field_data', 'nfd');
    $query->fields('nfd', ['title']);
    $query->condition('type', 'activity');
    $query->condition('status', 1);
    if ($nids) {
      $query->condition('nid', $nids, 'NOT IN');
    }
    $query->orderBy('title');
    $query->addTag('repeat_schedules_block_categories');
    $result = $query->execute()->fetchCol();

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
    $excluded_categories = [];
    $checked_categories = [];
    if (!empty($categories)) {
      $checked_categories = explode(',', $categories);
    }
    $checked_locations = [];
    if (!empty($locations)) {
      $checked_locations = explode(',', $locations);
    }
    // Find repeat_schedules paragraph.
    if (!$node = \Drupal::routeMatch()->getParameter('node')) {
      return [];
    }
    $paragraphs = $node->field_content->referencedEntities();
    foreach ($paragraphs as $p) {
      if ($p->bundle() == 'repeat_schedules') {
        $filters = self::getFiltersSettings($p);
        $pdf_only = !$p->field_prgf_rs_pdf_only_view->isEmpty() ? $p->field_prgf_rs_pdf_only_view->getValue()[0]['value'] : '';
        $schedule_excl = !$p->field_prgf_repeat_schedule_excl->isEmpty() ? $p->field_prgf_repeat_schedule_excl->getValue() : '';
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
        if ($schedule_excl) {
          foreach ($schedule_excl as  $item) {
            $excluded_categories[]  = $item['target_id'];
          }
        }
      }
    }

    return [
      '#theme' => 'openy_repeat_schedule_dashboard',
      '#locations' => $this->getLocations(),
      '#categories' => $this->getCategories($excluded_categories),
      '#checked_locations' => $checked_locations,
      '#checked_categories' => $checked_categories,
      '#filters' => $filters,
      '#cache' => ['contexts' => ['url.path', 'url.query_args']],
    ];
  }

  /**
   * Gets value of paragraph filters field.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $p
   *  The paragraph to take data from.
   *
   * @return array
   *  An associative array of values.
   */
  public static function getFiltersSettings(Paragraph $p) {
    if ($p->field_prgf_repeat_schedule_filt->isEmpty()) {
      return [];
    }

    $filters = [];
    foreach ($p->field_prgf_repeat_schedule_filt->getValue() as $f) {
      $filters[$f['value']] = $f['value'];
    }

    return $filters;
  }

}
