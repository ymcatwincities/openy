<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Database\Connection;
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
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * WinnersBlockForm constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $connection
   */
  public function __construct(
    RendererInterface $renderer,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $connection
  ) {
    $this->renderer = $renderer;
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('entity_type.manager'),
      $container->get('database')
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

    // Get all regions - branches.
    $branches = $this->getBranches($campaignId);

    $selected = !empty($form_state->getValue('branch')) ? $form_state->getValue('branch') : $branches['default'];
    $form['branch'] = [
      '#type' => 'select',
      '#title' => 'Select your branch',
      '#options' => $branches,
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => '::ajaxWinnersCallback',
        'wrapper' => 'winners-block-wrapper',
      ],
      '#prefix' => '<div class="winners-branches-select">',
      '#suffix' => '</div>',
    ];

    $winnersBlock = '';
    if (!empty($form_state->getValue('branch')) && $form_state->getValue('branch') != 'default') {
      $branchId = $form_state->getValue('branch');
      $winnersBlock = $this->showWinnersBlock($campaignId, $branchId);
    }

    $form['winners'] = [
      '#prefix' => '<div id="winners-block-wrapper">',
      '#suffix' => '</div>',
      '#markup' => $winnersBlock,
    ];

    return $form;
  }

  /**
   * Render Winners Block.
   *
   * @param $campaignId
   * @param $branchId
   *
   * @return \Drupal\Component\Render\MarkupInterface
   */
  public function showWinnersBlock($campaignId, $branchId) {
    $winners = $this->getCampaignWinners($campaignId, $branchId);
    $prizes = $this->getCampaignPrizes($campaignId);

    // Group prizes by titles and select all winners for these titles.
    // This grouping is needed if the campaign has several identical prizes.
    $prizesMap = [];
    $winnersMap = [];
    foreach ($prizes as $place => $prize) {
      $prizesMap[$prize['title']] = [
        'title' => $prize['title'],
        'description' => $prize['text'],
        'winners' => [],
      ];
      if (!empty($winners[$place])) {
        foreach ($winners[$place] as $winner) {
          $winnersMap[$prize['title']][] = $winner;
        }
      }
    }

    // Sort all winners alphabetically.
    foreach ($winnersMap as &$winners) {
      usort($winners, function ($a, $b) {
        return strcmp($a['name'], $b['name']);
      });
    }

    foreach ($prizesMap as $key => $prize) {
      if (!empty($winnersMap[$key])) {
        $prizesMap[$key]['winners'] = $winnersMap[$key];
      }
    }

    $output = [];
    foreach ($prizesMap as $key => $prize) {
      if (!empty($prize['winners'])) {
        $output[] = [
          '#theme' => 'openy_campaign_winners',
          '#title' => $prize['title'],
          '#prize' => $prize['description'],
          '#winners' => $prize['winners'],
        ];
      }
    }

    $render = $this->renderer->renderRoot($output);

    return $render;
  }

  /**
   * AJAX Callback for the winners list.
   */
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
   * Get all available branches.
   *
   * @param int $campaignId
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   */
  private function getBranches($campaignId = NULL) {
    $locations = [
      'default' => $this->t('Location'),
    ];

    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $nids = $query->condition('type', 'branch')
      ->condition('status', '1')
      ->sort('title', 'ASC')
      ->execute();
    $branches = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    // Get list of branches related to the Campaign.
    $campaign = NULL;
    $campaignBranches = [];
    if (!empty($campaignId)) {
      /** @var \Drupal\node\Entity\Node $campaign Campaign node. */
      $campaign = $this->entityTypeManager->getStorage('node')->load($campaignId);
      $branchesField = $campaign->get('field_campaign_branch_target')->getValue();
      foreach ($branchesField as $branchItem) {
        $campaignBranches[] = $branchItem['target_id'];
      }
    }
    /** @var \Drupal\node\Entity\Node $branch */
    foreach ($branches as $branch) {
      if (!empty($campaignBranches) && !in_array($branch->id(), $campaignBranches)) {
        continue;
      }
      $locations[$branch->id()] = $branch->getTitle();
    }

    return $locations;
  }

  /**
   * Get all winners of current Campaign by branch.
   *
   * @param $campaignId
   * @param $branchId
   *
   * @return array
   */
  private function getCampaignWinners($campaignId, $branchId) {
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this->connection->select('openy_campaign_winner', 'w');
    $query->join('openy_campaign_member_campaign', 'mc', 'mc.id = w.member_campaign');
    $query->condition('mc.campaign', $campaignId);
    $query->join('openy_campaign_member', 'm', 'm.id = mc.member');
    $query->condition('m.branch', $branchId);
    $query->condition('m.is_employee', FALSE);
    $query->fields('m', ['id', 'first_name', 'last_name', 'membership_id']);
    $query->fields('w', ['place']);
    $query->addField('mc', 'id', 'member_campaign');
    $results = $query->execute()->fetchAll();

    $winners = [];
    foreach ($results as $item) {
      $lastNameLetter = !empty($item->last_name) ? ' ' . strtoupper($item->last_name[0]) : '';

      $winners[$item->place][] = [
        'member_id' => $item->id,
        'member_campaign_id' => $item->member_campaign,
        'name' => $item->first_name . $lastNameLetter,
        'membership_id' => substr($item->membership_id, -4),
      ];
    }

    return $winners;
  }

  /**
   * Get all prizes of current Campaign.
   *
   * @param $campaignId
   *
   * @return array
   */
  private function getCampaignPrizes($campaignId) {
    /** @var \Drupal\node\Entity\Node $campaign Campaign node. */
    $campaign = $this->entityTypeManager->getStorage('node')->load($campaignId);

    $prizes = [];
    foreach ($campaign->field_campaign_winners_prizes as $item) {
      /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
      $paragraph = $item->entity;
      $place = $paragraph->field_prgf_place->value;
      if (!empty($place)) {
        $prizes[$place] = [
          'place' => $place,
          'title' => $paragraph->field_prgf_prize_title->value,
          'text' => check_markup($paragraph->field_prgf_prize_text->value, $paragraph->field_prgf_prize_text->format),
        ];
      }
    }

    return $prizes;
  }

}
