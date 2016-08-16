<?php

namespace Drupal\ymca_menu\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Menu;

/**
 * Implements Main menu configuration form.
 */
class YmcaMenuListConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ymca_menu_main_menu_list';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ymca_menu.menu_list'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $menus = Menu::loadMultiple();
    $menu_list = \Drupal::config('ymca_menu.menu_list')->get('menu_list');
    $menu_order = array_flip($menu_list);

    $form['menu_list_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Menu name'),
        $this->t('State'),
        $this->t('Weight'),
      ],
      '#tableselect' => FALSE,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'thing-weight',
        ],
      ],
    ];

    foreach ($menus as $menu_id => $menu) {
      $weight = isset($menu_order[$menu_id]) ? $menu_order[$menu_id] : count($menus);
      $form['menu_list_table'][$menu_id]['#attributes']['class'][] = 'draggable';
      $form['menu_list_table'][$menu_id]['#weight'] = $weight;

      $form['menu_list_table'][$menu_id]['title'] = [
        '#plain_text' => $menu->label(),
      ];

      $form['menu_list_table'][$menu_id]['state'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('State for @title', ['@title' => $menu->label()]),
        '#title_display' => 'invisible',
        '#default_value' => in_array($menu_id, $menu_list),
      ];

      $form['menu_list_table'][$menu_id]['weight'] = [
        '#type' => 'weight',
        '#delta' => count($menus),
        '#title' => $this->t('Weight for @title', ['@title' => $menu->label()]),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#attributes' => ['class' => ['thing-weight']],
      ];
    }
    uasort($form['menu_list_table'], function($a, $b) {
      if (!isset($a['#weight'], $b['#weight']) || $a['#weight'] == $b['#weight']) {
        return 0;
      }
      return $a['#weight'] > $b['#weight'] ? 1 : -1;
    });

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#tableselect' => TRUE,
    ];

    $form['#cache'] = [
      'max-age' => 0,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $all_menus = Menu::loadMultiple();
    $all_menu_names = array_keys($all_menus);

    $values = $form_state->getValue('menu_list_table');
    // Sort values order based on weight.
    uasort($values, array('Drupal\Component\Utility\SortArray', 'sortByWeightElement'));
    $config_values = [];
    foreach ($values as $name => $data) {
      if (!in_array($name, $all_menu_names) || $data['state'] == 0) {
        continue;
      }
      $config_values[] = $name;
    }
    $config = $this->getConfig();
    $config->set('menu_list', $config_values);
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Returns appropriate config object.
   *
   * @return object
   *   Config object.
   */
  private function getConfig() {
    $config_name = 'ymca_menu.menu_list';
    return $this->config($config_name);
  }

}
