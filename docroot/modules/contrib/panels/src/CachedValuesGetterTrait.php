<?php

namespace Drupal\panels;

use Drupal\user\SharedTempStoreFactory;

/**
 * Provides a method for panels wizards cached values for non-wizard forms.
 */
trait CachedValuesGetterTrait {

  /**
   * Gets cached values for non-wizard classes that interact with a wizard.
   *
   * This method is specifically geared toward the needs of a panels use case
   * both within and outside of PageManager. To that end, some of the logic in
   * here is explicitly checking for known PageManager standards and behaving
   * as necessary to compensate for PageManager's needs. Other Panels
   * implementations are generally simpler and do not need the same degree of
   * customization. This trait accounts for both use cases.
   *
   * @param \Drupal\user\SharedTempStoreFactory $tempstore
   *   The tempstore object in use for the desired cached values.
   * @param string $tempstore_id
   *   The tempstore identifier.
   * @param string $machine_name
   *   The tempstore key.
   *
   * @return mixed
   */
  protected function getCachedValues(SharedTempStoreFactory $tempstore, $tempstore_id, $machine_name) {
    $machine_name = explode('--', $machine_name);
    $cached_values = $tempstore->get($tempstore_id)->get($machine_name[0]);
    // PageManager specific handling. If $machine_name[1] is set, it's the
    // page variant ID.
    if (isset($machine_name[1]) && !isset($cached_values['page_variant'])) {
      /** @var \Drupal\page_manager\PageInterface $page */
      $page = $cached_values['page'];
      $cached_values['page_variant'] = $page->getVariant($machine_name[1]);
    }
    if (!isset($cached_values['plugin']) && !empty($cached_values['page_variant'])) {
      $cached_values['plugin'] = $cached_values['page_variant']->getVariantPlugin();
    }
    return $cached_values;
  }

}
