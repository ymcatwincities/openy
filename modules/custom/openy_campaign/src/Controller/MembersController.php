<?php

namespace Drupal\openy_campaign\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\openy_campaign\Entity\Member;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class MembersController.
 */
class MembersController extends ControllerBase {

  /**
   * Processes the members deletion batch.
   *
   * @param array $context
   *   The batch context.
   */
  public static function deleteAllMembersProcessBatch(&$context) {
    if (empty($context['sandbox'])) {
      $memberIds = \Drupal::entityQuery('openy_campaign_member')->execute();
      $memberCampaignIds = \Drupal::entityQuery('openy_campaign_member_campaign')->execute();

      $context['sandbox']['progress'] = 0;

      $context['sandbox']['members'] = array_values($memberIds);
      $context['sandbox']['member_campaigns'] = array_values($memberCampaignIds);
      $context['sandbox']['max'] = count($memberCampaignIds);
    }
    // Get Member and MemberCampaign ids.
    $memberId = $context['sandbox']['members'][$context['sandbox']['progress']];
    $memberCampaignId = $context['sandbox']['member_campaigns'][$context['sandbox']['progress']];

    // Get Member entity manager.
    $memberStorage = \Drupal::entityTypeManager()
      ->getStorage('openy_campaign_member');
    // Get MemberCampaign entity manager.
    $memberCampaignStorage = \Drupal::entityTypeManager()
      ->getStorage('openy_campaign_member_campaign');

    // Delete Member entity.
    $entities = $memberStorage->loadMultiple(array($memberId));
    $memberStorage->delete($entities);
    // Delete MemberCampaign entity.
    $entities = $memberCampaignStorage->loadMultiple(array($memberCampaignId));
    $memberCampaignStorage->delete($entities);

    // Save results.
    $context['results'][] = $memberId;
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
        ->formatPlural(count($results), 'Removed one item.', 'Removed @count items.');
    }
    drupal_set_message($message);

    $url = Url::fromRoute('entity.openy_campaign_member.collection');

    return new RedirectResponse($url->toString());
  }

  /**
   * Processes the members goal calculation batch.
   *
   * @param array $context
   *   The batch context.
   */
  public static function calculateGoalsProcessBatch(&$context) {
    if (empty($context['sandbox'])) {
      $member_ids = \Drupal::entityQuery('openy_campaign_member')->execute();

      $context['sandbox']['progress'] = 0;

      $context['sandbox']['members'] = array_values($member_ids);
      $context['sandbox']['max'] = count($member_ids);
    }

    $limit = 50;

    $member_ids = [];
    for ($i = 1; $i <= $limit; $i++) {
      if (!isset($context['sandbox']['members'][$context['sandbox']['progress']])) {
        break;
      }
      $member_id = $context['sandbox']['members'][$context['sandbox']['progress']];
      $member_ids[] = $member_id;
      $context['results'][] = $member_id;
      $context['sandbox']['progress']++;
    }

    $members = Member::loadMultiple($member_ids);
    $master_ids = [];
    /** @var Member $member */
    foreach ($members as $member) {
      $master_ids[] = $member->getPersonifyId();
    }

    if ($goals = Member::calculateVisitGoal($master_ids)) {
      foreach ($members as $member) {
        $member->setVisitGoal($goals[$member->getPersonifyId()]);
        $member->save();
      }
    }

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
   */
  public static function calculateGoalsFinishBatch($success, $results, $operations) {
    $message = t('Finished with an error.');
    if ($success) {
      $message = \Drupal::translation()
        ->formatPlural(count($results), 'Processed one member.', 'Processed @count members.');
    }
    drupal_set_message($message);
  }

}
