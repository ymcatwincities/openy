<?php

namespace Drupal\purge_ui\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\purge\Logger\LoggerServiceInterface;
use Drupal\purge_ui\Form\CloseDialogTrait;
use Drupal\purge_ui\Form\ReloadConfigFormCommand;

/**
 * Configure logging behavior.
 */
class LoggingConfigForm extends FormBase {
  use CloseDialogTrait;

  /**
   * @var \Drupal\purge\Logger\LoggerServiceInterface
   */
  protected $purgeLogger;

  /**
   * Constructs a LoggingConfigForm object.
   *
   * @param \Drupal\purge\Logger\LoggerServiceInterface $purge_logger
   *   Logging services for the purge module and its submodules.
   *
   * @return void
   */
  public function __construct(LoggerServiceInterface $purge_logger) {
    $this->purgeLogger = $purge_logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('purge.logger'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'purge_ui.logging_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['msg'] = [
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => $this->t("Purge and modules that integrate with it bundle all log messages into a single channel named <i><code>purge</code></i>.  This configuration form allows you to select what substreams and at which levels are allowed to log."),
    ];

    // Define the table header.
    $form['table'] = [
      '#type' => 'table',
      '#header' => ['id' => $this->t('Id')],
    ];
    foreach (RfcLogLevel::getLevels() as $level => $label) {
      $form['table']['#header']["$level"] = $label;
    }

    // Populate the rows and define checkboxes for each severity.
    foreach ($this->purgeLogger->getChannels() as $channel) {
      $form['table'][$channel['id']] = [];
      $form['table'][$channel['id']]['id'] = ['#markup' => $channel['id']];

      foreach (RfcLogLevel::getLevels() as $level => $label) {
        $form['table'][$channel['id']][$level] = [
          '#type' => 'checkbox',
          '#default_value' => in_array($level, $channel['grants']),
        ];
      }
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t("Save"),
      '#weight' => -10,
      '#button_type' => 'primary',
      '#ajax' => ['callback' => '::setChannels'],
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#button_type' => 'danger',
      '#ajax' => ['callback' => '::closeDialog'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function setChannels(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    if (self::submitForm($form, $form_state)) {
      $response->addCommand(new ReloadConfigFormCommand('edit-logging'));
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $has_resulted_in_changes = FALSE;
    if (is_array($values = $form_state->getValue('table'))) {
      foreach ($values as $id => $checkboxes) {
        if ($this->purgeLogger->hasChannel($id)) {
          $grants = [];
          foreach ($checkboxes as $severity => $checked) {
            if ($checked === "1") {
              $grants[] = $severity;
            }
          }
          $this->purgeLogger->setChannel($id, $grants);
          if (!$has_resulted_in_changes) {
            $has_resulted_in_changes = TRUE;
          }
        }
      }
    }
    return $has_resulted_in_changes;
  }

}
