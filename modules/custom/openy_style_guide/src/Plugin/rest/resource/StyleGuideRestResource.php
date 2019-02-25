<?php

namespace Drupal\openy_style_guide\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\Sql\Query;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "style_guide_rest_resource",
 *   label = @Translation("Open Y Style Guide resource"),
 *   uri_paths = {
 *     "canonical" = "/styleguide"
 *   }
 * )
 */
class StyleGuideRestResource extends ResourceBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\Query\Sql\Query
   */
  protected $queryFactory;

  /**
   * Constructs a new StyleGuideRestResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\Query\Sql\Query $queryFactory
   *   The query factory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    EntityTypeManagerInterface $entity_type_manager,
    Query $queryFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entityTypeManager = $entity_type_manager;
    $this->queryFactory = $queryFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('openy_style_guide'),
      $container->get('entity_type.manager'),
      $container->get('entity.query')->get('menu_link_content')
    );
  }

  /**
   * Responds to GET requests.
   *
   * @return ModifiedResourceResponse
   *   The HTTP response object.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function get() {
    $query = $this->queryFactory
      ->condition('menu_name', 'style-guide');
    $entity_ids = $query->execute();

    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $storageManager */
    $storageManager = $this->entityTypeManager->getStorage('menu_link_content');
    $storage = $this->entityTypeManager->getStorage('domain');
    $domains = $storage->loadMultiple();
    $items = [];

    /** @var  $domain */
    foreach ($domains as $domain) {
      $host = $domain->getPath();
      $site_name = $domain->get('name');
      foreach ($entity_ids as $id) {
        $entity = $storageManager->load($id);
        $value = $entity->get('link')->getValue();
        $url = $value[0]['uri'] != NULL ? Url::fromUri($value[0]['uri'])
          ->toString() : NULL;
        $items['domain'][$site_name][] = [
          'title' => $entity->get('title')->value,
          'link' => $host . $url,
        ];
      }
    }
    return new ModifiedResourceResponse($items);
  }
}
