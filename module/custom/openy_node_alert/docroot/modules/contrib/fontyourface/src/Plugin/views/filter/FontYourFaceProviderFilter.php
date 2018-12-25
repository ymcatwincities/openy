<?php

namespace Drupal\fontyourface\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\StringFilter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filter handler which allows to search based on font providers.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("fontyourface_font_pid")
 */
class FontYourFaceProviderFilter extends StringFilter {

  /**
   * Exposed filter options.
   *
   * @var bool
   */
  protected $alwaysMultiple = TRUE;

  /**
   * Provide simple equality operator.
   */
  public function operators() {
    return [
      '=' => [
        'title' => $this->t('Is equal to'),
        'short' => $this->t('='),
        'method' => 'opEqual',
        'values' => 1,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $options = ['All' => '- Any -'];
    foreach (\Drupal::moduleHandler()->getImplementations('fontyourface_api') as $module_name) {
      $name = $module_name;
      $module_info = \Drupal::moduleHandler()->invoke($module_name, 'fontyourface_api');
      if ($module_info['name']) {
        $name = $module_info['name'];
      }
      $options[$module_name] = $module_info['name'];
    }

    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Font Provider'),
      '#options' => $options,
      '#default_value' => $this->value,
    ];

    if ($exposed = $form_state->get('exposed')) {
      $identifier = $this->options['expose']['identifier'];
      $user_input = $form_state->getUserInput();
      if (!isset($user_input[$identifier])) {
        $user_input[$identifier] = $this->value;
        $form_state->setUserInput($user_input);
      }
    }
  }

}
