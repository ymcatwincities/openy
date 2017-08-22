<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\node\Entity\Node;
use Drupal\openy_calc\DataWrapperInterface;
use Drupal\openy_socrates\OpenySocratesFacade;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\openy_campaign\Entity\MemberCampaignActivity;

/**
 * Provides a "openy_campaign_activity_block_form" form.
 */
class ActivityBlockForm extends FormBase {

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
    return 'openy_campaign_activity_block_form';
  }

  /**
   * Return ajax default properties.
   *
   * @return array
   *   List of properties.
   */
//  private function getAjaxDefaults() {
//    return [
//      'callback' => [$this, 'rebuildAjaxCallback'],
//      'wrapper' => 'membership-calc-wrapper',
//      'method' => 'replace',
//      'effect' => 'fade',
//      'progress' => ['type' => 'throbber'],
//    ];
//  }

  /**
   * Custom ajax callback.
   */
//  public function rebuildAjaxCallback(array &$form, FormStateInterface $form_state) {
//    return $form;
//  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $campaignId = NULL) {

    // @TODO: get ID of currently logged in Member.
    $memberId = 1;

    $campaignMemberId = reset(\Drupal::entityQuery('openy_campaign_member_campaign')
      ->condition('member', $memberId)
      ->condition('campaign', $campaignId)
      ->range(0, 1)
      ->execute());

    $campaign = Node::load($campaignId);

    $vocabulary = $campaign->field_campaign_fitness_category->entity;
    $vocabularyStorage = $this->manager->getStorage('taxonomy_vocabulary');
    $tids = $vocabularyStorage->getToplevelTids([$vocabulary->id()]);

    $terms = Term::loadMultiple($tids);

    $start = $campaign->field_campaign_start_date->date;
    $end = $campaign->field_campaign_end_date->date;

    if (empty($start) || empty($end)) {
      drupal_set_message('Start or End dates are not set for campaign.', 'error');
      return [
        'message' => [
          '#markup' => '[ Placeholder for Activity Tracking block ]'
        ],
      ];
    }

    $stopper = 0;
    while ($end->format('U') > $start->format('U') && $stopper < 100) {
      $key = $start->format('Y-m-d');

      $disabled = FALSE;
      if (\Drupal::time()->getRequestTime() < $start->format('U')) {
        $disabled = TRUE;
      }

      foreach ($terms as $tid => $term) {

        $childTerms = \Drupal::service('entity_type.manager')->getStorage("taxonomy_term")->loadTree($vocabulary->id(), $tid, 1, TRUE);
        $childTermIds = [];
        foreach ($childTerms as $childTerm) {
          $childTermIds[] = $childTerm->id();
        }

        $date = new \DateTime($key);
        $activityIds = $this->getExistingActivities($campaignMemberId, $date, $childTermIds);

        $name = $term->getName();
        if (!empty($activityIds)) {
          $name .= ' x ' . count($activityIds);
        }

        $form[$key][$tid] = [
          '#type' => 'link',
          '#title' => $name,
          '#disabled' => $disabled,
          '#url' => Url::fromRoute('openy_campaign.track-activity', [
            'visit_date' => $key,
            'campaign_member_id' => $campaignMemberId,
            'top_term_id' => $tid,
          ]),
          '#attributes' => [
            'class' => [
              'use-ajax',
              'button',
            ],
          ],
        ];
      }

      $start->modify('+1 day');
      $stopper++;
    }

    $form['#theme'] = 'openy_campaign_activity_form';

    // Attach the library for pop-up dialogs/modals.
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

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

  protected function getExistingActivities($campaignMemberId, $date, $activityIds) {
    return \Drupal::entityQuery('openy_member_campaign_activity')
      ->condition('member', $campaignMemberId)
      ->condition('type', MemberCampaignActivity::TYPE_ACTIVITY)
      ->condition('date', $date->format('U'))
      ->condition('activity', $activityIds, 'IN')
      ->execute();
  }

}
