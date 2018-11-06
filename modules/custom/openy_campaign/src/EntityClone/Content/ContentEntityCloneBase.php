<?php

namespace Drupal\openy_campaign\EntityClone\Content;

use Drupal\entity_clone\EntityClone\Content\ContentEntityCloneBase as EntityCloneContentBase;
use Drupal\Core\Entity\EntityInterface;

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

    /** @var \Drupal\node\NodeStorage $nodeStorage */
    $nodeStorage = $this->entityTypeManager->getStorage('node');

    // Make clones of all referenced pages.
    if ($clonedEntity->hasField('field_pause_landing_page')) {
      $values = $clonedEntity->get('field_pause_landing_page')->getValue();

      if (!empty($values)) {
        $nid = $values[0]['target_id'];
        $landingPage = $nodeStorage->load($nid);
        $clonedLandingPage = $landingPage->createDuplicate();
        $clonedLandingPage->save();
        $clonedEntity->set('field_pause_landing_page', $clonedLandingPage->id());
      }
    }

    if ($clonedEntity->hasField('field_campaign_pages')) {
      $fieldCampaignPages = $clonedEntity->get('field_campaign_pages')->getValue();
      $landingPageIds = [];
      foreach ($fieldCampaignPages as $field) {
        $nid = $field['target_id'];
        $landingPage = $nodeStorage->load($nid);
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
