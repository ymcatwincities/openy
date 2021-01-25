<?php

namespace Drupal\openy_system\Form;

use Drupal\system\Form\ModulesListForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\openy\Form\ConfigureProfileForm;

/**
 * Provides openy modules installation interface.
 */
class OpenyModulesListForm extends ModulesListForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {


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
      '#description' => $this->t('Enter a part of the package name or description'),
      '#attributes' => [
        'class' => ['table-filter-text'],
        'data-table' => '#system-modules',
        'autocomplete' => 'off',
      ],
    ];
    $packages_array = $this->getPackages();
    // Iterate over each of the packages.
    $form['modules']['#tree'] = TRUE;
    foreach ($packages_array as $key => $package) {
      if ($key == 'demo') {
        continue;
      }
      $name = $package["name"];
      $form['modules'][$key][$name] = $this->buildPackageRow($package);
      $form['modules'][$key][$name] ['#parents'] = ['modules', $key];
    }

    // Add a wrapper around every package.
    foreach (Element::children($form['modules']) as $package) {
      $form['modules'][$package] += [
        '#type' => 'details',
        '#title' => $this->t($packages_array[$package]['name']),
        '#open' => TRUE,
        '#theme' => 'system_modules_details',
        '#attributes' => ['class' => ['package-listing']],
      ];
    }
    $form['#attached']['library'][] = 'system/drupal.system.modules';
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Install'),
      '#button_type' => 'primary',
    ];

    // TODO: Also, before component removing - would be nice to add a step
    // with a list of entities and where they are used ( for paragraps ) to
    // let content managers check all will be good after removal.
    // Just a simple table with a list of view/edit.
    return $form;
  }


  /**
   * Builds a table row for the OpenY packages  page.
   *
   * @param array $package
   *   The package for which to build the form row.
   *
   * @return array
   *   The form row for the given module.
   */
  protected function buildPackageRow(array $package) {
    // Set the basic properties. Should be present to avoid notices in template_preprocess_system_modules_details()
    $row['#required'] = [];
    $row['#requires'] = [];
    $row['#required_by'] = [];

    $modules = \Drupal::service('extension.list.module')->reset()->getList();
    // Get human readable names and status of modules in package.
    $module_names = [];
    foreach ($package['modules'] as $name) {
      if ($modules[$name]->status) {
        $module_names[] = $modules[$name]->info["name"];
      }
      else {
        $module_names[] = $modules[$name]->info["name"] . ' (' . $this->t('disabled') . ')';
      }
    }
    // Put module names and status to requires field of row.
    $row['#requires'] = $module_names;
    $row['name']['#markup'] = $package["name"];
    $row['description']['#markup'] = $this->t($package["description"]);
    $package_status = TRUE;
    foreach ($package['modules'] as $module_name) {
      if (!$this->moduleHandler->moduleExists($module_name)) {
        $package_status = FALSE;
      }
    }
    // Present a checkbox for installing and indicating the status of a module.
    $row['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Install'),
      '#default_value' => $package_status,
      '#disabled' => $package_status,
    ];

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $messenger = \Drupal::messenger();
    $packages = $this->getPackages();
    $modules_to_install = [];
    $packages_to_install = [];
    $values = $form_state->getValue('modules', FALSE);
    // Get list of modules to install for selected to enable packages.
    foreach ($values as $key => $value) {
      if ($value['enable'] === 1) {
        $modules_to_install = array_merge($modules_to_install, $packages[$key]['modules']);
        $packages_to_install[] = $packages[$key]['name'];
      }
    }
    // Remove from list installed modules.
    foreach ($modules_to_install as $key => $module) {
      if ($this->moduleHandler->moduleExists($module)) {
        unset($modules_to_install[$key]);
      }
    }

    // Install the given modules.
    if (!empty($modules_to_install)) {
      try {
        $this->moduleInstaller->install($modules_to_install);
        $messenger->addMessage($this->formatPlural(count($packages_to_install), 'Package %name has been enabled.', '@count packages have been enabled: %names.', [
          '%name' => $packages_to_install[0],
          '%names' => implode(', ', $packages_to_install),
        ]));
      } catch (PreExistingConfigException $e) {
        $config_objects = $e->flattenConfigObjects($e->getConfigObjects());
        $messenger->addMessage(
          $this->formatPlural(
            count($config_objects),
            'Unable to install @extension, %config_names already exists in active configuration.',
            'Unable to install @extension, %config_names already exist in active configuration.',
            [
              '%config_names' => implode(', ', $config_objects),
              '@extension' => $modules_to_install[$e->getExtension()],
            ]),
          'error'
        );
        return;
      } catch (UnmetDependenciesException $e) {
        $messenger->addMessage(
          $e->getTranslatedMessage($this->getStringTranslation(), $modules_to_install[$e->getExtension()]),
          'error'
        );
        return;
      }
    }
  }

  /**
   * Return packages array of Open Y profile.
   *
   * @return array
   *   Packages array.
   */
  protected function getPackages() {
    return ConfigureProfileForm::getPackages();
  }
}
