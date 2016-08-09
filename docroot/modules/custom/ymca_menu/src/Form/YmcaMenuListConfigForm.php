<?php

namespace Drupal\ymca_menu\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\system\Entity\Menu;
use Drupal\ymca_menu\Controller\YMCAMenuController;

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
    $options = [];
    /**
     * @var string $name
     * @var Menu $object
     */
    foreach ($all_menus as $name => $object) {
      $options[$name] = $object->label();
    }

    $form['menu_items'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Main menu items'),
      '#options' => $options,
      '#default_value' => array_combine($menu_list, $menu_list),
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
    $config = $this->getConfig();
    $config->set('menu_list', array_keys($values));
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
