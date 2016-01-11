<?php
/**
 * @file
 * Contains \Drupal\ymca_groupex\Form\FindClassesForm
 */

namespace Drupal\ymca_groupex\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ymca_groupex\GroupexRequestTrait;

/**
 * Implements a FindClassesForm.
 */
class FindClassesForm extends FormBase {

  use GroupexRequestTrait;

  /**
   * Get form item options.
   *
   * @param $data
   *   Data to iterate.
   * @param $key
   *   Key name.
   * @param $value
   *   Value name.
   *
   * @return array
   *   Array of options.
   */
  private function getOptions($data, $key, $value) {
    $options = [];
    foreach ($data as $item) {
      $options[$item->{$key}] = $item->{$value};
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'find_classes_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['note'] = [
      '#markup' => $this->t('Search dates and times for drop-in classes (no registration required). Choose a specific category or time of day, or simply click through to view all.'),
    ];

    $form['class_name'] = [
      '#type' => 'select',
      '#options' => array_merge(
        [0 => $this->t('All')],
        $this->getOptions($this->request(['query' => ['classes' => TRUE]]), 'id', 'title')
      ),
      '#title' => $this->t('Class Name (optional)'),
    ];

    $form['categories'] = [
      '#type' => 'select',
      '#options' => array_merge(
        [0 => $this->t('All')],
        $this->getOptions($this->request(['query' => ['categories' => TRUE]]), 'id', 'name')
      ),
      '#title' => $this->t('Category (optional)'),
    ];

    $form['time'] = [
      '#type' => 'checkboxes',
      '#options' => [
        'morning' => $this->t('Morning (6 a.m. - 12 p.m.)'),
        'afternoon' => $this->t('Afternoon (12 p.m. - 5 p.m.)'),
        'evening' => $this->t('Evening (5 p.m. - 10 p.m.)'),
      ],
      '#title' => $this->t('Time of Day (optional)'),
    ];

    $form['view'] = [
      '#type' => 'checkboxes',
      '#options' => [
        'day' => $this->t('Day'),
        'week' => $this->t('Week'),
      ],
      '#default_value' => 'day',
      '#title' => $this->t('View Day or Week'),
      '#description' => $this->t('Selecting more than one location limits your search to one day.'),
    ];

    $form['date'] = [
      '#type' => 'date',
      '#title' => $this->t('Start Date'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Find Class'),
    ];

    // Todo: Move that to all schedules form
//    $form['locations'] = [
//      '#type' => 'checkboxes',
//      '#options' => $this->getOptions($this->request(['query' => ['locations' => TRUE]]), 'id', 'name'),
//      '#title' => t('Locations'),
//    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect(
      'ymca_groupex.schedules_search_results',
      ['node' => 4],
      [
        'query' => [
          'location' => 26,
          'class' => 'any',
          'category' => 'any',
          'time_of_day' => 'morning',
          'filter_length' => 'day',
          'filter_date' => '1 11 16'
        ]
      ]
    );
  }
}