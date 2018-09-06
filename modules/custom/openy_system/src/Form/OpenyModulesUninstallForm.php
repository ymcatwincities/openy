<?php

namespace Drupal\openy_system\Form;

use Drupal\system\Form\ModulesUninstallForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openy\Form\ConfigureProfileForm;

/**
 * Provides OpenY packages uninstall interface.
 */
class OpenyModulesUninstallForm extends ModulesUninstallForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $packages = ConfigureProfileForm::getPackages();

    $form['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
    ];

    $form['filters']['text'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter packages'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by name or description'),
      '#description' => $this->t('Enter a part of the Open Y package name or description'),
      '#attributes' => [
        'class' => ['table-filter-text'],
        'data-table' => '#system-modules-uninstall',
        'autocomplete' => 'off',
      ],
    ];

    // Iterate over each of the packages.
    $form['uninstall'] = ['#tree' => TRUE];
    foreach ($packages as $key => $package) {

      $name = $package["name"];
      $form['modules'][$key]['#module_name'] = $name;
      $form['modules'][$key]['name']['#markup'] = $name;
      $form['modules'][$key]['description']['#markup'] = $this->t($package['description']);

      $form['uninstall'][$key] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Uninstall @module package', ['@module' => $name]),
        '#title_display' => 'invisible',
      ];
      $package_disabled = TRUE;
      foreach ($package['modules'] as $module_name) {
        if ($this->moduleHandler->moduleExists($module_name)) {
          $package_disabled = FALSE;
          break;
        }
      }
      $form['uninstall'][$key]['#disabled'] = $package_disabled;
    }

    $form['#attached']['library'][] = 'system/drupal.system.modules';
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Uninstall'),
      '#button_type' => 'primary',
    ];

    // TODO: Also, before component removing - would be nice to add a step
    // with a list of entities and where they are used ( for paragraps ) to
    // let content managers check all will be good after removal.
    // Just a simple table with a list of view/edit.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Form submitted, but no modules selected.
    if (!array_filter($form_state->getValue('uninstall'))) {
      $form_state->setErrorByName('', $this->t('No packages selected.'));
      $form_state->setRedirect('openy_system.modules_uninstall');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $profile_packages = ConfigureProfileForm::getPackages();
    // Get selected packages to store its modules in an expirable key value store.
    $packages = $form_state->getValue('uninstall');
    $uninstall = array_keys(array_filter($packages));
    $modules = [];
    // Get list of modules for selected to uninstall packages.
    foreach ($uninstall as $value) {
      $modules = array_merge($modules, $profile_packages[$value]['modules']);
    }
    // Remove from list already disabled modules.
    foreach ($modules as $key => $module) {
      if (!$this->moduleHandler->moduleExists($module)) {
        unset($modules[$key]);
      }
    }

    $account = $this->currentUser()->id();
    // Store the modules values for 6 hours. This expiration time is also used in
    // the form cache.
    $this->keyValueExpirable->setWithExpire($account, $modules, 6 * 60 * 60);

    // Redirect to the confirm form.
    $form_state->setRedirect('openy_system.modules_uninstall_confirm');
  }

}
