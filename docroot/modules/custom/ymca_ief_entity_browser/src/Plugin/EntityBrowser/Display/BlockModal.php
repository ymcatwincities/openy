<?php

namespace Drupal\ymca_ief_entity_browser\Plugin\EntityBrowser\Display;

use Drupal\Component\Utility\Html;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\entity_browser\DisplayRouterInterface;
use Drupal\entity_browser\Events\Events;
use Drupal\entity_browser\Events\RegisterJSCallbacks;
use Drupal\entity_browser\Plugin\EntityBrowser\Display\Modal;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\entity_browser\Events\AlterEntityBrowserDisplayData;

/**
 * Presents entity browser in an Modal.
 *
 * @EntityBrowserDisplay(
 *   id = "block_modal",
 *   label = @Translation("Block Modal"),
 *   description = @Translation("Displays entity browser (custom block) in a Modal."),
 *   uses_route = TRUE
 * )
 */
class BlockModal extends Modal implements DisplayRouterInterface {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, RouteMatchInterface $current_route_match, UuidInterface $uuid, CurrentPathStack $current_path, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $current_route_match, $uuid, $current_path, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function displayEntityBrowser(FormStateInterface $form_state) {
    $uuid = $this->getUuid();
    /** @var \Drupal\entity_browser\Events\RegisterJSCallbacks $event */
    $js_event_object = new RegisterJSCallbacks($this->configuration['entity_browser_id'], $uuid);
    $js_event_object->registerCallback('Drupal.entityBrowser.selectionCompleted');
    $js_event = $this->eventDispatcher->dispatch(Events::REGISTER_JS_CALLBACKS, $js_event_object );

    $original_path = $this->currentPath->getPath();

    $field_name = $form_state->getTriggeringElement()['#parents'][0];
    $form = $form_state->getCompleteForm();
    $options = array_keys($form[$field_name]['widget']['actions']['bundle']['#options']);

    $data = [
      'query_parameters' => [
        'query' => [
          'uuid' => $uuid,
          'original_path' => $original_path,
          'types' => $options,
        ],
      ],
      'attributes' => [
        'data-uuid' => $uuid,
      ],
    ];
    $event_object = new AlterEntityBrowserDisplayData($this->configuration['entity_browser_id'], $uuid, $this->getPluginDefinition(), $form_state, $data);
    $event = $this->eventDispatcher->dispatch(Events::ALTER_BROWSER_DISPLAY_DATA, $event_object);
    $data = $event->getData();

    return [
      '#theme_wrappers' => ['container'],
      'path' => [
        '#type' => 'hidden',
        '#value' => Url::fromRoute('entity_browser.' . $this->configuration['entity_browser_id'], [], $data['query_parameters'])->toString(),
      ],
      'open_modal' => [
        '#type' => 'submit',
        '#value' => $this->configuration['link_text'],
        '#limit_validation_errors' => [],
        '#submit' => [],
        '#name' => Html::getId('op_' . $this->configuration['entity_browser_id'] . '_' . $uuid),
        '#ajax' => [
          'callback' => [$this, 'openModal'],
          'event' => 'click',
        ],
        '#attributes' => $data['attributes'],
        '#attached' => [
          'library' => ['core/drupal.dialog.ajax',  'entity_browser/modal'],
          'drupalSettings' => [
            'entity_browser' => [
              'modal' => [
                $uuid => [
                  'uuid' => $uuid,
                  'js_callbacks' => $js_event->getCallbacks(),
                  'original_path' => $original_path,
                ],
              ],
            ],
          ],
        ],
      ],
    ];
  }

}
