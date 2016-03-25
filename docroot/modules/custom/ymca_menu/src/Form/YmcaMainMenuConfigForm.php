<?php

namespace Drupal\ymca_menu\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ymca_menu\Controller\YMCAMenuController;

/**
 * Implements Main menu configuration form.
 */
class YmcaMainMenuConfigForm implements FormInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ymca_menu_main_menu_config';
  }

  /**
   * Retrieves menu tree.
   */
  private function getMenuTree() {
    if ($cache = \Drupal::cache()->get(YMCA_MENU_CACHE_CID)) {
      $data = $cache->data;
    }
    else {
      $controller = new YMCAMenuController(\Drupal::database());
      $data = $controller->buildTree();
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $menu_tree = $this->getMenuTree();
    $root_id = reset($menu_tree->tree['o']);
    $top_level_items = &$menu_tree->tree[$root_id];
    $options = [];
    foreach ($top_level_items as $key => $item) {
      if ($key === 'o' || !$item) {
        continue;
      }
      $lookup = $menu_tree->lookup[$key];
      // Skip disabled menu items.
      if (!$lookup || !empty($lookup['x'])) {
        continue;
      }

      // Count enabled children.
      $has_children = FALSE;
      foreach ($item['o'] as $child) {
        if (empty($menu_tree->lookup[$child]['x'])) {
          $has_children = TRUE;
          break;
        }
      }
      // Don't show items without at least 1 enabled children.
      if (!$has_children) {
        continue;
      }

      $options[$key] = sprintf('%s (%d)', $lookup['n'], $key);
    }

    $default_value = \Drupal::config('ymca_menu.main_menu')->get('items');

    $form['menu_items'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Main menu items'),
      '#options' => $options,
      '#default_value' => $default_value,
    ];

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
    $values = $form_state->getValue('menu_items');
    $values = array_filter($values);
    $config = \Drupal::service('config.factory')->getEditable('ymca_menu.main_menu');
    $config->set('items', $values);
    $config->save();
    drupal_set_message($this->t('Main menu has been updated.'));
  }

}
