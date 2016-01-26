<?php
/**
 * @file
 * Contains Drupal\ymca_frontend\Controller\YMCALocationsController.
 */

namespace Drupal\ymca_frontend\Controller;

/**
 * Controller for "Locations" page.
 */
class YMCALocationsController {

  /**
   * Set page's content.
   */
  public function content() {
    $base_path = base_path();
    $locations = \Drupal::config('ymca_frontend.locations')->get();
    foreach($locations as $location) {
      // Push locations only with YMCA and Camp tags.
      if ($location['tags'] == 'YMCA' || $location['tags'] == 'Camp') {
        $location['address2']         = '';
        $location['geid']             = (string) $location['geid'];
        $location['latitude']         = (string) $location['latitude'];
        $location['longitude']        = (string) $location['longitude'];
        $location['zip']              = (string) $location['zip'];
        $location['personify_brcode'] = (string) $location['personify_brcode'];
        $location['icon']             = $base_path . $location['icon'];
        $location['shadow']           = $base_path . $location['shadow'];
        $location['url']              = $base_path . $location['url'];

        $locations_processed[]        = $location;
      }
    }
    return array(
      '#theme' => 'locations_content',
      '#locations' => array(),
      '#attached' => array(
        'library' => array(
          'ymca_frontend/locations_map',
        ),
        'drupalSettings' => array(
          'locations' => array(
            'locations' => $locations_processed,
            'bounding_box' => array(
              48.06293,
              -93.85798,
              44.70658,
              -90.437897,
            ),
            'center_point' => array(
              46.384755,
              -92.1479385,
            ),
          ),
        ),
      ),
    );
  }

  /**
   * Set Title.
   */
  public function setTitle() {
    return t('Locations');
  }

}
