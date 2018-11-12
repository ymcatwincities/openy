<?php

namespace Drupal\mindbody_antifraud;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\State\StateInterface;
use Drupal\mindbody_cache_proxy\MindbodyCacheProxy;

class MindbodyAntiFraudScanner {

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * MindBody Proxy client.
   *
   * @var MindbodyCacheProxy
   */
  protected $proxy;

  /**
   * Key Value storage.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * MindbodyAntiFraudScanner constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannel $loggerChannel
   */
  public function __construct(LoggerChannel $loggerChannel, MindbodyCacheProxy $proxy, StateInterface $state) {
    $this->logger = $loggerChannel;
    $this->proxy = $proxy;
    $this->state = $state;
  }

  /**
   * Method to scan data within external MindBody service.
   */
  public function scan() {
    // TODO: Get All staff IDs.
    $staffparams = [
      'PageSize' => 10,
      'UserCredentials' =>
        [
          'Username' => 'andrii.podanenko',
          'Password' => 'ygtc_api_2018UX',
          'SiteIDs' => [249173],
        ],
    ];
    $staffresponse = $this->proxy->call('StaffService', 'GetStaff', $staffparams, FALSE);
    if ($staffresponse->GetStaffResult && $staffresponse->GetStaffResult->StaffMembers && count($staffresponse->GetStaffResult->StaffMembers->Staff)) {
      $staffIds = [];
      foreach ($staffresponse->GetStaffResult->StaffMembers->Staff as $staff) {
        $staffIds[] = $staff->ID;
      }
    }
    else {
      throw new \Exception('Cannot receive staff members from MindBody');
    }

    // TODO: Get All Appointments for the previous month ending yesterday 23:59 CST.

    $staffChunks = array_chunk($staffIds, 25);
    $appointments = [];
    foreach ($staffChunks as $staffChunk) {
      $params = [
        'PageSize' => 20000,
        'StaffCredentials' =>
          [
            'Username' => 'andrii.podanenko',
            'Password' => 'ygtc_api_2018UX',
            'SiteIDs' => [249173],
          ],
        'StaffIDs' => $staffChunk,
        'StartDate' => date('Y-m-d',strtotime("-32 days")),
        'EndDate' => date('Y-m-d',strtotime("-1 days")),
      ];

      $response = $this->proxy->call('AppointmentService', 'GetStaffAppointments', $params, FALSE);
      if ($response->GetStaffAppointmentsResult && $response->GetStaffAppointmentsResult->Appointments && count($response->GetStaffAppointmentsResult->Appointments->Appointment)) {
        $appointments += $response->GetStaffAppointmentsResult->Appointments->Appointment;
      }
    }
    if (count($appointments)) {
      $max = $this->getMaxAppointmentId($appointments);
      // Save latest Appointment ID found in the received array.
      if ($this->state->get('mindbody_fraud_max_app_id') > $max) {
        throw new \Exception('General Logic Failure: previous sync was newer than current one.');
      }
      // TODO: If Latest Appointment ID exists - remove older appointments from array.
      $previousId = $this->state->get('mindbody_fraud_max_app_id');
      if ($previousId > 0) {
        $fraud = $this->getFraudAppointments($appointments, $previousId);
      }
      $this->state->set('mindbody_fraud_max_app_id', $max);
      $this->logger->debug('Latest appointment ID: @id', ['@id' => $max]);
    }

    // TODO: All doesn't removed - is fraud. Send a message.
  }

  /**
   * Returns max appointment id from an array.
   *
   * @param array $appointments
   *
   * @return mixed
   */
  private function getMaxAppointmentId(array $appointments) {
    $sorted = [];
    foreach ($appointments as $appointment) {
      $sorted[$appointment->ID] = TRUE;
    }
    return max(array_keys($sorted));
  }

  /**
   *
   * @param array $appointments
   * @param $previousId
   */
  private function getFraudAppointments(array $appointments, $previousId) {
    $filtered = [];
    $fraud = [];
    // Remove appointments by ID.
    foreach ($appointments as $appointment) {
      if (TRUE || $appointment->ID > $previousId) {
        $filtered[$appointment->ID] = $appointment;
      }
    }
    $tz = new \DateTimeZone('America/Chicago');
    $date_from = new \DateTime();
    $date_from->setTimezone($tz);
    $date_from->sub(new \DateInterval('P1D'))->setTime(0, 0, 0);
    $yesterday_timestamp = $date_from->getTimestamp();

    // Find appointments by date.
    foreach ($filtered as $candidate) {
      $mbDateTime = $date_from::createFromFormat(DATETIME_DATETIME_STORAGE_FORMAT, $candidate->StartDateTime, $tz);
      $mbDateTime_timestamp = $mbDateTime->getTimestamp();
      if ($mbDateTime_timestamp < $yesterday_timestamp) {
        $fraud[$candidate->ID] = $candidate;
      }
      else {
        $i = 0;
      }
    }

    return $fraud;
  }
}