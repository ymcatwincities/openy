<?php

namespace Drupal\activenet_registration\Plugin\Field\FieldFormatter;
     
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
     
/**
 * Plugin implementation of the 'activenet_sites' formatter.
 *
 * @FieldFormatter(
 *   id = "activenet_sites_formatter",
 *   label = @Translation("Sites from Activenet"),
 *   field_types = {
 *     "activenet_sites",
 *   }
 * )
 */

class SitesFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $storage =  \Drupal::service('activenet_registration.datastorage');
    $all_sites = $storage->getSites();
    
    if(@$items[0]->getValue()['site']){
      $selectedSiteId = $items[0]->getValue()['site'];

      foreach($all_sites as $key => $site_array) {
        if($site_array->site_id == $selectedSiteId) {
         return [
           '#theme' => 'activenet_site',
           '#site_id' => $selectedSiteId,
           '#site_name' => $site_array->site_name,
         ];
        }
      } 
    }
    else {
      return [];
    }
  }
}
