<?php

/**
 * @file
 * Contains \Drupal\entity_embed\Plugin\Filter\EntityEmbedFilter.
 */

namespace Drupal\entity_embed\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\entity_embed\EntityHelperTrait;
use Drupal\entity_embed\Exception\EntityNotFoundException;
use Drupal\entity_embed\Exception\RecursiveRenderingException;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\embed\DomHelperTrait;

/**
 * Provides a filter to display embedded entities based on data attributes.
 *
 * @Filter(
 *   id = "entity_embed",
 *   title = @Translation("Display embedded entities"),
 *   description = @Translation("Embeds entities using data attributes: data-entity-type, data-entity-uuid or data-entity-id, and data-view-mode."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class EntityEmbedFilter extends FilterBase implements ContainerFactoryPluginInterface {
  use EntityHelperTrait;
  use DomHelperTrait;

  /**
   * Constructs a EntityEmbedFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The Module Handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setEntityManager($entity_manager);
    $this->setModuleHandler($module_handler);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (strpos($text, 'data-entity-type') !== FALSE && (strpos($text, 'data-entity-embed-display') !== FALSE || strpos($text, 'data-view-mode') !== FALSE)) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);

      foreach ($xpath->query('//drupal-entity[@data-entity-type and (@data-entity-uuid or @data-entity-id) and (@data-entity-embed-display or @data-view-mode)]') as $node) {
        /** @var \DOMElement $node */
        $entity_type = $node->getAttribute('data-entity-type');
        $entity = NULL;
        $entity_output = '';

        try {
          // Load the entity either by UUID (preferred) or ID.
          $id = $node->getAttribute('data-entity-uuid') ?: $node->getAttribute('data-entity-id');
          $entity = $this->loadEntity($entity_type, $id);

          if ($entity) {
            // Protect ourselves from recursive rendering.
            static $depth = 0;
            $depth++;
            if ($depth > 20) {
              throw new RecursiveRenderingException(sprintf('Recursive rendering detected when rendering embedded %s entity %s.', $entity_type, $entity->id()));
            }

            // If a UUID was not used, but is available, add it to the HTML.
            if (!$node->getAttribute('data-entity-uuid') && $uuid = $entity->uuid()) {
              $node->setAttribute('data-entity-uuid', $uuid);
            }

            $access = $entity->access('view', NULL, TRUE);
            $access_metadata = CacheableMetadata::createFromObject($access);
            $entity_metadata = CacheableMetadata::createFromObject($entity);
            $result = $result->merge($entity_metadata)->merge($access_metadata);

            $context = $this->getNodeAttributesAsArray($node);
            $context += array('data-langcode' => $langcode);
            $entity_output = $this->renderEntityEmbed($entity, $context);

            $depth--;
          }
          else {
            throw new EntityNotFoundException(sprintf('Unable to load embedded %s entity %s.', $entity_type, $id));
          }
        }
        catch(\Exception $e) {
          watchdog_exception('entity_embed', $e);
        }

        $this->replaceNodeContent($node, $entity_output);
      }

      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('
        <p>You can embed entities. Additional properties can be added to the embed tag like data-caption and data-align if supported. Examples:</p>
        <ul>
          <li>Embed by ID: <code>&lt;drupal-entity data-entity-type="node" data-entity-id="1" data-view-mode="teaser" /&gt;</code></li>
          <li>Embed by UUID: <code>&lt;drupal-entity data-entity-type="node" data-entity-uuid="07bf3a2e-1941-4a44-9b02-2d1d7a41ec0e" data-view-mode="teaser" /&gt;</code></li>
        </ul>');
    }
    else {
      return $this->t('You can embed entities.');
    }
  }

}
