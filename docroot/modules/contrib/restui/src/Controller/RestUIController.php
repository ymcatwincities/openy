<?php

namespace Drupal\restui\Controller;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;

/**
 * Controller routines for REST resources.
 */
class RestUIController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Resource plugin manager.
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager
   */
  protected $resourcePluginManager;

  /**
   * The URL generator to use.
   *
   * @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Configuration entity to store enabled REST resources.
   *
   * @var \Drupal\rest\RestResourceConfigInterface
   */
  protected $resourceConfigStorage;

  /**
   * Injects RestUIManager Service.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.rest'),
      $container->get('url_generator'),
      $container->get('entity_type.manager')->getStorage('rest_resource_config')
    );
  }

  /**
   * Constructs a RestUIController object.
   *
   * @param \Drupal\rest\Plugin\Type\ResourcePluginManager $resourcePluginManager
   *   The REST resource plugin manager.
   * @param \Symfony\Component\Routing\Generator\UrlGeneratorInterface $url_generator
   *   The URL generator.
   * @param \Drupal\Core\Entity\EntityStorageInterface $resource_config_storage
   *   The REST resource config storage.
   */
  public function __construct(ResourcePluginManager $resourcePluginManager, UrlGeneratorInterface $url_generator, EntityStorageInterface $resource_config_storage) {
    $this->resourcePluginManager = $resourcePluginManager;
    $this->urlGenerator = $url_generator;
    $this->resourceConfigStorage = $resource_config_storage;
  }

  /**
   * Returns an administrative overview of all REST resources.
   *
   * @return string
   *   A HTML-formatted string with the administrative page content.
   */
  public function listResources() {
    // Get the list of enabled and disabled resources.
    $config = $this->resourceConfigStorage->loadMultiple();

    // Strip out the nested method configuration, we are only interested in the
    // plugin IDs of the resources.
    $enabled_resources = array_combine(array_keys($config), array_keys($config));
    $available_resources = ['enabled' => [], 'disabled' => []];
    $resources = $this->resourcePluginManager->getDefinitions();
    foreach ($resources as $id => $resource) {
      $key = $this->getResourceKey($id);
      $status = (in_array($key, $enabled_resources) && $config[$key]->status()) ? 'enabled' : 'disabled';
      $available_resources[$status][$id] = $resource;
    }

    // Sort the list of resources by label.
    $sort_resources = function ($resource_a, $resource_b) {
      return strcmp($resource_a['label'], $resource_b['label']);
    };
    if (!empty($available_resources['enabled'])) {
      uasort($available_resources['enabled'], $sort_resources);
    }
    if (!empty($available_resources['disabled'])) {
      uasort($available_resources['disabled'], $sort_resources);
    }

    // Heading.
    $list['resources_title'] = [
      '#markup' => '<h2>' . $this->t('REST resources') . '</h2>',
    ];
    $list['resources_help'] = [
      '#markup' => '<p>' . $this->t('Here you can enable and disable available resources. Once a resource has been enabled, you can restrict its formats and authentication by clicking on its "Edit" link.') . '</p>',
    ];
    $list['enabled']['heading']['#markup'] = '<h2>' . $this->t('Enabled') . '</h2>';
    $list['disabled']['heading']['#markup'] = '<h2>' . $this->t('Disabled') . '</h2>';

    // List of resources.
    foreach (['enabled', 'disabled'] as $status) {
      $list[$status]['#type'] = 'container';
      $list[$status]['#attributes'] = ['class' => ['rest-ui-list-section', $status]];
      $list[$status]['table'] = [
        '#theme' => 'table',
        '#header' => [
          'resource_name' => [
            'data' => $this->t('Resource name'),
            'class' => ['rest-ui-name']
          ],
          'path' => [
            'data' => $this->t('Path'),
            'class' => ['views-ui-path'],
          ],
          'description' => [
            'data' => $this->t('Description'),
            'class' => ['rest-ui-description'],
          ],
          'operations' => [
            'data' => $this->t('Operations'),
            'class' => ['rest-ui-operations'],
          ],
        ],
        '#rows' => [],
      ];
      foreach ($available_resources[$status] as $id => $resource) {
        $canonical_uri_path = !empty($resource['uri_paths']['canonical'])
          ? $resource['uri_paths']['canonical']
          : FALSE;

        // @see https://www.drupal.org/node/2737401
        // @todo Remove this in Drupal 9.0.0.
        $old_create_uri_path = !empty($resource['uri_paths']['https://www.drupal.org/link-relations/create'])
          ? $resource['uri_paths']['https://www.drupal.org/link-relations/create']
          : FALSE;
        $new_create_uri_path = !empty($resource['uri_paths']['create'])
          ? $resource['uri_paths']['create']
          : FALSE;
        $create_uri_path = $new_create_uri_path ?: $old_create_uri_path;

        $available_methods = array_intersect(array_map('strtoupper', get_class_methods($resource['class'])), [
          'HEAD',
          'GET',
          'POST',
          'PUT',
          'DELETE',
          'TRACE',
          'OPTIONS',
          'CONNECT',
          'PATCH',
        ]);

        // @todo Remove this when https://www.drupal.org/node/2300677 is fixed.
        $is_config_entity = isset($resource['serialization_class']) && is_subclass_of($resource['serialization_class'], \Drupal\Core\Config\Entity\ConfigEntityInterface::class, TRUE);
        if ($is_config_entity) {
          $available_methods = array_diff($available_methods, ['POST', 'PATCH', 'DELETE']);
          $create_uri_path = FALSE;
        }

        // Now calculate the configured methods: if a RestResourceConfig entity
        // exists for this @RestResource plugin, then regardless of whether that
        // configuration is enabled or not, inspect its enabled methods. Strike
        // through all disabled methods, so that it's clearly conveyed in the UI
        // which methods are supported on which URL, but may be disabled.
        if (isset($config[$this->getResourceKey($id)])) {
          $enabled_methods = $config[$this->getResourceKey($id)]->getMethods();
          $disabled_methods = array_diff($available_methods, $enabled_methods);
          $configured_methods = array_merge(
            array_intersect($available_methods, $enabled_methods),
            array_map(function ($method) { return "<del>$method</del>"; }, $disabled_methods)
          );
        }
        else {
          $configured_methods = $available_methods;
        }

        // All necessary information is collected, now generate some HTML.
        $canonical_methods = implode(', ', array_diff($configured_methods, ['POST']));
        if ($canonical_uri_path && $create_uri_path) {
          $uri_paths = "<code>$canonical_uri_path</code>: $canonical_methods";
          $uri_paths.= "</br><code>$create_uri_path</code>: POST";
        }
        else {
          if ($canonical_uri_path) {
            $uri_paths = "<code>$canonical_uri_path</code>: $canonical_methods";
          }
          else {
            $uri_paths = "<code>$create_uri_path</code>: POST";
          }
        }

        $list[$status]['table']['#rows'][$id] = [
          'data' => [
            'name' => !$is_config_entity ? $resource['label'] : $this->t('@label <sup>(read-only)</sup>', ['@label' => $resource['label']]),
            'path' => [
              'data' => [
                '#type' => 'inline_template',
                '#template' => $uri_paths,
              ],
            ],
            'description' => [],
            'operations' => [],
          ],
        ];

        if ($status == 'disabled') {
          $list[$status]['table']['#rows'][$id]['data']['operations']['data'] = [
            '#type' => 'operations',
            '#links' => [
              'enable' => [
                'title' => $this->t('Enable'),
                'url' => Url::fromRoute('restui.edit', ['resource_id' => $id]),
              ],
            ],
          ];
        }
        else {
          $list[$status]['table']['#rows'][$id]['data']['operations']['data'] = [
            '#type' => 'operations',
            '#links' => [
              'edit' => [
                'title' => $this->t('Edit'),
                'url' => Url::fromRoute('restui.edit', ['resource_id' => $id]),

              ],
              'disable' => [
                'title' => $this->t('Disable'),
                'url' => Url::fromRoute('restui.disable', ['resource_id' => $id]),
              ],
              'permissions' => [
                'title' => $this->t('Permissions'),
                'url' => Url::fromRoute('user.admin_permissions', [], ['fragment' => 'module-rest']),
              ],
            ],
          ];

          $list[$status]['table']['#rows'][$id]['data']['description']['data'] = [
            '#theme' => 'restui_resource_info',
            '#granularity' => $config[$this->getResourceKey($id)]->get('granularity'),
            '#configuration' => $config[$this->getResourceKey($id)]->get('configuration'),
          ];
        }
      }
    }

    $list['enabled']['table']['#empty'] = $this->t('There are no enabled resources.');
    $list['disabled']['table']['#empty'] = $this->t('There are no disabled resources.');
    $list['#title'] = $this->t('REST resources');
    return $list;
  }

  /**
   * Disables a resource.
   *
   * @param string $resource_id
   *   The identifier or the REST resource.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects back to the listing page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Access is denied, if the token is invalid or missing.
   */
  public function disable($resource_id) {
    $resources = $this->resourceConfigStorage->loadMultiple();

    if ($resources[$this->getResourceKey($resource_id)]) {
      $resources[$this->getResourceKey($resource_id)]->disable()->save();
      drupal_set_message(t('The resource was disabled successfully.'));
    }

    // Redirect back to the page.
    return new RedirectResponse($this->urlGenerator->generate('restui.list', [], TRUE));
  }

  /**
   * The key used in the form.
   *
   * @param string $resource_id
   *   The resource ID.
   *
   * @return string
   *   The resource key in the form.
   */
  protected function getResourceKey($resource_id) {
    return str_replace(':', '.', $resource_id);
  }

}
