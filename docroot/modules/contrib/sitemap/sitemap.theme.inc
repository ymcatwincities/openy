<?php

/**
 * @file
 * Sitemap theme functions.
 */

use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Preprocesses variables for sitemap-feed-icon.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - url: The url of the feed.
 *   - name: The name of the feed.
 *   - type: The type of feed icon.
 */
function template_preprocess_sitemap_feed_icon(array &$variables) {
  $output = '';

  switch ($variables['type']) {
    case 'node':
      $image = array(
        '#theme' => 'image',
        '#uri' => drupal_get_path('module', 'sitemap') . '/images/feed-small.png',
        '#title' => t('Syndicated feed icon'),
        '#alt' => t('Syndicated feed icon'),
      );
      $output = \Drupal::service('renderer')->render($image);
      break;
  }

  if (!empty($variables['url'])) {
    $output = Link::fromTextAndUrl($output, Url::fromUri('base://' . $variables['url'], array(
      'attributes' => array(
        'class' => 'feed-link',
        'title' => t('Syndicated feed for @feed_name', array('@feed_name' => $variables['name'])),
      ),
      'html' => TRUE,
    )))->toString();
  }

  $variables['icon'] = $output;
}

/**
 * Preprocesses the rendered tree for theme_sitemap_menu_tree().
 *
 * This is a clone of the core template_preprocess_menu_tree() function
 * with the exception of the sitemap specific class name used in the
 * UL that also allow themers to override the function only
 * for the sitemap page.
 */
function template_preprocess_sitemap_menu_tree(&$variables) {
  $variables['tree'] = $variables['tree']['#children'];
}

/**
 * Returns HTML for a wrapper for a menu sub-tree.
 *
 * This is a clone of the core theme_menu_tree() function with the exception of
 * the sitemap specific class name used in the UL that also allow themers to
 * override the function only for the sitemap page.
 *
 * @param array $variables
 *   An associative array containing:
 *   - tree: An HTML string containing the tree's items.
 *
 * @return string
 *   Returns the html string with the <ul> for the menu tree.
 *
 * @see template_preprocess_menu_tree()
 *
 * @ingroup themeable
 */
function theme_sitemap_menu_tree(array $variables) {
  return '<ul class="sitemap-menu">' . $variables['tree'] . '</ul>';
}

/**
 * Returns HTML for a menu link and submenu.
 *
 * This is a one by one clone of the core theme_menu_link() function that allows
 * custom theming of the sitemap page items.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: Structured array data for a menu link.
 *
 * @return string
 *   Returns html string for menu link.
 *
 * @ingroup themeable
 */
function theme_sitemap_menu_link(array $variables) {
  $element = $variables['element'];
  $sub_menu = '';

  if ($element['#below']) {
    $sub_menu = \Drupal::service('renderer')->render($element['#below']);
  }
  /** @var \Drupal\Core\Url $url */
  $url = $element['#url'];
  $url->setOption('set_active_class', TRUE);
  $output = \Drupal::linkGenerator()->generate($element['#title'], $url);
  return '<li' . new Attribute($element['#attributes']) . '>' . $output . $sub_menu . "</li>\n";
}


/**
 * Preprocesses the variables for sitemap.html.twig.
 *
 * @see sitemap.html.twig
 */
function template_preprocess_sitemap(array &$variables) {
  $config = \Drupal::config('sitemap.settings');

  $message = $config->get('message.value');
  if (!empty($message)) {
    $variables['message'] = ['#markup' => $message];
  }

  if ($config->get('show_titles')) {
    $variables['show_titles'] = TRUE;
  }

  $variables['sitemap_items'] = [];
  $sitemap_order = $config->get('order');
  asort($sitemap_order);

  foreach ($sitemap_order as $content => $weight) {
    // Get type of content.
    $type = substr($content, 0, strpos($content, '_'));
    $id = substr($content, strpos($content, '_') + 1);
    if (empty($type)) {
      $type = $content;
      $id = NULL;
    }

    switch ($type) {
      case 'front':
        if ($config->get('show_front')) {
          $variables['sitemap_items']['front'] = _sitemap_front_page();
        }
        break;

      case 'books':
        $books = $config->get('show_books');
        if (!empty($books)) {
          $variables['sitemap_items']['books'] = _sitemap_books();
        }
        break;

      case 'menus':
        $menus = array_filter($config->get('show_menus'));
        if (!empty($menus[$id])) {
          $variables['sitemap_items']['menu_' . $id] = _sitemap_menus($id);
        }
        break;

      case 'vocabularies':
        $vocabulary = \Drupal::entityManager()->getStorage('taxonomy_vocabulary')->load($id);
        $vocabularies = $config->get('show_vocabularies');

        if (!empty($vocabularies[$vocabulary->id()])) {
          // Compile the vocabulary trees.
          $variables['sitemap_items']['vocabulary_' . $vocabulary->id()] = \Drupal::service('sitemap.helper')->getTerms($vocabulary);
        }
        break;
    }
  }

  // Invoke all custom modules and integrate themed HTML into the sitemap.
  $additional = \Drupal::service('module_handler')->invokeAll('sitemap');
  foreach ($additional as $themed_sitemap_code) {
    $variables['additional'] = $themed_sitemap_code;
  }
}
