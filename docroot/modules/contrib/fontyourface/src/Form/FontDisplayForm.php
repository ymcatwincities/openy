<?php

namespace Drupal\fontyourface\Form;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\fontyourface\FontDisplayInterface;
use Drupal\fontyourface\Entity\Font;

/**
 * Class FontDisplayForm.
 *
 * @package Drupal\fontyourface\Form
 */
class FontDisplayForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $font_display = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $font_display->label(),
      '#description' => $this->t("Label for the Font display."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $font_display->id(),
      '#machine_name' => [
        'exists' => '\Drupal\fontyourface\Entity\FontDisplay::load',
      ],
      '#disabled' => !$font_display->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    $fonts = Font::loadActivatedFonts();
    if (empty($fonts)) {
      drupal_set_message($this->t('Please enable at least one font before creating/updating a font style.'), 'warning');
      $this->redirect('entity.font.collection')->send();
      exit();
    }

    $available_fonts = [];
    foreach ($fonts as $font) {
      $available_fonts[$font->url->value] = $font->name->value;
    }

    $drupal_themes = \Drupal::service('theme_handler')->listInfo();
    $themes = [];
    foreach ($drupal_themes as $key => $theme) {
      if (!empty($theme->info['hidden'])) {
        continue;
      }
      $themes[$key] = $theme->info['name'];
    }

    $form['font_url'] = [
      '#type' => 'select',
      '#title' => $this->t('Font'),
      '#description' => $this->t('Select the font to use as part of the font style'),
      '#default_value' => $font_display->getFontUrl(),
      '#options' => $available_fonts,
      '#required' => TRUE,
    ];

    foreach ($fonts as $font) {
      $element_id = 'font_display_usage_' . $font->Id();
      $form[$element_id] = [
        '#type' => 'container',
        '#states' => [
          'visible' => [
            'select[name="font_url"]' => ['value' => $font->url->value],
          ],
        ],
      ];
      $form[$element_id]['usage'] = [
        '#type' => 'fieldset',
        '#collapsible' => FALSE,
        '#title' => 'Usage',
      ];
      $form[$element_id]['usage']['instructions'] = [
        '#type' => 'item',
        '#markup' => 'If you wish to skip using the font display and add the css directly to your theme, copy/paste the following for the font into your theme css file:',
      ];
      $form[$element_id]['usage']['preview'] = [
        '#type' => 'html_tag',
        '#tag' => 'code',
        '#attributes' => [
          'style' => 'white-space: pre;',
        ],
        '#value' => fontyourface_font_css($font, NULL, "\n"),
      ];
    }

    $form['fallback'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fallback fonts'),
      '#description' => $this->t('Fallback fonts in case selected font fails to load.'),
      '#default_value' => $font_display->getFallback(),
    ];

    $preset_selectors = $this->getPresetSelectors();
    $form['preset_selectors'] = [
      '#type' => 'select',
      '#title' => $this->t('Preset Selectors'),
      '#description' => $this->t('Use preset selectors to easily display your font.'),
      '#options' => $preset_selectors,
      '#default_value' => $this->getDefaultSelectorOption($font_display),
      '#required' => TRUE,
    ];

    $form['selectors'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Selectors'),
      '#description' => $this->t('Selects the selected font will apply to. Note that all pages will have a "fontyourface" class on the body tag. You can use that to specify a font.'),
      '#default_value' => $font_display->getSelectors(),
      '#maxlength' => 300,
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          'select[name="preset_selectors"]' => ['value' => 'other'],
        ],
      ],
    ];

    $form['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Theme'),
      '#description' => $this->t('Select theme this display will work for.'),
      '#default_value' => $font_display->getTheme(),
      '#options' => $themes,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $preset_selectors = $form_state->getValue('preset_selectors');
    $selectors = $form_state->getValue('selectors');
    if ($preset_selectors == 'other' && empty($selectors)) {
      $form_state->setError($form['selectors'], $this->t("A selector is required if 'other' preset selector is selected"));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $font_display = $this->entity;
    $preset_selectors = $form_state->getValue('preset_selectors');
    $selectors = $form_state->getValue('selectors');
    $font_display->setSelectors($preset_selectors);
    if ($preset_selectors == 'other') {
      $font_display->setSelectors($selectors);
    }
    $status = $font_display->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Font display.', [
          '%label' => $font_display->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Font display.', [
          '%label' => $font_display->label(),
        ]));
    }
    fontyourface_save_and_generate_font_display_css($font_display);
    drupal_flush_all_caches();
    $form_state->setRedirectUrl($font_display->urlInfo('collection'));
  }

  /**
   * Get a list of preset selectors.
   *
   * @return array
   *   List of key-value selectors for selecting css selector presets.
   */
  private function getPresetSelectors() {
    return [
      '' => '-- Select --',
      '.fontyourface h1, .fontyourface h2, .fontyourface h3, .fontyourface h4, .fontyourface h5, .fontyourface h6' => 'All Headers (h1, h2, h3, h4, h5, h6)',
      '.fontyourface h1' => 'h1',
      '.fontyourface h2' => 'h2',
      '.fontyourface h3' => 'h3',
      '.fontyourface p, .fontyourface div' => 'standard text (p, div)',
      '.fontyourface' => 'everything',
      'other' => 'other',
    ];
  }

  /**
   * Return string that maps to selector.
   *
   * @param \Drupal\fontyourface\FontDisplayInterface $font_display
   *   Current Font Display entity.
   *
   * @return string
   *   String that maps to preset selector. 'Other' or empty string otherwise.
   */
  private function getDefaultSelectorOption(FontDisplayInterface $font_display) {
    $preset_selectors = $this->getPresetSelectors();
    $font_selector = $font_display->getSelectors();
    if (!empty($preset_selectors[$font_selector])) {
      return $font_selector;
    }
    elseif (!empty($font_selector)) {
      return 'other';
    }
    return '';
  }

}
