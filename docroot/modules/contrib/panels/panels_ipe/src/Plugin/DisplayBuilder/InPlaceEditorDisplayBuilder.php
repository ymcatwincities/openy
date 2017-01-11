<?php

namespace Drupal\panels_ipe\Plugin\DisplayBuilder;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\layout_plugin\Plugin\Layout\LayoutInterface;
use Drupal\panels\Plugin\DisplayBuilder\StandardDisplayBuilder;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Drupal\panels\Storage\PanelsStorageManagerInterface;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The In-place editor display builder for viewing and editing a
 * PanelsDisplayVariant in the same place.
 *
 * @DisplayBuilder(
 *   id = "ipe",
 *   label = @Translation("In-place editor")
 * )
 */
class InPlaceEditorDisplayBuilder extends StandardDisplayBuilder {

  /**
   * @var \Drupal\user\SharedTempStore
   */
  protected $tempStore;

  /**
   * @var \Drupal\panels\Storage\PanelsStorageManagerInterface
   */
  protected $panelsStorage;

  /**
   * Constructs a new InPlaceEditorDisplayBuilder.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The context handler.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\user\SharedTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   * @param \Drupal\panels\Storage\PanelsStorageManagerInterface
   *   The Panels storage manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContextHandlerInterface $context_handler, AccountInterface $account, SharedTempStoreFactory $temp_store_factory, PanelsStorageManagerInterface $panels_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $context_handler, $account);
    $this->tempStore = $temp_store_factory->get('panels_ipe');
    $this->panelsStorage = $panels_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('context.handler'),
      $container->get('current_user'),
      $container->get('user.shared_tempstore'),
      $container->get('panels.storage_manager')
    );
  }

  /**
   * Compiles settings needed for the IPE to function.
   *
   * @param array $regions
   *   The render array representing regions.
   * @param \Drupal\layout_plugin\Plugin\Layout\LayoutInterface $layout
   *   The current layout.
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display
   *   The Panels display we are editing.
   * @param bool $unsaved
   *   Whether or not there are unsaved changes.
   *
   * @return array|bool
   *   An associative array representing the contents of drupalSettings, or
   *   FALSE if there was an error.
   */
  protected function getDrupalSettings(array $regions, LayoutInterface $layout, PanelsDisplayVariant $panels_display, $unsaved) {
    $settings = [
      'regions' => [],
    ];

    // Add current block IDs to settings sorted by region.
    foreach ($regions as $region => $blocks) {
      $settings['regions'][$region]  = [
        'name' => $region,
        'label' => '',
        'blocks' => [],
      ];

      if (!$blocks) {
        continue;
      }

      /** @var \Drupal\Core\Block\BlockPluginInterface[] $blocks */
      foreach ($blocks as $block_uuid => $block) {
        $configuration = $block->getConfiguration();
        $plugin_definition = $block->getPluginDefinition();
        $setting = [
          'uuid' => $block_uuid,
          'label' => $block->label(),
          'id' => $block->getPluginId(),
          'provider' => $configuration['provider'],
          'plugin_id' => $plugin_definition['id'],
        ];
        $settings['regions'][$region]['blocks'][$block_uuid] = $setting;
      }
    }

    $storage_type = $panels_display->getStorageType();
    $storage_id = $panels_display->getStorageId();

    // Add the layout information.
    $layout_definition = $layout->getPluginDefinition();
    $settings['layout'] = [
      'id' => $layout->getPluginId(),
      'label' => $layout_definition['label'],
      'original' => true,
    ];

    // Add information about the current user's permissions.
    $settings['user_permission'] = [
      'change_layout' => $this->panelsStorage->access($storage_type, $storage_id, 'change layout', $this->account)->isAllowed(),
      'create_content' => $this->account->hasPermission('administer blocks'),
    ];

    // Add the display variant's config.
    $settings['panels_display'] = [
      'storage_type' => $storage_type,
      'storage_id' => $storage_id,
      'id' => $panels_display->id(),
    ];

    // Inform the App of our saved state.
    $settings['unsaved'] = $unsaved;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function build(PanelsDisplayVariant $panels_display) {
    // Check to see if the current user has permissions to use the IPE.
    $has_permission = $this->account->hasPermission('access panels in-place editing') && $this->panelsStorage->access($panels_display->getStorageType(), $panels_display->getStorageId(), 'update', $this->account)->isAllowed();
    if ($has_permission) {
      $has_permission = \Drupal::service('plugin.manager.ipe_access')->access($panels_display);
    }

    // Attach the Panels In-place editor library based on permissions.
    if ($has_permission) {
      // This flag tracks whether or not there are unsaved changes.
      $unsaved = FALSE;

      // If a temporary configuration for this variant exists, use it.
      $temp_store_key = $panels_display->id();
      if ($variant_config = $this->tempStore->get($temp_store_key)) {
        unset($variant_config['id']);
        $panels_display->setConfiguration($variant_config);

        // Indicate that the user is viewing un-saved changes.
        $unsaved = TRUE;
      }

      $build = parent::build($panels_display);

      $regions = $panels_display->getRegionAssignments();
      $layout = $panels_display->getLayout();


      foreach ($regions as $region => $blocks) {
        // Wrap each region with a unique class and data attribute.
        $region_name = Html::getClass("block-region-$region");
        $build[$region]['#prefix'] = '<div class="' . $region_name . '" data-region-name="' . $region . '">';
        $build[$region]['#suffix'] = '</div>';

        if ($blocks) {
          foreach ($blocks as $block_id => $block) {
            $build[$region][$block_id]['#attributes']['data-block-id'] = $block_id;
          }
        }
      }

      // Attach the required settings and IPE.
      $build['#attached']['library'][] = 'panels_ipe/panels_ipe';
      $build['#attached']['drupalSettings']['panels_ipe'] = $this->getDrupalSettings($regions, $layout, $panels_display, $unsaved);

      // Add our custom elements to the build.
      $build['#prefix'] = '<div id="panels-ipe-content">';

      $build['#suffix'] = '</div><div id="panels-ipe-tray"></div>';
    }
    // Use a standard build if the user can't use IPE.
    else {
      $build = parent::build($panels_display);
    }

    return $build;
  }

}
