<?php

namespace Drupal\lndr\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;


/**
 * Controller routines for page example routes.
 */
class LndrExampleController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'lndr';
  }

  /**
   * Output test project data from Lndr for development purposes
   * @return Response
   */
  public function service() {
    $response = new Response();

    // Read the test file from file.
    $file_name = drupal_get_path('module', 'lndr') . '/lndr_test.json';
    $handle = fopen($file_name, 'r');
    $json = fread($handle, filesize($file_name));
    fclose($handle);

    $response->headers->set('Content-Type', 'application/json; charset=utf-8');
    $response->setContent($json);

    return $response;
  }
}
