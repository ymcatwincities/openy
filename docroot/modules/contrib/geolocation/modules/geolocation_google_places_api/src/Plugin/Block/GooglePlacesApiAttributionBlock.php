<?php

namespace Drupal\geolocation_google_places_api\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Fax' block.
 *
 * @Block(
 *   id = "geolocation_google_places_api_attribution_block",
 *   admin_label = @Translation("Geolocation - Google Places API Attribution block"),
 * )
 */
class GooglePlacesApiAttributionBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#markup' => '<span id="geolocation-google-places-api-attribution"></span>'];
  }

}
