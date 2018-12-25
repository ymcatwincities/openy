<?php

namespace Drupal\restui\Form;

use Drupal\Core\Authentication\AuthenticationCollectorInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\rest\Plugin\ResourceInterface;
use Drupal\rest\RestResourceConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\rest\Plugin\Type\ResourcePluginManager;

/**
 * Provides a REST resource configuration form.
 */
class RestUIForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The authentication collector.
   *
   * @var \Drupal\Core\Authentication\AuthenticationCollectorInterface
   */
  protected $authenticationCollector;

  /**
   * The available serialization formats.
   *
   * @var array
   */
  protected $formats;

  /**
   * The REST plugin manager.
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager
   */
  protected $resourcePluginManager;

  /**
   * The REST resource config storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $resourceConfigStorage;

  /**
   * Constructs a \Drupal\user\RestForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   * @param \Drupal\Core\Authentication\AuthenticationCollectorInterface $authentication_collector
   *   The authentication collector.
   * @param array $formats
   *   The available serialization formats.
   * @param \Drupal\rest\Plugin\Type\ResourcePluginManager $resourcePluginManager
   *   The REST plugin manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $resource_config_storage
   *   The REST resource config storage.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandler $module_handler, AuthenticationCollectorInterface $authentication_collector, array $formats, ResourcePluginManager $resourcePluginManager, EntityStorageInterface $resource_config_storage) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->authenticationCollector = $authentication_collector;
    $this->formats = $formats;
    $this->resourcePluginManager = $resourcePluginManager;
    $this->resourceConfigStorage = $resource_config_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('authentication_collector'),
      $container->getParameter('serializer.formats'),
      $container->get('plugin.manager.rest'),
      $container->get('entity_type.manager')->getStorage('rest_resource_config')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'restui';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'rest.settings',
    ];
  }

  /**
   * Gets a REST resource config's granularity: from the form, otherwise config.
   *
   * @param string $id
   *   A REST resource config entity ID.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return string
   *   Either:
   *   - \Drupal\rest\RestResourceConfigInterface::METHOD_GRANULARITY
   *   - \Drupal\rest\RestResourceConfigInterface::RESOURCE_GRANULARITY
   */
  protected function getGranularity($id, FormStateInterface $form_state) {
    $granularity = $this->config("rest.resource.{$id}")->get('granularity');
    if ($form_state->hasValue('granularity')) {
      $granularity = $form_state->getValue('granularity');
    }
    if ($granularity === NULL) {
      $granularity = RestResourceConfigInterface::RESOURCE_GRANULARITY;
    }
    return $granularity;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $resource_id
   *   A string that identifies the REST resource.
   *
   * @return array
   *   The form structure.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   When no plugin found.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $resource_id = NULL) {
    $plugin = $this->resourcePluginManager->createInstance($resource_id);
    if (empty($plugin)) {
      throw new NotFoundHttpException();
    }

    $id = str_replace(':', '.', $resource_id);

    $config = $this->config("rest.resource.{$id}")->get('configuration') ?: [];
    $pluginDefinition = $plugin->getPluginDefinition();
    $form['#title'] = $this->t('Settings for resource %label', ['%label' => $pluginDefinition['label']]);
    $form['#tree'] = TRUE;
    $form['resource_id'] = ['#type' => 'value', '#value' => $resource_id];


    $authentication_providers = array_keys($this->authenticationCollector->getSortedProviders());
    $authentication_providers = array_combine($authentication_providers, $authentication_providers);
    $format_options = array_combine($this->formats, $this->formats);

    $granularity = $this->getGranularity($id, $form_state);

    // Granularity selection.
    $form['granularity'] = [
      '#title' => t('Granularity'),
      '#type' => 'select',
      '#options' => [
        RestResourceConfigInterface::RESOURCE_GRANULARITY => $this->t('Resource'),
        RestResourceConfigInterface::METHOD_GRANULARITY => $this->t('Method'),
      ],
      '#default_value' => $granularity,
      '#ajax' => [
        'callback' => '::processAjaxForm',
        'wrapper' => 'wrapper',
      ],
    ];

    // Wrapper for ajax callback.
    $form['wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'wrapper'],
    ];

    $form['wrapper'] += ($granularity === RestResourceConfigInterface::RESOURCE_GRANULARITY)
      ? $this->buildConfigurationFormForResourceGranularity($plugin, $authentication_providers, $format_options, $config)
      : $this->buildConfigurationFormForMethodGranularity($plugin, $authentication_providers, $format_options, $config);

    return parent::buildForm($form, $form_state);
  }

  /**
   * Subform constructor when the selected granularity is 'method'.
   *
   * @param \Drupal\rest\Plugin\ResourceInterface $plugin
   *   The REST Resource plugin being configured.
   * @param array $authentication_providers
   *   All available authentication providers, to use for #options.
   * @param array $format_options
   *   All available formats, to use for #options.
   * @param array $config
   *   The current configuration for the REST Resource config entity, or the
   *   empty array if it does not yet exist.
   *
   * @return array
   *   The subform structure.
   */
  protected function buildConfigurationFormForMethodGranularity(ResourceInterface $plugin, array $authentication_providers, array $format_options, array $config) {
    $methods = $plugin->availableMethods();

    $form = [];

    foreach ($methods as $method) {
      $group = [];
      $group[$method] = [
        '#title' => $method,
        '#type' => 'checkbox',
        '#default_value' => isset($config[$method]),
      ];
      $group['settings'] = [
        '#type' => 'container',
        '#attributes' => ['style' => 'padding-left:20px'],
      ];

      // Available request formats.
      $enabled_formats = [];
      if (isset($config[$method]['supported_formats'])) {
        $enabled_formats = $config[$method]['supported_formats'];
      }
      $method_checkbox_selector = ':input[name="wrapper[methods][' . $method . '][' . $method . ']"]';
      $states_show_if_method_is_enabled = [
        'visible' => [$method_checkbox_selector => ['checked' => TRUE]],
        'invisible' => [$method_checkbox_selector => ['checked' => FALSE]],
      ];
      $group['settings']['formats'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Accepted request formats'),
        '#options' => $format_options,
        '#default_value' => $enabled_formats,
        '#states' => $states_show_if_method_is_enabled,
      ];

      // Authentication providers.
      $enabled_auth = [];
      if (isset($config[$method]['supported_auth'])) {
        $enabled_auth = $config[$method]['supported_auth'];
      }
      $group['settings']['auth'] = [
        '#title' => $this->t('Authentication providers'),
        '#type' => 'checkboxes',
        '#options' => $authentication_providers,
        '#default_value' => $enabled_auth,
        '#states' => $states_show_if_method_is_enabled,
      ];
      $form['methods'][$method] = $group;
    }
    return $form;
  }

  /**
   * Subform constructor when the selected granularity is 'resource'.
   *
   * @param \Drupal\rest\Plugin\ResourceInterface $plugin
   *   The REST Resource plugin being configured.
   * @param array $authentication_providers
   *   All available authentication providers, to use for #options.
   * @param array $format_options
   *   All available formats, to use for #options.
   * @param array $config
   *   The current configuration for the REST Resource config entity, or the
   *   empty array if it does not yet exist.
   *
   * @return array
   *   The subform structure.
   */
  protected function buildConfigurationFormForResourceGranularity(ResourceInterface $plugin, array $authentication_providers, array $format_options, array $config) {
    $methods = $plugin->availableMethods();
    $method_options = array_combine($methods, $methods);

    $form = [];

    // Methods.
    $enabled_methods = [];
    foreach ($methods as $method) {
      if (isset($config['methods']) && in_array($method, $config['methods'])) {
        $enabled_methods[$method] = $method;
      }
    }
    $form['settings']['methods'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Methods'),
      '#options' => $method_options,
      '#default_value' => $enabled_methods,
    ];

    // Formats.
    $enabled_formats = [];
    if (isset($config['formats'])) {
      $enabled_formats = $config['formats'];
    }

    $form['settings']['formats'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Accepted request formats'),
      '#options' => $format_options,
      '#default_value' => $enabled_formats,
    ];

    // Authentication providers.
    $enabled_auth = [];
    if (isset($config['authentication'])) {
      $enabled_auth = $config['authentication'];
    }

    $form['settings']['authentication'] = [
      '#title' => $this->t('Authentication providers'),
      '#type' => 'checkboxes',
      '#options' => $authentication_providers,
      '#default_value' => $enabled_auth,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\rest\Routing\ResourceRoutes::alterRoutes()
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('granularity') === RestResourceConfigInterface::RESOURCE_GRANULARITY) {
      $this->validateFormValuesForResourceGranularity($form_state);
    }
    else {
      $this->validateFormValuesForMethodGranularity($form_state);
    }
  }

  /**
   * Form validation handler when the selected granularity is 'method'.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\restui\Form\RestUIForm::validateForm()
   */
  protected function validateFormValuesForMethodGranularity(FormStateInterface $form_state) {
    // At least one method must be checked.
    $method_checked = FALSE;
    foreach ($form_state->getValue(['wrapper', 'methods']) as $method => $values) {
      if ($values[$method]) {
        $method_checked = TRUE;
        // At least one format and authentication provider must be selected.
        $formats = array_filter($values['settings']['formats']);
        if (empty($formats)) {
          $form_state->setErrorByName('methods][' . $method . '][settings][formats', $this->t('At least one format must be selected for method @method.', ['@method' => $method]));
        }
        $auth = array_filter($values['settings']['auth']);
        if (empty($auth)) {
          $form_state->setErrorByName('methods][' . $method . '][settings][auth', $this->t('At least one authentication provider must be selected for method @method.', ['@method' => $method]));
        }
      }
    }
    if (!$method_checked) {
      $form_state->setErrorByName('methods', $this->t('At least one HTTP method must be selected'));
    }
  }

  /**
   * Form validation handler when the selected granularity is 'resource'.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\restui\Form\RestUIForm::validateForm()
   */
  protected function validateFormValuesForResourceGranularity(FormStateInterface $form_state) {
    $settings = $form_state->getValue(['wrapper', 'settings']);

    if (empty(array_filter($settings['methods']))) {
      $form_state->setErrorByName('methods', $this->t('At least one HTTP method must be selected.'));
    }
    if (empty(array_filter($settings['formats']))) {
      $form_state->setErrorByName('formats', $this->t('At least one request format must be selected.'));
    }
    if (empty(array_filter($settings['authentication']))) {
      $form_state->setErrorByName('authentication', $this->t('At least one authentication provider must be selected'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $resource_id = str_replace(':', '.', $form_state->getValue('resource_id'));
    $config = $this->resourceConfigStorage->load($resource_id);
    $granularity = $form_state->getValue('granularity');

    if (!$config) {
      $config = $this->resourceConfigStorage->create(['id' => $resource_id]);
    }

    $configuration = ($granularity === RestResourceConfigInterface::RESOURCE_GRANULARITY)
      ? static::getConfigurationForResourceGranularity($form_state)
      : static::getConfigurationForMethodGranularity($form_state);

    $config->set('granularity', $granularity);
    $config->set('configuration', $configuration);
    $config->enable();
    $config->save();

    drupal_set_message($this->t('The resource has been updated.'));
    // Redirect back to the listing.
    $form_state->setRedirect('restui.list');
  }

  /**
   * Calculates the REST resource configuration when granularity is 'method'.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The value for the 'configuration' key in a REST Resource config entity.
   */
  protected static function getConfigurationForMethodGranularity(FormStateInterface $form_state) {
    $configuration = [];
    $methods = $form_state->getValue(['wrapper', 'methods']);
    foreach ($methods as $method => $settings) {
      if ($settings[$method]) {
        $configuration[$method] = [
          'supported_formats' => array_keys(array_filter($settings['settings']['formats'])),
          'supported_auth' => array_keys(array_filter($settings['settings']['auth'])),
        ];
      }
      else {
        unset($configuration[$method]);
      }
    }
    return $configuration;
  }

  /**
   * Calculates the REST resource configuration when granularity is 'resource'.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The value for the 'configuration' key in a REST Resource config entity.
   */
  protected static function getConfigurationForResourceGranularity(FormStateInterface $form_state) {
    $settings = $form_state->getValue(['wrapper', 'settings']);
    $configuration = [
      'methods' => array_keys(array_filter($settings['methods'])),
      'formats' => array_keys(array_filter($settings['formats'])),
      'authentication' => array_keys(array_filter($settings['authentication'])),
    ];
    return $configuration;
  }

  /**
   * Return the settings part of the form when rebuilding through ajax.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function processAjaxForm(array $form, FormStateInterface &$form_state) {
    return $form['wrapper'];
  }

}
