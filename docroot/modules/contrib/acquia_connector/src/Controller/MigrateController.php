<?php

namespace Drupal\acquia_connector\Controller;

use Drupal\acquia_connector\Helper\Storage;
use Drupal\acquia_connector\Migration;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\acquia_connector\ConnectorException;

/**
 * Class MigrateController.
 */
class MigrateController extends ControllerBase {

  /**
   * Acquia_connector.migrate route callback.
   */
  public function migratePage() {
    $storage = new Storage();
    $identifier = $storage->getIdentifier();
    $key = $storage->getKey();

    if (!empty($identifier) && !empty($key)) {
      try {
        \Drupal::service('acquia_connector.client')->getSubscription($identifier, $key);
      }
      catch (ConnectorException $e) {
        $error_message = acquia_connector_connection_error_message($e->getCustomMessage('code'));
      }
    }
    else {
      $error_message = $this->t('Missing Acquia Subscription credentials. Please enter your Acquia Subscription Identifier and Key.');
    }

    // If there was an error.
    if (!empty($error_message)) {
      drupal_set_message($this->t('There was an error in communicating with Acquia.com. @err', array('@err' => $error_message)), 'error');
    }
    else {
      $form_builder = \Drupal::formBuilder();
      return $form_builder->getForm('Drupal\acquia_connector\Form\MigrateForm');
    }

    $this->redirect('acquia_connector.settings');
  }

  /**
   * Acquia_connector.migrate_check route callback for checking client upload.
   */
  public function migrateCheck() {
    $return = array('compatible' => TRUE);

    $migrate = new Migration();
    $env = $migrate->checkEnv();

    if (empty($env) || $env['error'] !== FALSE) {
      $return['compatible'] = FALSE;
      $return['message'] = $env['error'];
    }

    return new JsonResponse($return);
  }

}
