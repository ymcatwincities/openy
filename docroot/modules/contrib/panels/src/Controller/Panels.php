<?php

namespace Drupal\panels\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\ctools\Form\AjaxFormTrait;
use Drupal\panels\CachedValuesGetterTrait;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route controllers for Panels routes.
 */
class Panels extends ControllerBase {

  use AjaxFormTrait;
  use CachedValuesGetterTrait;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The condition manager.
   *
   * @var \Drupal\Core\Plugin\Context\ContextAwarePluginManagerInterface
   */
  protected $conditionManager;

  /**
   * The variant manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $variantManager;

  /**
   * The context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * Tempstore factory.
   *
   * @var \Drupal\user\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * Constructs a new VariantPluginEditForm.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $condition_manager
   *   The condition manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $variant_manager
   *   The variant manager.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The context handler.
  +   * @param \Drupal\user\SharedTempStoreFactory $tempstore
  +   *   The tempstore factory.
   */
  public function __construct(BlockManagerInterface $block_manager, PluginManagerInterface $condition_manager, PluginManagerInterface $variant_manager, ContextHandlerInterface $context_handler, SharedTempStoreFactory $tempstore) {
    $this->blockManager = $block_manager;
    $this->conditionManager = $condition_manager;
    $this->variantManager = $variant_manager;
    $this->contextHandler = $context_handler;
    $this->tempstore = $tempstore;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('plugin.manager.condition'),
      $container->get('plugin.manager.display_variant'),
      $container->get('context.handler'),
      $container->get('user.shared_tempstore'),
      $container->get('plugin.manager.panels.pattern')
    );
  }

  /**
   * Presents a list of blocks to add to the variant.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $machine_name
   *   The identifier of the block display variant.
   * @param string $tempstore_id
   *   The identifier of the temporary store.
   *
   * @return array
   *   The block selection page.
   */
  public function selectBlock(Request $request, $machine_name, $tempstore_id) {
    $cached_values = $this->getCachedValues($this->tempstore, $tempstore_id, $machine_name);
    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variant_plugin */
    $variant_plugin = $cached_values['plugin'];
    /** @var \Drupal\panels\Plugin\PanelsPattern\PanelsPatternInterface $pattern_plugin */
    $pattern_plugin = $variant_plugin->getPattern();

    $contexts = $pattern_plugin->getDefaultContexts($this->tempstore, $tempstore_id, $machine_name);
    $variant_plugin->setContexts($contexts);

    // Add a section containing the available blocks to be added to the variant.
    $build = [
      '#type' => 'container',
      '#attached' => [
        'library' => [
          'core/drupal.ajax',
        ],
      ],
    ];
    $available_plugins = $this->blockManager->getDefinitionsForContexts($variant_plugin->getContexts());
    // Order by category, and then by admin label.
    $available_plugins = $this->blockManager->getSortedDefinitions($available_plugins);
    foreach ($available_plugins as $plugin_id => $plugin_definition) {
      // Make a section for each region.
      $category = $plugin_definition['category'];
      $category_key = 'category-' . $category;
      if (!isset($build[$category_key])) {
        $build[$category_key] = [
          '#type' => 'fieldgroup',
          '#title' => $category,
          'content' => [
            '#theme' => 'links',
          ],
        ];
      }
      // Add a link for each available block within each region.
      $build[$category_key]['content']['#links'][$plugin_id] = [
        'title' => $plugin_definition['admin_label'],
        'url' => $pattern_plugin->getBlockAddUrl($tempstore_id, $machine_name, $plugin_id, $request->query->get('region'), $request->query->get('destination')),
        'attributes' => $this->getAjaxAttributes(),
      ];
    }
    return $build;
  }

}
