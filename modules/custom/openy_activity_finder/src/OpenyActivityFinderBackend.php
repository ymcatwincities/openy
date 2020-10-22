<?php

namespace Drupal\openy_activity_finder;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;

abstract class OpenyActivityFinderBackend implements OpenyActivityFinderBackendInterface {

  /**
   * Activity Finder configuration.
   */
  protected $config;

  /**
   * Site's default timezone.
   */
  protected $timezone;

  /**
   * Run Programs search.
   *
   * @param $parameters
   *   GET parameters for the search.
   * @param $log_id
   *   Id of the Search Log needed for tracking Register / Details actions.
   */
  abstract public function runProgramSearch($parameters, $log_id);

  /**
   * Get list of all locations for filters.
   */
  abstract public function getLocations();

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('openy_activity_finder.settings');
    $this->timezone = new \DateTimeZone(\Drupal::config('system.date')->get('timezone')['default']);
  }

  /**
   * Get ages from configuration.
   */
  public function getAges() {
    $ages = [];

    $ages_config = $this->config->get('ages');

    if (!$ages_config) {
      return [];
    }

    foreach (explode("\n", $ages_config) as $row) {
      $row = trim($row);
      list($months, $label) = explode(',', $row);
      $ages[] = [
        'label' => $label,
        'value' => $months,
      ];
    }

    return $ages;
  }

  /**
   * Get weeks from configuration.
   */
  public function getWeeks() {
    $weeks = [];

    $weeks_config = $this->config->get('weeks');

    if (!$weeks_config) {
      return [];
    }

    foreach (explode("\n", $weeks_config) as $row) {
      $row = trim($row);
      list($months, $label) = explode(',', $row);
      $weeks[] = [
        'label' => $label,
        'value' => $months,
      ];
    }

    return $weeks;
  }

  public function getCategoriesType() {
    return 'multiple';
  }

}
