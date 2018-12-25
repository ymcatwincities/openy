<?php

namespace Drupal\geolocation_demo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Field\WidgetPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for geolocation_demo module routes.
 */
class DemoWidgetFormsController extends ControllerBase {

  /**
   * Drupal\Core\Field\WidgetPluginManager definition.
   *
   * @var \Drupal\Core\Field\WidgetPluginManager
   */
  protected $pluginManagerFieldWidget;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(WidgetPluginManager $plugin_manager_field_widget, EntityTypeManager $entity_type_manager) {
    $this->pluginManagerFieldWidget = $plugin_manager_field_widget;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.field.widget'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Return the non-functional geocoding widget form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Page request object.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return array
   *   A render array.
   */
  public function widgets(Request $request, RouteMatchInterface $route_match) {

    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->entityTypeManager->getStorage('node')->create([
      'type' => 'geolocation_default_article',
    ]);

    $items = $node->get('field_geolocation_demo_single');

    $field_definition = $node->getFieldDefinition('field_geolocation_demo_single');

    $widget_settings = [
      'field_definition' => $field_definition,
      'form_mode' => 'default',
      // No need to prepare, defaults have been merged in setComponent().
      'prepare' => TRUE,
      'configuration' => [
        'settings' => [],
        'third_party_settings' => [],
      ],
    ];

    $form_state = new FormState();
    $form = [];

    foreach ([
      'geolocation_googlegeocoder',
      'geolocation_latlng',
      'geolocation_html5',
    ] as $widget_id) {
      $widget = $this->pluginManagerFieldWidget->getInstance(array_merge_recursive($widget_settings, ['configuration' => ['type' => $widget_id]]));

      $form[$widget_id] = [
        '#type' => 'fieldset',
        '#title' => $widget->getPluginDefinition()['label'],
        'widget' => $widget->formElement($items, 0, [], $form, $form_state),
      ];
    }

    return $form;
  }

}
