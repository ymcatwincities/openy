<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap_custom\Controller\XmlSitemapCustomListController.
 */

namespace Drupal\xmlsitemap_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;

/**
 * Builds the list table for all custom links.
 */
class XmlSitemapCustomListController extends ControllerBase {

  /**
   * The language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new XmlSitemapController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory'), $container->get('language_manager')
    );
  }

  /**
   * Renders a list with all custom links.
   *
   * @return array
   *   The list to be rendered.
   */
  public function render() {
    $build['xmlsitemap_add_custom'] = array(
      '#type' => 'link',
      '#title' => t('Add custom link'),
      '#href' => 'admin/config/search/xmlsitemap/custom/add',
    );
    $header = array(
      'loc' => array('data' => t('Location'), 'field' => 'loc', 'sort' => 'asc'),
      'priority' => array('data' => t('Priority'), 'field' => 'priority'),
      'changefreq' => array('data' => t('Change frequency'), 'field' => 'changefreq'),
      'language' => array('data' => t('Language'), 'field' => 'language'),
      'operations' => array('data' => t('Operations')),
    );

    $rows = array();
    $destination = drupal_get_destination();

    $query = db_select('xmlsitemap');
    $query->fields('xmlsitemap');
    $query->condition('type', 'custom');
    $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(50);
    $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header);
    $result = $query->execute();

    foreach ($result as $link) {
      $language = $this->languageManager->getLanguage($link->language);
      $row = array();
      $row['loc'] = $this->l($link->loc, Url::fromUri($link->loc));
      $row['priority'] = number_format($link->priority, 1);
      $row['changefreq'] = $link->changefreq ? drupal_ucfirst(xmlsitemap_get_changefreq($link->changefreq)) : t('None');
      if (isset($header['language'])) {
        $row['language'] = t($language->name);
      }
      $operations['edit'] = array(
        'title' => t('Edit'),
        'route_name' => 'xmlsitemap_custom.edit',
        'route_parameters' => array(
          'link' => $link->id,
        ),
      );
      $operations['delete'] = array(
        'title' => t('Delete'),
        'route_name' => 'xmlsitemap_custom.delete',
        'route_parameters' => array(
          'link' => $link->id,
        ),
      );
      $row['operations'] = array(
        'data' => array(
          '#type' => 'operations',
          '#theme' => 'links',
          '#links' => $operations,
          '#attributes' => array('class' => array('links', 'inline')),
        ),
      );
      $rows[] = $row;
    }

    // @todo Convert to tableselect
    $build['xmlsitemap_custom_table'] = array(
      '#type' => 'tableselect',
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No custom links available. <a href="@custom_link">Add custom link</a>', array('@custom_link' => Url::fromRoute('xmlsitemap_custom.add', [], array('query' => $destination)))),
    );
    $build['xmlsitemap_custom_pager'] = array('#theme' => 'pager');

    return $build;
  }

}
