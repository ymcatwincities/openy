<?php

namespace Drupal\webform_test_ajax\Plugin\Block;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockBase;
use Drupal\webform\Entity\Webform;

/**
 * Provides a 'webform_test_block_context' block.
 *
 * @Block(
 *   id = "webform_test_ajax_block",
 *   admin_label = @Translation("Webform Ajax"),
 *   category = @Translation("Webform Test")
 * )
 */
class WebformTestAjaxBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $webforms = Webform::loadMultiple();

    $links = [];
    foreach ($webforms as $webform_id => $webform) {
      if (strpos($webform_id, 'test_ajax') !== 0) {
        continue;
      }

      if (!in_array($webform_id, ['test_ajax_confirmation_page', 'test_ajax_confirmation_url', 'test_ajax_confirmation_url_msg'])) {
        // Add destination to Ajax webform that don't redirect to confirmation page or URL.
        $route_options = ['query' => \Drupal::destination()->getAsArray()];
      }
      else {
        $route_options = [];
      }

      $links[$webform_id] = [
        'title' => $this->t('Open @webform_id', ['@webform_id' => $webform_id]),
        'url' => $webform->toUrl('canonical', $route_options),
        'attributes' => [
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 800,
          ]),
          'class' => [
            'use-ajax',
          ],
        ],
      ];
    }

    return [
      '#theme' => 'links',
      '#links' => $links,
      '#attached' => ['library' => ['core/drupal.ajax']],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
