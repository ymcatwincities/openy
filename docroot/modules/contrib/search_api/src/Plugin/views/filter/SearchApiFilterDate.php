<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\views\filter\SearchApiFilterDate.
 */

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a filter for filtering on dates.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_date")
 */
class SearchApiFilterDate extends SearchApiFilter {

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    return parent::defineOptions() + array(
      'widget_type' => array('default' => 'default'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function hasExtraOptions() {
    if (\Drupal::moduleHandler()->moduleExists('date_popup')) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildExtraOptionsForm($form, $form_state);
    if (\Drupal::moduleHandler()->moduleExists('date_popup')) {
      $widget_options = array(
        'default' => $this->t('Default'),
        'date_popup' => $this->t('Date popup'),
      );
      $form['widget_type'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Date selection form element'),
        '#default_value' => $this->options['widget_type'],
        '#options' => $widget_options,
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    // If we are using the date popup widget, overwrite the settings of the form
    // according to what date_popup expects.
    if ($this->options['widget_type'] == 'date_popup' && \Drupal::moduleHandler()->moduleExists('date_popup')) {
      $form['value']['#type'] = 'date_popup';
      $form['value']['#date_format'] = 'm/d/Y';
      unset($form['value']['#description']);
    }
    elseif (!$form_state->get('exposed')) {
      $form['value']['#description'] = $this->t('A date in any format understood by <a href="@doc-link">PHP</a>. For example, "@date1" or "@date2".', array(
        '@doc-link' => 'http://php.net/manual/en/function.strtotime.php',
        '@date1' => format_date(REQUEST_TIME, 'custom', 'Y-m-d H:i:s'),
        '@date2' => 'now + 1 day',
      ));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if ($this->operator === 'empty') {
      $this->query->addCondition($this->realField, NULL, '=', $this->options['group']);
    }
    elseif ($this->operator === 'not empty') {
      $this->query->addCondition($this->realField, NULL, '<>', $this->options['group']);
    }
    else {
      while (is_array($this->value)) {
        $this->value = $this->value ? reset($this->value) : NULL;
      }
      $v = is_numeric($this->value) ? $this->value : strtotime($this->value, REQUEST_TIME);
      if ($v !== FALSE) {
        $this->query->addCondition($this->realField, $v, $this->operator, $this->options['group']);
      }
    }
  }

}
