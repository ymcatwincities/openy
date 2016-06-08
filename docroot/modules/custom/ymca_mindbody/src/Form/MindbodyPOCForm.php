<?php

namespace Drupal\ymca_mindbody\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\ymca_mindbody\MindBodyAPI;
use Drupal\ymca_mindbody\MindbodyProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Provides the POC form for Schedules Personal Training.
 *
 * @ingroup ymca_mindbody
 */
class MindbodyPOCForm extends FormBase {

  /**
   * Mindbody Proxy.
   *
   * @var MindbodyProxyInterface
   */
  protected $proxy;

  /**
   * Credentials.
   *
   * @var ImmutableConfig
   */
  protected $credentials;

  public function __construct(MindbodyProxyInterface $proxy) {
    $this->proxy = $proxy;
    $this->credentials = $this->config('mindbody.settings');

    $credentials = $this->config('ymca_mindbody.settings')->get();
    $this->sourcename = $credentials['sourcename'];
    $this->password = $credentials['password'];
    $this->site_id = $credentials['site_id'];
    $this->user_name = $credentials['user_name'];
    $this->user_password = $credentials['user_password'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('ymca_mindbody.proxy'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mindbody_poc';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="mindbody-poc-form-wrapper" class="content"><div class="container">';
    $form['#suffix'] = '</div></div>';

    $values = $form_state->getUserInput();

    $locations = $this->proxy->call('SiteService', 'GetLocations');
    $location_options = [];
    foreach ($locations->GetLocationsResult->Locations->Location as $location) {
      if ($location->HasClasses != TRUE) {
        continue;
      }
      $location_options[$location->ID] = $location->Name;
    }

    $form['location'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Which Location?'),
      '#options' => $location_options,
      '#default_value' => '',
      '#ajax' => array(
        'callback' => array($this, 'rebuildAjaxCallback'),
        'wrapper' => 'mindbody-poc-form-wrapper',
        'event' => 'change',
        'effect' => 'fade',
        'progress' => array(
          'type' => 'throbber',
          'message' => $this->t('loading…'),
        ),
      ),
    );

    if (isset($values['location'])) {
      $programs = $this->proxy->call('SiteService', 'GetPrograms', ['OnlineOnly' => FALSE, 'ScheduleType' => 'Appointment']);
      $program_options = [];
      foreach ($programs->GetProgramsResult->Programs->Program as $program) {
        $program_options[$program->ID] = $program->Name;
      }
      $form['program'] = array(
        '#type' => 'radios',
        '#title' => $this->t('What are you looking for?'),
        '#options' => $program_options,
        '#ajax' => array(
          'callback' => array($this, 'rebuildAjaxCallback'),
          'wrapper' => 'mindbody-poc-form-wrapper',
          'event' => 'change',
          'effect' => 'fade',
          'progress' => array(
            'type' => 'throbber',
            'message' => $this->t('loading…'),
          ),
        ),
      );
    }

    if (isset($values['location']) && isset($values['program'])) {
      $session_types = $this->proxy->call('SiteService', 'GetSessionTypes', ['OnlineOnly' => FALSE, 'ProgramIDs' => [$values['program']]]);
      $session_type_options = [];
      foreach ($session_types->GetSessionTypesResult->SessionTypes->SessionType as $type) {
        $session_type_options[$type->ID] = $type->Name;
      }
      $form['session_type'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Which training?'),
        '#options' => $session_type_options,
        '#default_value' => '',
        '#ajax' => array(
          'callback' => array($this, 'rebuildAjaxCallback'),
          'wrapper' => 'mindbody-poc-form-wrapper',
          'event' => 'change',
          'effect' => 'fade',
          'progress' => array(
            'type' => 'throbber',
            'message' => $this->t('loading…'),
          ),
        ),
      );
    }

    if (isset($values['location']) && isset($values['program']) && isset($values['session_type'])) {

      /* NOTE: MINDBODY API doesn't support filtering staff by location without
       * specific date and time. That's why we see all trainers, even courts.
       * See https://developers.mindbodyonline.com/Develop/StaffService */
      $booking_params = array(
        'UserCredentials' => array(
          'Username' => $this->credentials->get('user_name'),
          'Password' => $this->credentials->get('user_password'),
          'SiteIDs' => [$this->credentials->get('site_id')],
        ),
        'SessionTypeIDs' => [$values['session_type']],
        'LocationIDs' => [$values['location']],
      );
      $bookable = $this->proxy->call('AppointmentService', 'GetBookableItems', $booking_params);

      $staff_list = array();
      foreach ($bookable->GetBookableItemsResult->ScheduleItems->ScheduleItem as $bookable_item) {
        $photo = $this->proxy->call('StaffService', 'GetStaffImgURL', ['StaffID' => $bookable_item->Staff->ID]);
        $staff_list[$bookable_item->Staff->ID] = $bookable_item->Staff;
      }
      $trainer_options = array(
        'all' => $this->t('All'),
      );
      foreach ($staff_list as $staff) {
        $trainer_options[$staff->ID] = $staff->Name;
      }

      $form['trainer'] = array(
        '#type' => 'select',
        '#title' => $this->t('With whom?'),
        '#options' => $trainer_options,
        '#required' => TRUE,
        '#default_value' => '',
      );

      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
        '#weight' => 20,
      );
    }

    if (isset($values['location']) && isset($values['program']) && isset($values['session_type']) && isset($values['trainer'])) {
      $form['when'] = array(
        '#title' => $this->t('When?'),
        '#markup' => '<p>[form with date, time and week days]<br /><i>* Will be implemented.</i></p>',
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function rebuildAjaxCallback(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSearchResults($values) {
    if (isset($values['location']) && isset($values['program']) && isset($values['session_type']) && isset($values['trainer'])) {
      $booking_params = [
        'UserCredentials' => [
          'Username' => $this->credentials->get('user_name'),
          'Password' => $this->credentials->get('user_password'),
          'SiteIDs' => $this->credentials->get('site_id'),
        ],
        'SessionTypeIDs' => [$values['session_type']],
        'LocationIDs' => [$values['location']],
      ];

      if (!empty($values['trainer']) && $values['trainer'] != 'all') {
        $booking_params['StaffIDs'] = array($values['trainer']);
      }
      $booking_params['StartDate'] = date('Y-m-d', strtotime('+ 1 day'));
      $booking_params['EndDate'] = date('Y-m-d', strtotime('+ 4 day'));

      // Todo: Move this method to appropriate class.
      $bookable = $this->proxy->call('AppointmentService', 'GetBookableItems', $booking_params);

      $search_results = '';
      if (count($bookable->GetBookableItemsResult->ScheduleItems->ScheduleItem) == 1) {
        $bookable_item = $bookable->GetBookableItemsResult->ScheduleItems->ScheduleItem;
        $search_results = $bookable_item->Staff->Name . ' | ' . date('m/d/Y h:i a', strtotime($bookable_item->StartDateTime)) . ' - ' . date('m/d/Y h:i a', strtotime($bookable_item->EndDateTime)) . '<br />';
      }
      else {
        foreach ($bookable->GetBookableItemsResult->ScheduleItems->ScheduleItem as $bookable_item) {
          $search_results .= $bookable_item->Staff->Name . ' | ' . date('m/d/Y h:i a', strtotime($bookable_item->StartDateTime)) . ' - ' . date('m/d/Y h:i a', strtotime($bookable_item->EndDateTime)) . '<br />';
        }
      }

      return $search_results;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getUserInput();
    if (isset($values['location']) && isset($values['program']) && isset($values['session_type']) && isset($values['trainer'])) {
      $params = [
        'location'     => $values['location'],
        'program'      => $values['program'],
        'session_type' => $values['session_type'],
        'trainer'      => $values['trainer'],
      ];
      $form_state->setRedirect(
        'ymca_mindbody.poc.results',
        [],
        ['query' => $params]
      );
    }
  }

}
