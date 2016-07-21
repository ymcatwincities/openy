<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Controller\PageManagerController.
 */

namespace Drupal\page_manager_ui\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Url;
use Drupal\ctools\Form\AjaxFormTrait;
use Drupal\page_manager\PageInterface;
use Drupal\page_manager\PageVariantInterface;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route controllers for Page Manager.
 */
class PageManagerController extends ControllerBase {

  use AjaxFormTrait;

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
      $container->get('user.shared_tempstore')
    );
  }

  /**
   * Route title callback.
   *
   * @param string $machine_name
   *   The page's machine_name.
   * @param string $tempstore_id
   *   The temporary store identifier.
   *
   * @return string
   *   The title for the page edit form.
   */
  public function editPageTitle($machine_name, $tempstore_id) {
    $cached_values = $this->tempstore->get($tempstore_id)->get($machine_name);
    /** @var \Drupal\page_manager\PageInterface $page */
    $page = $cached_values['page'];
    return $this->t('Edit %label page', ['%label' => $page->label()]);
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\page_manager\PageVariantInterface $page_variant
   *   The page variant entity.
   *
   * @return string
   *   The title for the page variant edit form.
   */
  public function editPageVariantTitle(PageVariantInterface $page_variant) {
    return $this->t('Edit %label variant', ['%label' => $page_variant->label()]);
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   * @param string $condition_id
   *   The access condition ID.
   *
   * @return string
   *   The title for the access condition edit form.
   */
  public function editAccessConditionTitle(PageInterface $page, $condition_id) {
    $access_condition = $page->getAccessCondition($condition_id);
    return $this->t('Edit %label access condition', ['%label' => $access_condition->getPluginDefinition()['label']]);
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\page_manager\PageVariantInterface $page_variant
   *   The page variant entity.
   * @param string $condition_id
   *   The selection condition ID.
   *
   * @return string
   *   The title for the selection condition edit form.
   */
  public function editSelectionConditionTitle(PageVariantInterface $page_variant, $condition_id) {
    $selection_condition = $page_variant->getSelectionCondition($condition_id);
    return $this->t('Edit %label selection condition', ['%label' => $selection_condition->getPluginDefinition()['label']]);
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   * @param string $name
   *   The parameter context name.
   *
   * @return string
   *   The title for the parameter edit form.
   */
  public function editParameterTitle(PageInterface $page, $name) {
    return $this->t('Edit @label parameter', ['@label' => $page->getParameter($name)['label']]);
  }

  /**
   * Enables or disables a Page.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   * @param string $op
   *   The operation to perform, usually 'enable' or 'disable'.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect back to the pages list page.
   */
  public function performPageOperation(PageInterface $page, $op) {
    $page->$op()->save();

    if ($op == 'enable') {
      drupal_set_message($this->t('The %label page has been enabled.', ['%label' => $page->label()]));
    }
    elseif ($op == 'disable') {
      drupal_set_message($this->t('The %label page has been disabled.', ['%label' => $page->label()]));
    }

    return $this->redirect('entity.page.collection');
  }

  /**
   * Presents a list of variants to add to the page entity.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   *
   * @return array
   *   The variant selection page.
   */
  public function selectVariant(PageInterface $page) {
    $build = [
      '#theme' => 'links',
      '#links' => [],
    ];
    foreach ($this->variantManager->getDefinitions() as $variant_plugin_id => $variant_plugin) {
      // The following two variants are provided by Drupal Core. They are not
      // configurable and therefore not compatible with Page Manager but have
      // similar and confusing labels. Skip them so that they are not shown in
      // the UI.
      if (in_array($variant_plugin_id, ['simple_page', 'block_page'])) {
        continue;
      }

      $build['#links'][$variant_plugin_id] = [
        'title' => $variant_plugin['admin_label'],
        'url' => Url::fromRoute('entity.page_variant.add_form', [
          'page' => $page->id(),
          'variant_plugin_id' => $variant_plugin_id,
        ]),
        'attributes' => $this->getAjaxAttributes(),
      ];
    }
    return $build;
  }

  /**
   * Presents a list of access conditions to add to the page entity.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   *
   * @return array
   *   The access condition selection page.
   */
  public function selectAccessCondition(PageInterface $page) {
    $build = [
      '#theme' => 'links',
      '#links' => [],
    ];
    $available_plugins = $this->conditionManager->getDefinitionsForContexts($page->getContexts());
    foreach ($available_plugins as $access_id => $access_condition) {
      $build['#links'][$access_id] = [
        'title' => $access_condition['label'],
        'url' => Url::fromRoute('page_manager.access_condition_add', [
          'page' => $page->id(),
          'condition_id' => $access_id,
        ]),
        'attributes' => $this->getAjaxAttributes(),
      ];
    }
    return $build;
  }

  /**
   * Presents a list of selection conditions to add to the page entity.
   *
   * @param \Drupal\page_manager\PageVariantInterface $page_variant
   *   The page variant entity.
   *
   * @return array
   *   The selection condition selection page.
   */
  public function selectSelectionCondition(PageVariantInterface $page_variant) {
    $build = [
      '#theme' => 'links',
      '#links' => [],
    ];
    $available_plugins = $this->conditionManager->getDefinitionsForContexts($page_variant->getContexts());
    foreach ($available_plugins as $selection_id => $selection_condition) {
      $build['#links'][$selection_id] = [
        'title' => $selection_condition['label'],
        'url' => Url::fromRoute('page_manager.selection_condition_add', [
          'page' => $page_variant->get('page'),
          'page_variant' => $page_variant->id(),
          'condition_id' => $selection_id,
        ]),
        'attributes' => $this->getAjaxAttributes(),
      ];
    }
    return $build;
  }

  /**
   * Presents a list of blocks to add to the variant.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $block_display
   *   The identifier of the block display variant.
   * @param string $tempstore_id
   *   The identifier of the temporary store.
   *
   * @return array
   *   The block selection page.
   */
  public function selectBlock(Request $request, $block_display, $tempstore_id) {
    $cached_values = $this->tempstore->get($tempstore_id)->get($block_display);
    /** @var \Drupal\page_manager\Plugin\DisplayVariant\PageBlockDisplayVariant $variant_plugin */
    $variant_plugin = $cached_values['plugin'];

    // Rehydrate the contexts on this end.
    $contexts = [];
    /**
     * @var string $context_name
     * @var \Drupal\Core\Plugin\Context\ContextDefinitionInterface $context_definition
     */
    foreach ($cached_values['contexts'] as $context_name => $context_definition) {
      $contexts[$context_name] = new Context($context_definition);
    }
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
        'url' => Url::fromRoute('page_manager.block_display_add_block', [
          'block_display' => $block_display,
          'block_id' => $plugin_id,
          'region' => $request->query->get('region'),
          'destination' => $request->query->get('destination'),
        ]),
        'attributes' => $this->getAjaxAttributes(),
      ];
    }
    return $build;
  }

  /**
   * Build the page variant entity add form.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page this page variant belongs to.
   * @param string $variant_plugin_id
   *   The variant plugin ID.
   *
   * @return array
   *   The page variant entity add form.
   */
  public function addPageVariantEntityForm(PageInterface $page, $variant_plugin_id) {
    // Create a page variant entity.
    $entity = $this->entityTypeManager()->getStorage('page_variant')->create([
      'page' => $page->id(),
      'variant' => $variant_plugin_id,
    ]);

    return $this->entityFormBuilder()->getForm($entity, 'add');
  }

}
