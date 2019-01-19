<?php

namespace Drupal\openy_activity_finder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\openy_activity_finder\Entity\ProgramSearchLog;
use Drupal\openy_activity_finder\OpenyActivityFinderBackendInterface;
use Drupal\openy_activity_finder\Entity\ProgramSearchCheckLog;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * {@inheritdoc}
 */
class ActivityFinderController extends ControllerBase {

  /**
   * @var \Drupal\openy_activity_finder\OpenyActivityFinderBackendInterface
   */
  protected $backend;

  /**
   * Creates a new ActivityFinderController.
   */
  public function __construct(OpenyActivityFinderBackendInterface $backend) {
    $this->backend = $backend;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $config = $container->get('config.factory')->get('openy_activity_finder.settings');

    return new static(
      $container->get($config->get('backend'))
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getData(Request $request) {
    $ip = $request->getClientIp();
    $user_agent = $request->headers->get('User-Agent', '');
    $hash_ip_agent = substr($user_agent, 0, 50) . '   ' . $ip;
    $record = [
      'hash_ip_agent' => $hash_ip_agent,
      'location' => $request->get('locations'),
      'keyword' => $request->get('keywords'),
      'category' => $request->get('categories'),
      'page' => $request->get('page'),
      'day' => $request->get('days'),
      'age' => $request->get('ages'),
    ];
    $record['hash'] = md5(json_encode($record));

    $log = ProgramSearchLog::create($record);
    $log->save();

    $parameters = $request->query->all();

    foreach ($parameters as &$value) {
      $value = urldecode($value);
    }

    $data = $this->backend->runProgramSearch($parameters, $log->id());

    return new JsonResponse($data);
  }

  /**
   * Redirect to register.
   */
  public function redirectToRegister(Request $request, $log) {
    $details = $request->get('details');
    $url = $request->get('url');

    if (!empty($details) && !empty($log)) {
      $details_log = ProgramSearchCheckLog::create([
        'details' => $details,
        'log_id' => $log,
        'type' => ProgramSearchCheckLog::TYPE_REGISTER,
      ]);
      $details_log->save();
    }

    if (empty($url)) {
      throw new NotFoundHttpException();
    }

    return new TrustedRedirectResponse($url, 301);
  }

  /**
   * Callback to retrieve programs full information.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function ajaxProgramsMoreInfo(Request $request) {
    $result = $this->backend->getProgramsMoreInfo($request);

    return new JsonResponse($result);
  }
}
