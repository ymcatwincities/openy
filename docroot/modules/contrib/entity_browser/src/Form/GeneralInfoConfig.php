<?php

namespace Drupal\entity_browser\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\DisplayManager;
use Drupal\entity_browser\SelectionDisplayManager;
use Drupal\entity_browser\WidgetManager;
use Drupal\entity_browser\WidgetSelectorManager;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * General information configuration step in entity browser form wizard.
 */
class GeneralInfoConfig extends FormBase {

  /**
   * Entity browser display plugin manager.
   *
   * @var \Drupal\entity_browser\DisplayManager
   */
  protected $displayManager;

  /**
   * Entity browser widget selector plugin manager.
   *
   * @var \Drupal\entity_browser\WidgetSelectorManager
   */
  protected $widgetSelectorManager;

  /**
   * Entity browser selection display plugin manager.
   *
   * @var \Drupal\entity_browser\SelectionDisplayManager
   */
  protected $selectionDisplayManager;

  /**
   * Entity browser widget plugin manager.
   *
   * @var \Drupal\entity_browser\WidgetManager
   */
  protected $widgetManager;

  /**
   * Constructs GeneralInfoConfig form class.
   *
   * @param \Drupal\entity_browser\DisplayManager $display_manager
   *   Entity browser display plugin manager.
   * @param \Drupal\entity_browser\WidgetSelectorManager $widget_selector_manager
   *   Entity browser widget selector plugin manager.
   * @param \Drupal\entity_browser\SelectionDisplayManager $selection_display_manager
   *   Entity browser selection display plugin manager.
   * @param \Drupal\entity_browser\WidgetManager $widget_manager
   *   Entity browser widget plugin manager.
   */
  function __construct(DisplayManager $display_manager, WidgetSelectorManager $widget_selector_manager, SelectionDisplayManager $selection_display_manager, WidgetManager $widget_manager) {
    $this->displayManager = $display_manager;
    $this->selectionDisplayManager = $selection_display_manager;
    $this->widgetSelectorManager = $widget_selector_manager;
    $this->widgetManager = $widget_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_browser.display'),
      $container->get('plugin.manager.entity_browser.widget_selector'),
      $container->get('plugin.manager.entity_browser.selection_display'),
      $container->get('plugin.manager.entity_browser.widget')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_browser_general_info_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\entity_browser\EntityBrowserInterface  $entity_browser */
    $entity_browser = $cached_values['entity_browser'];

    $displays = [];
    foreach ($this->displayManager->getDefinitions() as $plugin_id => $plugin_definition) {
      $displays[$plugin_id] = $plugin_definition['label'];
    }
    $form['display'] = [
      '#type' => 'select',
      '#title' => $this->t('Display plugin'),
      '#default_value' => $entity_browser->get('display') ? $entity_browser->getDisplay()->getPluginId() : NULL,
      '#options' => $displays,
      '#required' => TRUE,
    ];

    $widget_selectors = [];
    foreach ($this->widgetSelectorManager->getDefinitions() as $plugin_id => $plugin_definition) {
      $widget_selectors[$plugin_id] = $plugin_definition['label'];
    }
    $form['widget_selector'] = [
      '#type' => 'select',
      '#title' => $this->t('Widget selector plugin'),
      '#default_value' => $entity_browser->get('widget_selector') ? $entity_browser->getWidgetSelector()->getPluginId() : NULL,
      '#options' => $widget_selectors,
      '#required' => TRUE,
    ];

    $selection_display = [];
    foreach ($this->selectionDisplayManager->getDefinitions() as $plugin_id => $plugin_definition) {
      $selection_display[$plugin_id] = $plugin_definition['label'];
    }
    $form['selection_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Selection display plugin'),
      '#default_value' => $entity_browser->get('selection_display') ? $entity_browser->getSelectionDisplay()->getPluginId() : NULL,
      '#options' => $selection_display,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_browser\EntityBrowserInterface $entity_browser */
    $entity_browser = $form_state->getTemporaryValue('wizard')['entity_browser'];
    $entity_browser->setName($form_state->getValue('id'))
      ->setLabel($form_state->getValue('label'))
      ->setDisplay($form_state->getValue('display'))
      ->setWidgetSelector($form_state->getValue('widget_selector'))
      ->setSelectionDisplay($form_state->getValue('selection_display'));
  }

}
