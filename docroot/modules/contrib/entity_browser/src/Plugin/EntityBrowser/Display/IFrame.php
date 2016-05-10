<?php

/**
 * Contains \Drupal\entity_browser\Plugin\EntityBrowser\Display\IFrame.
 */

namespace Drupal\entity_browser\Plugin\EntityBrowser\Display;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\entity_browser\DisplayBase;
use Drupal\entity_browser\DisplayRouterInterface;
use Drupal\entity_browser\Events\Events;
use Drupal\entity_browser\Events\RegisterJSCallbacks;
use Drupal\entity_browser\Events\AlterEntityBrowserDisplayData;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Path\CurrentPathStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;

/**
 * Presents entity browser in an iFrame.
 *
 * @EntityBrowserDisplay(
 *   id = "iframe",
 *   label = @Translation("iFrame"),
 *   description = @Translation("Displays entity browser in an iFrame."),
 *   uses_route = TRUE
 * )
 */
class IFrame extends DisplayBase implements DisplayRouterInterface {

  /**
   * Current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * UUID generator interface.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidGenerator;

  /**
   * UIID string.
   *
   * @var string
   */
  protected $uuid = NULL;

  /**
   * Current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs display plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\Core\Routing\RouteMatchInterface
   *   The currently active route match object.
   * @param \Drupal\Component\Uuid\UuidInterface
   *   UUID generator interface.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, RouteMatchInterface $current_route_match, UuidInterface $uuid, Request $request, CurrentPathStack $current_path) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher);
    $this->currentRouteMatch = $current_route_match;
    $this->uuidGenerator = $uuid;
    $this->request = $request;
    $this->currentPath = $current_path;
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
      $container->get('current_route_match'),
      $container->get('uuid'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('path.current')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'width' => '650',
      'height' => '500',
      'link_text' => t('Select entities'),
      'auto_open' => FALSE,
    ) + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function displayEntityBrowser(FormStateInterface $form_state) {
    $uuid = $this->getUuid();
    /** @var \Drupal\entity_browser\Events\RegisterJSCallbacks $event */
    // TODO - $uuid is unused in this event but we need to pass it as
    // constructor expects it. See https://www.drupal.org/node/2600706 for more
    // info.
    $js_event_object = new RegisterJSCallbacks($this->configuration['entity_browser_id'], $uuid);
    $js_event_object->registerCallback('Drupal.entityBrowser.selectionCompleted');
    $callback_event = $this->eventDispatcher->dispatch(Events::REGISTER_JS_CALLBACKS, $js_event_object);
    $original_path = $this->currentPath->getPath();
    $data = [
      'query_parameters' => [
        'query' => [
          'uuid' => $uuid,
          'original_path' => $original_path,
        ],
      ],
      'attributes' => [
        'href' => '#browser',
        'class' => ['entity-browser-handle', 'entity-browser-iframe'],
        'data-uuid' => $uuid,
        'data-original-path' => $original_path,
      ],
    ];
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

  /**
   * {@inheritdoc}
   */
  public function selectionCompleted(array $entities) {
    $this->entities = $entities;
    $this->eventDispatcher->addListener(KernelEvents::RESPONSE, [$this, 'propagateSelection']);
  }

  /**
   * KernelEvents::RESPONSE listener. Intercepts default response and injects
   * response that will trigger JS to propagate selected entities upstream.
   *
   * @param FilterResponseEvent $event
   *   Response event.
   */
  public function propagateSelection(FilterResponseEvent $event) {
    $render = [
      'labels' => [
        '#markup' => 'Labels: ' . implode(', ', array_map(function (EntityInterface $item) {return $item->label();}, $this->entities)),
        '#attached' => [
          'library' => ['entity_browser/iframe_selection'],
          'drupalSettings' => [
            'entity_browser' => [
              'iframe' => [
                'entities' => array_map(function (EntityInterface $item) {return [$item->id(), $item->uuid(), $item->getEntityTypeId()];}, $this->entities),
                'uuid' => $this->request->query->get('uuid'),
              ],
            ],
          ],
        ],
      ],
    ];

    $event->setResponse(new Response(\Drupal::service('bare_html_page_renderer')->renderBarePage($render, 'Entity browser', 'page')));
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid() {
    if (empty($this->uuid)) {
      $this->uuid = $this->uuidGenerator->generate();
    }
    return $this->uuid;
  }

  /**
    * {@inheritdoc}
    */
  public function setUuid($uuid) {
    $this->uuid = $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function path() {
    return '/entity-browser/iframe/' . $this->configuration['entity_browser_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $form['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width of the iFrame'),
      '#min' => 1,
      '#default_value' => $configuration['width'],
    ];

    $form['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height of the iFrame'),
      '#min' => 1,
      '#default_value' => $configuration['height'],
    ];

    $form['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#default_value' => $configuration['link_text'],
    ];

    $form['auto_open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto open entity browser'),
      '#default_value' => $configuration['auto_open'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('width') <= 0) {
      $form_state->setError($form['width'], $this->t('Width must be greather than 0.'));
    }
    if ($form_state->getValue('height') <= 0) {
      $form_state->setError($form['height'], $this->t('Height must be greather than 0.'));
    }
  }

}
