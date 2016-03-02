<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\views\filter\SearchApiTerm.
 */

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Defines a filter for filtering on taxonomy term references.
 *
 * Based on \Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_term")
 */
// @todo Needs updating, especially the DB queries that merge on vocabulary.
class SearchApiTerm extends SearchApiFilterEntityBase {

  /**
   * {@inheritdoc}
   */
  public function hasExtraOptions() {
    return !empty($this->definition['vocabulary']);
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['type'] = array('default' => !empty($this->definition['vocabulary']) ? 'textfield' : 'select');
    $options['hierarchy'] = array('default' => 0);
    $options['error_message'] = array('default' => TRUE, 'bool' => TRUE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {
    $form['type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Selection type'),
      '#options' => array('select' => $this->t('Dropdown'), 'textfield' => $this->t('Autocomplete')),
      '#default_value' => $this->options['type'],
    );

    $form['hierarchy'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show hierarchy in dropdown'),
      '#default_value' => !empty($this->options['hierarchy']),
    );
    $form['hierarchy']['#states']['visible'][':input[name="options[type]"]']['value'] = 'select';
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    if (!empty($this->definition['vocabulary'])) {
      $vocabulary = Vocabulary::load($this->definition['vocabulary']);
      $title = $this->t('Select terms from vocabulary @voc', array('@voc' => $vocabulary->label()));
    }
    else {
      $vocabulary = FALSE;
      $title = $this->t('Select terms');
    }
    $form['value']['#title'] = $title;

    if ($vocabulary && $this->options['type'] == 'textfield') {
      $form['value']['#autocomplete_path'] = 'admin/views/ajax/autocomplete/taxonomy/' . $vocabulary->id();
    }
    else {
      if ($vocabulary && !empty($this->options['hierarchy'])) {
        /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
        $term_storage = \Drupal::entityManager()->getStorage('taxonomy_term');
        $tree = $term_storage->loadTree($vocabulary->id());
        $options = array();

        if ($tree) {
          foreach ($tree as $term) {
            $choice = new \stdClass();
            $choice->option = array($term->tid => str_repeat('-', $term->depth) . $term->name);
            $options[] = $choice;
          }
        }
      }
      else {
        $options = array();
        $query = Database::getConnection()->select('taxonomy_term_data', 'td');
        $query->innerJoin('taxonomy_vocabulary', 'tv', 'td.vid = tv.vid');
        $query->fields('td');
        $query->orderby('tv.weight');
        $query->orderby('tv.name');
        $query->orderby('td.weight');
        $query->orderby('td.name');
        $query->addTag('term_access');
        if ($vocabulary) {
          $query->condition('tv.vid', $vocabulary->id());
        }
        $result = $query->execute();
        foreach ($result as $term) {
          $options[$term->tid] = $term->name;
        }
      }

      $default_value = (array) $this->value;

      if ($form_state->get('exposed')) {
        $identifier = $this->options['expose']['identifier'];

        if (!empty($this->options['expose']['reduce'])) {
          $options = $this->reduceValueOptions($options);

          if (!empty($this->options['expose']['multiple']) && empty($this->options['expose']['required'])) {
            $default_value = array();
          }
        }

        if (empty($this->options['expose']['multiple'])) {
          if (empty($this->options['expose']['required']) && (empty($default_value) || !empty($this->options['expose']['reduce']))) {
            $default_value = 'All';
          }
          elseif (empty($default_value)) {
            $keys = array_keys($options);
            $default_value = array_shift($keys);
          }
          // Due to #1464174 there is a chance that array('') was saved in the
          // admin ui. Let's choose a safe default value.
          elseif ($default_value == array('')) {
            $default_value = 'All';
          }
          else {
            $copy = $default_value;
            $default_value = array_shift($copy);
          }
        }
      }
      $form['value']['#type'] = 'select';
      $form['value']['#multiple'] = TRUE;
      $form['value']['#options'] = $options;
      $form['value']['#size'] = min(9, count($options));
      $form['value']['#default_value'] = $default_value;

      $input = &$form_state->getUserInput();
      if ($form_state->get('exposed') && isset($identifier) && !isset($input[$identifier])) {
        $input[$identifier] = $default_value;
      }
    }
  }

  /**
   * Reduces the available exposed options according to the selection.
   *
   * @param array $options
   *   The original options list.
   *
   * @return array
   *   A reduced version of the options list.
   */
  protected function reduceValueOptions(array $options) {
    foreach ($options as $id => $option) {
      if (empty($this->options['value'][$id])) {
        unset($options[$id]);
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function valueValidate($form, FormStateInterface $form_state) {
    // We only validate if they've chosen the text field style.
    if ($this->options['type'] != 'textfield') {
      return;
    }

    parent::valueValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    if (empty($this->options['exposed'])) {
      return TRUE;
    }

    // If view is an attachment and is inheriting exposed filters, then assume
    // exposed input has already been validated.
    if (!empty($this->view->is_attachment) && $this->view->display_handler->usesExposed()) {
      $this->validatedExposedInput = (array) $this->view->exposed_raw_input[$this->options['expose']['identifier']];
    }

    // If it's non-required and there's no value don't bother filtering.
    if (!$this->options['expose']['required'] && empty($this->validatedExposedInput)) {
      return FALSE;
    }

    return parent::acceptExposedInput($input);
  }

  /**
   * {@inheritdoc}
   */
  public function validateExposed(&$form, FormStateInterface $form_state) {
    if (empty($this->options['exposed']) || empty($this->options['expose']['identifier'])) {
      return;
    }

    // We only validate if they've chosen the text field style.
    if ($this->options['type'] != 'textfield') {
      $input = $form_state->getValues()[$this->options['expose']['identifier']];
      if ($this->options['is_grouped'] && isset($this->options['group_info']['group_items'][$input])) {
        $input = $this->options['group_info']['group_items'][$input]['value'];
      }

      if ($input != 'All')  {
        $this->validatedExposedInput = (array) $input;
      }
      return;
    }

    parent::validateExposed($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function validateEntityStrings(array &$form, array $values, FormStateInterface $form_state) {
    if (empty($values)) {
      return array();
    }

    $tids = array();
    $names = array();
    $missing = array();
    foreach ($values as $value) {
      $missing[strtolower($value)] = TRUE;
      $names[] = $value;
    }

    if (!$names) {
      return array();
    }

    $query = Database::getConnection()->select('taxonomy_term_data', 'td');
    $query->innerJoin('taxonomy_vocabulary', 'tv', 'td.vid = tv.vid');
    $query->fields('td');
    $query->condition('td.name', $names);
    if (!empty($this->definition['vocabulary'])) {
      $query->condition('tv.id', $this->definition['vocabulary']);
    }
    $query->addTag('term_access');
    $result = $query->execute();
    foreach ($result as $term) {
      unset($missing[strtolower($term->name)]);
      $tids[] = $term->tid;
    }

    if ($missing) {
      if (!empty($this->options['error_message'])) {
        $form_state->setError($form, $this->formatPlural(count($missing), 'Unable to find term: @terms', 'Unable to find terms: @terms', array('@terms' => implode(', ', array_keys($missing)))));
      }
      else {
        // Add a bogus TID which will show an empty result for a positive filter
        // and be ignored for an excluding one.
        $tids[] = 0;
      }
    }

    return $tids;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    if ($this->options['type'] != 'select') {
      unset($form['expose']['reduce']);
    }
    $form['error_message'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display error message'),
      '#description' => $this->t('Display an error message if one of the entered terms could not be found.'),
      '#default_value' => !empty($this->options['error_message']),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function idsToString(array $ids) {
    return implode(', ', Database::getConnection()->select('taxonomy_term_data', 'td')
      ->fields('td', array('name'))
      ->condition('td.tid', array_filter($ids))
      ->execute()
      ->fetchCol());
  }

}
