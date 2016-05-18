<?php

namespace Drupal\panels_ipe\Helpers;

use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Symfony\Component\HttpFoundation\Request;

interface RequestHandlerInterface {

  /**
   * Handles an incoming request for a given PanelsDisplayVariant.
   *
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param bool $save_to_temp_store
   */
  public function handleRequest(PanelsDisplayVariant $panels_display, Request $request, $save_to_temp_store = FALSE);

  /**
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function getJsonResponse();

}
