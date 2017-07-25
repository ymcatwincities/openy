<?php

namespace Drupal\ymca_retention\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * YMCA Retention layout settings.
 */
class YmcaRetention extends LayoutDefault implements PluginFormInterface {

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

    /** @var \Drupal\ymca_retention\ActivityManager $service */
    $service = \Drupal::service('ymca_retention.activity_manager');

    // @todo disabled for summer campaign.
    /*
    $settings = \Drupal::configFactory()->get('ymca_retention.instant_win');
    $build['#attached']['drupalSettings']['ymca_retention']['loss_messages'] = [
    'part_1' => $settings->get('loss_messages_long_1'),
    'part_2' => $settings->get('loss_messages_long_2'),
    ];
     */

    // @todo disabled for summer campaign.
    $build['#attached']['drupalSettings']['ymca_retention']['resources'] = [
      'campaign' => Url::fromRoute('ymca_retention.campaign_json')->toString(),
      /*'spring2017campaign' => Url::fromRoute('ymca_retention.spring2017_campaign_json')->toString(),*/
      'member' => Url::fromRoute('ymca_retention.member_json')->toString(),
      'member_activities' => $service->getUrl(),
      /*'member_chances' => Url::fromRoute('ymca_retention.member_chances_json')->toString(),*/
      'member_checkins' => Url::fromRoute('ymca_retention.member_checkins_json')
        ->toString(),
      /*'member_bonuses' => Url::fromRoute('ymca_retention.member_bonuses_json')->toString(),*/
      /*'member_add_bonus' => Url::fromRoute('ymca_retention.member_add_bonus')->toString(),*/
      /*'recent_winners' => Url::fromRoute('ymca_retention.recent_winners_json')->toString(),*/
      /*'todays_insight' => Url::fromRoute('ymca_retention.todays_insight_json')->toString(),*/
    ];

    return $build;
  }

}
