<?php

namespace Drupal\ymca_menu\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\system\Entity\Menu;

/**
 * Implements Main menu configuration form.
 */
class YmcaMenuListConfigForm extends ConfigFormBase {
  use StringTranslationTrait;

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
    $all_menus = Menu::loadMultiple();
    $menu_list = \Drupal::config('ymca_menu.menu_list')->get('menu_list');

    $form['menu_list_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Weight'),
        $this->t('State'),
        $this->t('Menu name')
      ],
      '#tableselect' => TRUE,
      '#js_select' => FALSE,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'thing-weight',
        ]
      ],
      '#attached' => [
        'library' => [
          'ymca_menu/draggable_table'
        ]
      ],
    ];

    $all_menus_sorted = $menu_list + array_diff(
        array_keys($all_menus),
        $menu_list
      );
    /**
     * @var string $name
     * @var Menu $object
     */
    foreach ($all_menus_sorted as $name) {

      $form['menu_list_table'][$name]['weight'] = [
        '#type' => 'checkbox',
        '#title' => $this->t(
          'Weight for @title',
          array('@title' => $all_menus[$name]->label())
        ),
        '#title_display' => 'invisible',
        '#attributes' => array('class' => array('thing-weight')),
      ];

      $form['menu_list_table'][$name]['state'] = [
        '#type' => 'checkbox',
        '#title' => $this->t(
          'State for @title',
          array('@title' => $all_menus[$name]->label())
        ),
        '#title_display' => 'invisible',
        '#default_value' => in_array($name, $menu_list),
      ];
      $form['menu_list_table'][$name]['#attributes']['class'][] = 'draggable';
      $form['menu_list_table'][$name]['title'] = [
        '#plain_text' => $all_menus[$name]->label(),
      ];
    }

    $form['submit'] = [
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
    $values = $form_state->getUserInput()['menu_list_table'];
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
   * @return object
   *   Config object.
   */
  private function getConfig() {
    $config_name = 'ymca_menu.menu_list';
    return $this->config($config_name);
  }

}
