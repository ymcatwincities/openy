<?php

namespace Drupal\ymca_menu\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ymca_menu\Controller\YMCAMenuController;

/**
 * Implements Main menu configuration form.
 */
class YmcaMainMenuConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ymca_menu_main_menu_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ymca_menu.main_menu', 'ymca_menu.main_menu_b'];
  }

  /**
   * Retrieves menu tree.
   */
  private function getMenuTree() {
    $controller = new YMCAMenuController();
    $data = $controller->buildTree();
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $menu_tree = $this->getMenuTree();
    $root_id = reset($menu_tree->tree['o']);
    $top_level_items = &$menu_tree->tree[$root_id];

    $config_state = $this
      ->getConfig($form_state->getBuildInfo()['args'][0])
      ->get('items');

    $form['menu_items_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Menu name'),
        $this->t('Show in meganav'),
        $this->t('Enable overview link'),
      ],
      '#tableselect' => FALSE,
    ];

    foreach ($top_level_items as $key => $item) {
      if ($key === 'o') {
        continue;
      }
      $lookup = isset($menu_tree->lookup[$key]) ? $menu_tree->lookup[$key] : NULL;
      // Skip empty and disabled menu items.
      if (!$lookup || !empty($lookup['x'])) {
        continue;
      }

      if (!isset($config_state[$key])) {
        $config_state[$key] = [
          'show' => 0,
          'overview' => 1,
        ];
      }

      $title = $lookup['n'];
      if (!empty($lookup['m'])) {
        $title .= ' <small>(' . $lookup['m'] . ')</small>';
      }

      $form['menu_items_table'][$key]['title'] = [
        '#markup' => $title,
      ];

      $form['menu_items_table'][$key]['show'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('State for @title', ['@title' => $title]),
        '#title_display' => 'invisible',
        '#default_value' => !empty($config_state[$key]['show']),
        '#id' => 'menu-item-enabled-' . $key,
      ];

      $form['menu_items_table'][$key]['overview'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Overview link for @title', ['@title' => $title]),
        '#title_display' => 'invisible',
        '#default_value' => !empty($config_state[$key]['overview']),
        '#states' => [
          'enabled' => [
            '#menu-item-enabled-' . $key => ['checked' => TRUE],
          ]
        ]
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    $form['#cache'] = [
      'max-age' => 0,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('menu_items');
    $values = array_filter($values);
    if (count($values) > 6) {
      drupal_set_message($this->t('You have selected more than 6 items. It\'s recommended to show up to 6 items in meganav menu.'), 'warning');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('menu_items_table');
    $values = array_filter($values);
    $config = $this->getConfig($form_state->getBuildInfo()['args'][0]);
    $config->set('items', $values);
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Returns appropriate config object.
   *
   * @param string $id
   *   A/B variant id.
   *
   * @return object
   *   Config object.
   */
  private function getConfig($id) {
    $config_name = $id == 'b' ? 'ymca_menu.main_menu_b' : 'ymca_menu.main_menu';
    return $this->config($config_name);
  }

}
