<?php

namespace Drupal\panels\Form;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for configuring a layout's settings.
 */
class LayoutChangeSettings extends FormBase {

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $manager;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.core.layout'),
      $container->get('user.shared_tempstore')
    );
  }

  /**
   * LayoutChangeSettings constructor.
   *
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $manager
   *   The layout plugin manager.
   * @param \Drupal\user\SharedTempStoreFactory $tempstore
   *   The tempstore factory.
   */
  public function __construct(LayoutPluginManagerInterface $manager, SharedTempStoreFactory $tempstore) {
    $this->manager = $manager;
    $this->tempstore = $tempstore;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'panels_layout_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');

    /* @var $variant_plugin \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant */
    $variant_plugin = $cached_values['plugin'];

    $form['old_layout'] = [
      '#title' => $this->t('Old Layout'),
      '#type' => 'select',
      '#options' => $this->manager->getLayoutOptions(),
      '#default_value' => !empty($cached_values['layout_change']['old_layout']) ? $cached_values['layout_change']['old_layout'] : '',
      '#disabled' => TRUE,
      '#access' => !empty($cached_values['layout_change']),
    ];

    $form['new_layout'] = [
      '#title' => $this->t('New Layout'),
      '#type' => 'select',
      '#options' => $this->manager->getLayoutOptions(),
      '#default_value' => !empty($cached_values['layout_change']['new_layout']) ? $cached_values['layout_change']['new_layout'] : '',
      '#disabled' => TRUE,
      '#access' => !empty($cached_values['layout_change']),
    ];

    // If a layout is already selected, show the layout settings.
    $form['layout_settings_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Layout settings'),
      '#tree' => TRUE,
    ];

    $layout_settings = !empty($cached_values['layout_change']['layout_settings']) ? $cached_values['layout_change']['layout_settings'] : [];
    if (!$layout_settings && $variant_plugin->getLayout() instanceof ConfigurablePluginInterface) {
      $layout_settings = $variant_plugin->getLayout()->getConfiguration();
    }
    $layout_id = !empty($cached_values['layout_change']['new_layout']) ? $cached_values['layout_change']['new_layout'] : $variant_plugin->getConfiguration()['layout'];
    $layout = $this->manager->createInstance($layout_id, $layout_settings);
    if ($layout instanceof PluginFormInterface) {
      $form['layout_settings_wrapper']['layout_settings'] = $layout->buildConfigurationForm([], $form_state);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\ctools\Wizard\EntityFormWizardInterface $wizard */
    $wizard = $form_state->getFormObject();
    $next_params = $wizard->getNextParameters($cached_values);
    /* @var $plugin \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant */
    $plugin = $cached_values['plugin'];
    $layout_id = !empty($cached_values['layout_change']['new_layout']) ? $cached_values['layout_change']['new_layout'] : $plugin->getConfiguration()['layout'];
    /** @var \Drupal\Core\Layout\LayoutInterface $layout */
    $layout = $this->manager->createInstance($layout_id, []);
    // If we're dealing with a form, submit it.
    if ($layout instanceof PluginFormInterface) {
      $sub_form_state = new FormState();
      $plugin_values = $form_state->getValue(['layout_settings_wrapper', 'layout_settings']);
      // If form values came through the step's submission, handle them.
      if ($plugin_values) {
        $sub_form_state->setValues($plugin_values);
        $layout->submitConfigurationForm($form, $sub_form_state);
        // If this plugin is configurable, get that configuration and set it in
        // cached values.
        if ($layout instanceof ConfigurablePluginInterface) {
          $cached_values = $this->setCachedValues($next_params['step'], $plugin, $layout, $cached_values, $layout->getConfiguration());
        }
      }
      // If no values came through, set the cached values layout config to
      // empty array.
      else {
        $cached_values = $this->setCachedValues($next_params['step'], $plugin, $layout, $cached_values, []);
      }
    }
    // If we're not dealing with a Layout plugin that implements
    // PluginFormInterface, handle this unlikely situation.
    else {
      $cached_values = $this->setCachedValues($next_params['step'], $plugin, $layout, $cached_values, []);
    }
    $form_state->setTemporaryValue('wizard', $cached_values);
  }

  /**
   * Sets the appropriate cached values for the layout settings.
   *
   * Depending upon the next step, this form could be required to properly
   * update the values of the PanelsDisplayVariant plugin in the cached values
   * or it could just be adding the configuration to the cached values
   * directly. This bit of logic is repeated a number of times in the form
   * submission, and so abstracting it is typical DRY approach.
   *
   * @param string $next_step
   *   The next step of the wizard.
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $plugin
   *   The plugin to update.
   * @param \Drupal\Core\Layout\LayoutInterface $layout
   *   The layout for which we are upating settings.
   * @param array $cached_values
   *   The current cached values from the wizard.
   * @param array $configuration
   *   The new configuration of the layout.
   *
   * @return mixed
   *   Returns the new cached values.
   */
  protected function setCachedValues($next_step, PanelsDisplayVariant $plugin, LayoutInterface $layout, $cached_values, $configuration) {
    // The step is modified by various wizards but will end in "regions"
    if (substr($next_step, 0 -7) == 'regions') {
      $cached_values['layout_change']['layout_settings'] = $configuration;
    }
    else {
      $plugin->setLayout($layout, $configuration);
      $cached_values['plugin'] = $plugin;
      unset($cached_values['layout_change']);
    }
    return $cached_values;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /* @var $plugin \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant */
    $plugin = $cached_values['plugin'];
    $layout_id = !empty($cached_values['layout_change']['new_layout']) ? $cached_values['layout_change']['new_layout'] : $plugin->getConfiguration()['layout'];
    $layout = $this->manager->createInstance($layout_id, []);
    if ($layout instanceof PluginFormInterface) {
      $sub_form_state = new FormState();
      $plugin_values = $form_state->getValue(['layout_settings_wrapper', 'layout_settings']);
      if ($plugin_values) {
        $sub_form_state->setValues($plugin_values);
        $layout->validateConfigurationForm($form, $sub_form_state);
      }
    }
  }

}
