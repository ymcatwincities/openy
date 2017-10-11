<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a "openy_campaign_game_block_form" form.
 */
class GameBlockForm extends FormBase {

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
          '#markup' => $this->t('You do not have any Games available.'),
        ],
      ];
    }

    $form['games'] = [
      '#type' => 'value',
      '#value' => $unplayedGames,
    ];

    /** @var \Drupal\Node\Entity\Node $campaign */
    $campaign = \Drupal::service('openy_campaign.campaign_menu_handler')->getCampaignNodeFromRoute();
    $coverImagePath = NULL;
    if (!empty($campaign->field_flip_cards_cover_image->entity)) {
      /** @var \Drupal\file\Entity\File $coverImage */
      $coverImage = $campaign->field_flip_cards_cover_image->entity;
      $coverImagePath = \Drupal::service('stream_wrapper_manager')->getViaUri($coverImage->getFileUri())->getExternalUrl();
    }
    else {
      $coverImagePath = base_path() . \Drupal::theme()->getActiveTheme()->getPath() . '/img/instant_game_cover_1.png';
    }

    $form['label'] = [
      '#markup' => $this->formatPlural(count($unplayedGames), 'You have one game remaining.', 'You have @count games available.'),
    ];

    $form['cover_image'] = [
      '#markup' => $coverImagePath,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Play instant-win game now!'),
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

    /** @var \Drupal\Node\Entity\Node $campaign */
    $campaign = $game->member->entity->campaign->entity;

    $form_state->setRedirect('openy_campaign.campaign_game', [ 'uuid' => $game->uuid()], [
      'query'=> ['campaign_id' => $campaign->id()]
    ]);
  }

}
