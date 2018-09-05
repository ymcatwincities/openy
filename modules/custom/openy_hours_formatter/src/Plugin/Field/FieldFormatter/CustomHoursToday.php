<?php

namespace Drupal\openy_hours_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\openy_field_custom_hours\Plugin\Field\FieldFormatter\CustomHoursFormatterDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation for openy_custom_hours formatter.
 *
 * @FieldFormatter(
 *   id = "openy_today_custom_hours",
 *   label = @Translation("OpenY Today's hours"),
 *   field_types = {
 *     "openy_custom_hours"
 *   }
 * )
 */
class CustomHoursToday extends CustomHoursFormatterDefault implements ContainerFactoryPluginInterface {

  /**
   * Currently active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * Constructs an AddressDefaultFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $configuration
   *   Configuration array.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   Currently active route match object.
   */
  public function __construct($plugin_id, $plugin_definition, array $configuration, RouteMatchInterface $current_route_match) {
    $field_definition = $configuration['field_definition'];
    $settings = $configuration['settings'];
    $label = $configuration['label'];
    $view_mode = $configuration['view_mode'];
    $third_party_settings = $configuration['third_party_settings'];
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // @see \Drupal\Core\Field\FormatterPluginManager::createInstance().
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elementsParent = parent::viewElements($items, $langcode);

    $lazy_hours = $lazy_hours_placeholder = [];
    foreach ($items as $delta => $item) {
      // Group days by their values.
      foreach ($item as $i_item) {
        $name = $i_item->getName();
        $day = str_replace('hours_', '', $name);
        $value = $i_item->getValue() ? $i_item->getValue() : 'closed';
        // Do not process label.
        if ($day != 'label') {
          $lazy_hours[$day] = $value;
        }
      }

      if ($delta == 0) {
        $lazy_hours_placeholder = [
          '#lazy_builder' => [
            'openy_hours_formatter.hours_today:generateHoursToday',
            $lazy_hours,
          ],
          '#create_placeholder' => TRUE,
        ];
      }
    }

    // Create unique Id for field in case another openy_custom_hours field will use this FieldFormatter.
    $node = $this->currentRouteMatch->getParameter('node');
    $nid = ($node instanceof NodeInterface) ? $node->id() : '';
    $fieldId = &drupal_static(__FUNCTION__);
    $fieldId++;

    $elements[] = [
      '#theme' => 'openy_hours_formatter',
      '#hours' => $lazy_hours_placeholder,
      '#week' => [
        '#theme' => 'item_list',
        '#attributes' => [
          'class' => [
            'branch-hours',
          ],
        ],
        '#items' => $elementsParent,
      ],
      '#id' => $nid . $fieldId,
      '#attached' => [
        'library' => [
          'openy_hours_formatter/openy_hours_formatter',
        ],
      ],
    ];

    return $elements;
  }

}
