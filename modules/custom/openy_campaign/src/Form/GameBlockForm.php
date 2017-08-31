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
          '#markup' => $this->t('You do not have any Games available. Visit facility more'),
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
      '#value' => $this->t('Play now!'),
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
