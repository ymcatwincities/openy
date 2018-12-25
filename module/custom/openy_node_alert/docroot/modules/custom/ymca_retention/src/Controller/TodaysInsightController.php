<?php

namespace Drupal\ymca_retention\Controller;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\ymca_retention\Entity\Member;
use Drupal\ymca_retention\AnonymousCookieStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TodaysInsightController.
 */
class TodaysInsightController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct TodaysInsightController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * Return leaderboard for specified branch id.
   */
  public function todaysInsightJson() {

    $member_id = AnonymousCookieStorage::get('ymca_retention_member');
    if (!$member_id) {
      return new JsonResponse();
    }

    $config = $this->config('ymca_retention.bonus_codes_settings');
    $general_config = $this->config('ymca_retention.general_settings');

    $date = (new \DateTime($general_config->get('date_campaign_open')))->setTime(0, 0);
    $date_end = (new \DateTime($general_config->get('date_campaign_close')))->setTime(0, 0);

    $bonus_codes = $config->get('bonus_codes');

    if ($date <= $date_end) {
      $now = new \DateTime();
      $now->format('Y-m-d H:i:s');

      // Get which day to show.
      $delta = $date->diff($now)->format("%a");

      if (!isset($bonus_codes[$delta])) {
        return new JsonResponse("No content for today");
      }
      $nid = $bonus_codes[$delta]['reference'];
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      if (!empty($node)) {
        $view_builder = $this->entityTypeManager->getViewBuilder('node');
        $nodeview = $view_builder->view($node);
        $renderer = \Drupal::service('renderer');
        $html = $renderer->render($nodeview);
        return new JsonResponse($html);
      }
      else {
        return new JsonResponse("No content for today");
      }
    }

  }

}
