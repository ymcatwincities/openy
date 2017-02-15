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
      'event' => 'change',
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
    switch ($trigger['#name']) {
      case 'type':
        $step = 2;
        break;

      case 'location':
        $step = 3;
        break;
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
        'active' => $step == 1 ? TRUE : FALSE,
      ],
      [
        'title' => $this->t('Primary Location'),
        'number' => '2',
        'active' => $step == 2 ? TRUE : FALSE,
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

    $form['type'] = [
      '#element_variables' => $types,
      '#subtype' => 'membership_type_radio',
      '#type' => 'calc_radios',
      '#title' => $this->t('Which option best describes the type of membership you need?'),
      '#options' => $types_options,
      '#ajax' => $this->getAjaxDefaults(),
    ];

    if ($step > 1) {
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
        '#ajax' => $this->getAjaxDefaults(),
      ];
    }

    if ($step > 2) {
      $summary = [
        '#theme' => 'openy_calc_form_summary',
        '#result' => $this->dataWrapper->getSummary($form_state->getValue('location'), $form_state->getValue('type')),
      ];
      $summary = $this->renderer->renderRoot($summary);
      $form['summary'] = [
        '#markup' => $summary,
      ];

      $form['select'] = [
        '#markup' => $this->t('Complete registration'),
        '#theme_wrappers' => [
          'container' => [
            '#attributes' => [
              'class' => [
                'btn',
                'btn-default',
              ],
            ],
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This form without submit.
  }

}
