<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the MemberCampaign entity edit forms.
 *
 * @ingroup openy_campaign_member_campaign
 */
class CampaignUtilizationActivityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.openy_campaign_utilization_campaign.collection');
    $entity = $this->getEntity();
    $entity->save();
  }

}
