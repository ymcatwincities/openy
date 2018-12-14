<?php

namespace Drupal\mindbody_antifraud;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\State\StateInterface;
use Drupal\mindbody_cache_proxy\MindbodyCacheProxy;
use Maknz\Slack\Client;

/**
 * Class MindbodyAntiFraudScanner.
 *
 * @package Drupal\mindbody_antifraud
 */
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
   *   Channel for logging.
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
    // Get All staff IDs.
    $staffparams = [
      'PageSize' => 1000,
      'UserCredentials' =>
        [
          'Username' => 'andrii.podanenko',
          'Password' => 'ygtc_api_2018UX',
          'SiteIDs' => [249173],
        ],
    ];
    $staffresponse = $this->proxy->call('StaffService', 'GetStaff', $staffparams, TRUE);
    if ($staffresponse->GetStaffResult && $staffresponse->GetStaffResult->StaffMembers && count($staffresponse->GetStaffResult->StaffMembers->Staff)) {
      $staffIds = [];
      foreach ($staffresponse->GetStaffResult->StaffMembers->Staff as $staff) {
        $staffIds[] = $staff->ID;
      }
    }
    else {
      throw new \Exception('Cannot receive staff members from MindBody');
    }

    // Get All Appointments for the previous month ending yesterday 23:59 CST.
    $staffChunks = array_chunk($staffIds, 5);
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
        'StartDate' => date('Y-m-d', strtotime("-32 days")),
        'EndDate' => date('Y-m-d', strtotime("-1 days")),
      ];
      // We can't cache, because we'd miss important information.
      sleep(1);
      $response = $this->proxy->call('AppointmentService', 'GetStaffAppointments', $params, FALSE);
      if ($response->GetStaffAppointmentsResult && $response->GetStaffAppointmentsResult->Appointments && count($response->GetStaffAppointmentsResult->Appointments->Appointment)) {
        $newAppointments = $response->GetStaffAppointmentsResult->Appointments->Appointment;
        if (!is_array($newAppointments)) {
          $newAppointments = [$newAppointments];
        }
        $appointments = array_merge($appointments, $newAppointments);
      }
    }
    if (count($appointments)) {
      $max = $this->getMaxAppointmentId($appointments);
      // Save latest Appointment ID found in the received array.
      if ($this->state->get('mindbody_fraud_max_app_id') > $max) {
        throw new \Exception('General Logic Failure: previous sync was newer than current one.');
      }
      // If Latest Appointment ID exists - remove older appointments from array.
      $previousId = $this->state->get('mindbody_fraud_max_app_id');
      $frauds = [];
      if ($previousId > 0) {
        $frauds = $this->getFraudAppointments($appointments, $previousId);
        // All that wasn't removed - is fraud. Send a message.
        if (count($frauds)) {
          $settings = [
            'username' => 'MBbot',
            'channel' => 'mindbody_antifraud',
            'link_names' => TRUE,
          ];
          $client = new Client('https://hooks.slack.com/services/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', $settings);
          $slackFields = [];
          foreach ($frauds as $fraud) {
            $slackFields = [
              [
                'title' => 'DateTime',
                'value' => $fraud->StartDateTime,
                'short' => TRUE,
              ],
              [
                'title' => 'Location ID',
                'value' => $fraud->Location->ID,
                'short' => TRUE,
              ],
              [
                'title' => 'Customer Name',
                'value' => $fraud->Client->FirstName . ' ' . $fraud->Client->LastName,
                'short' => TRUE,
              ],
              [
                'title' => 'Customer ID',
                'value' => $fraud->Client->UniqueID,
                'short' => TRUE,
              ],
              [
                'title' => 'Trainer Name',
                'value' => $fraud->Staff->Name,
                'short' => TRUE,
              ],
              [
                'title' => 'Created/detected',
                'value' => date('Y-m-d'),
                'short' => TRUE,
              ],
            ];
            $client->to('#mindbody_antifraud')->attach([
              'fallback' => 'Possible Fraud Appointment detected',
              'text' => 'Possible Fraud Appointment detected',
              'color' => 'danger',
              'fields' => $slackFields,
            ])->send('Possible Fraud Appointment detected');

            $this->logger->warning('Possible fraud detected: DateTime @dt Location ID @lid Client Name @cname Customer ID @cid Trainer Name @tname', [
              '@dt' => $fraud->StartDateTime,
              '@lid' => $fraud->Location->ID,
              '@cname' => $fraud->Client->FirstName . ' ' . $fraud->Client->LastName,
              '@cid' => $fraud->Client->UniqueID,
              '@tname' => $fraud->Staff->Name,
            ]);
          }
        }
      }
      $this->state->set('mindbody_fraud_max_app_id', $max);
      $this->logger->debug('Latest appointment ID: @id', ['@id' => $max]);
      $this->logger->info('Antifraud protection run succeeded. Updated max ID.');
    }
    else {
      $this->logger->info('Antifraud protection run succeeded. No appointments pulled.');
    }

  }

  /**
   * Returns max appointment id from an array.
   *
   * @param array $appointments
   *   List of appointments to process.
   *
   * @return mixed
   *   Max ID from the array.
   */
  private function getMaxAppointmentId(array $appointments) {
    $sorted = [];
    foreach ($appointments as $appointment) {
      $sorted[$appointment->ID] = TRUE;
    }
    return max(array_keys($sorted));
  }

  /**
   * Get possible fraud appointments based on previous ID and date.
   *
   * @param array $appointments
   *   List of appointments to process.
   * @param int $previousId
   *   ID of previous appointment ( previous run maximum )
   *
   * @return array|null
   *   Array of appointments.
   *
   * @throws \Exception
   */
  private function getFraudAppointments(array $appointments, $previousId) {
    $filtered = [];
    $fraud = [];
    
    $whitelistedProgramNames = [
      'Member Engagements',
      'Internal Staff Appointments'
    ];

    foreach ($appointments as $appointment) {
      // Remove appointments by ID.
      if ($appointment->ID < $previousId) {
        continue;
      }
      // Remove whitelisted appointments.
      if (in_array($appointment->Program->Name, $whitelistedProgramNames)) {
        continue;
      }
      $filtered[$appointment->ID] = $appointment;
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
      // Create fraud Visit into the past manually via MondBody UI to test this is working.
      if ($mbDateTime_timestamp < $yesterday_timestamp) {
        $fraud[$candidate->ID] = $candidate;
      }
    }

    return $fraud;
  }

}
