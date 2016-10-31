<?php

namespace Drupal\ymca_mindbody\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\mindbody\MindbodyException;
use Drupal\node\NodeInterface;
use Drupal\ymca_mindbody\YmcaMindbodyRequestGuard;
use Drupal\ymca_mindbody\YmcaMindbodyResultsSearcher;
use Drupal\ymca_mindbody\YmcaMindbodyResultsSearcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the Personal Training Form.
 *
 * @ingroup ymca_mindbody
 */
class MindbodyPTForm extends FormBase {

  /**
   * Default value for start time on PT form.
   */
  const DEFAULT_START_TIME = 4;

  /**
   * Default value for end time on PT form.
   */
  const DEFAULT_END_TIME = 22;

  /**
   * Default date range on PT form.
   */
  const DEFAULT_DATE_RANGE = '3days';

  /**
   * The Ymca Mindbody settings.
   *
   * @var ImmutableConfig
   */
  protected $settings;

  /**
   * The YMCA Mindbody request guard.
   *
   * @var YmcaMindbodyRequestGuard
   */
  protected $requestGuard;

  /**
   * The logger channel.
   *
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * The node object.
   *
   * @var NodeInterface
   */
  protected $node;

  /**
   * The state.
   *
   * @var array
   */
  protected $state;

  /**
   * The YMCA Mindbody results searcher.
   *
   * @var YmcaMindbodyResultsSearcherInterface
   */
  protected $resultsSearcher;

  /**
   * Creates a new MindbodyPTForm.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param YmcaMindbodyResultsSearcherInterface $results_searcher
   *   The YMCA Mindbody results search.
   * @param YmcaMindbodyRequestGuard $request_guard
   *   The Mindbody request guard.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param RequestStack $request_stack
   *   The request stack.
   * @param array $state
   *   The state.
   */
  public function __construct(
      ConfigFactory $config_factory,
      YmcaMindbodyResultsSearcherInterface $results_searcher,
      YmcaMindbodyRequestGuard $request_guard,
      LoggerChannelFactoryInterface $logger_factory,
      RequestStack $request_stack,
      array $state = []
    ) {
    $this->resultsSearcher = $results_searcher;
    $this->requestGuard = $request_guard;
    $this->logger = $logger_factory->get('ymca_mindbody');
    $this->setConfigFactory($config_factory);
    $this->settings = $this->config('ymca_mindbody.settings');
    $this->setRequestStack($request_stack);
    $this->node = $this->getRequest()->get('node');

    try {
      if (!$this->requestGuard->validateSearchCriteria($state)) {
        $state = [];
      }
    }
    catch (MindbodyException $e) {
      $this->logger->error('Failed to validate search criteria: %msg', ['%msg' => $e->getMessage()]);
      $state = [];
    }
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $query = \Drupal::request()->query->all();
    $state = array(
      'step' => isset($query['step']) && is_numeric($query['step']) ? $query['step'] : NULL,
      'mb_location' => isset($query['mb_location']) && is_numeric($query['mb_location']) ? $query['mb_location'] : NULL,
      'mb_program' => isset($query['mb_program']) && is_numeric($query['mb_program']) ? $query['mb_program'] : NULL,
      'mb_session_type' => isset($query['mb_session_type']) && is_numeric($query['mb_session_type']) ? $query['mb_session_type'] : NULL,
      'mb_trainer' => isset($query['mb_trainer']) && is_numeric($query['mb_trainer']) ? $query['mb_trainer'] : NULL,
      'mb_start_time' => isset($query['mb_start_time']) && is_numeric($query['mb_start_time']) ? $query['mb_start_time'] : NULL,
      'mb_end_time' => isset($query['mb_end_time']) && is_numeric($query['mb_end_time']) ? $query['mb_end_time'] : NULL,
      'mb_date_range' => !empty($query['mb_date_range']) ? $query['mb_date_range'] : NULL,
      'prepopulated_location' => FALSE,
    );

    if (isset($query['context'], $query['location']) && is_numeric($query['location'])) {
      $state['context'] = $query['context'];
      $state['location'] = $query['location'];
      $state['mb_location'] = $query['location'];
      $state['prepopulated_location'] = TRUE;
    }
    if (isset($query['context'], $query['trainer']) && $query['context'] == 'trainer' && is_numeric($query['trainer'])) {
      $state['trainer'] = $query['trainer'];
      $state['mb_trainer'] = $query['trainer'];
      $state['prepopulated_trainer'] = TRUE;
    }

    // Prevent corrupted remote calls on corrupted page urls.
    if (!isset($state['mb_location'])) {
      $state['step'] = 1;
    }
    elseif (!isset($state['mb_program'])) {
      $state['step'] = 2;
    }
    elseif (!isset($state['mb_session_type'])) {
      $state['step'] = 3;
    }

    return new static(
      $container->get('config.factory'),
      $container->get('ymca_mindbody.results_searcher'),
      $container->get('ymca_mindbody.request_guard'),
      $container->get('logger.factory'),
      $container->get('request_stack'),
      $state
    );
  }

  /**
   * Check if the form has to be disabled.
   */
  private function isDisabled() {
    // Check whether the form was disabled by administrator.
    if ($this->settings->get('pt_form_disabled')) {
      return TRUE;
    }

    // Disable form if we exceed max requests to MindBody API.
    if (!$this->requestGuard->status()) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mindbody_pt';
  }

  /**
   * Helper method rendering header markup.
   *
   * @return string
   *   Header HTML-markup.
   */
  protected function getElementHeaderMarkup($type, $text, $disabled = FALSE) {
    $classes = 'header-row';
    if ($disabled) {
      $classes .= ' disabled';
    }

    $icon = $id = '';
    switch ($type) {
      case 'location':
        $icon = 'location2';
        $id = 'location-wrapper';
        break;

      case 'program':
        $icon = 'training';
        $id = 'program-wrapper';
        break;

      case 'type':
        $icon = 'clock';
        $id = 'session-type-wrapper';
        break;

      case 'trainer':
        $icon = 'user';
        $id = 'trainer-wrapper';
        break;
    }
    $markup = '<div class="' . $classes . '"><div class="container">';
    $markup .= '<span class="icon icon-' . $icon . '"></span>';
    $markup .= '<span class="choice">' . $text . '</span>';
    $markup .= '<a href="#' . $id . '" class="change"><span class="icon icon-cog"></span>' . $this->t('Change') . '</a>';
    $markup .= '</div></div>';

    return $markup;
  }

  /**
   * Helper method retrieving time options.
   *
   * @return array
   *   Array of time options to be used in form element.
   */
  protected function getTimeOptions() {
    $time_options = [
      '12 am', '1 am', '2 am', '3 am', '4 am', '5 am', '6 am', '7 am', '8 am', '9 am', '10 am', '11 am',
      '12 pm', '1 pm', '2 pm', '3 pm', '4 pm', '5 pm', '6 pm', '7 pm', '8 pm', '9 pm', '10 pm', '11 pm', '12 am',
    ];

    $possible_hours = range(YmcaMindbodyResultsSearcher::MIN_TIME_RANGE, YmcaMindbodyResultsSearcher::MAX_TIME_RANGE);
    $possible_hours_keyed = array_combine($possible_hours, $possible_hours);

    $time_options = array_intersect_key($time_options, $possible_hours_keyed);

    return $time_options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    try {
      // Populate form state with state data.
      if ($this->state) {
        foreach ($this->state as $key => $value) {
          if (!$form_state->hasValue($key)) {
            $form_state->setValue($key, $value);
          }
        }
      }

      $values = $form_state->getValues();
      if ($trigger_element = $form_state->getTriggeringElement()) {
        switch ($trigger_element['#name']) {
          case 'mb_location':
            unset($values['mb_program']);
            unset($values['mb_session_type']);
            unset($values['mb_trainer']);
            $user_input = $form_state->getUserInput();
            unset($user_input['mb_program']);
            unset($user_input['mb_session_type']);
            unset($user_input['mb_trainer']);
            $form_state->setUserInput($user_input);
            $values['step'] = 2;
            break;

          case 'mb_program':
            unset($values['mb_session_type']);
            unset($values['mb_trainer']);
            $user_input = $form_state->getUserInput();
            unset($user_input['mb_session_type']);
            unset($user_input['mb_trainer']);
            $form_state->setUserInput($user_input);
            $values['step'] = 3;
            break;

          case 'mb_session_type':
            unset($values['mb_trainer']);
            $user_input = $form_state->getUserInput();
            unset($user_input['mb_trainer']);
            $form_state->setUserInput($user_input);
            $values['step'] = 4;
            break;

          case 'ok':
            $values['step'] = 5;
            break;
        }
      }

      if (!isset($values['step'])) {
        $values['step'] = 1;
      }

      $form['step'] = [
        '#type' => 'hidden',
        '#value' => $values['step'],
      ];

      // Vary on the listed query args.
      $form['#cache'] = [
        // Remove max-age when mindbody tags invalidation is done.
        'max-age' => 0,
        'contexts' => [
          'mindbody_state',
          'url.query_args:step',
          'url.query_args:mb_location',
          'url.query_args:mb_program',
          'url.query_args:mb_session_type',
          'url.query_args:mb_trainer',
          'url.query_args:mv_date_range',
          'url.query_args:mb_start_time',
          'url.query_args:mb_end_time',
        ],
      ];

      $form['#attached'] = [
        'library' => [
          'ymca_mindbody/ymca_mindbody',
        ],
        'drupalSettings' => $settings,
      ];

      $form['#prefix'] = '<div id="mindbody-pt-form-wrapper" class="content step-' . $values['step'] . '">';
      $form['#suffix'] = '</div>';

      if ($this->isDisabled()) {
        $form['disable'] = $this->resultsSearcher->getDisabledMarkup();
        return $form;
      }

      $location_options = $this->resultsSearcher->getLocations();
      $form['mb_location'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Select Location'),
        '#options' => $location_options,
        '#default_value' => isset($values['mb_location']) ? $values['mb_location'] : '',
        '#prefix' => '<div id="location-wrapper" class="row"><div class="container">',
        '#suffix' => '</div></div>',
        '#description' => $this->t('You can only select 1 branch per search'),
        '#weight' => 2,
        '#ajax' => array(
          'callback' => array($this, 'rebuildAjaxCallback'),
          'wrapper' => 'mindbody-pt-form-wrapper',
          'event' => 'change',
          'method' => 'replace',
          'effect' => 'fade',
          'progress' => array(
            'type' => 'throbber',
          ),
        ),
      );

      if ($values['step'] >= 2) {
        $form['mb_location_header'] = array(
          '#markup' => $this->getElementHeaderMarkup('location', $location_options[$values['mb_location']], $this->state['prepopulated_location']),
          '#weight' => 1,
        );
        $program_options = $this->resultsSearcher->getPrograms();
        $form['mb_program'] = array(
          '#type' => 'radios',
          '#title' => $this->t('Appointment Type'),
          '#options' => $program_options,
          '#default_value' => isset($values['mb_program']) ? $values['mb_program'] : '',
          '#prefix' => '<div id="program-wrapper" class="row"><div class="container">',
          '#suffix' => '</div></div>',
          '#weight' => 4,
          '#ajax' => array(
            'callback' => array($this, 'rebuildAjaxCallback'),
            'wrapper' => 'mindbody-pt-form-wrapper',
            'method' => 'replace',
            'event' => 'change',
            'effect' => 'fade',
            'progress' => array(
              'type' => 'throbber',
            ),
          ),
        );
      }

      if ($values['step'] >= 3) {
        $form['mb_program_header'] = array(
          '#markup' => $this->getElementHeaderMarkup('program', $program_options[$values['mb_program']]),
          '#weight' => 3,
        );
        $session_type_options = $this->resultsSearcher->getSessionTypes($values['mb_program']);
        $form['mb_session_type'] = array(
          '#type' => 'radios',
          '#title' => $this->t('Training type'),
          '#options' => $session_type_options,
          '#default_value' => isset($values['mb_session_type']) ? $values['mb_session_type'] : '',
          '#prefix' => '<div id="session-type-wrapper" class="row"><div class="container">',
          '#suffix' => '</div></div>',
          '#weight' => 6,
          '#ajax' => array(
            'callback' => array($this, 'rebuildAjaxCallback'),
            'wrapper' => 'mindbody-pt-form-wrapper',
            'event' => 'change',
            'effect' => 'fade',
            'progress' => array(
              'type' => 'throbber',
            ),
          ),
        );
      }

      if ($values['step'] >= 4) {
        $form['mb_session_type_header'] = array(
          '#markup' => $this->getElementHeaderMarkup('type', $session_type_options[$values['mb_session_type']]),
          '#weight' => 5,
        );
        $trainer_options = $this->resultsSearcher->getTrainers($values['mb_session_type'], $values['mb_location']);

        $form['mb_trainer'] = array(
          '#access' => !empty($this->state['prepopulated_trainer']) ? FALSE : TRUE,
          '#type' => 'select',
          '#title' => $this->t('Trainer'),
          '#options' => $trainer_options,
          '#default_value' => isset($values['mb_trainer']) ? $values['mb_trainer'] : 'all',
          '#prefix' => '<div id="trainer-wrapper" class="row"><div class="container"><div class="col-sm-4">',
          '#suffix' => '</div></div></div>',
          '#weight' => 8,
        );

        $form['actions']['#weight'] = 20;
        $form['actions']['#prefix'] = '<div id="actions-wrapper" class="row"><div class="container"><div class="col-sm-12">';
        $form['actions']['#suffix'] = '</div></div></div>';

        $form['mb_date'] = [
          '#type' => 'fieldset',
          '#prefix' => '<div id="when-wrapper" class="row"><div class="container"><div class="col-sm-12">',
          '#suffix' => '</div></div></div>',
          '#weight' => 9,
        ];
        $form['mb_date']['mb_start_time'] = [
          '#type' => 'select',
          '#title' => $this->t('Time range'),
          '#options' => $this->getTimeOptions(),
          '#default_value' => isset($values['mb_start_time']) ? $values['mb_start_time'] : $this::DEFAULT_START_TIME,
          '#suffix' => '<span class="dash">—</span>',
          '#weight' => 9,
        ];
        $form['mb_date']['mb_end_time'] = [
          '#type' => 'select',
          '#title' => '',
          '#options' => $this->getTimeOptions(),
          '#default_value' => isset($values['mb_end_time']) ? $values['mb_end_time'] : $this::DEFAULT_END_TIME,
          '#weight' => 9,
        ];
        $form['mb_date']['mb_date_range'] = [
          '#type' => 'select',
          '#title' => $this->t('Date range'),
          '#options' => [
            '3days' => $this->t('Next 3 days'),
            'week' => $this->t('Next week'),
            '3weeks' => $this->t('Next 3 weeks'),
          ],
          '#default_value' => isset($values['mb_date_range']) ? $values['mb_date_range'] : $this::DEFAULT_DATE_RANGE,
          '#weight' => 9,
        ];

        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Search'),
        );
      }
    }
    catch (MindbodyException $e) {
      $form['disabled'] = $this->resultsSearcher->getDisabledMarkup();
      $this->logger->error('Failed to build the form. Message: %msg', ['%msg' => $e->getMessage()]);
    }

    return $form;
  }

  /**
   * Custom ajax callback.
   */
  public function rebuildAjaxCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (isset($values['mb_start_time']) && isset($values['mb_end_time']) && $values['mb_start_time'] >= $values['mb_end_time']) {
      $form_state->setErrorByName('mb_start_time', $this->t('Please check time range.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = $this->state;
    $values = $form_state->getUserInput();
    if (!isset($values['mb_trainer']) && isset($query['trainer'])) {
      $values['mb_trainer'] = $query['trainer'];
    }
    if (!empty($values['mb_location']) &&
      !empty($values['mb_program']) &&
      !empty($values['mb_session_type']) &&
      !empty($values['mb_trainer']) &&
      !empty($values['mb_start_time']) &&
      !empty($values['mb_end_time']) &&
      !empty($values['mb_date_range'])) {
      $params = [
        'location' => $values['mb_location'],
        'p'        => $values['mb_program'],
        's'        => $values['mb_session_type'],
        'trainer'  => $values['mb_trainer'],
        'st'       => $values['mb_start_time'],
        'et'       => $values['mb_end_time'],
        'dr'       => $values['mb_date_range'],
      ];
      if (isset($query['context'])) {
        $params['context'] = $query['context'];
      }

      $form_state->setRedirectUrl($this->getResultsLink($params));
    }
  }

  /**
   * Returns results link based on context.
   *
   * @param array $options
   *   Array of options.
   *
   * @return \Drupal\Core\Url
   *   Route object.
   */
  protected function getResultsLink($options) {
    if (!isset($this->node)) {
      return Url::fromRoute('ymca_mindbody.pt.results', [], ['query' => $options]);
    }
    return Url::fromRoute('ymca_mindbody.location.pt.results', ['node' => $this->node->id()], ['query' => $options]);
  }

}
