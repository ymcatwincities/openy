<?php

namespace Drupal\ygh_programs_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\ygh_programs_search\DataStorageInterface;
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
   * @var \Drupal\ygh_programs_search\DataStorageInterface
   */
  protected $storage;

  /**
   * Enabled locations for the Form instance.
   *
   * @var array
   */
  private $locations;

  /**
   * ProgramsSearchBlockForm constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   * @param \Drupal\ygh_programs_search\DataStorageInterface $storage
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
      $container->get('ygh_programs_search.data_storage')
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
  public function buildForm(array $form, FormStateInterface $form_state, $locations = []) {
    // Put enabled locations to variable for future filtering.
    $this->locations = $locations;
    // Set step.
    $form_state->setValue('step', 1);
    if ($trigger = $form_state->getTriggeringElement()) {
      if ('type' == $trigger['#name']) {
        $form_state->setValue('step', 2);
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
          break;

        case 'school':
          $step = 4;
          break;

        case 'program':
          $step = 5;
          break;

        case 'rate':
          $step = 6;
          break;

      }
      $form_state->setValue('step', $step);
    }

    if ($form_state->getValue('step') >= 2) {
      $options = array_filter($this->storage->getLocations(), [$this, 'filterLocations'], ARRAY_FILTER_USE_BOTH);
      $form['location'] = [
        '#type' => 'radios',
        '#title' => $this->t('Location'),
        '#options' => $options,
        '#ajax' => $this->getAjaxDefaults(),
      ];
    }

    if ($form_state->getValue('step') >= 3) {
      $schools = $this->storage->getSchoolsByLocation($form_state->getValue('location'));

      $form['school'] = [
        '#type' => 'radios',
        '#title' => $this->t('School'),
        '#options' => $schools,
        '#ajax' => $this->getAjaxDefaults(),
      ];
    }

    if ($form_state->getValue('step') >= 4) {
      $programs = $this->storage->getChildCareProgramsBySchool($form_state->getValue('school'));

      $form['program'] = [
        '#type' => 'radios',
        '#title' => $this->t('Programs'),
        '#options' => $programs,
        '#ajax' => $this->getAjaxDefaults(),
      ];
    }

    if ($form_state->getValue('step') >= 5) {
      $rates_data = $this->storage->getChildCareProgramRateOptions($form_state->getValue('school'), $form_state->getValue('program'));
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
        '#markup' => $this->t('Congrats! Here is your program registration %link!', ['%link' => $link->toString()]),
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
          break;

        case 'program':
          $step = 4;
          break;

        case 'session':
          $step = 5;
          break;
      }
      $form_state->setValue('step', $step);
    }

    if ($form_state->getValue('step') >= 2) {
      $options = array_filter($this->storage->getLocations(), [$this, 'filterLocations'], ARRAY_FILTER_USE_BOTH);
      $form['location'] = [
        '#type' => 'radios',
        '#title' => $this->t('Location'),
        '#options' => $options,
        '#ajax' => $this->getAjaxDefaults(),
      ];
    }

    if ($form_state->getValue('step') >= 3) {
      $items = $this->storage->getProgramsByLocation($form_state->getValue('location'));
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

    if ($form_state->getValue('step') >= 4) {
      $items = $this->storage->getSessionsByProgramAndLocation($form_state->getValue('program'), $form_state->getValue('location'));
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

    if ($form_state->getValue('step') >= 5) {
      $uri = $this->storage->getRegistrationLink($form_state->getValue('program'), $form_state->getValue('session'));
      $url = Url::fromUri($uri);
      $link = Link::fromTextAndUrl($this->t('link'), $url);
      $form['link'] = [
        '#markup' => $this->t('Congrats! Here is your program registration %link!', ['%link' => $link->toString()]),
      ];
    }

    return $form;
  }

  /**
   * Custom ajax callback.
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
   */
  private function filterLocations($value, $key) {
    if (in_array($key, $this->locations)) {
      return $value;
    }
  }

}
