<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a tabs selector block.
 *
 * @Block(
 *   id = "retention_tabs_selector_block",
 *   admin_label = @Translation("[YMCA Retention] Tabs selector"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class TabsSelector extends BlockBase {

  /**
   * The tab count.
   *
   * @var TAB_COUNT
   */
  const TAB_COUNT = 5;

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    // Create settings for $tabCount.
    for ($i = 1; $i <= self::TAB_COUNT; $i++) {
      $value = "tab_{$i}";
      $form[$value] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Tab @n', ['@n' => $i]),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#tree' => TRUE,
      ];

      $form[$value]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enabled'),
        '#default_value' => isset($config[$value]) ? $config[$value]['enabled'] : FALSE,
      ];

      $form[$value]['tab_text'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Tab Text'),
        '#default_value' => isset($config[$value]) ? $config[$value]['tab_text'] : '',
        '#description' => $this->t('The text of the tab.'),
        '#states' => [
          'visible' => [
            "input[name='settings[{$value}][enabled]']" => ['checked' => TRUE],
          ],
          'required' => [
            "input[name='settings[{$value}][enabled]']" => ['checked' => TRUE],
          ],
        ],
      ];

      $form[$value]['tab_class'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Tab Class Attribute'),
        '#default_value' => isset($config[$value]) ? $config[$value]['tab_class'] : '',
        '#description' => $this->t('Add a class or classes to the tab for special styling.'),
        '#states' => [
          'visible' => [
            "input[name='settings[{$value}][enabled]']" => ['checked' => TRUE],
          ],
        ],
      ];

      $form[$value]['login_required'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Login Required'),
        '#default_value' => isset($config[$value]) ? $config[$value]['login_required'] : FALSE,
        '#description' => $this->t('If checked, the login modal will be triggered if not logged in.'),
        '#states' => [
          'visible' => [
            "input[name='settings[{$value}][enabled]']" => ['checked' => TRUE],
          ],
        ],
      ];

      $form[$value]['campaign_not_started'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Campaign Not Started'),
        '#default_value' => isset($config[$value]) ? $config[$value]['campaign_not_started'] : FALSE,
        '#description' => $this->t('If checked and the campaign hasn\'t started, the "Campaign Not Started Modal" modal will open.'),
        '#states' => [
          'visible' => [
            "input[name='settings[{$value}][enabled]']" => ['checked' => TRUE],
          ],
        ],
      ];

      $form[$value]['campaign_not_started_modal_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Campaign Not Started Modal'),
        '#default_value' => isset($config[$value]) ? $config[$value]['campaign_not_started_modal_id'] : FALSE,
        '#description' => $this->t('Fill out the modal hash ID to target with the modal. Ex: #bonus-modal-day'),
        '#states' => [
          'visible' => [
            "input[name='settings[{$value}][enabled]']" => ['checked' => TRUE],
            "input[name='settings[{$value}][campaign_not_started]']" => ['checked' => TRUE],
          ],
          'required' => [
            "input[name='settings[{$value}][campaign_not_started]']" => ['checked' => TRUE],
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Create settings for $tabCount.
    for ($i = 1; $i <= self::TAB_COUNT; $i++) {
      $tab = $form_state->getValue("tab_{$i}");
      // Only save enabled tabs.
      if (empty($tab) || empty($tab['enabled'])) {
        continue;
      }
      if (empty($tab['login_required'])) {
        $tab['login_required'] = FALSE;
      }
      if (empty($tab['campaign_not_started'])) {
        $tab['campaign_not_started'] = FALSE;
        unset($tab['campaign_not_started_modal_id']);
      }
      $this->setConfigurationValue("tab_{$i}", $tab);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $settings = \Drupal::config('ymca_retention.general_settings');
    $current_date = new \DateTime();
    $open_date = new \DateTime($settings->get('date_campaign_open'));
    $diff = $current_date->diff($open_date);

    $tabs = [];
    // Create settings for $tabCount.
    for ($i = 1; $i <= self::TAB_COUNT; $i++) {
      if (isset($config["tab_{$i}"])) {
        $tabs['tabs']["tab_{$i}"] = $config["tab_{$i}"];
      }
    }

    $build = [
      '#theme' => 'ymca_retention_tabs_selector',
      '#content' => $tabs,
      '#attached' => [
        'library' => [
          'ymca_retention/tabs-selector',
        ],
        'drupalSettings' => [
          'ymca_retention' => [
            'tabs_selector' => [
              'campaign_started' => $diff->invert,
            ],
          ],
        ],
      ],
    ];

    return $build;
  }

}
