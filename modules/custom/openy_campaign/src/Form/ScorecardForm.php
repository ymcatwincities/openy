<?php

namespace Drupal\openy_campaign\Form;

use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openy_campaign\CampaignScorecardService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the Scorecard form.
 *
 * @ingroup openy_campaign_member
 */
class ScorecardForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Campaign Scorecard service.
   *
   * @var \Drupal\openy_campaign\CampaignScorecardService
   */
  protected $campaignScorecardService;

  /**
   * Scorecard form constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\openy_campaign\CampaignScorecardService $campaign_scorecard_service
   *   The Campaign Scorecard service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
    CampaignScorecardService $campaign_scorecard_service) {
    $this->entityTypeManager = $entity_type_manager;
    $this->campaignScorecardService = $campaign_scorecard_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('openy_campaign.generate_campaign_scorecard')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_campaign_scorecard_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // The block should always show the most actual data.
    $form['#cache'] = ['max-age' => CAMPAIGN_CACHE_TIME];

    $form['#prefix'] = '<div class="container">';
    $form['#suffix'] = '</div>';

    // Render Scorecard for all Campaigns in the system.
    $campaignIds = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'campaign')
      ->sort('created', 'DESC')
      ->execute();
    $campaigns = $this->entityTypeManager->getStorage('node')->loadMultiple($campaignIds);
    $options = [];
    foreach ($campaigns as $item) {
      /** @var \Drupal\node\Entity\Node $item */
      $options[$item->id()] = $item->getTitle();
    }

    $defaultCampaignID = (!empty($form_state->getValue('campaign_id'))) ? $form_state->getValue('campaign_id') : key($options);
    $defaultCampaign = $this->entityTypeManager->getStorage('node')->load($defaultCampaignID);

    if ($defaultCampaign instanceof Node === TRUE) {
      $form['#attached']['library'][] = 'openy_campaign/campaign_scorecard';
      $form['campaign_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Select Campaign'),
        '#options' => $options,
        '#default_value' => $defaultCampaign->id(),
      ];
      $scorecard = $this->campaignScorecardService->generateLiveScorecard($defaultCampaign);

      $form['scorecard'] = [
        '#markup' => '<div id="scorecard-wrapper">' . render($scorecard) . '</div>',
        '#weight' => 100500,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
