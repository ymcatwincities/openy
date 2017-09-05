<?php

namespace Drupal\openy_campaign\Form;

use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Provides a "openy_campaign_winners_block_form" form.
 */
class WinnersBlockForm extends FormBase {

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
    return 'openy_campaign_winners_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $campaignId = NULL) {
    // Disable caching on this form.
    $form_state->setCached(FALSE);

    // Get all regions - branches
    $branches = $this->getBranches();

    $selected = !empty($form_state->getValue('branch')) ? $form_state->getValue('branch') : key($branches);
    $form['branch'] = [
      '#type' => 'select',
      '#title' => 'Select your region',
      '#options' => $branches,
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => '::ajaxWinnersCallback',
        'wrapper' => 'winners-block-wrapper',
      ],
    ];

    $winnersBlock = '';
    if (!empty($form_state->getValue('branch'))) {
      $branch = $form_state->getValue('branch');
      $winnersBlock = $this->showWinnersBlock($campaignId, $branch);
    }

    $form['winners'] = [
      '#prefix' => '<div id="winners-block-wrapper">',
      '#suffix' => '</div>',
      '#markup' =>  $winnersBlock,
    ];

    return $form;
  }

  /**
   * Render Winners Block
   *
   * @param $campaignId
   * @param $branch
   *
   * @return \Drupal\Component\Render\MarkupInterface
   */
  public function showWinnersBlock($campaignId, $branch) {
    $places = [
      '1st',
      '2nd',
      '3rd',
    ];
    $winners = $this->getCampaignWinners($campaignId, $branch);
    $prizes = $this->getCampaignPrizes($campaignId);

    $output = [];
    foreach ($places as $key => $place) {
      $output[] = [
        '#theme' => 'openy_campaign_winners',
        '#title' => $place,
        '#winners' => $winners[$key],
        '#prizes' => $prizes[$key],
      ];
    }

    $render = $this->renderer->renderRoot($output);

    return $render;
  }

  public function ajaxWinnersCallback($form, $form_state) {
    return $form['winners'];
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

  /**
   * Get all available branches
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   */
  private function getBranches() {
    $locations = [];
    $values = [
      'type' => 'branch',
      'status' => 1,
    ];
    $branches = $this->entityTypeManager->getListBuilder('node')->getStorage()->loadByProperties($values);

    /** @var \Drupal\node\Entity\Node $branch */
    foreach ($branches as $branch) {
      /** @var Term $locationName */
      $locationName = Term::load($branch->field_location_area->target_id);
      if (empty($locationName)) {
        continue;
      }
      $locations[$branch->id()] = $locationName->getName();
    }

    return $locations;
  }

  private function getCampaignWinners($campaignId, $branch) {
    $winners = [];

    // Get all

    return $winners;
  }

  private function getCampaignPrizes($campaignId) {
    $prizes = [];

    return $prizes;
  }

}
