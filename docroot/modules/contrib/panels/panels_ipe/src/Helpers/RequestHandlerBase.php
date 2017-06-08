<?php

namespace Drupal\panels_ipe\Helpers;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Drupal\panels\Storage\PanelsStorageManagerInterface;
use Drupal\panels_ipe\Exception\EmptyRequestContentException;
use Drupal\user\SharedTempStore;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class RequestHandlerBase implements RequestHandlerInterface {

  /**
   * @var int */
  private $responseStatusCode = 200;

  /**
   * @var array */
  private $response = [];

  /**
   * @var \Drupal\user\SharedTempStore */
  private $tempStore;

  /**
   * @var \Drupal\panels\Storage\PanelsStorageManagerInterface */
  private $panelsStore;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface */
  private $moduleHandler;

  public function __construct(ModuleHandlerInterface $module_handler, PanelsStorageManagerInterface $panels_store, SharedTempStore $temp_store) {
    $this->moduleHandler = $module_handler;
    $this->panelsStore = $panels_store;
    $this->tempStore = $temp_store;
  }

  /**
   * @inheritdoc
   */
  public function handleRequest(PanelsDisplayVariant $panels_display, Request $request, $save_to_temp_store = FALSE) {
    $this->setResponse([]);

    try {
      $this->handle($panels_display, self::decodeRequest($request), $save_to_temp_store);
    }
    catch (EmptyRequestContentException $e) {
      $this->setResponse(['success' => FALSE], 400);
    }
  }

  /**
   * Handles the decoded request by making some change to the Panels Display.
   *
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display
   * @param mixed $decoded_request
   * @param bool $save_to_temp_store
   *
   * @throws \Drupal\panels_ipe\Exception\EmptyRequestContentException
   */
  protected abstract function handle(PanelsDisplayVariant $panels_display, $decoded_request, $save_to_temp_store = FALSE);

  /**
   * Attempts to decode the incoming request's content as JSON.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return mixed
   *
   * @throws \Drupal\panels_ipe\Exception\EmptyRequestContentException
   */
  protected static function decodeRequest(Request $request) {
    if (empty($request->getContent())) {
      throw new EmptyRequestContentException();
    }

    return Json::decode($request->getContent());
  }

  /**
   * Helper function for invoking hooks for all enabled modules.
   *
   * @param $hook
   * @param array $arguments
   */
  protected function invokeHook($hook, array $arguments) {
    $this->moduleHandler->invokeAll($hook, $arguments);
  }

  /**
   * Deletes TempStore and saves the current Panels display.
   *
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display
   *   The Panels display to be saved.
   *
   * @throws \Drupal\user\TempStoreException
   *   If there are any issues manipulating the entry in the temp store.
   */
  protected function savePanelsDisplay(PanelsDisplayVariant $panels_display) {
    $this->deletePanelsDisplayTempStore($panels_display);
    $this->panelsStore->save($panels_display);
  }

  /**
   * Saves the given Panels Display to TempStore.
   *
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display
   *
   * @throws \Drupal\user\TempStoreException
   */
  protected function savePanelsDisplayToTempStore(PanelsDisplayVariant $panels_display) {
    $this->tempStore->set($panels_display->getTempStoreId(), $panels_display->getConfiguration());
  }

  /**
   * Deletes the given Panels Display from TempStore.
   *
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display
   *
   * @throws \Drupal\user\TempStoreException
   */
  protected function deletePanelsDisplayTempStore(PanelsDisplayVariant $panels_display) {
    $this->tempStore->delete($panels_display->getTempStoreId());
  }

  /**
   * Returns the current response data as a JSON Response.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function getJsonResponse() {
    return new JsonResponse($this->response, $this->responseStatusCode);
  }

  /**
   * Updates our response and response status code properties.
   *
   * @param array $response
   * @param int $response_status_code
   */
  protected function setResponse(array $response, $response_status_code = 200) {
    $this->response = $response;
    $this->responseStatusCode = $response_status_code;
  }

}
