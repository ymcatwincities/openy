<?php
/**
 * @file
 * Contains \Drupal\ymca_groupex\Form\FindClassesForm
 */

namespace Drupal\ymca_groupex\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements a FindClassesForm.
 */
class FindClassesForm extends FormBase {

  /**
   * Uri to make requests.
   */
  const URI = 'http://api.groupexpro.com/schedule/embed';

  /**
   * Account ID.
   */
  const ACCOUNT = 3;

  /**
   * Make a request to GroupEx.
   *
   * @param $options
   *   Request options.
   *
   * @return array
   *   Data.
   */
  private function request($options) {
    $client = \Drupal::httpClient();
    $data = [];
    $options_defaults = [
      'query' => [
        'a' => self::ACCOUNT,
      ],
    ];

    try {
      $response = $client->request('GET', self::URI, array_merge_recursive($options_defaults, $options));
      $body = $response->getBody();
      $data = json_decode($body->getContents());
    }
    catch(\Exception $e) {
      watchdog_exception('ymca_groupex', $e);
    }

    return $data;
  }

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
      '#markup' => $this->t('(Please select a location, class or category)'),
    ];

    $form['locations'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getOptions($this->request(['query' => ['locations' => TRUE]]), 'id', 'name'),
      '#title' => t('Locations'),
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
      '#type' => 'select',
      '#options' => [
        'morning' => $this->t('Morning (6 a.m. - 12 p.m.'),
        'afternoon' => $this->t('Afternoon (12 p.m. - 5 p.m.'),
        'evening' => $this->t('Evening (5 p.m. - 10 p.m.'),
      ],
      '#title' => $this->t('Time of Day (optional)'),
    ];

    $form['view'] = [
      '#type' => 'select',
      '#options' => [
        'day' => $this->t('Day'),
        'week' => $this->t('Week'),
      ],
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('Yahoo!!!'));
  }

}