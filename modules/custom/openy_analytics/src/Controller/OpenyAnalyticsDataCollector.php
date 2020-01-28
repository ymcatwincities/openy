<?php

namespace Drupal\openy_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\system\Controller\SystemInfoController;
use GuzzleHttp\Psr7\Response;


class OpenyAnalyticsDataCollector extends ControllerBase {
  public function postData() {
    $request = \Drupal::request();
    ddm($request->getContent());
//    $data = $request->getContent();
    // This condition checks the `Content-type` and makes sure to
    // decode JSON string from the request body into array.
    if ( 0 === strpos( $request->headers->get( 'Content-Type' ), 'application/json' ) ) {
      $data = json_decode( $request->getContent(), TRUE );
      $request->request->replace( is_array( $data ) ? $data : [] );
    }

    $response['data'] = 'Some test data to return';
    $response['method'] = 'POST';

    $response = new Response(200, [], serialize($data));
    return $response;
  }

  public function collectData() {
    $data = [];

    $data = [
      'phpversion' => phpversion(),
      'something' => '8.4.x',
      'another_something' => '7.0.x',
      'database' => shell_exec('mysql --version'),
    ];

    include_once DRUPAL_ROOT . '/core/includes/install.inc';
    drupal_load_updates();
    $requirements = system_requirements('runtime');

    // php
    $data['php'] = $requirements['php']['value']->getArguments()['@phpversion'];

    // database
    $data['database_system'] = $requirements['database_system']['value']->getUntranslatedString();
    $data['database_system_version'] = $requirements['database_system_version']['value'];

    // webserver
    $data['webserver'] = $requirements['webserver']['value'];

    // install_profile
    $data['install_profile'] = $requirements['install_profile']['value']->getArguments()['%profile'];
    $data['install_profile_version'] = $requirements['install_profile']['value']->getArguments()['%version'];

    // drupal
    $data['drupal'] = $requirements['drupal']['value'];

    self::saveData($data);
    return $data;
  }

  public function saveData($data) {
    $file_save_path_stream_directory =  'private://openy_analytics';
    \Drupal::service('file_system')->prepareDirectory($file_save_path_stream_directory, FileSystemInterface::CREATE_DIRECTORY);
    $fileLocation = $file_save_path_stream_directory . '/openy_analytics.json';
    $file = file_save_data(serialize($data), $fileLocation, FileSystemInterface::EXISTS_REPLACE);
  }
}
