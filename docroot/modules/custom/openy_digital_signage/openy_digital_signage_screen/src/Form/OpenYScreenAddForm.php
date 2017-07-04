<?php

namespace Drupal\openy_digital_signage_screen\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Form controller for OpenY Digital Signage Screen add form.
 *
 * @ingroup openy_digital_signage_screen
 */
class OpenYScreenAddForm extends ContentEntityForm {

  /**
   * The private temp store.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $store;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityManagerInterface $entity_manager, PrivateTempStoreFactory $temp_store_factory, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->store = $temp_store_factory->get('multistep_data');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('user.private_tempstore'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve stored form step index.
    if (!$step = $this->store->get('screen_step')) {
      $step = 1;
    }
    // Retrieve store Screen entity.
    if ($entity = $this->store->get('screen_entity')) {
      $this->setEntity($entity);
    }

    $form = parent::buildForm($form, $form_state);
    switch ($step) {
      case 1:
        $form = $this->step1($form, $form_state);
        break;

      case 2:
        $form = $this->step2($form, $form_state);
        break;
    }

    return $form;
  }

  /**
   * Screen entity add form step 1 builder.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   Form array.
   */
  private function step1(array $form, FormStateInterface $form_state) {
    // Rename submit button and change submit handlers.
    $form['actions']['submit']['#value'] = $this->t('Next');
    $form['actions']['submit']['#submit'] = ['::step1NextSubmit'];

    // Hide Fallback content and schedule fields.
    $form['fallback_content']['#access'] = FALSE;
    $form['screen_schedule']['#access'] = FALSE;
    return $form;
  }

  /**
   * Step 1 Next button submit callback.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function step1NextSubmit(array $form, FormStateInterface $form_state) {
    // Build entity out of submitted values.
    $entity = parent::buildEntity($form, $form_state);

    if (!$entity->fallback_content->entity) {
      $id = $this->config('openy_digital_signage_screen.default_fallback_content')->get('target_id');
      $entity->set('fallback_content', $id);
    }
    // Store Screen entity and switch to step 2.
    $this->store->set('screen_entity', $entity);
    $this->store->set('screen_step', 2);
  }

  /**
   * Screen entity add form step 2 builder.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   Form array.
   */
  private function step2(array $form, FormStateInterface $form_state) {
    // Add 'Previous' button.
    $form['actions']['previous'] = [
      '#type' => 'submit',
      '#value' => $this->t('Previous'),
      '#weight' => 0,
      '#submit' => ['::step2PrevSubmit'],
      '#button_type' => 'primary',
    ];

    // Add submit handler for schedule processing.
    array_unshift($form['actions']['submit']['#submit'], '::processSchedule');

    // Hide everything but Fallback content, Schedule fields and action buttons.
    $element_keys = Element::children($form);
    foreach ($element_keys as $key) {
      if (in_array($key, ['screen_schedule', 'fallback_content', 'actions'])) {
        continue;
      }
      $form[$key]['#access'] = FALSE;
    }

    if (!$form_state->getValue('schedule') && $schedule = $this->store->get('schedule')) {
      $form_state->setValue('schedule', $schedule);
    }
    if (!$form_state->getValue('new_schedule') && $new_schedule = $this->store->get('new_schedule')) {
      $form_state->setValue('new_schedule', $new_schedule);
    }

    $entity = $this->store->get('screen_entity');
    // Add schedule elements.
    $form['schedule'] = [
      '#type' => 'radios',
      '#options' => [
        'new' => $this->t('Create new schedule'),
        'existing' => $this->t('Use existing'),
      ],
      '#title' => $this->t('Content schedule'),
      '#default_value' => $form_state->getValue('schedule') ?: 'new',
    ];

    $form['new_schedule'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('New schedule'),
      '#tree' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="schedule"]' => ['value' => 'new'],
        ],
      ],
      'title' => [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#default_value' => $form_state->getValue('new_schedule')['title'] ?: $entity->label() . ' schedule',
        '#states' => [
          'required' => [
            ':input[name="schedule"]' => ['value' => 'new'],
          ],
        ],
      ],
      'description' => [
        '#type' => 'textarea',
        '#title' => $this->t('Description'),
        '#default_value' => $form_state->getValue('new_schedule')['description'] ?: 'Schedule for screen "' . $entity->label() . '".',
        '#states' => [
          'required' => [
            ':input[name="schedule"]' => ['value' => 'new'],
          ],
        ],
      ],
    ];

    $form['screen_schedule']['#states'] = [
      'visible' => [
        ':input[name="schedule"]' => ['value' => 'existing'],
      ],
    ];
    $form['screen_schedule']['widget'][0]['target_id']['#states'] = [
      'required' => [
        ':input[name="schedule"]' => ['value' => 'existing'],
      ],
    ];

    return $form;
  }

  /**
   * Step 2 Previous button submit callback.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function step2PrevSubmit(array $form, FormStateInterface $form_state) {
    $entity = parent::buildEntity($form, $form_state);
    $this->store->set('screen_entity', $entity);
    $this->store->set('screen_step', 1);
    $this->store->set('schedule', $form_state->getValue('schedule'));
    $this->store->set('new_schedule', $form_state->getValue('new_schedule'));
  }

  /**
   * Step 2 save button submit callback.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function processSchedule(array $form, FormStateInterface $form_state) {
    if ($form_state->getValue('schedule') == 'existing') {
      return;
    }
    $new_schedule = $form_state->getValue('new_schedule');
    // Create new Schedule entity.
    $schedule = $this->entityManager
      ->getStorage('openy_digital_signage_schedule')
      ->create([
        'title' => $new_schedule['title'],
        'description' => $new_schedule['description'],
      ]);
    $schedule->save();
    $value = [['target_id' => $schedule->id()]];
    // Update form state value in order to point to the new schedule entity.
    $form_state->setValue('screen_schedule', $value);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $this->store->delete('screen_entity');
    $this->store->delete('screen_step');
    $this->store->delete('schedule');
    $this->store->delete('new_schedule');

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('OpenY Digital Signage Screen %label has been created.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('OpenY Digital Signage Screen %label has been saved.', [
          '%label' => $entity->label(),
        ]));
    }

    // Redirect to the new Schedule entity edit form.
    $form_state->setRedirect('entity.openy_digital_signage_screen.schedule', ['openy_digital_signage_screen' => $entity->id()]);
  }

}
