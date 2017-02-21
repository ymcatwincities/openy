<?php

namespace Drupal\openy_calc\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\openy_calc\DataWrapperInterface;
use Drupal\openy_socrates\OpenySocratesFacade;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a "calc_block_form" form.
 */
class CalcBlockForm extends FormBase {

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Data wrapper.
   *
   * @var \Drupal\openy_calc\DataWrapperInterface
   */
  protected $dataWrapper;

  /**
   * CalcBlockForm constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   * @param \Drupal\openy_socrates\OpenySocratesFacade $dataWrapper
   *   Socrates.
   */
  public function __construct(RendererInterface $renderer, OpenySocratesFacade $dataWrapper) {
    $this->renderer = $renderer;
    $this->dataWrapper = $dataWrapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('socrates')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'calc_block_form';
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
      'wrapper' => 'membership-calc-wrapper',
      'method' => 'replace',
      'effect' => 'fade',
      'progress' => ['type' => 'throbber'],
    ];
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $step = 1;
    $trigger = $form_state->getTriggeringElement();
    $storage = $form_state->getStorage();
    if ($trigger) {
      $step = (int) preg_replace('/\D/', '', $trigger['#name']);
    }

    $form['#prefix'] = '<div id="membership-calc-wrapper">';
    $form['#suffix'] = '</div>';

    $types = $this->dataWrapper->getMembershipTypes();
    $types_options = [];
    foreach ($types as $id => $type) {
      $types_options[$id] = $type['title'];
    }

    $steps = [
      [
        'title' => $this->t('Membership Type'),
        'number' => '1',
        'active' => $step >= 1 ? TRUE : FALSE,
      ],
      [
        'title' => $this->t('Primary Location'),
        'number' => '2',
        'active' => $step >= 2 ? TRUE : FALSE,
      ],
      [
        'title' => $this->t('Summary'),
        'number' => '3',
        'active' => $step == 3 ? TRUE : FALSE,
      ],
    ];
    $header = [
      '#theme' => 'openy_calc_form_header',
      '#steps' => $steps,
    ];
    $header = $this->renderer->renderRoot($header);
    $form['header'] = [
      '#markup' => $header,
    ];

    switch ($step) {
      case 1:
        // Membership type step.
        $form['type'] = [
          '#element_variables' => $types,
          '#subtype' => 'membership_type_radio',
          '#type' => 'calc_radios',
          '#title' => $this->t('Which option best describes the type of membership you need?'),
          '#options' => $types_options,
          '#default_value' => isset($storage['type']) ? $storage['type'] : NULL,
          '#required' => TRUE,
        ];
        break;

      case 2:
        // Select branch step.
        $form['map'] = [
          '#type' => 'openy_map',
          '#element_variables' => $this->dataWrapper->getBranchPins(),
        ];
        $locations = $this->dataWrapper->getLocations();
        $locations_options = [];
        foreach ($locations as $id => $location) {
          $locations_options[$id] = $location['title'];
        }
        $form['location'] = [
          '#type' => 'radios',
          '#title' => $this->t('Location'),
          '#options' => $locations_options,
          '#default_value' => isset($storage['location']) ? $storage['location'] : NULL,
          '#required' => TRUE,
        ];
        break;

      case 3:
        // Summary step.
        $form['summary'] = [
          '#theme' => 'openy_calc_form_summary',
          '#result' => $this->dataWrapper->getSummary($storage['location'], $storage['type']),
          '#map' => [
            '#type' => 'openy_map',
            '#element_variables' => $this->dataWrapper->getBranchPins($storage['location']),
          ],
        ];
        break;
    }

    if ($step > 1) {
      $form['actions']['prev'] = [
        '#type' => 'submit',
        '#value' => $this->t('Prev'),
        '#name' => 'step-' . ($step - 1),
        '#submit' => [[$this, 'navButtonSubmit']],
        '#ajax' => $this->getAjaxDefaults(),
        '#attributes' => [
          'class' => ['btn', 'blue', 'pull-left'],
        ],
      ];
    }

    if ($step < 3) {
      $form['actions']['next'] = [
        '#type' => 'submit',
        '#value' => $this->t('Next'),
        '#name' => 'step-' . ($step + 1),
        '#submit' => [[$this, 'navButtonSubmit']],
        '#ajax' => $this->getAjaxDefaults(),
        '#attributes' => [
          'class' => ['btn', 'blue', 'pull-right'],
        ],
      ];
    }
    else {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Complete registration'),
        '#attributes' => [
          'class' => [
            'btn',
            'btn-default',
            'blue',
            'complete-registration',
            'pull-right',
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * Navigation buttons submit callback.
   */
  public function navButtonSubmit(array &$form, FormStateInterface &$form_state) {
    $storage = $form_state->getStorage();
    // Save steps values to storage.
    if ($form_state->getValue('location')) {
      $storage['location'] = $form_state->getValue('location');
    }
    if ($form_state->getValue('type')) {
      $storage['type'] = $form_state->getValue('type');
    }
    $form_state->setStorage($storage);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Redirect to selected membership.
  }

}
