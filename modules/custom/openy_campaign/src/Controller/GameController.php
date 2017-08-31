<?php

namespace Drupal\openy_campaign\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\node\NodeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Class GameController.
 */
class GameController extends ControllerBase {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a new GameController.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(EntityRepositoryInterface $entity_repository) {
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository')
    );
  }

  /**
   * Play the Game page.
   */
  public function playGamePage($uuid) {
    /** @var \Drupal\openy_campaign\Entity\MemberGame $game */
    $game = $this->entityRepository->loadEntityByUuid('openy_campaign_member_game', $uuid);

    $result = $game->result->value;
    /** @var \Drupal\Node\Entity\Node $campaign */
    $campaign = $game->member->entity->campaign->entity;

    $link = Link::fromTextAndUrl(t('Back to Campaign'), new Url('entity.node.canonical', [ 'node' => $campaign->id()]))->toString();

    if (!empty($result)) {
      return [
        '#markup' => $link . ' You have played this chance already. Your result: ' . $result,
      ];
    }

    $expected = $campaign->field_campaign_expected_visits->value;
    $coefficient = $campaign->field_campaign_prize_coefficient->value;
    $ranges = [];

    $previousRange = 0;
    foreach ($campaign->field_campaign_prizes as $target) {
      $prize = $target->entity;
      $nextRange = $previousRange + ceil($prize->field_prgf_prize_amount->value * $coefficient);

      $ranges[] = [
        'min' => $previousRange,
        'max' => $nextRange,
        'description' => $prize->field_prgf_prize_description->value,
      ];

      $previousRange = $nextRange;
    }

    $randomNumber = rand(0, $expected);

    $result = $campaign->field_campaign_prize_nowin->value;
    foreach ($ranges as $range) {
      if ($randomNumber >= $range['min'] && $randomNumber < $range['max']) {
        $result = $range['description'];
        break;
      }
    }

    $logMessage = json_encode([
      'randomNumber' => $randomNumber,
      'expected' => $expected,
      'coefficient' => $coefficient,
      'ranges' => $ranges,
    ]);

    $game->result->value = $result;
    $game->date = \Drupal::time()->getRequestTime();
    $game->log->value = $logMessage;
    $game->save();

    $html = '<div class="shadow"></div>
    <div class="epos">
    <div class="eball">
      <div class="egrad"></div>
      <div class="ewin"><div>
      <div class="triangle"></div>
      <div class="textbox"></div>
    </div>
    </div>';

    return [
      '#markup' => $link . $html,
      '#attached' => [
        'library' => [
          'openy_campaign/magic_ball',
        ],
        'drupalSettings' => [
          'openy_campaign' => [
            'result' => $result,
          ],
        ],
      ]
    ];
  }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function campaignResultsAccess(NodeInterface $node, AccountInterface $account) {
    return AccessResult::allowedIf($account->hasPermission('administer retention campaign') && $node->getType() == 'campaign');
  }

  /**
   * @param \Drupal\node\NodeInterface $node
   *
   * @return array Render array
   */
  public function campaignResults(NodeInterface $node) {
    $build = [
      'view' => views_embed_view('campaign_results', 'default', $node->id()),
    ];

    return $build;
  }

}
