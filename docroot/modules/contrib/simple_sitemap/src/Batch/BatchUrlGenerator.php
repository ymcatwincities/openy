<?php

namespace Drupal\simple_sitemap\Batch;

use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_sitemap\Logger;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\simple_sitemap\SitemapGenerator;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathValidator;
use Drupal\Core\Entity\Query\QueryFactory;

/**
 * Class BatchUrlGenerator.
 *
 * @package Drupal\simple_sitemap\Batch
 */
class BatchUrlGenerator {

  use StringTranslationTrait;

  const ANONYMOUS_USER_ID = 0;
  const PATH_DOES_NOT_EXIST_OR_NO_ACCESS_MESSAGE = "The custom path @path has been omitted from the XML sitemap as it either does not exist, or it is not accessible to anonymous users. You can review custom paths <a href='@custom_paths_url'>here</a>.";
  const PROCESSING_PATH_MESSAGE = 'Processing path #@current out of @max: @path';
  const REGENERATION_FINISHED_MESSAGE = "The <a href='@url' target='_blank'>XML sitemap</a> has been regenerated for all languages.";
  const REGENERATION_FINISHED_ERROR_MESSAGE = 'The sitemap generation finished with an error.';

  protected $generator;
  protected $sitemapGenerator;
  protected $languageManager;
  protected $languages;
  protected $defaultLanguageId;
  protected $entityTypeManager;
  protected $pathValidator;
  protected $entityQuery;
  protected $logger;
  protected $anonUser;

  protected $context;
  protected $batchInfo;

  /**
   * BatchUrlGenerator constructor.
   *
   * @param $generator
   * @param $sitemap_generator
   * @param $language_manager
   * @param $entity_type_manager
   * @param $path_validator
   * @param $entity_query
   * @param $logger
   */
  public function __construct(
    Simplesitemap $generator,
    SitemapGenerator $sitemap_generator, //todo: use $this->generator->sitemapGenerator instead?
    LanguageManagerInterface $language_manager,
    EntityTypeManagerInterface $entity_type_manager,
    PathValidator $path_validator,
    QueryFactory $entity_query,
    Logger $logger
  ) {
    $this->generator = $generator;
    // todo: using only one method, maybe make method static instead?
    $this->sitemapGenerator = $sitemap_generator;
    $this->languageManager = $language_manager;
    $this->languages = $language_manager->getLanguages();
    $this->defaultLanguageId = $language_manager->getDefaultLanguage()->getId();
    $this->entityTypeManager = $entity_type_manager;
    $this->pathValidator = $path_validator;
    $this->entityQuery = $entity_query;
    $this->logger = $logger;
    $this->anonUser = $this->entityTypeManager->getStorage('user')->load(self::ANONYMOUS_USER_ID);
  }

  /**
   * @param $context
   * @return $this
   */
  public function setContext(&$context) {
    $this->context = &$context;
    return $this;
  }

  /**
   * @param $batch_info
   * @return $this
   */
  public function setBatchInfo($batch_info) {
    $this->batchInfo = $batch_info;
    return $this;
  }

  /**
   * Batch callback function which generates urls to entity paths.
   *
   * @param array $entity_info
   */
  public function generateBundleUrls($entity_info) {

    foreach ($this->getBatchIterationEntities($entity_info) as $entity_id => $entity) {

      $this->setCurrentId($entity_id);

      $entity_settings = $this->generator->getEntityInstanceSettings($entity_info['entity_type_name'], $entity_id);

      if (empty($entity_settings['index'])) {
        continue;
      }

      switch ($entity_info['entity_type_name']) {
        // Loading url object for menu links.
        case 'menu_link_content':
          if (!$entity->isEnabled()) {
            continue 2;
          }
          $url_object = $entity->getUrlObject();
          break;

        // Loading url object for other entities.
        default:
          $url_object = $entity->toUrl();
      }

      // Do not include external paths.
      if (!$url_object->isRouted()) {
        continue;
      }

      $path = $url_object->getInternalPath();

      // Do not include paths that have been already indexed.
      if ($this->batchInfo['remove_duplicates'] && $this->pathProcessed($path)) {
        continue;
      }

      $url_object->setOption('absolute', TRUE);

      $path_data = [
        'path' => $path,
        'entity_info' => ['entity_type' => $entity_info['entity_type_name'], 'id' => $entity_id],
        'lastmod' => method_exists($entity, 'getChangedTime') ? date_iso8601($entity->getChangedTime()) : NULL,
        'priority' => $entity_settings['priority'],
      ];
      $this->addUrlVariants($url_object, $path_data, $entity);
    }
    $this->processSegment();
  }

  /**
   * Batch function which generates urls to custom paths.
   *
   * @param array $custom_paths
   */
  public function generateCustomUrls($custom_paths) {

    $custom_paths = $this->getBatchIterationCustomPaths($custom_paths);

    if ($this->needsInitialization()) {
      $this->initializeBatch(count($custom_paths));
    }

    foreach ($custom_paths as $i => $custom_path) {
      $this->setCurrentId($i);

      // todo: Change to different function, as this also checks if current user has access. The user however varies depending if process was started from the web interface or via cron/drush. Use getUrlIfValidWithoutAccessCheck()?
      if (!$this->pathValidator->isValid($custom_path['path'])) {
//        if (!(bool) $this->pathValidator->getUrlIfValidWithoutAccessCheck($custom_path['path'])) {
        $this->logger->m(self::PATH_DOES_NOT_EXIST_OR_NO_ACCESS_MESSAGE,
          ['@path' => $custom_path['path'], '@custom_paths_url' => $GLOBALS['base_url'] . '/admin/config/search/simplesitemap/custom'])
          ->display('warning', 'administer sitemap settings')
          ->log('warning');
        continue;
      }
      $url_object = Url::fromUserInput($custom_path['path'], ['absolute' => TRUE]);

      $path = $url_object->getInternalPath();
      if ($this->batchInfo['remove_duplicates'] && $this->pathProcessed($path)) {
        continue;
      }

      $entity = $this->getEntityFromUrlObject($url_object);

      $path_data = [
        'path' => $path,
        'lastmod' => method_exists($entity, 'getChangedTime') ? date_iso8601($entity->getChangedTime()) : NULL,
        'priority' => isset($custom_path['priority']) ? $custom_path['priority'] : NULL,
      ];
      if (!is_null($entity)) {
        $path_data['entity_info'] = ['entity_type' => $entity->getEntityTypeId(), 'id' => $entity->id()];
      }
      $this->addUrlVariants($url_object, $path_data, $entity);
    }
    $this->processSegment();
  }

  /**
   * @return bool
   */
  protected function isBatch() {
    return $this->batchInfo['from'] != 'nobatch';
  }

  /**
   * @param $path
   * @return bool
   */
  protected function pathProcessed($path) {
    $path_pool = isset($this->context['results']['processed_paths']) ? $this->context['results']['processed_paths'] : [];
    if (in_array($path, $path_pool)) {
      return TRUE;
    }
    $this->context['results']['processed_paths'][] = $path;
    return FALSE;
  }

  /**
   * @param $entity_info
   * @return mixed
   */
  private function getBatchIterationEntities($entity_info) {
    $query = $this->entityQuery->get($entity_info['entity_type_name']);

    if (!empty($entity_info['keys']['id'])) {
      $query->sort($entity_info['keys']['id'], 'ASC');
    }
    if (!empty($entity_info['keys']['bundle'])) {
      $query->condition($entity_info['keys']['bundle'], $entity_info['bundle_name']);
    }
    if (!empty($entity_info['keys']['status'])) {
      $query->condition($entity_info['keys']['status'], 1);
    }

    if ($this->needsInitialization()) {
      $count_query = clone $query;
      $this->initializeBatch($count_query->count()->execute());
    }

    if ($this->isBatch()) {
      $query->range($this->context['sandbox']['progress'], $this->batchInfo['batch_process_limit']);
    }

    return $this->entityTypeManager
      ->getStorage($entity_info['entity_type_name'])
      ->loadMultiple($query->execute());
  }

  /**
   * @param $custom_paths
   * @return mixed
   */
  private function getBatchIterationCustomPaths($custom_paths) {

    if ($this->needsInitialization()) {
      $this->initializeBatch(count($custom_paths));
    }

    if ($this->isBatch()) {
      $custom_paths = array_slice($custom_paths, $this->context['sandbox']['progress'], $this->batchInfo['batch_process_limit']);
    }

    return $custom_paths;
  }

  /**
   * @param $url_object
   * @param $path_data
   * @param $entity
   */
  private function addUrlVariants($url_object, $path_data, $entity) {
    $alternate_urls = [];

    $translation_languages = !is_null($entity) && $this->batchInfo['skip_untranslated']
      ? $entity->getTranslationLanguages() : $this->languages;

    // Entity is not translated.
    if (!is_null($entity) && isset($translation_languages['und'])) {
      if ($url_object->access($this->anonUser)) {
        $url_object->setOption('language', $this->languages[$this->defaultLanguageId]);
        $alternate_urls[$this->defaultLanguageId] = $this->replaceBaseUrlWithCustom($url_object->toString());
      }
    }
    else {
      // Including only translated variants of entity.
      if (!is_null($entity) && $this->batchInfo['skip_untranslated']) {
        foreach ($translation_languages as $language) {
          $translation = $entity->getTranslation($language->getId());
          if ($translation->access('view', $this->anonUser)) {
            $url_object->setOption('language', $language);
            $alternate_urls[$language->getId()] = $this->replaceBaseUrlWithCustom($url_object->toString());
          }
        }
      }

      // Not an entity or including all untranslated variants.
      elseif ($url_object->access($this->anonUser)) {
        foreach ($translation_languages as $language) {
          $url_object->setOption('language', $language);
          $alternate_urls[$language->getId()] = $this->replaceBaseUrlWithCustom($url_object->toString());
        }
      }
    }

    foreach ($alternate_urls as $langcode => $url) {
      $this->context['results']['generate'][] = $path_data + ['langcode' => $langcode, 'url' => $url, 'alternate_urls' => $alternate_urls];
    }
  }

  /**
   * @return bool
   */
  protected function needsInitialization() {
    return empty($this->context['sandbox']);
  }

  /**
   * @param $max
   */
  protected function initializeBatch($max) {
    $this->context['results']['generate'] = !empty($this->context['results']['generate']) ? $this->context['results']['generate'] : [];
    if ($this->isBatch()) {
      $this->context['sandbox']['progress'] = 0;
      $this->context['sandbox']['current_id'] = 0;
      $this->context['sandbox']['max'] = $max;
      $this->context['results']['processed_paths'] = !empty($this->context['results']['processed_paths'])
        ? $this->context['results']['processed_paths'] : [];
    }
  }

  /**
   * @param $id
   */
  protected function setCurrentId($id) {
    if ($this->isBatch()) {
      $this->context['sandbox']['progress']++;
      $this->context['sandbox']['current_id'] = $id;
    }
  }

  /**
   *
   */
  protected function processSegment() {
    if ($this->isBatch()) {
      $this->setProgressInfo();
    }
    if (!empty($this->batchInfo['max_links']) && count($this->context['results']['generate']) >= $this->batchInfo['max_links']) {
      $chunks = array_chunk($this->context['results']['generate'], $this->batchInfo['max_links']);
      foreach ($chunks as $i => $chunk_links) {
        if (count($chunk_links) == $this->batchInfo['max_links']) {
          $remove_sitemap = empty($this->context['results']['chunk_count']);
          $this->sitemapGenerator->generateSitemap($chunk_links, $remove_sitemap);
          $this->context['results']['chunk_count'] = !isset($this->context['results']['chunk_count'])
            ? 1 : $this->context['results']['chunk_count'] + 1;
          $this->context['results']['generate'] = array_slice($this->context['results']['generate'], count($chunk_links));
        }
      }
    }
  }

  /**
   *
   */
  protected function setProgressInfo() {
    if ($this->context['sandbox']['progress'] != $this->context['sandbox']['max']) {
      // Providing progress info to the batch API.
      $this->context['finished'] = $this->context['sandbox']['progress'] / $this->context['sandbox']['max'];
      // Adding processing message after finishing every batch segment.
      end($this->context['results']['generate']);
      $last_key = key($this->context['results']['generate']);
      if (!empty($this->context['results']['generate'][$last_key]['path'])) {
        $this->context['message'] = $this->t(self::PROCESSING_PATH_MESSAGE, [
          '@current' => $this->context['sandbox']['progress'],
          '@max' => $this->context['sandbox']['max'],
          '@path' => HTML::escape($this->context['results']['generate'][$last_key]['path']),
        ]);
      }
    }
  }

  /**
   * @param $url_object
   * @return object|null
   */
  private function getEntityFromUrlObject($url_object) {
    $route_parameters = $url_object->getRouteParameters();
    return !empty($route_parameters) && $this->entityTypeManager
      ->getDefinition($entity_type_id = key($route_parameters), FALSE)
      ? $this->entityTypeManager->getStorage($entity_type_id)
        ->load($route_parameters[$entity_type_id])
      : NULL;
  }

  private function replaceBaseUrlWithCustom($url) {
    return !empty($this->batchInfo['base_url'])
      ? str_replace($GLOBALS['base_url'], $this->batchInfo['base_url'], $url)
      : $url;
  }

  /**
   * Callback function called by the batch API when all operations are finished.
   *
   * @see https://api.drupal.org/api/drupal/core!includes!form.inc/group/batch/8
   */
  public function finishGeneration($success, $results, $operations) {
    if ($success) {
      $remove_sitemap = empty($results['chunk_count']);
      if (!empty($results['generate']) || $remove_sitemap) {
        $this->sitemapGenerator->generateSitemap($results['generate'], $remove_sitemap);
      }
      Cache::invalidateTags(['simple_sitemap']);
      $this->logger->m(self::REGENERATION_FINISHED_MESSAGE,
        ['@url' => $GLOBALS['base_url'] . '/sitemap.xml'])
//        ['@url' => $this->sitemapGenerator->getCustomBaseUrl() . '/sitemap.xml']) //todo: Use actual base URL for message.
        ->display('status')
        ->log('info');
    }
    else {
      $this->logger->m(self::REGENERATION_FINISHED_ERROR_MESSAGE)
        ->display('error', 'administer sitemap settings')
        ->log('error');
    }
  }
}
