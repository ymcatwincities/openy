<?php

namespace Drupal\ymca_retention\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class MembersController.
 */
class MembersController extends ControllerBase {

  /**
   * Processes the drawing winners batch.
   *
   * @param array $context
   *   The batch context.
   */
  public static function deleteAllMembersProcessBatch(&$context) {
    if (empty($context['sandbox'])) {
      $member_ids = \Drupal::entityQuery('ymca_retention_member')->execute();

      $context['sandbox']['progress'] = 0;

      $context['sandbox']['members'] = array_values($member_ids);
      $context['sandbox']['max'] = count($member_ids);
    }
    // Get member id.
    $member_id = $context['sandbox']['members'][$context['sandbox']['progress']];

    // Get entity manager.
    $storage = \Drupal::entityTypeManager()
      ->getStorage('ymca_retention_member');

    // Delete member entity.
    $entities = $storage->loadMultiple(array($member_id));
    $storage->delete($entities);

    // Save results.
    $context['results'][] = $member_id;
    $context['sandbox']['progress']++;
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Finish batch.
   *
   * @param bool $success
   *   Status.
   * @param array $results
   *   Results.
   * @param array $operations
   *   Operations.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response.
   */
  public static function deleteAllMembersFinishBatch($success, $results, $operations) {
    $message = t('Finished with an error.');
    if ($success) {
      $message = \Drupal::translation()
        ->formatPlural(count($results), 'Removed one member.', 'Removed @count members.');
    }
    drupal_set_message($message);

    $url = Url::fromRoute('entity.ymca_retention_member.collection');

    return new RedirectResponse($url->toString());
  }
}
