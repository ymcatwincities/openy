<?php

namespace Drupal\colorbutton\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "colorbutton" plugin.
 *
 * @CKEditorPlugin(
 *   id = "colorbutton",
 *   label = @Translation("Color Button")
 * )
 */
class ColorButton extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface {
  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return ['panelbutton'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return 'libraries/colorbutton/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $settings = $editor->getSettings();

    // enableMore can only be supported if the Color Dialog plugin is present.
    $config = [
      'colorButton_enableMore' => false,
      'colorButton_enableAutomatic' => true,
    ];

    if (!empty($settings['plugins']['colorbutton']['colors'])) {
      $config['colorButton_colors'] = $settings['plugins']['colorbutton']['colors'];
    }

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'TextColor' => array(
        'label' => $this->t('Text Color'),
        'image' => 'libraries/colorbutton/icons/textcolor.png',
      ),
      'BGColor' => array(
        'label' => $this->t('Background Color'),
        'image' => 'libraries/colorbutton/icons/bgcolor.png',
      ),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();

    $form['colors'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Text Colors'),
      '#description' => $this->t('Enter the hex values of all colors you would like to support (without the # symbol) separated by a comma. Leave blank to use the default colors for Color Button.'),
      '#default_value' => !empty($settings['plugins']['colorbutton']['colors']) ? $settings['plugins']['colorbutton']['colors'] : '',
    );

    $form['colors']['#element_validate'][] = array($this, 'validateInput');
    return $form;
  }

  /**
   * Ensure values entered for color hex values contain no unsafe characters.
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateInput(array $element, FormStateInterface $form_state) {
    $input = $form_state->getValue(['editor', 'settings', 'plugins', 'colorbutton', 'colors']);

    if (preg_match('/([^A-F0-9,])/i', $input)) {
      $form_state->setError($element, 'Only valid hex values are allowed (A-F, 0-9). No other symbols or letters are allowed. Please check your settings and try again.');
    }
  }
}