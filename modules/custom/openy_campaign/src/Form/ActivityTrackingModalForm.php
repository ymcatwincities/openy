<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\taxonomy\Entity\Term;
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
   * CalcBlockForm constructor.
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


    $term = Term::load($topTermId);
    $childTerms = $this->entityTypeManager->getStorage("taxonomy_term")->loadTree($term->getVocabularyId(), $topTermId, 1, TRUE);


    $form['#prefix'] = '<div class="activity_tracking_form_wrapper">';
    $form['#suffix'] = '</div>';

    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $options = [];
    /** @var Term $term */
    foreach ($childTerms as $term) {
      $options[$term->id()] = $term->getName();
    }

    // Build default values (already marked activities).
    $dateObject = new \DateTime($date);
    $existingActivitiesIds = MemberCampaignActivity::getExistingActivities($memberCampaignId, $dateObject, array_keys($options));

    $existingActivitiesEntities = $this->entityTypeManager->getStorage('openy_campaign_memb_camp_actv')->loadMultiple($existingActivitiesIds);
    $default_values = [];
    /** @var MemberCampaignActivity $activity */
    foreach ($existingActivitiesEntities as $activity) {
      $default_values[$activity->activity->entity->id()] = $activity->activity->entity->id();
    }
    $form['activities'] = [
      '#title' => $this->t('What activities did you do?'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $default_values,
      '#validated' => true,
    ];

    $form['member_campaign_id'] = [
      '#value' => $memberCampaignId,
      '#type' => 'hidden',
    ];

    $form['date'] = [
      '#value' => $date,
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
