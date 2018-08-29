<?php

namespace Drupal\openy_node_alert\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\views\Entity\View;
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
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
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
      $container->get('current_user')
    );
  }

  /**
   * Responds to GET requests.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return \Drupal\rest\ResourceResponse
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
      if ($alert->hasField('field_alert_belongs') && !$alert->field_alert_belongs->isEmpty() && !$alert->field_alert_place->isEmpty()) {
        $refid = $alert->field_alert_belongs->target_id;
        $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $refid);
        if ($_GET['uri'] != $alias) {
          // Do not show alerts for not current page.
          continue;
        }
        $sendAlerts[$alert->field_alert_place->value]['local'][] = [
          'title' => $alert->getTitle(),
          'textColor' => $alert->field_alert_text_color->entity->field_color->value,
          'description' => $alert->field_alert_description->value,
          'color' => $alert->field_alert_color->entity->field_color->value,
        ];
      }
      elseif ($alert->hasField('field_alert_belongs') && $alert->field_alert_belongs->isEmpty() && !$alert->field_alert_place->isEmpty()) {
        $sendAlerts[$alert->field_alert_place->value]['global'][] = [
          'title' => $alert->getTitle(),
          'textColor' => $alert->field_alert_text_color->entity->field_color->value,
          'description' => $alert->field_alert_description->value,
          'color' => $alert->field_alert_color->entity->field_color->value,
        ];
      }
      else {
        throw new \HttpException('Field configuration for alerts is wrong');
      }
    }
    return new ResourceResponse($sendAlerts, 200);
  }

}
