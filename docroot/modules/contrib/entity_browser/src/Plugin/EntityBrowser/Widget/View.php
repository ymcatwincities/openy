<?php

/**
 * Contains \Drupal\entity_browser\Plugin\EntityBrowser\Widget\View.
 */

namespace Drupal\entity_browser\Plugin\EntityBrowser\Widget;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\entity_browser\WidgetBase;
use Drupal\Core\Url;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Uses a view to provide entity listing in a browser's widget.
 *
 * @EntityBrowserWidget(
 *   id = "view",
 *   label = @Translation("View"),
 *   provider = "views",
 *   description = @Translation("Uses a view to provide entity listing in a browser's widget.")
 * )
 */
class View extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'view' => NULL,
      'view_display' => NULL,
    ) + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity.manager'),
      $container->get('current_user')
    );
  }

  /**
   * Constructs a new View object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
   public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityManagerInterface $entity_manager, AccountInterface $current_user) {
     parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_manager);
     $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $aditional_widget_parameters) {
    $form = [];
    // TODO - do we need better error handling for view and view_display (in case
    // either of those is nonexistent or display not of correct type)?

    $form['#attached']['library'] = ['entity_browser/view'];

    /** @var \Drupal\views\ViewExecutable $view */
    $view = $this->entityManager
      ->getStorage('view')
      ->load($this->configuration['view'])
      ->getExecutable();

    // Check if the current user has access to this view.
    if (!$view->access($this->configuration['view_display'])) {
      return [
        '#markup' => t('You do not have access to this View.'),
      ];
    }

    if (!empty($this->configuration['arguments'])) {
      if (!empty($aditional_widget_parameters['path_parts'])) {
        $arguments = [];
        // Map configuration arguments with original path parts.
        foreach ($this->configuration['arguments'] as $argument) {
          $arguments[] = isset($aditional_widget_parameters['path_parts'][$argument]) ? $aditional_widget_parameters['path_parts'][$argument] : '';
        }
        $view->setArguments(array_values($arguments));
      }
    }

    $form['view'] = $view->executeDisplay($this->configuration['view_display']);

    if (empty($view->field['entity_browser_select'])) {
      $url = Url::fromRoute('entity.view.edit_form',['view'=>$this->configuration['view']])->toString();
      if ($this->currentUser->hasPermission('administer views')) {
        return [
          '#markup' => t('Entity browser select form field not found on a view. <a href=":link">Go fix it</a>!', [':link' => $url]),
        ];
      }
      else {
        return [
          '#markup' => t('Entity browser select form field not found on a view. Go fix it!'),
        ];
      }
    }

    // When rebuilding makes no sense to keep checkboxes that were previously
    // selected.
    if (!empty($form['view']['entity_browser_select']) && $form_state->isRebuilding()) {
      foreach (Element::children($form['view']['entity_browser_select']) as $child) {
        $form['view']['entity_browser_select'][$child]['#process'][] = ['\Drupal\entity_browser\Plugin\EntityBrowser\Widget\View', 'processCheckbox'];
        $form['view']['entity_browser_select'][$child]['#process'][] = ['\Drupal\Core\Render\Element\Checkbox', 'processAjaxForm'];
        $form['view']['entity_browser_select'][$child]['#process'][] = ['\Drupal\Core\Render\Element\Checkbox', 'processGroup'];
      }
    }

    $form['view']['view'] = [
      '#markup' => \Drupal::service('renderer')->render($form['view']['view']),
    ];

    return $form;
  }

  /**
   * Sets the #checked property when rebuilding form.
   *
   * Every time when we rebuild we want all checkboxes to be unchecked.
   *
   * @see \Drupal\Core\Render\Element\Checkbox::processCheckbox()
   */
  public static function processCheckbox(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#checked'] = FALSE;
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    if (isset($user_input['entity_browser_select'])) {
      $selected_rows = array_values(array_filter($user_input['entity_browser_select']));
      foreach ($selected_rows as $row) {
        // Verify that the user input is a string and split it.
        // Each $row is in the format entity_type:id.
        if (is_string($row) && $parts = explode(':', $row, 2)) {
          // Make sure we have a type and id present.
          if (count($parts) == 2) {
            try {
              $storage = $this->entityManager->getStorage($parts[0]);
              if (!$storage->load($parts[1])) {
                $message = t('The @type Entity @id does not exist.', [
                  '@type' => $parts[0],
                  '@id' => $parts[1]
                ]);
                $form_state->setError($form['widget']['view']['entity_browser_select'], $message);
              }
            }
            catch (PluginNotFoundException $e) {
              $message = t('The Entity Type @type does not exist.', [
                '@type' => $parts[0],
              ]);
              $form_state->setError($form['widget']['view']['entity_browser_select'], $message);
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $selected_rows = array_values(array_filter($form_state->getUserInput()['entity_browser_select']));
    $entities = [];
    foreach ($selected_rows as $row) {
      list($type, $id) = explode(':', $row);
      $storage = $this->entityManager->getStorage($type);
      if ($entity = $storage->load($id)) {
        $entities[] = $entity;
      }
    }

    $this->selectEntities($entities, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];
    // Get only those enabled Views that have entity_browser displays.
    $displays = Views::getApplicableViews('entity_browser_display');
    foreach ($displays as $display) {
      list($view_id, $display_id) = $display;
      $view = $this->entityManager->getStorage('view')->load($view_id);
      $options[$view_id . '.' . $display_id] = $this->t('@view : @display', array('@view' => $view->label(), '@display' => $view->get('display')[$display_id]['display_title']));
    }

    $form['view'] = [
      '#type' => 'select',
      '#title' => $this->t('View : View display'),
      '#default_value' => $this->configuration['view'] . '.' . $this->configuration['view_display'],
      '#options' => $options,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues()['table'][$this->uuid()]['form'];

    if (!empty($values['view'])) {
      list($view_id, $display_id) = explode('.', $values['view']);
      $this->configuration['view'] = $view_id;
      $this->configuration['view_display'] = $display_id;
    }
  }


}
