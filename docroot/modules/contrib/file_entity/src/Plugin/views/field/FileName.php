<?php

namespace Drupal\file_entity\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Field handler to provide simple renderer that allows linking to a file.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("file_name")
 */
class FileName extends FieldPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // Don't add the additional fields to groupby
    if (!empty($this->options['link_to_file'])) {
      $this->additional_fields['fid'] = array('table' => 'file_managed', 'field' => 'fid');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_file'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  /**
   * Provide link to file option.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_file'] = array(
      '#title' => t('Link this field to view the file'),
      '#description' => t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_file']),
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Prepares link to the file.
   *
   * @param string $data
   *   The XSS safe string for the link text.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from a single row of a view's query result.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($data, ResultRow $values) {
    if (!empty($this->options['link_to_file']) && !empty($this->additional_fields['fid'])) {
      if ($data !== NULL && $data !== '') {
        $this->options['alter']['make_link'] = TRUE;
        $this->options['alter']['url'] = Url::fromRoute('entity.file.canonical', ['file' => $this->getValue($values, 'fid')]);
      }
      else {
        $this->options['alter']['make_link'] = FALSE;
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->renderLink($this->sanitizeValue($value), $values);
  }

}
