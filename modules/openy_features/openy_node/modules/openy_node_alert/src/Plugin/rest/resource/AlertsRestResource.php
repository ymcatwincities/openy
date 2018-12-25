<?php

namespace Drupal\openy_node_alert\Plugin\rest\resource;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "alerts_rest_resource",
 *   label = @Translation("OpenY Alerts resource"),
 *   uri_paths = {
 *     "canonical" = "/alerts"
 *   }
 * )
 */
class AlertsRestResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new AlertsRestResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user, $aliasManager, $pathMatcher, $pathCurrent) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
    $this->aliasManager = $aliasManager;
    $this->pathMatcher = $pathMatcher;
    $this->pathCurrent = $pathCurrent;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('openy_node_alert'),
      $container->get('current_user'),
      $container->get('path.alias_manager'),
      $container->get('path.matcher'),
      $container->get('path.current')
    );
  }

  /**
   * Responds to GET requests.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get() {

    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }
    $alerts = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'alert', 'status' => 1]);
    $sendAlerts = [];
    /** @var \Drupal\node\Entity\Node $alert */
    foreach ($alerts as $alert) {
      if (!$alert->hasField('field_alert_visibility_pages')) {
        $url = $alert->field_alert_link->uri != NULL ? Url::fromUri($alert->field_alert_link->uri)->setAbsolute()->toString() : null;
        if ($alert->hasField('field_alert_belongs') && !$alert->field_alert_belongs->isEmpty() && !$alert->field_alert_place->isEmpty()) {
          $refid = $alert->field_alert_belongs->target_id;
          $alias = $this->aliasManager->getAliasByPath('/node/' . $refid);
          if ($_GET['uri'] != $alias) {
            // Do not show alerts for current page.
            continue;
          }
          $sendAlerts[$alert->field_alert_place->value]['local'][] = [
            'title' => $alert->getTitle(),
            'textColor' => $alert->field_alert_text_color->entity->field_color->value,
            'bgColor' => $alert->field_alert_color->entity->field_color->value,
            'description' => $alert->field_alert_description->value,
            'iconColor' => $alert->field_alert_icon_color->entity->field_color->value,
            'linkUrl' => $url,
            'linkText' => $alert->field_alert_link->title,
            'id' => $alert->id(),
          ];

        }
        elseif ($alert->hasField('field_alert_belongs') && $alert->field_alert_belongs->isEmpty() && !$alert->field_alert_place->isEmpty()) {
          $sendAlerts[$alert->field_alert_place->value]['global'][] = [
            'title' => $alert->getTitle(),
            'textColor' => $alert->field_alert_text_color->entity->field_color->value,
            'bgColor' => $alert->field_alert_color->entity->field_color->value,
            'description' => $alert->field_alert_description->value,
            'iconColor' => $alert->field_alert_icon_color->entity->field_color->value,
            'linkUrl' => $url,
            'linkText' => $alert->field_alert_link->title,
            'id' => $alert->id(),
          ];
        }
        else {
          throw new \HttpException('Field configuration for alerts is wrong');
        }
      }
      else {
        // TODO: Implement new logic from OpenY code.
        if ($this->checkVisibility($alert)) {
          $sendAlerts[$alert->field_alert_place->value]['local'][] = [
            'title' => $alert->getTitle(),
            'textColor' => $alert->field_alert_text_color->entity->field_color->value,
            'bgColor' => $alert->field_alert_color->entity->field_color->value,
            'description' => $alert->field_alert_description->value,
            'iconColor' => $alert->field_alert_icon_color->entity->field_color->value,
            'linkUrl' => $url,
            'linkText' => $alert->field_alert_link->title,
            'id' => $alert->id(),
          ];
        }
      }
    }
    return new ModifiedResourceResponse($sendAlerts, 200);
  }

  /**
   * Check visibility of alert.
   *
   * @param $node
   * @return bool
   */
  private function checkVisibility(\Drupal\node\NodeInterface $node) {

    $pages = '';
    if ($node->hasField('field_alert_visibility_pages')) {
      $pages = $node->get('field_alert_visibility_pages')->value;
    }

    $state = 'include';
    if ($node->hasField('field_alert_visibility_state')) {
      $state = $node->get('field_alert_visibility_state')->value;
    }

    $pages = Unicode::strtolower($pages);
    if (!$pages) {
      // Global alert.
      return TRUE;
    }


    // Convert path to lowercase. This allows comparison of the same path.
    // with different case. Ex: /Page, /page, /PAGE.
    // Compare the lowercase path alias (if any) and internal path.
    $current_path = $_GET['uri'];
    $path = $this->aliasManager->getAliasByPath($current_path);
    $path = Unicode::strtolower($path);

    // Do not trim a trailing slash if that is the complete path.
    $path = $path === '/' ? $path : rtrim($path, '/');

    $is_path_match = $this->pathMatcher->matchPath($path, $pages);
    if ($state == 'include' && $is_path_match || $state == 'exclude' && !$is_path_match) {
      // Local alert.
      return TRUE;
    }

    return FALSE;
  }

}
