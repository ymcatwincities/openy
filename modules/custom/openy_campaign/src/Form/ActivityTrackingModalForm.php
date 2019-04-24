<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\openy_campaign\Entity\MemberCampaignActivity;

/**
 * Provides a "openy_campaign_activity_block_form" form.
 */
class ActivityTrackingModalForm extends FormBase {

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ActivityTrackingModalForm constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RendererInterface $renderer, EntityTypeManagerInterface $entity_type_manager) {
    $this->renderer = $renderer;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_campaign_activity_modal_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $date = NULL, $memberCampaignId = NULL, $topTermId = NULL) {
    /** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
    $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    $memberCampaignStorage = $this->entityTypeManager->getStorage('openy_campaign_member_campaign');
    /** @var \Drupal\node\NodeStorage $nodeStorage */
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $memberCampaignActivityStorage = $this->entityTypeManager->getStorage('openy_campaign_memb_camp_actv');

    $term = $termStorage->load($topTermId);
    $childTerms = $this->entityTypeManager->getStorage("taxonomy_term")->loadTree($term->getVocabularyId(), $topTermId, 1, TRUE);

    $memberCampaign = $memberCampaignStorage->load($memberCampaignId);
    $campaignId = $memberCampaign->getCampaign()->id();
    $campaign = $nodeStorage->load($campaignId);
    $enableActivitiesCounter = $campaign->field_enable_activities_counter->value;

    $form['#prefix'] = '<div class="activity_tracking_form_wrapper">';
    $form['#suffix'] = '</div>';

    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $options = [];
    $terms_with_counter = [];
    /** @var \Drupal\taxonomy\Entity\Term $term */
    foreach ($childTerms as $term) {
      $options[$term->id()] = $term->getName();
      if ($term->field_enable_activities_counter->value) {
        $terms_with_counter[] = $term->id();
      }
    }

    // Build default values (already marked activities).
    $dateObject = new \DateTime($date);
    $existingActivitiesIds = MemberCampaignActivity::getExistingActivities($memberCampaignId, $dateObject, array_keys($options));

    $existingActivitiesEntities = $memberCampaignActivityStorage->loadMultiple($existingActivitiesIds);
    $default_values = [];
    $activity_count_values = [];
    /** @var \Drupal\openy_campaign\Entity\MemberCampaignActivity $activity */
    foreach ($existingActivitiesEntities as $activity) {
      $default_values[] = $activity->activity->entity->id();
      $activity_count_values[$activity->activity->entity->id()] = $activity->count->value;
    }
    $form['activities'] = [
      '#title' => $this->t('What activities did you do?'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $default_values,
      '#validated' => TRUE,
    ];

    if ($enableActivitiesCounter) {
      $form['activities_count'] = [
        '#type' => 'container',
        '#tree' => TRUE,
        '#attributes' => array(
          'class' => 'activities-count',
        ),
      ];

      foreach (array_keys($options) as $activityId) {
        if (!in_array($activityId, $terms_with_counter)) {
          continue;
        }
        $form['activities_count'][$activityId] = [
          '#type' => 'hidden',
          '#value' => floatval($activity_count_values[$activityId]) ?? 0,
          '#attributes' => array(
            'class' => 'count-value',
            'data-activityId' => $activityId,
            'data-date' => $date
          ),
        ];
      }
    }

    $form['top_term_id'] = [
      '#value' => $topTermId,
      '#type' => 'hidden',
    ];

    $form['member_campaign_id'] = [
      '#value' => $memberCampaignId,
      '#type' => 'hidden',
    ];

    $form['date'] = [
      '#value' => $date,
      '#type' => 'hidden',
    ];

    $form['enable_activities_counter'] = [
      '#value' => $enableActivitiesCounter,
      '#type' => 'hidden',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
