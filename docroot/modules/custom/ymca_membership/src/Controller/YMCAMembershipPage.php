<?php

namespace Drupal\ymca_membership\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Render\RendererInterface;

/**
 * Implements YMCAMembershipPage.
 */
class YMCAMembershipPage extends ControllerBase {

  /**
   * Show the page.
   */
  public function pageView() {
    $assets = \Drupal::config('ymca_membership.assets')->get();

    $module_path = drupal_get_path('module', 'ymca_membership');
    foreach ($assets as &$asset) {
      $asset = Url::fromUri('base:' . $module_path . '/assets/' . $asset);
    }
    $assets_path = Url::fromUri('base:' . $module_path . '/assets');

    $form = $this->getForm('membership_form');

    $block_form_fee = $this->getBlock('Membership page: Form Enrollment Fee');
    $block_cost = $this->getBlock('Membership page: What Does It Cost?');

    return [
      '#assets' => $assets,
      '#assets_path' => $assets_path,
      '#block_form_fee' => $block_form_fee,
      '#block_cost' => $block_cost,
      '#form' => $form,
      '#theme' => 'membership_page',
      '#cache' => [
        'max-age' => 0,
      ],
      '#attached' => [
        'library' => [
          'ymca_membership/membership_page',
        ],
      ],
    ];
  }

  /**
   * Retrieves form.
   *
   * @param string $form_id
   *   Contact form machine name.
   *
   * @return array
   *   Form build array.
   */
  private function getForm($form_id) {
    $message = $this->entityManager()
      ->getStorage('contact_message')
      ->create(array(
        'contact_form' => $form_id,
      ));

    $form = $this->entityFormBuilder()->getForm($message);
    $form['#cache']['contexts'][] = 'user.permissions';

    return $form;
  }

  /**
   * Retrieves block.
   *
   * @param string $block_description
   *   Block description.
   *
   * @return array
   *   Form build array.
   */
  private function getBlock($block_description) {
    $block = $this->entityManager()
      ->getStorage('block_content')
      ->loadByProperties([
        'info' => $block_description,
      ]);

    $block_build = [
      '#markup' => '<div>No block named «<em>' . $block_description . '</em>» found. '
        . \Drupal::l('Add block', Url::fromUri('base:block/add')) . '</div>'
    ];
    if ($block) {
      $block_content_entity = reset($block);
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('block_content');
      $block_content = $view_builder->view($block_content_entity);
      $block_build = [
        '#theme' => ['datebased_block_with_cl'],
        '#content' => $block_content,
        '#contextual_links' => $block_content['#contextual_links'],
      ];
    }

    return $block_build;
  }

}
