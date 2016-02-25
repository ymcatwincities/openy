<?php

namespace Drupal\ymca_membership\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Link;

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

  /**
   * Builds Membership thank you page.
   */
  public function submissionView() {
    $uuid = \Drupal::request()->query->get('key');
    // Load submission data.
    $submissions = $this->entityTypeManager()
      ->getStorage('contact_message')
      ->loadByProperties([
        'uuid' => $uuid,
      ]);

    // Incorrect UUID entered.
    if (!$submissions) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Incorrect UUID entered');
    }

    $submission = reset($submissions);

    // Submission data available only for 1 minute.
    if (time() - $submission->created->getValue()[0]['value'] > 60) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    $location_build = [];
    $location_title = '';
    $location = \Drupal::service('webforms.node_extractor')
      ->extractNode($submission, 'field_what_is_your_preferred_y_l', 'location');
    if (!$location) {
      \Drupal::logger('ymca_membership')->alert(t('Unbound prefered Y location value selected.'));
    }
    else {
      $location_title = $location->getTitle();
      $address = $location->field_location->getValue()[0];
      list($line1, $line2) = explode(', ', $address['address_line1'], 2);
      $gmaps_address = str_replace(' ', '+', $line1);
      $phone = $location->field_phone->getValue()[0]['value'];

      $map_link = new Link('Map and Directions', URL::fromUri('http://maps.google.com/', [
        'query' => ['q' => $address['address_line1'] . $address['postal_code']],
      ]));

      $location_build = [
        // TODO: full title should be retrieved from other field.
        'full_title' => $location->getTitle(),
        'gmaps_address' => $gmaps_address,
        'address_line1' => $line1,
        'address_line2' => $line2,
        'postal_code' => $address['postal_code'],
        'map_link' => $map_link,
        'phone' => $phone,
        'more_link' => new Link('More about this location', URL::fromUri('entity:node/' . $location->id())),
      ];
    }

    $submission_build = [
      'firstname' => $submission->field_first_name->getValue()[0]['value'],
      'lastname' => $submission->field_last_name->getValue()[0]['value'],
      'email' => $submission->field_email_address->getValue()[0]['value'],
      'phone' => $submission->field_phone_number->getValue()[0]['value'],
      'location' => $location_title,
    ];

    return [
      '#theme' => 'membership_thank_you',
      '#location' => $location_build,
      '#submission' => $submission_build,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
