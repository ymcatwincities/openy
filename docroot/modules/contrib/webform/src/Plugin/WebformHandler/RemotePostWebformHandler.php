<?php

namespace Drupal\webform\Plugin\WebformHandler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform submission remote post handler.
 *
 * @WebformHandler(
 *   id = "remote_post",
 *   label = @Translation("Remote post"),
 *   category = @Translation("External"),
 *   description = @Translation("Posts webform submissions to a URL."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class RemotePostWebformHandler extends WebformHandlerBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, ClientInterface $http_client, WebformTokenManagerInterface $token_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $config_factory, $entity_type_manager);
    $this->moduleHandler = $module_handler;
    $this->httpClient = $http_client;
    $this->tokenManager = $token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('webform.remote_post'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('http_client'),
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $configuration = $this->getConfiguration();
    if (!$this->isResultsEnabled()) {
      $configuration['settings']['updated_url'] = '';
      $configuration['settings']['deleted_url'] = '';
    }
    if (!$this->isDraftEnabled()) {
      $configuration['settings']['draft_url'] = '';
    }
    if (!$this->isConvertEnabled()) {
      $configuration['settings']['converted_url'] = '';
    }
    return [
      '#settings' => $configuration['settings'],
    ] + parent::getSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $field_names = array_keys(\Drupal::service('entity_field.manager')->getBaseFieldDefinitions('webform_submission'));
    $excluded_data = array_combine($field_names, $field_names);
    return [
      'type' => 'x-www-form-urlencoded',
      'excluded_data' => $excluded_data,
      'custom_data' => '',
      'custom_options' => '',
      'debug' => FALSE,
      // States.
      'completed_url' => '',
      'completed_custom_data' => '',
      'updated_url' => '',
      'updated_custom_data' => '',
      'deleted_url' => '',
      'deleted_custom_data' => '',
      'draft_url' => '',
      'draft_custom_data' => '',
      'converted_url' => '',
      'converted_custom_data' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $webform = $this->getWebform();

    // States
    $states = [
      WebformSubmissionInterface::STATE_COMPLETED => [
        'state' => $this->t('completed'),
        'label' => $this->t('Completed'),
        'description' => $this->t('Post data when submission is <b>completed</b>.'),
        'access' => TRUE,
      ],
      WebformSubmissionInterface::STATE_UPDATED => [
        'state' => $this->t('updated'),
        'label' => $this->t('Updated'),
        'description' => $this->t('Post data when submission is <b>updated</b>.'),
        'access' => $this->isResultsEnabled(),
      ],
      WebformSubmissionInterface::STATE_DELETED => [
        'state' => $this->t('deleted'),
        'label' => $this->t('Deleted'),
        'description' => $this->t('Post data when submission is <b>deleted</b>.'),
        'access' => $this->isResultsEnabled(),
      ],
      WebformSubmissionInterface::STATE_DRAFT => [
        'state' => $this->t('draft'),
        'label' => $this->t('Draft'),
        'description' => $this->t('Post data when <b>draft</b> is saved.'),
        'access' => $this->isDraftEnabled(),
      ],
      WebformSubmissionInterface::STATE_CONVERTED => [
        'state' => $this->t('converted'),
        'label' => $this->t('Converted'),
        'description' => $this->t('Post data when anonymous submission is <b>converted</b> to authenticated.'),
        'access' => $this->isConvertEnabled(),
      ],
    ];
    foreach ($states as $state => $state_item) {
      $state_url = $state . '_url';
      $state_custom_data = $state . '_custom_data';
      $t_args = [
        '@state' => $state_item['state'],
        '@title' => $state_item['label'],
        '@url' => 'http://www.mycrm.com/form_' . $state . '_handler.php'
      ];
      $form[$state] = [
        '#type' => 'details',
        '#open' => ($state === WebformSubmissionInterface::STATE_COMPLETED),
        '#title' => $state_item['label'],
        '#access' => $state_item['access'],
      ];
      $form[$state]['description'] = [
        '#markup' => $state_item['description'],
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ];
      if ($state === WebformSubmissionInterface::STATE_COMPLETED) {
        $form[$state]['token'] = [
          '#markup' => $this->t('Response data can be passed to submission data using [webform:handler:{machine_name}:{state}:{key}] tokens. (ie [webform:handler:remote_post:completed:confirmation_number])'),
          '#prefix' => '<div>',
          '#suffix' => '</div>',
        ];
      }
      $form[$state][$state_url] = [
        '#type' => 'url',
        '#title' => $this->t('@title URL', $t_args),
        '#description' => $this->t('The full URL to POST to when an existing webform submission is @state. E.g. @url', $t_args),
        '#required' => ($state === WebformSubmissionInterface::STATE_COMPLETED),
        '#parents' => ['settings', $state_url],
        '#default_value' => $this->configuration[$state_url],
      ];
      $form[$state][$state_custom_data] = [
        '#type' => 'webform_codemirror',
        '#mode' => 'yaml',
        '#title' => $this->t('@title custom data', $t_args),
        '#description' => $this->t('Enter custom data that will be included when a webform submission is @state.', $t_args),
        '#parents' => ['settings', $state_custom_data],
        '#states' => ['visible' => [':input[name="settings[' . $state_url . ']"]' => ['filled' => TRUE]]],
        '#default_value' => $this->configuration[$state_custom_data],
      ];
    }

    // Settings.
    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('Settings'),
    ];
    $form['general']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Post type'),
      '#description' => $this->t('Use x-www-form-urlencoded if unsure, as it is the default format for HTML webforms. You also have the option to post data in <a href="http://www.json.org/" target="_blank">JSON</a> format.'),
      '#options' => [
        'x-www-form-urlencoded' => $this->t('x-www-form-urlencoded'),
        'json' => $this->t('JSON'),
      ],
      '#required' => TRUE,
      '#parents' => ['settings', 'type'],
      '#default_value' => $this->configuration['type'],
    ];
    $form['general']['custom_data'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Custom data'),
      '#description' => $this->t('Enter custom data that will be included in all remote post requests.'),
      '#parents' => ['settings', 'custom_data'],
      '#default_value' => $this->configuration['custom_data'],
    ];
    $form['general']['custom_options'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Custom options'),
      '#description' => $this->t('Enter custom <a href=":href">request options</a> that will be used by the Guzzle HTTP client. Request options can included custom headers.', [':href' => 'http://docs.guzzlephp.org/en/stable/request-options.html']),
      '#parents' => ['settings', 'custom_options'],
      '#default_value' => $this->configuration['custom_options'],
    ];
    $form['general']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, posted submissions will be displayed onscreen to all users.'),
      '#return_value' => TRUE,
      '#parents' => ['settings', 'debug'],
      '#default_value' => $this->configuration['debug'],
    ];

    // Submission data.
    $form['submission_data'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission data'),
    ];
    $form['submission_data']['excluded_data'] = [
      '#type' => 'webform_excluded_columns',
      '#title' => $this->t('Posted data'),
      '#title_display' => 'invisible',
      '#webform_id' => $webform->id(),
      '#required' => TRUE,
      '#parents' => ['settings', 'excluded_data'],
      '#default_value' => $this->configuration['excluded_data'],
    ];

    $form['token_tree_link'] = $this->tokenManager->buildTreeLink();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValues();
    foreach ($this->configuration as $name => $value) {
      if (isset($values[$name])) {
        $this->configuration[$name] = $values[$name];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $state = $webform_submission->getWebform()->getSetting('results_disabled') ? WebformSubmissionInterface::STATE_COMPLETED : $webform_submission->getState();
    $this->remotePost($state, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function postDelete(WebformSubmissionInterface $webform_submission) {
    $this->remotePost(WebformSubmissionInterface::STATE_DELETED, $webform_submission);
  }

  /**
   * Execute a remote post.
   *
   * @param string $state
   *   The state of the webform submission.
   *   Either STATE_NEW, STATE_DRAFT, STATE_COMPLETED, STATE_UPDATED, or
   *   STATE_CONVERTED depending on the last save operation performed.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission to be posted.
   */
  protected function remotePost($state, WebformSubmissionInterface $webform_submission) {
    if (empty($this->configuration[$state . '_url'])) {
      return;
    }

    $request_url = $this->configuration[$state . '_url'];
    $request_type = $this->configuration['type'];
    $request_options = (!empty($this->configuration['custom_options'])) ? Yaml::decode($this->configuration['custom_options']) : [];
    $request_options[($request_type == 'json' ? 'json' :'form_params')] = $this->getRequestData($state, $webform_submission);

    try {
      $response = $this->httpClient->post($request_url, $request_options);
    }
    catch (RequestException $request_exception) {
      $message = $request_exception->getMessage();
      $response = $request_exception->getResponse();

      // Encode HTML entities to prevent broken markup from breaking the page.
      $message = nl2br(htmlentities($message));

      // If debugging is enabled, display the error message on screen.
      $this->debug($message, $state, $request_url, $request_type, $request_options, $response, 'error');

      // Log error message.
      $context = [
        '@form' => $this->getWebform()->label(),
        '@state' => $state,
        '@type' => $request_type,
        '@url' => $request_url,
        '@message' => $message,
        'link' => $this->getWebform()->toLink($this->t('Edit'), 'handlers-form')->toString(),
      ];
      $this->logger->error('@form webform remote @type post (@state) to @url failed. @message', $context);
      return;
    }

    // If debugging is enabled, display the request and response.
    $this->debug(t('Remote post successful!'), $state, $request_url, $request_type, $request_options, $response, 'warning');

    // Replace [webform:handler] tokens in submission data.
    // Data structured for [webform:handler:remote_post:completed:key] tokens.
    $submission_data = $webform_submission->getData();
    $has_token = (strpos(print_r($submission_data, TRUE), '[webform:handler:' . $this->getHandlerId() . ':') !== FALSE) ? TRUE : FALSE;
    if ($has_token) {
      $response_data = $this->getResponseData($response);
      $token_data = ['webform_handler' => [$this->getHandlerId() => [$state => $response_data]]];
      $submission_data = $this->tokenManager->replace($submission_data, $webform_submission, $token_data);
      $webform_submission->setData($submission_data);
      // Save changes to the submission data without invoking any hooks
      // or handlers.
      if ($this->isResultsEnabled()) {
        $this->submissionStorage->saveData($webform_submission);
      }
    }
  }

  /**
   * Get a webform submission's request data.
   *
   * @param string $state
   *   The state of the webform submission.
   *   Either STATE_NEW, STATE_DRAFT, STATE_COMPLETED, STATE_UPDATED, or
   *   STATE_CONVERTED depending on the last save operation performed.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission to be posted.
   *
   * @return array
   *   A webform submission converted to an associative array.
   */
  protected function getRequestData($state, WebformSubmissionInterface $webform_submission) {
    // Get submission and elements data.
    $data = $webform_submission->toArray(TRUE);

    // Flatten data.
    // Prioritizing elements before the submissions fields.
    $data = $data['data'] + $data;
    unset($data['data']);

    // Excluded selected submission data.
    $data = array_diff_key($data, $this->configuration['excluded_data']);

    // Append custom data.
    if (!empty($this->configuration['custom_data'])) {
      $data = Yaml::decode($this->configuration['custom_data']) + $data;
    }

    // Append state custom data.
    if (!empty($this->configuration[$state . '_custom_data'])) {
      $data = Yaml::decode($this->configuration[$state . '_custom_data']) + $data;
    }

    // Replace tokens.
    $data = $this->tokenManager->replace($data, $webform_submission);

    return $data;
  }

  /**
   * Authentication remote post options and authentication tokens
   *
   * @param array $options
   *   Request options including the form_params or json and the request header.
   *
   * @return array
   *   The request options with authentication tokens add to the
   *   parameters or header.
   *
   */
  protected function authenticate(array $options) {
    // Here you can set a custom authentication token to the remote post options.
    return $options;
  }

  /**
   * Get response data
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response returned by the remote server.
   *
   * @return array|string
   *   An array of data, parse from JSON, or a string.
   */
  protected function getResponseData(ResponseInterface $response) {
    $body = (string) $response->getBody();
    $data = json_decode($body, TRUE);
    return (json_last_error() === JSON_ERROR_NONE) ? $data : $body;
  }

  /**
   * Display debugging information.
   *
   * @param string $message
   *   Message to be displayed.
   * @param string $state
   *   The state of the webform submission.
   *   Either STATE_NEW, STATE_DRAFT, STATE_COMPLETED, STATE_UPDATED, or
   *   STATE_CONVERTED depending on the last save operation performed.
   * @param string $request_url
   *   The remote URL the request is being posted to.
   * @param string $request_type
   *   The type of remote post.
   * @param string $request_options
   *   The requests options including the submission data..
   * @param \Psr\Http\Message\ResponseInterface|null $response
   *   The response returned by the remote server.
   * @param string $type
   *   The type of message to be displayed to the end use.
   */
  protected function debug($message, $state, $request_url, $request_type, $request_options, ResponseInterface $response = NULL, $type = 'warning') {
    if (empty($this->configuration['debug'])) {
      return;
    }

    $build = [
      '#type' => 'details',
      '#title' => $this->t('Debug: Remote post: @title [@state]', ['@title' => $this->label(), '@state' => $state]),
    ];

    // State.
    $build['state'] = [
      '#type' => 'item',
      '#title' => $this->t('Submission state/operation:'),
      '#markup' => $state,
      '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
    ];

    // Request.
    $build['request'] = ['#markup' => '<hr />'];
    $build['request_url'] = [
      '#type' => 'item',
      '#title' => $this->t('Request URL'),
      '#markup' => $request_url,
      '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
    ];
    $build['request_type'] = [
      '#type' => 'item',
      '#title' => $this->t('Request type:'),
      '#markup' => $request_type,
      '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
    ];
    $build['request_options'] = [
      '#type' => 'item',
      '#title' => $this->t('Request options:'),
      '#wrapper_attributes' => ['style' => 'margin: 0'],
      'data' => [
        '#markup' => htmlspecialchars(Yaml::encode($request_options)),
        '#prefix' => '<pre>',
        '#suffix' => '</pre>',
      ],
    ];

    // Response.
    $build['response'] = ['#markup' => '<hr />'];
    if ($response) {
      $build['response_code'] = [
        '#type' => 'item',
        '#title' => $this->t('Response status code:'),
        '#markup' => $response->getStatusCode(),
        '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
      ];
      $build['response_header'] = [
        '#type' => 'item',
        '#title' => $this->t('Response header:'),
        '#wrapper_attributes' => ['style' => 'margin: 0'],
        'data' => [
          '#markup' => htmlspecialchars(Yaml::encode($response->getHeaders())),
          '#prefix' => '<pre>',
          '#suffix' => '</pre>',
        ],
      ];
      $build['response_body'] = [
        '#type' => 'item',
        '#wrapper_attributes' => ['style' => 'margin: 0'],
        '#title' => $this->t('Response body:'),
        'data' => [
          '#markup' => htmlspecialchars($response->getBody()),
          '#prefix' => '<pre>',
          '#suffix' => '</pre>',
        ],
      ];
      $response_data = $this->getResponseData($response);
      if ($tokens = $this->getResponseTokens($response_data, ['webform', 'handler', $this->getHandlerId(), $state])) {
        asort($tokens);
        $build['response_tokens'] = [
          '#type' => 'item',
          '#wrapper_attributes' => ['style' => 'margin: 0'],
          '#title' => $this->t('Response tokens:'),
          'description' => ['#markup' => $this->t('Below tokens can ONLY be used to insert response data into value and hidden elements.')],
          'data' => [
            '#markup' => implode(PHP_EOL, $tokens),
            '#prefix' => '<pre>',
            '#suffix' => '</pre>',
          ],
        ];
      }
    }
    else {
      $build['response_code'] = [
        '#markup' => t('No response. Please see the recent log messages.'),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
    }

    // Message.
    $build['message'] = ['#markup' => '<hr />'];
    $build['message_message'] = [
      '#type' => 'item',
      '#wrapper_attributes' => ['style' => 'margin: 0'],
      '#title' => $this->t('Message:'),
      '#markup' => $message,
    ];

    drupal_set_message(\Drupal::service('renderer')->renderPlain($build), $type);
  }

  /**
   * Get webform handler tokens from response data.
   *
   * @param mixed $data
   *   Response data.
   * @param array $parents
   *   Webform handler token parents.
   *
   * @return array
   *   A list of webform handler tokens.
   */
  protected function getResponseTokens($data, array $parents = []) {
    $tokens = [];
    if (is_array($data)) {
      foreach ($data as $key => $value) {
        $tokens = array_merge($tokens, $this->getResponseTokens($value, array_merge($parents, [$key])));
      }
    }
    else {
      $tokens[] = '[' . implode(':', $parents) . ']';
    }
    return $tokens;
  }

  /**
   * Determine if saving of results is enabled.
   *
   * @return bool
   *   TRUE if saving of results is enabled.
   */
  protected function isResultsEnabled() {
    return ($this->getWebform()->getSetting('results_disabled') === FALSE);
  }

  /**
   * Determine if saving of draft is enabled.
   *
   * @return bool
   *   TRUE if saving of draft is enabled.
   */
  protected function isDraftEnabled() {
    return $this->isResultsEnabled() && ($this->getWebform()->getSetting('draft') != WebformInterface::DRAFT_NONE);
  }

  /**
   * Determine if converting anoynmous submissions to authenticated is enabled.
   *
   * @return bool
   *   TRUE if converting anoynmous submissions to authenticated is enabled.
   */
  protected function isConvertEnabled() {
    return $this->isDraftEnabled() && ($this->getWebform()->getSetting('form_convert_anonymous') === TRUE);
  }

}
