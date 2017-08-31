<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\openy_campaign\Entity\MemberCampaignActivity;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Drupal\openy_campaign\Entity\MemberCheckin;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a "openy_campaign_game_block_form" form.
 */
class GameBlockForm extends FormBase {

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
    return 'openy_campaign_game_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $unplayedGames = NULL) {

    if (empty($unplayedGames)) {
      return [
        'message' => [
          '#markup' => 'You do not have any Games available. Visit facility more',
        ],
      ];
    }

    $form['games'] = [
      '#type' => 'value',
      '#value' => $unplayedGames,
    ];

    $form['label'] = [
      '#markup' => $this->formatPlural(count($unplayedGames), 'You have one game remaining', 'You have @count games available'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Play now!',
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
    $games = $form_state->getValue('games');
    $game = array_shift($games);

    $form_state->setRedirect('openy_campaign.game', [ 'uuid' => $game->uuid()]);
  }

}
