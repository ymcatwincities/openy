<?php

namespace Drupal\openy_programs_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\openy_programs_search\DataStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a "programs_search_block_form" form.
 */
class ProgramsSearchBlockForm extends FormBase {

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Storage.
   *
   * @var \Drupal\openy_programs_search\DataStorageInterface
   */
  protected $storage;

  /**
   * Enabled locations for the Form instance.
   *
   * @var array
   */
  private $locations;

  /**
   * Enabled categories for the Form instance.
   *
   * @var array
   */
  private $categories;

  /**
   * ProgramsSearchBlockForm constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   * @param \Drupal\openy_programs_search\DataStorageInterface $storage
   *   Data storage.
   */
  public function __construct(RendererInterface $renderer, DataStorageInterface $storage) {
    $this->renderer = $renderer;
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('openy_programs_search.data_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'programs_search_block_form';
  }

  /**
   * Return ajax default properties.
   *
   * @return array
   *   List of properties.
   */
  private function getAjaxDefaults() {
    return [
      'callback' => [$this, 'rebuildAjaxCallback'],
      'wrapper' => 'programs-search-form-wrapper',
      'event' => 'change',
      'method' => 'replace',
      'effect' => 'fade',
      'progress' => ['type' => 'throbber'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $configuration = []) {
    // Put enabled locations and categories to variable for future filtering.
    $this->locations = $configuration['enabled_locations'];
    $this->categories = $configuration['enabled_categories'];

    // Set step.
    $form_state->setValue('step', 1);
    if ($trigger = $form_state->getTriggeringElement()) {
      if ('type' == $trigger['#name']) {
        $form_state->setValue('step', 2);
        $this->clearForm(['location', 'school', 'program', 'rate', 'session'], $form_state);
      }
    }

    $form['#prefix'] = '<div id="programs-search-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select type'),
      '#options' => [
        'child' => $this->t('Child Care'),
        'adult' => $this->t('Programs'),
      ],
      '#ajax' => $this->getAjaxDefaults(),
    ];

    // The form will have 2 separate branches depending on the type.
    switch ($form_state->getValue('type')) {
      case 'child':
        $form = $this->getChildForm($form, $form_state);
        break;

      case 'adult':
        $form = $this->getAdultForm($form, $form_state);
        break;
    }

    return $form;
  }

  /**
   * Returns $form for child care programs.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Array with form elements.
   */
  private function getChildForm(array $form, FormStateInterface $form_state) {
    // Get steps for the form.
    $step = $form_state->getValue('step');
    if ($trigger = $form_state->getTriggeringElement()) {
      switch ($trigger['#name']) {
        case 'location':
          $step = 3;
          $this->clearForm(['school', 'program', 'rate', 'session'], $form_state);
          break;

        case 'school':
          $step = 4;
          $this->clearForm(['program', 'rate', 'session'], $form_state);
          break;

        case 'program':
          $this->clearForm(['rate', 'session'], $form_state);
          $step = 5;
          break;

        case 'rate':
          $step = 6;
          break;

      }
      $form_state->setValue('step', $step);
    }

    // Locations.
    if ($form_state->getValue('step') >= 2) {
      $locations = $this->storage->getLocations();
      $options = array_filter($locations, [$this, 'filterLocations'], ARRAY_FILTER_USE_BOTH);
      if (empty($options)) {
        $options = $locations;
      }

      if (empty($options)) {
        return $this->noResults($form);
      }

      $form['location'] = [
        '#type' => 'radios',
        '#title' => $this->t('Location'),
        '#options' => $options,
        '#ajax' => $this->getAjaxDefaults(),
      ];
    }

    // Schools.
    if ($form_state->getValue('step') >= 3) {
      $schools = $this->storage->getSchoolsByLocation($form_state->getValue('location'));
      if (empty($schools)) {
        return $this->noResults($form);
      }

      $form['school'] = [
        '#type' => 'radios',
        '#title' => $this->t('School'),
        '#options' => $schools,
        '#ajax' => $this->getAjaxDefaults(),
      ];
    }

    // Programs.
    if ($form_state->getValue('step') >= 4) {
      $programs = $this->storage->getChildCareProgramsBySchool($form_state->getValue('school'));
      if (empty($programs)) {
        return $this->noResults($form);
      }

      $form['program'] = [
        '#type' => 'radios',
        '#title' => $this->t('Programs'),
        '#options' => $programs,
        '#ajax' => $this->getAjaxDefaults(),
      ];
    }

    // Rates.
    if ($form_state->getValue('step') >= 5) {
      $rates_data = $this->storage->getChildCareProgramRateOptions($form_state->getValue('school'), $form_state->getValue('program'));
      if (empty($rates_data)) {
        return $this->noResults($form);
      }

      $rates_options = [];
      foreach ($rates_data as $rate) {
        $rates_options[$rate['context_id']] = "$rate[name] ($rate[context_id])";
      }

      if (empty($rates_options)) {
        $form['rate'] = [
          '#markup' => $this->t('Sorry, all sessions for this program have been cancelled.'),
        ];

        return $form;
      }

      $form['rate'] = [
        '#type' => 'radios',
        '#title' => $this->t('Rate options'),
        '#options' => $rates_options,
        '#ajax' => $this->getAjaxDefaults(),
      ];
    }

    if ($form_state->getValue('step') >= 6) {
      $uri = $this->storage->getChildCareRegistrationLink(
        $form_state->getValue('school'),
        $form_state->getValue('program'),
        $form_state->getValue('rate')
      );

      $url = Url::fromUri($uri);
      $link = Link::fromTextAndUrl($this->t('link'), $url);

      $form['link'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => 'result',
        ],
        '#value' => $this->t('Congrats! Here is your program registration %link!', ['%link' => $link->toString()]),
      ];
    }

    return $form;
  }

  /**
   * Returns $form for adult programs.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Array with form elements.
   */
  private function getAdultForm(array $form, FormStateInterface $form_state) {
    // Get steps for the form.
    $step = $form_state->getValue('step');
    if ($trigger = $form_state->getTriggeringElement()) {
      switch ($trigger['#name']) {
        case 'location':
          $step = 3;
          $this->clearForm(['program', 'category', 'rate', 'session'], $form_state);
          break;

        case 'category':
          $this->clearForm(['program', 'rate', 'session'], $form_state);
          $step = 4;
          break;

        case 'program':
          $this->clearForm(['rate', 'session'], $form_state);
          $step = 5;
          break;

        case 'session':
          $step = 6;
          break;
      }
      $form_state->setValue('step', $step);
    }

    // Locations.
    if ($form_state->getValue('step') >= 2) {
      $locations = $this->storage->getLocations();
      $options = array_filter($locations, [$this, 'filterLocations'], ARRAY_FILTER_USE_BOTH);
      if (empty($options)) {
        // Use all locations if user hasn't provided any filters.
        $options = $locations;
      }

      if (empty($options)) {
        return $this->noResults($form);
      }

      $form['location'] = [
        '#type' => 'radios',
        '#title' => $this->t('Location'),
        '#options' => $options,
        '#ajax' => $this->getAjaxDefaults(),
      ];
    }

    // Categories.
    if ($form_state->getValue('step') >= 3) {
      $categories = $this->storage->getCategoriesByBranch($form_state->getValue('location'));
      $items = array_filter($categories, [$this, 'filterCategories'], ARRAY_FILTER_USE_BOTH);
      if (empty($items)) {
        $items = $categories;
      }

      if (empty($categories)) {
        return $this->noResults($form);
      }

      $categories = [];
      foreach ($items as $item_id => $item_title) {
        $categories[$item_id] = $item_title;
      }

      $form['category'] = [
        '#type' => 'radios',
        '#title' => $this->t('Category'),
        '#options' => $categories,
        '#ajax' => $this->getAjaxDefaults(),
      ];
    }

    // Programs.
    if ($form_state->getValue('step') >= 4) {
      $items = $this->storage->getProgramsByBranchAndCategory($form_state->getValue('location'), $form_state->getValue('category'));
      if (empty($items)) {
        return $this->noResults($form);
      }

      $programs = [];
      foreach ($items as $item) {
        $programs[$item->id] = $item->name;
      }

      $form['program'] = [
        '#type' => 'radios',
        '#title' => $this->t('Program'),
        '#options' => $programs,
        '#ajax' => $this->getAjaxDefaults(),
      ];
    }

    if ($form_state->getValue('step') >= 5) {
      $items = $this->storage->getSessionsByProgramAndLocation($form_state->getValue('program'), $form_state->getValue('location'));
      if (empty($items)) {
        return $this->noResults($form);
      }

      $sessions = [];
      foreach ($items as $item) {
        $sessions[$item->id] = $item->name;
      }

      $form['session'] = [
        '#type' => 'radios',
        '#title' => $this->t('Session'),
        '#options' => $sessions,
        '#ajax' => $this->getAjaxDefaults(),
      ];
    }

    if ($form_state->getValue('step') >= 6) {
      $uri = $this->storage->getRegistrationLink($form_state->getValue('program'), $form_state->getValue('session'));
      $url = Url::fromUri($uri);
      $link = Link::fromTextAndUrl($this->t('link'), $url);

      $form['link'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => 'result',
        ],
        '#value' => $this->t('Congrats! Here is your program registration %link!', ['%link' => $link->toString()]),
      ];
    }

    return $form;
  }

  /**
   * Custom ajax callback.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Form.
   */
  public function rebuildAjaxCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo Implement submit.
  }

  /**
   * Helper function to filter out disabled locations.
   *
   * @param string $value
   *   Location name.
   * @param int $key
   *   Location ID.
   *
   * @return mixed
   *   Location value.
   */
  private function filterLocations($value, $key) {
    if (!empty($this->locations) && in_array($key, $this->locations)) {
      return $value;
    }
    return FALSE;
  }

  /**
   * Helper function to filter out disabled categories.
   *
   * @param string $category
   *   Category title.
   *
   * @return mixed
   *   Category title.
   */
  private function filterCategories($category) {
    if (in_array($category, $this->categories)) {
      return $category;
    }
    return FALSE;
  }

  /**
   * Clear previously selected form values.
   *
   * @param array $values
   *   A list of values to clear.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  private function clearForm(array $values, FormStateInterface &$form_state) {
    $input = $form_state->getUserInput();

    foreach ($values as $value) {
      if ($form_state->hasValue($value)) {
        $form_state->unsetValue($value);
      }

      if (isset($input[$value])) {
        unset($input[$value]);
      }
    }

    $form_state->setUserInput($input);
  }

  /**
   * Return "Nothing found markup".
   *
   * @param array $form
   *   Form.
   *
   * @return array
   *   Nothing found markup.
   */
  private function noResults(array $form) {
    $form['no_results'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => 'no-result',
      ],
      '#value' => $this->t('Sorry, nothing has been found.'),
    ];

    return $form;
  }

}
