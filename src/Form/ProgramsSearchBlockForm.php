<?php

namespace Drupal\ygh_programs_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
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
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Check triggering element to get current step.
    if ($trigger_element = $form_state->getTriggeringElement()) {
      switch ($trigger_element['#name']) {
        case 'type':
          $values['step'] = 2;
          break;

        case 'location':
          $values['step'] = 3;
          break;

        case 'program':
          $values['step'] = 4;
          break;
      }
    }

    // Set default step value.
    if (!isset($values['step'])) {
      $values['step'] = 1;
    }

    // Set default #ajax properties.
    $ajax = [
      'callback' => [$this, 'rebuildAjaxCallback'],
      'wrapper' => 'programs-search-form-wrapper',
      'event' => 'change',
      'method' => 'replace',
      'effect' => 'fade',
      'progress' => ['type' => 'throbber'],
    ];

    $form['#prefix'] = '<div id="programs-search-form-wrapper" class="content step-' . $values['step'] . '">';
    $form['#suffix'] = '</div>';

    $form['step'] = [
      '#type' => 'hidden',
      '#value' => $values['step'],
    ];

    $form['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select type'),
      '#options' => [1 => 'Child Care', 2 => 'Adult'],
      '#ajax' => $ajax,
    ];

    if ($values['step'] >= 2) {
      // Stop here if user selected not Adult type.
      if ($values['type'] != 2) {
        $form['sorry'] = [
          '#markup' => $this->t('Sorry, this type is not supported yet.'),
        ];

        return $form;
      }

      $form['location'] = [
        '#type' => 'radios',
        '#title' => $this->t('Location'),
        '#options' => $this->storage->getLocations(),
        '#ajax' => $ajax,
      ];
    }

    if ($values['step'] >= 3) {
      $form['program'] = [
        '#type' => 'radios',
        '#title' => $this->t('Location'),
        '#options' => $this->storage->getProgramsByLocation($values['location']),
        '#ajax' => $ajax,
      ];
    }

    if ($values['step'] >= 4) {
      $link = $this->storage->getRegistrationLinkByProgram($values['program']);
      $form['sorry'] = [
        '#markup' => $this->t('Congrats! Here is your program registration %link!', ['%link' => $link]),
      ];
    }

//    $form['actions'] = ['#type' => 'actions'];
//
//    $form['actions']['search'] = [
//      '#type' => 'submit',
//      '#value' => $this->t('Search'),
//    ];

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

}
