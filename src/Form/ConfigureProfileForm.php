<?php

namespace Drupal\openy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Extension\MissingDependencyException;

/**
 * Defines a form for selecting features to install.
 */
class ConfigureProfileForm extends FormBase {

  const DEFAULT_PRESET = 'none';
  const DEFAULT_PRESET_DRUSH = 'complete';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_configure_profile';
  }

  /**
   * Loads available installation types.
   *
   * @param bool $include_hidden
   *
   * @return mixed
   */
  public static function getInstallationTypes($include_hidden = FALSE) {
    $path = drupal_get_path('profile', 'openy');
    $installation_types = Yaml::decode(file_get_contents($path . '/openy.installation_types.yml'));

    foreach ($installation_types as $key => $installation_type) {
      if (!empty($installation_type['hidden']) && !$include_hidden) {
        unset($installation_types[$key]);
      }
    }

    return $installation_types;
  }

  /**
   * Loads available packages.
   *
   * @return mixed
   */
  public static function getPackages() {
    $path = drupal_get_path('profile', 'openy');
    $packages = Yaml::decode(file_get_contents($path . '/openy.packages.yml'));
    // Demo content package should be hidden.
    if (isset($packages['demo'])) {
      unset($packages['demo']);
    }

    return $packages;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array &$install_state = NULL) {
    $form['#title'] = $this->t('Select installation type');

    $installation_types = self::getInstallationTypes(function_exists('drush_main'));
    $presets = ['' => $this->t('Choose One')];
    foreach ($installation_types as $key => $type) {
      $presets[$key] = $this->t($type['name']);
    }

    $default_preset = $this->getDefaultPreset();
    $form['preset'] = [
      '#type' => 'select',
      '#title' => $this->t('Select your Open Y Install'),
      '#options' => $presets,
      '#default_value' => $default_preset,
      '#required' => TRUE,
    ];
    $form['preset_info'] = [
      '#type' => '#markup',
      '#markup' => self::buildQuestionMark($this->getOverallPresetsDescription()),
    ];
    $form['preset_top_info'] = [
      '#type' => '#markup',
      '#markup' => $this->t("
<p>Standard is the recommended version of Open Y for the majority of Y associations. If you are unsure of which version to pick, start with Standard.</p> ")
      ,];

    // Preset specific content.
    foreach ($presets as $preset => $name) {
      $form['preset_' . $preset . '_markup'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'preset-markup',
            'preset-markup-' . $preset,
          ],
        ],
        'content' => $this->getSelectedPresetMarkup($preset),
        '#states' => [
          'visible' => [
            ':input[name="preset"]' => array(
              'value' => $preset,
            ),
          ],
        ],
      ];
    }

    $form['actions'] = [
      'continue' => [
        '#type' => 'submit',
        '#value' => $this->t('Continue'),
      ],
      '#type' => 'actions',
    ];

    $form['#attached']['library'] = ['openy/installation_ui'];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $GLOBALS['install_state']['openy']['preset'] = $form_state->getValue('preset');
  }

  /**
   * Returns default preset machine name.
   *
   * @return string
   *   Default preset machine name.
   */
  private function getDefaultPreset() {
    if (!empty($GLOBALS['install_state']['forms'][$this->getFormId()]['preset'])) {
      return $GLOBALS['install_state']['forms'][$this->getFormId()]['preset'];
    };

    if (function_exists('drush_main')) {
      return self::DEFAULT_PRESET_DRUSH;
    }

    return self::DEFAULT_PRESET;
  }

  /**
   * Returns the lists of features groupped by preset.
   *
   * @return array
   *   Presets info.
   */
  private static function getPresetsInfo() {
    $installation_types = self::getInstallationTypes(TRUE);
    $presets_info = [];
    $packages_info = self::getPackages();

    foreach ($installation_types as $preset => $preset_info) {
      $presets_info[$preset] = $preset_info;
      $presets_info[$preset]['packages_expanded'] = [];
      foreach ($preset_info['packages'] as $package) {
        if (empty($packages_info[$package])) {
          continue;
        }
        $presets_info[$preset]['packages_expanded'][$package] = $packages_info[$package];
        if (!isset($packages_info[$package]['usage'])) {
          $packages_info[$package]['usage'] = 0;
        }
        $packages_info[$package]['usage']++;
      }
    }

    return $presets_info;
  }

  /**
   * Builds question mark tooltip markup.
   *
   * @param $contents
   *   The contents of the popup.
   *
   * @return string
   *   Final markup.
   */
  public static function buildQuestionMark($contents) {
    return "<div class='tooltip-helper'>
    <a href='#' class='tooltip-helper-icon'>?</a>
    <div class='tooltip-helper-contents'>" . $contents . "</div>
</div>";
  }

  /**
   * Returns description of the installation type select drop-down element.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  private function getOverallPresetsDescription() {
    return $this->t("
<p><strong>Standard</strong> is the recommended and most common Open Y installation. It contains all of the functionality used by the majority of Y associations, such as contact forms, programs, membership calculators, blogs, and news posts.</p>
<p><strong>Extended</strong> contains additional features such as GroupExPro integrations.</p>
<p>if you are unsure of what to select, please start with <strong>Standard</strong>.</p>
<p>NOTE: You can easily add ANY of the hundreds of Open Y features after you are finished setting up your site. We have tutorials to help you through the process.</p>");
  }

  /**
   * Builds markup for individual preset.
   *
   * @param $preset
   *
   * @return array
   * @throws \Drupal\Core\Extension\MissingDependencyException
   */
  private function getSelectedPresetMarkup($preset) {
    $presets = self::getPresetsInfo();
    if (!isset($presets[$preset])) {
      return [
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#value' => $this->t('Choose an installation type.'),
        ]
      ];
    }

    $form = [];
    $form['packages_to_install'] = [
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $this->t('%name - the following features will be installed:', [
          '%name' => $presets[$preset]['name'],
        ]),
      ],
      'list' => [
        '#type' => 'inline_template',
        '#template' => '
          <ul class="packages-to-install">
            {% for id, package in packages %}
              <li class="package">
                <a href="#{{ id }}">{{ package.name }}</a>
                <div class="package-description">{{ package.help|raw }}</div>
              </li>
            {% endfor %}
          </ul>',
        '#context' => [
          'packages' => $presets[$preset]['packages_expanded'],
        ],
      ],
    ];

    // Calculate the list of package that won't be installed.
    $packages_info = self::getPackages();
    foreach ($presets[$preset]['packages_expanded'] as $package => $package_info) {
      unset($packages_info[$package]);
    }

    if (!empty($packages_info)) {
      $form['other_packages'] = [
        'title' => [
          '#type' => 'inline_template',
          '#template' => '{{ content|raw }}',
          '#context' => [
            'content' => $this->getExtendedPackagesMarkup(),
          ],
        ],
        'list' => [
          '#type' => 'inline_template',
          '#template' => '
            <ul class="packages-other">
              {% for id, package in packages %}
                <li class="package">
                  <a href="#{{ id }}">{{ package.name }}</a>
                  <div class="package-description">{{ package.help|raw }}</div>
                </li>
              {% endfor %}
            </ul>',
          '#context' => [
            'packages' => $packages_info,
          ],
        ]

      ];
    }

    $form['experimental'] = [
      '#type' => 'inline_template',
      '#template' => '{{ content|raw }}',
      '#context' => [
        'content' => $this->getExperimentalModulesMarkup(),
      ],
    ];

    $modules = self::getModulesToInstall($preset);
    $form['debug'] = [
      '#access' => FALSE,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#title' => $this->t('Debug info'),
      '#type' => 'details',
      'without_dependencies' => [
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#title' => $this->t('Modules directly included into the selected packages'),
        '#type' => 'details',
        'without_dependencies' => [
          '#type' => 'inline_template',
          '#template' => '<p>{{ dependencies|join(", ") }}</p>',
          '#context' => [
            'dependencies' => $modules,
          ],
        ],
      ],
      'with_dependencies' => [
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#title' => $this->t('Dependencies to be installed'),
        '#type' => 'details',
        'with_dependencies' => [
          '#type' => 'inline_template',
          '#template' => '<p>{{ dependencies|join(", ") }}</p>',
          '#context' => [
            'dependencies' => self::getDependencies($modules, TRUE),
          ],
        ],
      ],
    ];

    return $form;
  }

  private function getExtendedPackagesMarkup() {
    $output = '<h3>The following features will not be installed however they can be easily added in the future:';
    $output .= $this->buildQuestionMark('<p>You can enable these feature any time from the admin interface in the CMS.</p>');
    $output .= '</h3>';
    return $output;
  }

  private function getExperimentalModulesMarkup() {
    $output = '<h3>Custom and experimental modules can also be installed in the future';
    $output .= $this->buildQuestionMark('<p>Open Y also has experimental features, but be aware that those features are experimental may not be stable.</p>');
    $output .= '</h3>';
    return $output;
  }


  public static function getModulesToInstall($preset) {
    $presets_info = self::getPresetsInfo();

    if (empty($presets_info[$preset])) {
      return [];
    }

    $module_list = [];
    foreach ($presets_info[$preset]['packages_expanded'] as $package) {
      foreach ($package['modules'] as $module) {
        $module_list[$module] = $module;
      }
    }

    return $module_list;
  }

  public static function getModulesToInstallWithDependencies($preset) {
    return self::getDependencies(self::getModulesToInstall($preset));
  }

  /**
   * @param array $module_list
   *
   * @param bool $only_dependencies
   *
   * @return array|bool
   * @throws \Drupal\Core\Extension\MissingDependencyException
   */
  public static function getDependencies(array $module_list, $only_dependencies = FALSE) {
    $module_list_orig = $module_list;
    $extension_config = \Drupal::configFactory()->getEditable('core.extension');
    // Get all module data so we can find dependencies and sort.
    $module_data = \Drupal::service('extension.list.module')->getList();
    $module_list = $module_list ? array_combine($module_list, $module_list) : [];
    if ($missing_modules = array_diff_key($module_list, $module_data)) {
      // One or more of the given modules doesn't exist.
      throw new MissingDependencyException(sprintf('Unable to install modules %s due to missing modules %s.', implode(', ', $module_list), implode(', ', $missing_modules)));
    }

    // Only process currently uninstalled modules.
    $installed_modules = $extension_config->get('module') ?: [];
    if (!$module_list = array_diff_key($module_list, $installed_modules)) {
      // Nothing to do. All modules already installed.
      return TRUE;
    }

    foreach ($module_list as $module => &$module_value) {
      foreach (array_keys($module_data[$module]->requires) as $dependency) {
        if (!isset($module_data[$dependency])) {
          // The dependency does not exist.
          throw new MissingDependencyException("Unable to install modules: module '$module' is missing its dependency module $dependency.");
        }

        // Skip already installed modules.
        if (!isset($module_list[$dependency]) && !isset($installed_modules[$dependency])) {
          $module_list[$dependency] = $dependency;
        }
      }
    }

    // Set the actual module weights.
    $module_list = array_map(function ($module) use ($module_data) {
      return $module_data[$module]->sort;
    }, $module_list);

    // Sort the module list by their weights (reverse).
    arsort($module_list);

    if ($only_dependencies) {
      foreach ($module_list_orig as $key => $value) {
        unset($module_list[$key]);
      }
    }

    $module_list = array_keys($module_list);

    return $module_list;
  }

}
