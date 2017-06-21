<?php

namespace Drupal\ymca_retention\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\Element;
use Drupal\ymca_retention\Entity\Member;

/**
 * YMCA Retention layout settings.
 */
class YmcaRetentionWinnersLayout extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'extra_classes' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $form['extra_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extra classes'),
      '#default_value' => $configuration['extra_classes'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['extra_classes'] = $form_state->getValue('extra_classes');
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);

    // Remove prefix and suffix on regions.
    foreach (Element::children($build) as $key) {
      unset($build[$key]['#prefix']);
      unset($build[$key]['#suffix']);
    }

    $build['#attached']['drupalSettings']['ymca_retention']['winnersData'] = $this->prepareWinnersData();

    return $build;
  }

  /**
   * Prepare winners.
   */
  private function prepareWinnersData() {
    $cache_service = \Drupal::service('cache.ymca_retention');

    if ($cache = $cache_service->get('winnersdata')) {
      $winnersData = $cache->data;

      return $winnersData;
    }

    $winnersData = [
      'branches' => [],
      'winners' => [],
    ];
    // Load all winners.
    $winner_ids = \Drupal::entityQuery('ymca_retention_winner')
      ->execute();
    $storage = \Drupal::entityTypeManager()->getStorage('ymca_retention_winner');
    $winners = $storage->loadMultiple($winner_ids);

    $member_ids = [];
    foreach ($winners as $winner) {
      $member_ids[] = $winner->getMemberId();
    }

    $members = Member::loadMultiple($member_ids);

    foreach ($winners as $winner) {
      $branch_id = $winner->getBranchId();
      $member = $members[$winner->getMemberId()];
      $member_id = $member->getMemberId();
      $hidden_member_id = str_repeat('*', strlen($member_id) - 4) . substr($member_id, -4);
      $member_first_name = $member->getFirstName();
      $member_last_name = $member->getLastName();
      $member_name = $member_first_name . ' ' . $member_last_name[0] . '.';

      if (!isset($winnersData['winners'][$branch_id])) {
        $winnersData['winners'][$branch_id] = [
          1 => [],
          2 => [],
          3 => [],
        ];
      }

      $winnersData['winners'][$branch_id][$winner->getPlace()][] = [
        'id' => $hidden_member_id,
        'name' => $member_name,
      ];
    }

    // Define compare function.
    $winnersCompare = function ($winner1, $winner2) {
      return strcmp($winner1['name'], $winner2['name']);
    };

    foreach ($winnersData['winners'] as $branch_id => $branch_winners) {
      foreach ($branch_winners as $place => $winners_array) {
        uasort($winnersData['winners'][$branch_id][$place], $winnersCompare);
        $winnersData['winners'][$branch_id][$place] = array_values($winnersData['winners'][$branch_id][$place]);
      }
    }

    $branch_ids = array_keys($winnersData['winners']);
    $storage = \Drupal::entityTypeManager()->getStorage('mapping');
    $branches = $storage->loadMultiple($branch_ids);
    foreach ($branches as $branch_id => $branch) {
      $winnersData['branches'][$branch_id] = $branch->getName();
    }
    asort($winnersData['branches']);

    $cache_service->set('winnersdata', $winnersData, REQUEST_TIME + 6 * 60 * 60);

    return $winnersData;
  }

}
