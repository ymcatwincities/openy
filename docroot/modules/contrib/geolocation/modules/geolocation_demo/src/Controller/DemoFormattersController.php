<?php

namespace Drupal\geolocation_demo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Field\FormatterPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for geolocation_demo module routes.
 */
class DemoFormattersController extends ControllerBase {

  /**
   * Drupal\Core\Field\FormatterPluginManager definition.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  protected $pluginManagerFieldFormatter;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(FormatterPluginManager $plugin_manager_field_formatter, EntityTypeManager $entity_type_manager) {
    $this->pluginManagerFieldFormatter = $plugin_manager_field_formatter;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.field.formatter'),
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
  public function formatters(Request $request, RouteMatchInterface $route_match) {

    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->entityTypeManager->getStorage('node')->create([
      'type' => 'geolocation_default_article',
    ]);

    $items = $node->get('field_geolocation_demo_single');
    $default_language = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $field_definition = $node->getFieldDefinition('field_geolocation_demo_single');

    $widget_settings = [
      'field_definition' => $field_definition,
      'configuration' => [
        'settings' => [
          'tokenized_text' => 'The date is [current-date:html_date] and the latitude value [geolocation_current_item:lat]',
        ],
        'third_party_settings' => [],
      ],
      'view_mode' => 'default',
    ];

    $form = [];

    foreach ([
      'geolocation_latlng',
      'geolocation_map',
      'geolocation_sexagesimal',
      'geolocation_token',
    ] as $formatter_id) {
      $formatter = $this->pluginManagerFieldFormatter->getInstance(array_merge_recursive($widget_settings, ['configuration' => ['type' => $formatter_id]]));

      $form[$formatter_id] = [
        '#type' => 'fieldset',
        '#title' => $formatter->getPluginDefinition()['label'],
        'widget' => $formatter->viewElements($items, $default_language),
      ];
    }

    return $form;
  }

}
