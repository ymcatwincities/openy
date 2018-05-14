<?php

namespace Drupal\lndr\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for page example routes.
 */
class LndrServiceController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'lndr';
  }

  public function sync_content() {
    $response = new Response();
    $response->headers->set('Content-Type', 'application/json; charset=utf-8');
    $auth_response = $this->service_auth(FALSE);

    // If token doesn't check out or others, we exit
    if ($auth_response['response']['type'] === 'error') {
      $content = json_encode($auth_response);
      $response->setContent($content);
      return $response;
    }

    // Fire the sync content
    $controller = new \Drupal\lndr\Controller\LndrController();
    $controller->sync_path();

    $response_content = array(
      'response' => array(
        'type' => 'content_synced',
        'message' => 'Content successfully synced',
        'code' => '200',
      ),
    );
    $response->setContent(json_encode($response_content));
    return $response;
  }

  /**
   * Check whether a path alias is available.
   * @return Response
   */
  public function validate_path() {
    $response = new Response();
    $response->headers->set('Content-Type', 'application/json; charset=utf-8');
    $auth_response = $this->service_auth();

    // If token doesn't check out or others, we exit
    if ($auth_response['response']['type'] === 'error') {
      $content = json_encode($auth_response);
      $response->setContent($content);
      return $response;
    }

    // Query array is stored in response message if everything is fine
    $query = $auth_response['response']['message'];

    // 1. Let's check to see if the path is available in the system (Assuming path validation is done on Lndr side)
    // returns the same path back if the alias is available
    try {
      $existing_alias = \Drupal::service('path.alias_manager')->getPathByAlias($query['path']);
      if ($existing_alias === $query['path']) {
        $response_content = array(
          'response' => array(
            'type' => 'path_valid',
            'message' => 'The chosen path is available',
            'code' => '200',
          ),
        );
        $response->setContent(json_encode($response_content));
        return $response;
      }
      else
      {
        $response_content = array(
          'response' => array(
            'type' => 'path_taken',
            'message' => 'The requested path is not available for Lndr',
            'code' => '403',
          ),
        );
        $response->setContent(json_encode($response_content));
        return $response;
      }
    }
    catch (\InvalidArgumentException $e) {
      $error_response = array(
        'response' => array(
          'type' => 'error',
          'message' => t($e->getMessage()),
          'code' => '500',
        ),
      );
      $response->setContent(json_encode($error_response));
      return $response;
    }
  }

  /**
   * Reserve a new path in Drupal
   * @return Response
   */
  public function reserve_path() {
    $response = new Response();
    $response->headers->set('Content-Type', 'application/json; charset=utf-8');
    $auth_response = $this->service_auth();

    // If token doesn't check out or others, we exit
    if ($auth_response['response']['type'] === 'error') {
      $content = json_encode($auth_response);
      $response->setContent($content);
      return $response;
    }
    // Query array is stored in response message if everything is fine
    $query = $auth_response['response']['message'];

    // if the path sent does not have starting slash
    $reserve_path = urldecode($query['path']);
    if (substr($reserve_path,0, 1) != '/') {
      $reserve_path = '/' . $reserve_path;
    }

    // Edge case: checking if this path is already reserved. (Seems Drupal doesn't care)
    $system_path = \Drupal::service('path.alias_manager')->getPathByAlias($reserve_path);
    if ($system_path === '/lndr/reserved') {
      $content_response = array(
        'type' => 'error',
        'message' => 'The path requested is already reserved',
        'code' => '500',
      );
      $response->setContent(json_encode($content_response));
      return $response;
    }

    try {
      $reserved_path = \Drupal::service('path.alias_storage')->save('/lndr/reserved', $reserve_path);
      if (!$reserved_path) {
        $content_response = array(
          'type' => 'error',
          'message' => 'Failed to reserve the path: ' . $reserve_path,
          'code' => '500',
        );
        $response->setContent(json_encode($content_response));
        return $response;
      }
      else {
        $content_response = array(
          'response' => array(
            'type' => 'path_valid',
            'message' => 'The path has been successfully reserved',
            'code' => '200',
          ),
        );
        $response->setContent(json_encode($content_response));
        return $response;
      }
    }
    catch (\InvalidArgumentException $e)
    {
      // invalid token given
      $error_response = array(
        'response' => array(
          'type' => 'error',
          'message' => t($e->getMessage()),
          'code' => '500',
        ),
      );
      $response->setContent(json_encode($error_response));
      return $response;
    }
  }

  /**
   * performs various checks on the incoming web service request
   * @return array
   */
  private function service_auth($check_path = TRUE) {
    $request = \Drupal::request();
    $headers = $request->headers->all();

    if (!array_key_exists('authorization', $headers)) {
      // no token exist
      $response = array(
        'response' => array(
          'type' => 'error',
          'message' => t('No token in the request header'),
          'code' => '403',
        ),
      );
      return $response;
    }

    $config = \Drupal::config('lndr.settings');
    $api_token = $config->get('lndr_token');
    if ($api_token === '') {
      // no token set in Drupal yet
      $response = array(
        'response' => array(
          'type' => 'error',
          'message' => t('No API token configured in Drupal'),
          'code' => '403',
        ),
      );
      return $response;
    }

    $authorization = str_replace('Token token=', '', $headers['authorization'][0]);
    if ($api_token != $authorization) {
      // invalid token given
      $response = array(
        'response' => array(
          'type' => 'error',
          'message' => t('Invalid token given.'),
          'code' => '403',
        ),
      );
      return $response;
    }

    parse_str($request->getQueryString(), $query);
    if ($check_path === TRUE) {
      if (!array_key_exists('path', $query)) {
        $response = array(
          'response' => array(
            'type' => 'error',
            'message' => t('Required parameter path not given'),
            'code' => '403',
          ),
        );
        return $response;
      }
    }

    // if everything checks out, we just pass the query back
    $response = array(
      'response' => array(
        'type' => 'status',
        'message' => $query,
        'code' => '200',
      ),
    );
    return $response;
  }
}
