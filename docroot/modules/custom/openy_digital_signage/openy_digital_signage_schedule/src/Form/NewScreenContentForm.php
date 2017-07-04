<?php

namespace Drupal\openy_digital_signage_schedule\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * NewScreenContentForm class.
 */
class NewScreenContentForm extends FormBase {

  /**
   * The create Node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {
    $form['title'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Title'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create'),
      '#ajax' => [
        'callback' => '::ajaxSubmitHandler',
      ],
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#attributes' => [
        'class' => [
          'primary-button',
        ],
      ],
      '#ajax' => [
        'callback' => '::ajaxSubmitHandler',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmitHandler(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    // Add an AJAX command to close a modal dialog with the form as the content.
    $response->addCommand(new CloseModalDialogCommand());

    // Add an AJAX command in order to update entity reference field.
    $value = $this->node->label() . ' (' . $this->node->id() . ')';
    $response->addCommand(new InvokeCommand('[data-drupal-selector="edit-content-0-target-id"]', 'val', [$value]));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $title = $form_state->getValue('title');
    $this->node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->create([
        'title' => $title,
        'type' => 'screen_content',
      ]);
    $this->node->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'new_screen_content_form';
  }

}
