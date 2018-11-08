<?php

namespace Drupal\openy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Defines a form for selecting theme to install.
 */
class ThemeSelectForm extends FormBase {

  const DEFAULT_THEME = 'openy_rose';

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_theme_select';
  }

  /**
   * Constructs a ThemeSettingsForm object.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(ThemeHandlerInterface $theme_handler) {
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme_handler')
    );
  }

  /**
   * Loads Open Y themes for selection from openy.themes.yml file.
   *
   * @return mixed
   */
  public static function getOpenyThemes() {
    $path = drupal_get_path('profile', 'openy');
    $themes = Yaml::decode(file_get_contents($path . '/openy.themes.yml'));
    return $themes;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array &$install_state = NULL) {
    // Get all available themes.
    $themes = $this->themeHandler->rebuildThemeData();
    // Get Open Y themes for selection.
    $openy_themes = self::getOpenyThemes();
    $form['#title'] = $this->t('Select theme');
    foreach ($openy_themes as $key => &$theme) {
      $themes_options[$key] = $this->t($theme['name']);
      // Create a list which includes the current theme and all its base themes.
      if (isset($themes[$key]->base_themes)) {
        $theme_keys = array_keys($themes[$key]->base_themes);
        $theme_keys[] = $key;
      }
      else {
        $theme_keys = [$key];
      }
      // Look for a screenshot in the current theme or in its closest ancestor.
      foreach (array_reverse($theme_keys) as $theme_key) {
        if (isset($themes[$theme_key]) && file_exists($themes[$theme_key]->info['screenshot'])) {
          $openy_themes[$key]['screenshot'] = [
            'uri' => $themes[$theme_key]->info['screenshot'],
          ];
          break;
        }
      }
      // Get theme description
      $openy_themes[$key]['description'] = $themes[$key]->info["description"];
    }

    $form['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Select your preferred Open Y theme'),
      '#options' => $themes_options,
      '#default_value' => $this->getDefaultTheme(),
    ];

    // Theme specific content.
    foreach ($openy_themes as $key => $value) {
      $form['theme_info_' . $key] = [
        '#type' => 'container',
        'content' => $this->getSelectedThemeMarkup($value),
        '#states' => [
          'visible' => [
            ':input[name="theme"]' => [
              'value' => $key,
            ],
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

    return $form;
  }

  /**
   * Builds markup for individual theme info.
   *
   * @param $theme
   *
   * @return array
   * @throws \Drupal\Core\Extension\MissingDependencyException
   */
  private function getSelectedThemeMarkup($theme) {
    $form['name_markup'] = [
      '#type' => 'markup',
      '#markup' => $theme['name'],
      '#prefix' => '<h3>',
      '#suffix' => '</h3>',
    ];
    $form['desc_markup'] = [
      '#type' => 'markup',
      '#markup' => $theme['description'],
      '#prefix' => '<div class="desc_markup">',
      '#suffix' => '</div>',
    ];
    $form['screenshot_markup'] = [
      '#prefix' => '<div> <img src="' . base_path() . $theme['screenshot']['uri'] . '" width="294">',
      '#suffix' => '</div>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $GLOBALS['install_state']['openy']['theme'] = $form_state->getValue('theme');
  }

  /**
   * Returns default theme machine name.
   *
   * @return string
   *   Default preset machine name.
   */
  private function getDefaultTheme() {
    if (!empty($GLOBALS['install_state']['forms'][$this->getFormId()]['theme'])) {
      return $GLOBALS['install_state']['forms'][$this->getFormId()]['theme'];
    };

    return self::DEFAULT_THEME;
  }

}
