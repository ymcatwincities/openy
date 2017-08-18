<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
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
   * Entity Manager
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $manager;


  /**
   * CalcBlockForm constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   */
  public function __construct(RendererInterface $renderer, EntityManagerInterface $manager) {
    $this->renderer = $renderer;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('entity.manager')
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
  public function buildForm(array $form, FormStateInterface $form_state, $date = NULL, $campaignMemberId = NULL, $topTermId = NULL) {
    $term = Term::load($topTermId);
    $childTerms = \Drupal::service('entity_type.manager')->getStorage("taxonomy_term")->loadTree($term->getVocabularyId(), $topTermId, 1, TRUE);

    $form['#prefix'] = '<div id="activity_tracking_modal_form_wrapper">';
    $form['#suffix'] = '</div>';

    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $options = [];
    foreach ($childTerms as $term) {
      $options[$term->id()] = $term->getName();
    }

    // Build default values (already marked activities).
    $dateObject = new \DateTime($date);
    $existingActivitiesIds = $this->getExistingActivities($campaignMemberId, $dateObject, array_keys($options));

    $existingActivitiesEntities = \Drupal::entityManager()->getStorage('openy_member_campaign_activity')->loadMultiple($existingActivitiesIds);
    $default_values = [];
    foreach ($existingActivitiesEntities as $activity) {
      $default_values[$activity->activity->entity->id()] = $activity->activity->entity->id();
    }
    $form['activities'] = [
      '#title' => $this->t('What activities did you do?'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $default_values,
    ];

    $form['campaign_member_id'] = [
      '#value' => $campaignMemberId,
      '#type' => 'value',
    ];

    $form['date'] = [
      '#value' => $date,
      '#type' => 'value',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'check',
      '#attributes' => [
        'class' => [
          'use-ajax',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'submitModalFormAjax'],
        'event' => 'click',
      ],
    ];

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#activity_tracking_modal_form_wrapper', $form));
    }
    else {
      $campaignMemberId = $form_state->getValue('campaign_member_id');
      $date = new \DateTime($form_state->getValue('date'));
      $activityIds = $form_state->getValue('activities');

      // Delete all records first.
      $existingActivityIds = $this->getExistingActivities($campaignMemberId, $date, array_keys($activityIds));
      entity_delete_multiple('openy_member_campaign_activity', $existingActivityIds);

      // Save new selection.
      $activityIds = array_filter($activityIds);
      foreach ($activityIds as $activityTermId) {
        $activity = MemberCampaignActivity::create([
          'created' => REQUEST_TIME,
          'date' => $date->format('U'),
          'member' => $campaignMemberId,
          'type' => MemberCampaignActivity::TYPE_ACTIVITY,
          'activity' => $activityTermId,
        ]);

        $activity->save();
      }

      $response->addCommand(new OpenModalDialogCommand("Great Success!", 'The modal form has been submitted.', ['width' => 800]));
    }

    return $response;
  }

  protected function getExistingActivities($campaignMemberId, $date, $activityIds) {
    return \Drupal::entityQuery('openy_member_campaign_activity')
      ->condition('member', $campaignMemberId)
      ->condition('type', MemberCampaignActivity::TYPE_ACTIVITY)
      ->condition('date', $date->format('U'))
      ->condition('activity', $activityIds, 'IN')
      ->execute();
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
