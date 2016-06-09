<?php

namespace Drupal\ymca_entity_browser\Plugin\EntityBrowser\Display;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\embed\EmbedButtonInterface;
use Drupal\embed\Entity\EmbedButton;
use Drupal\entity_browser\DisplayRouterInterface;
use Drupal\entity_browser\Events\Events;
use Drupal\entity_browser\Events\RegisterJSCallbacks;
use Drupal\entity_browser\Events\AlterEntityBrowserDisplayData;
use Drupal\entity_browser\Plugin\EntityBrowser\Display\IFrame;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Presents entity browser in an iFrame.
 *
 * @EntityBrowserDisplay(
 *   id = "block_iframe",
 *   label = @Translation("iFrame (Block)"),
 *   description = @Translation("Displays entity browser (Block) in an iFrame."),
 *   uses_route = TRUE
 * )
 */
class BlockIFrame extends IFrame implements DisplayRouterInterface {

  /**
   * BlockIFrame constructor.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   * @param RouteMatchInterface $current_route_match
   *   RouteMatchInterface.
   * @param UuidInterface $uuid
   *   UUID.
   * @param Request $request
   *   Request.
   * @param CurrentPathStack $current_path
   *   CurrentPathStack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, RouteMatchInterface $current_route_match, UuidInterface $uuid, Request $request, CurrentPathStack $current_path) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $current_route_match, $uuid, $request, $current_path);
  }

  /**
   * {@inheritdoc}
   */
  public function displayEntityBrowser(FormStateInterface $form_state) {
    $uuid = $this->getUuid();

    $js_event_object = new RegisterJSCallbacks($this->configuration['entity_browser_id'], $uuid);
    $js_event_object->registerCallback('Drupal.entityBrowser.selectionCompleted');
    $callback_event = $this->eventDispatcher->dispatch(Events::REGISTER_JS_CALLBACKS, $js_event_object);

    $original_path = $this->currentPath->getPath();

    $data = [
      'query_parameters' => [
        'query' => [
          'uuid' => $uuid,
          'original_path' => $original_path,
          'types' => ['promo_block'],
        ],
      ],
      'attributes' => [
        'href' => '#browser',
        'class' => ['entity-browser-handle', 'entity-browser-iframe'],
        'data-uuid' => $uuid,
        'data-original-path' => $original_path,
      ],
    ];

    // Add list of available block types.
    $build_info = $form_state->getBuildInfo();
    if ($button = $build_info['args'][1]) {
      if ($button instanceof EmbedButtonInterface) {
        /** @var EmbedButton $button */
        $data['query_parameters']['query']['types'] = $button->getTypeSetting('bundles');
      }
    }

    $event_object = new AlterEntityBrowserDisplayData($this->configuration['entity_browser_id'], $uuid, $this->getPluginDefinition(), $form_state, $data);
    $event = $this->eventDispatcher->dispatch(Events::ALTER_BROWSER_DISPLAY_DATA, $event_object);
    $data = $event->getData();
    return [
      '#theme_wrappers' => ['container'],
      'link' => [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#value' => $this->configuration['link_text'],
        '#attributes' => $data['attributes'],
        '#attached' => [
          'library' => ['entity_browser/iframe'],
          'drupalSettings' => [
            'entity_browser' => [
              'iframe' => [
                $uuid => [
                  'src' => Url::fromRoute('entity_browser.' . $this->configuration['entity_browser_id'], [], $data['query_parameters'])
                    ->toString(),
                  'width' => $this->configuration['width'],
                  'height' => $this->configuration['height'],
                  'js_callbacks' => $callback_event->getCallbacks(),
                  'entity_browser_id' => $this->configuration['entity_browser_id'],
                  'auto_open' => $this->configuration['auto_open'],
                ],
              ],
            ],
          ]
        ],
      ],
    ];
  }

}
