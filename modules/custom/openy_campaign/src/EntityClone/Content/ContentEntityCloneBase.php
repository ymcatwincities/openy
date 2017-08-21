<?php

namespace Drupal\openy_campaign\EntityClone\Content;

use Drupal\entity_clone\EntityClone\Content\ContentEntityCloneBase as EntityCloneContentBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;

/**
 * Class ContentEntityCloneBase.
 */
class ContentEntityCloneBase extends EntityCloneContentBase {

  /**
   * {@inheritdoc}
   */
  public function cloneEntity(EntityInterface $entity, EntityInterface $clonedEntity, $properties = []) {
    parent::cloneEntity($entity, $clonedEntity, $properties);
    if ($entity->bundle() != 'campaign') {
      return $clonedEntity;
    }

    // Make clones of all referenced pages.
    if ($clonedEntity->hasField('field_pause_landing_page')) {
      $nid = $clonedEntity->get('field_pause_landing_page')->getValue();

      if (!empty($nid)) {
        $landingPage = Node::load($clonedEntity->get('field_pause_landing_page')->getValue());
        $clonedLandingPage = $landingPage->createDuplicate();
        $clonedLandingPage->save();
        $clonedEntity->set('field_pause_landing_page', $clonedLandingPage->id());
      }
    }

    if ($clonedEntity->hasField('field_rules_prizes_page')) {
      $nid = $clonedEntity->get('field_rules_prizes_page')->getValue();

      if (!empty($nid)) {
        $landingPage = Node::load($nid);
        $clonedLandingPage = $landingPage->createDuplicate();
        $clonedLandingPage->save();
        $clonedEntity->set('field_rules_prizes_page', $clonedLandingPage->id());
      }
    }

    if ($clonedEntity->hasField('field_campaign_pages')) {
      $fieldCampaignPages = $clonedEntity->get('field_campaign_pages')->getValue();
      $landingPageIds = [];
      foreach ($fieldCampaignPages as $field) {
        $nid = $field['target_id'];
        $landingPage = Node::load($nid);
        $clonedLandingPage = $landingPage->createDuplicate();
        $clonedLandingPage->save();
        $landingPageIds[] = $clonedLandingPage->id();
      }
      $clonedEntity->set('field_campaign_pages', $landingPageIds);
    }

    $clonedEntity->save();
    return $clonedEntity;
  }
}
