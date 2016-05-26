<?php

namespace Drupal\ymca_mindbody\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\ymca_mindbody\MindBodyAPI;

/**
 * Provides the POC form for Schedules Personal Training.
 *
 * @ingroup ymca_mindbody
 */
class MindbodyPOCForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mindbody_poc';
  }

  /**
   * MindbodyPOCForm constructor.
   */
  public function __construct() {
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
  protected function mbSite() {
    $mb_site = new MindBodyAPI('SiteService', TRUE);
    $mb_site->setCredentials($this->sourcename, $this->password, array($this->site_id));

    return $mb_site;
  }

  /**
   * {@inheritdoc}
   */
  protected function mbApp() {
    $mb_app = new MindBodyAPI('AppointmentService', TRUE);
    $mb_app->setCredentials($this->sourcename, $this->password, array($this->site_id));

    return $mb_app;
  }

  /**
   * {@inheritdoc}
   */
  protected function mbStaff() {
    $mb_staff = new MindBodyAPI('StaffService', TRUE);
    $mb_staff->setCredentials($this->sourcename, $this->password, array($this->site_id));

    return $mb_staff;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="mindbody-poc-form-wrapper" class="content"><div class="container">';
    $form['#suffix'] = '</div></div>';

    $values = $form_state->getUserInput();

    $locations = $this->mbSite()->call('GetLocations', array());
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
      $programs = $this->mbSite()->call('GetPrograms', array('OnlineOnly' => FALSE, 'ScheduleType' => 'Appointment'));
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
      $session_types = $this->mbSite()->call('GetSessionTypes', array('OnlineOnly' => FALSE, 'ProgramIDs' => array($values['program'])));
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
      /*
       * NOTE: MINDBODY API doesn't support filtering staff by location without specific date and time.
       * That's why we see all trainers, even courts.
       * see screenshot https://goo.gl/I9uNY2
       * see API Docs https://developers.mindbodyonline.com/Develop/StaffService
       */
      $booking_params = array(
        'UserCredentials' => array(
          'Username' => $this->user_name,
          'Password' => $this->user_password,
          'SiteIDs' => array(
            $this->site_id,
          ),
        ),
        'SessionTypeIDs' => array($values['session_type']),
        'LocationIDs' => array($values['location']),
      );
      $bookable = $this->mbApp()->call('GetBookableItems', $booking_params);

      $staff_list = array();
      foreach ($bookable->GetBookableItemsResult->ScheduleItems->ScheduleItem as $bookable_item) {
        $photo = $this->mbStaff()->call('GetStaffImgURL', array('StaffID' => $bookable_item->Staff->ID));
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
      $booking_params = array(
        'UserCredentials' => array(
          'Username' => $this->user_name,
          'Password' => $this->user_password,
          'SiteIDs' => array(
            $this->site_id,
          ),
        ),
        'SessionTypeIDs' => array($values['session_type']),
        'LocationIDs' => array($values['location']),
      );

      if (!empty($values['trainer']) && $values['trainer'] != 'all') {
        $booking_params['StaffIDs'] = array($values['trainer']);
      }
      $booking_params['StartDate'] = date('Y-m-d', strtotime('+ 1 day'));
      $booking_params['EndDate'] = date('Y-m-d', strtotime('+ 4 day'));

      $bookable = $this->mbApp()->call('GetBookableItems', $booking_params);

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
