<?php

namespace Drupal\ymca_entity_embed\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\entity_embed\Exception\EntityNotFoundException;
use Drupal\entity_embed\Exception\RecursiveRenderingException;
use Drupal\filter\FilterProcessResult;
use Drupal\entity_embed\Plugin\Filter\EntityEmbedFilter;

/**
 * Provides a filter to display embedded entities based on data attributes.
 *
 * @Filter(
 *   id = "ymca_entity_embed",
 *   title = @Translation("Display embedded entities (without wrappers)"),
 *   description = @Translation("Embeds entities using data attributes: data-entity-type, data-entity-uuid or data-entity-id, and data-view-mode."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class YMCAEntityEmbedFilter extends EntityEmbedFilter implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (strpos($text, 'data-entity-type') !== FALSE && (strpos($text, 'data-entity-embed-display') !== FALSE || strpos($text, 'data-view-mode') !== FALSE)) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);

      foreach ($xpath->query('//*[@data-entity-type and (@data-entity-uuid or @data-entity-id) and (@data-entity-embed-display or @data-view-mode)]') as $node) {
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
        catch (\Exception $e) {
          watchdog_exception('entity_embed', $e);
        }

        // Ensure this element is using <div> now if it was <drupal-entity>.
        if ($node->tagName == 'drupal-entity') {
          $this->changeNodeName($node, 'div');
        }
        $this->replaceNodeContent($node, $entity_output);
      }

      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

}
