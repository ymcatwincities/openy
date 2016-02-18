<?php

namespace Drupal\ymca_migrate\EventSubscriber;

use Drupal\Component\Serialization\Yaml;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\views\Entity\View;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class MigrationEvents.
 *
 * @package Drupal\ymca_migrate\EventSubscriber
 */
class MigrationEvents implements EventSubscriberInterface {

  /**
   * Run configs after import.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The Event to process.
   */
  public function onPostImport(MigrateImportEvent $event) {
    switch ($event->getMigration()->id()) {
      case 'ymca_migrate_taxonomy_term_tags':
        // Check if the view is already exists.
        if (\Drupal::entityManager()->getStorage('view')->load('ymca_news')) {
          break;
        }

        $loader = file_get_contents(drupal_get_path('module', 'ymca_migrate') . '/config/disabled/views.view.ymca_news.yml');
        $data = Yaml::decode($loader);
        $news_term_id = \Drupal::entityQuery('taxonomy_term')
          ->condition('name', 'News')
          ->condition('vid', 'tags')
          ->execute();
        $term_id = (int) array_shift($news_term_id);
        $news_term = \Drupal::entityManager()->getStorage('taxonomy_term')->load($term_id);
        $data['display']['default']['display_options']['filters']['field_tags_target_id']['value'] = array($term_id => $term_id);
        $view = View::create($data);
        $view->set('dependencies', array('content' => array('taxonomy_term:tags:' . $news_term->uuid())));
        $view->save();

        // Check if the view is already exists.
        if (\Drupal::entityManager()->getStorage('view')->load('ymca_news_archive')) {
          break;
        }

        $loader = file_get_contents(drupal_get_path('module', 'ymca_migrate') . '/config/disabled/views.view.ymca_news_archive.yml');
        $data = Yaml::decode($loader);
        $news_term_id = \Drupal::entityQuery('taxonomy_term')
          ->condition('name', 'News')
          ->condition('vid', 'tags')
          ->execute();
        $term_id = (int) array_shift($news_term_id);
        $news_term = \Drupal::entityManager()->getStorage('taxonomy_term')->load($term_id);
        $data['display']['default']['display_options']['filters']['field_tags_target_id']['value'] = array($term_id => $term_id);
        $view = View::create($data);
        $view->set('dependencies', array('content' => array('taxonomy_term:tags:' . $news_term->uuid())));
        $view->save();

        if (\Drupal::entityManager()->getStorage('view')->load('ymca_twin_cities_blog')) {
          break;
        }

        $loader = file_get_contents(drupal_get_path('module', 'ymca_migrate') . '/config/disabled/views.view.ymca_twin_cities_blog.yml');
        $data = Yaml::decode($loader);
        $news_term_id = \Drupal::entityQuery('taxonomy_term')
          ->condition('name', 'News', '!=')
          ->condition('vid', 'tags')
          ->execute();
        $terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadMultiple($news_term_id);
        $data['display']['default']['display_options']['filters']['field_tags_target_id']['value'] = array();
        $deps = array();
        foreach ($terms as $term) {
          $data['display']['default']['display_options']['filters']['field_tags_target_id']['value'][(int) $term->id()] = (int) $term->id();
          $deps[] = 'taxonomy_term:tags:' . $term->uuid();
        }

        $view = View::create($data);
        $view->set('dependencies', array('content' => $deps));
        $view->save();

        if (\Drupal::entityManager()->getStorage('view')->load('ymca_twin_cities_blog_archive')) {
          break;
        }

        $loader = file_get_contents(drupal_get_path('module', 'ymca_migrate') . '/config/disabled/views.view.ymca_twin_cities_blog_archive.yml');
        $data = Yaml::decode($loader);
        $news_term_id = \Drupal::entityQuery('taxonomy_term')
          ->condition('name', 'News', '!=')
          ->condition('vid', 'tags')
          ->execute();
        $terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadMultiple($news_term_id);
        $data['display']['default']['display_options']['filters']['field_tags_target_id']['value'] = array();
        $deps = array();
        foreach ($terms as $term) {
          $data['display']['default']['display_options']['filters']['field_tags_target_id']['value'][(int) $term->id()] = (int) $term->id();
          $deps[] = 'taxonomy_term:tags:' . $term->uuid();
        }

        $view = View::create($data);
        $view->set('dependencies', array('content' => $deps));
        $view->save();

        break;
    }

  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    return [MigrateEvents::POST_IMPORT => [['onPostImport', 100]]];
  }

}
