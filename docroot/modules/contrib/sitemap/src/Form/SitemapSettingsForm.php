<?php

/**
 * @file
 * Contains \Drupal\sitemap\Form\SitemapSettingsForm.
 */

namespace Drupal\sitemap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Menu;
use Drupal\book\BookManagerInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Provides a configuration form for sitemap.
 */
class SitemapSettingsForm extends ConfigFormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The book manager.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * Constructs a SitemapSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactory $config_factory, ModuleHandler $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $module_handler = $container->get('module_handler');
    $form = new static(
      $container->get('config.factory'),
      $module_handler
    );
    if ($module_handler->moduleExists('book')) {
      $form->setBookManager($container->get('book.manager'));
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sitemap_settings';
  }

  /**
   * Set book manager service.
   *
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   Book manager service to set.
   */
  public function setBookManager(BookManagerInterface $book_manager) {
    $this->bookManager = $book_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('sitemap.settings');

    $form['page_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Page title'),
      '#default_value' => $config->get('page_title'),
      '#description' => $this->t('Page title that will be used on the @sitemap_page.', array('@sitemap_page' => $this->l($this->t('sitemap page'), Url::fromRoute('sitemap.page')))),
    );

    $sitemap_message = $config->get('message');
    $form['message'] = array(
      '#type' => 'text_format',
      '#format' => isset($sitemap_message['format']) ? $sitemap_message['format'] : NULL,
      '#title' => $this->t('Sitemap message'),
      '#default_value' => $sitemap_message['value'],
      '#description' => $this->t('Define a message to be displayed above the sitemap.'),
    );

    $form['sitemap_content'] = array(
      '#type' => 'details',
      '#title' => $this->t('Sitemap content'),
      '#open' => TRUE,
    );
    $sitemap_ordering = array();
    $form['sitemap_content']['show_front'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show front page'),
      '#default_value' => $config->get('show_front'),
      '#description' => $this->t('When enabled, this option will include the front page in the sitemap.'),
    );
    $sitemap_ordering['front'] = t('Front page');

    // Build list of books.
    if ($this->moduleHandler->moduleExists('book')) {
      $book_options = array();
      foreach ($this->bookManager->getAllBooks() as $book) {
        $book_options[$book['bid']] = $book['title'];
        $sitemap_ordering['books_' . $book['bid']] = $book['title'];
      }
      $form['sitemap_content']['show_books'] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Books to include in the sitemap'),
        '#default_value' => $config->get('show_books'),
        '#options' => $book_options,
        '#multiple' => TRUE,
      );
    }

    // Build list of menus.
    $menus = Menu::loadMultiple();
    $menu_options = array();
    foreach ($menus as $id => $menu) {
      $menu_options[$id] = $menu->label();
      $sitemap_ordering['menus_' . $id] = $menu->label();
    }
    $form['sitemap_content']['show_menus'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Menus to include in the sitemap'),
      '#default_value' => $config->get('show_menus'),
      '#options' => $menu_options,
      '#multiple' => TRUE,
    );

    // Build list of vocabularies.
    if ($this->moduleHandler->moduleExists('taxonomy')) {
      $vocab_options = array();
      $vocabularies = Vocabulary::loadMultiple();
      foreach ($vocabularies as $vocabulary) {
        $vocab_options[$vocabulary->id()] = $vocabulary->label();
        $sitemap_ordering['vocabularies_' . $vocabulary->id()] = $vocabulary->label();
      }
      $form['sitemap_content']['show_vocabularies'] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Vocabularies to include in the sitemap'),
        '#default_value' => $config->get('show_vocabularies'),
        '#options' => $vocab_options,
        '#multiple' => TRUE,
      );
    }

    // Follows FilterFormatFormBase for tabledrag ordering.
    $form['sitemap_content']['order'] = array(
      '#type' => 'table',
      '#attributes' => array('id' => 'sitemap-order'),
      '#title' => $this->t('Sitemap order'),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'sitemap-order-weight',
        ),
      ),
      '#tree' => FALSE,
      '#input' => FALSE,
      '#theme_wrappers' => array('form_element'),
    );
    $sitemap_order_defaults = $config->get('order');
    foreach ($sitemap_ordering as $content_id => $content_title) {
      $form['sitemap_content']['order'][$content_id] = array(
        'content' => array(
          '#markup' => $content_title,
        ),
        'weight' => array(
          '#type' => 'weight',
          '#title' => t('Weight for @title', array('@title' => $content_title)),
          '#title_display' => 'invisible',
          '#delta' => 50,
          '#default_value' => isset($sitemap_order_defaults[$content_id]) ? $sitemap_order_defaults[$content_id] : -50,
          '#parents' => array('order', $content_id),
          '#attributes' => array('class' => array('sitemap-order-weight')),
        ),
        '#weight' => isset($sitemap_order_defaults[$content_id]) ? $sitemap_order_defaults[$content_id] : -50,
        '#attributes' => ['class' => ['draggable']],
      );
    }
    $form['#attached']['library'][] = 'sitemap/sitemap.admin';

    $form['sitemap_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Sitemap settings'),
    ];
    $form['sitemap_options']['show_titles'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show titles'),
      '#default_value' => $config->get('show_titles'),
      '#description' => $this->t('When enabled, this option will show titles. Disable to not show section titles.'),
    );
    $form['sitemap_options']['sitemap_rss_options'] = array(
      '#type' => 'details',
      '#title' => $this->t('RSS settings'),
    );
    $form['sitemap_options']['sitemap_rss_options']['rss_front'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('RSS feed for front page'),
      '#default_value' => $config->get('rss_front'),
      '#description' => $this->t('The RSS feed for the front page, default is rss.xml.'),
    );
    $form['sitemap_options']['sitemap_rss_options']['show_rss_links'] = array(
      '#type' => 'select',
      '#title' => $this->t('Include RSS links'),
      '#default_value' => $config->get('show_rss_links'),
      '#options' => array(
        0 => $this->t('None'),
        1 => $this->t('Include on the right side'),
        2 => $this->t('Include on the left side'),
      ),
      '#description' => $this->t('When enabled, this option will show links to the RSS feeds for the front page and taxonomy terms, if enabled.'),
    );
    $form['sitemap_options']['sitemap_rss_options']['rss_taxonomy'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('RSS depth for vocabularies'),
      '#default_value' => $config->get('rss_taxonomy'),
      '#size' => 3,
      '#maxlength' => 10,
      '#description' => $this->t('Specify how many RSS feed links should be displayed with taxonomy terms. Enter "-1" to include with all terms, "0" not to include with any terms, or "1" to show only for top-level taxonomy terms.'),
    );
    $form['sitemap_options']['sitemap_css_options'] = array(
      '#type' => 'details',
      '#title' => $this->t('CSS settings'),
    );
    $form['sitemap_options']['sitemap_css_options']['css'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Do not include sitemap CSS file'),
      '#default_value' => $config->get('css'),
      '#description' => $this->t("If you don't want to load the included CSS file you can check this box. To learn how to override or specify the CSS at the theme level, visit the @documentation_page.", array('@documentation_page' => $this->l($this->t("documentation page"), Url::fromUri('https://www.drupal.org/node/2615568')))),
    );

    if ($this->moduleHandler->moduleExists('book')) {
      $form['sitemap_book_options'] = [
        '#type' => 'details',
        '#title' => $this->t('Book settings'),
      ];
      $form['sitemap_book_options']['books_expanded'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Show books expanded'),
        '#default_value' => $config->get('books_expanded'),
        '#description' => $this->t('When enabled, this option will show all children pages for each book.'),
      ];
    }

    if ($this->moduleHandler->moduleExists('forum')) {
      $form['sitemap_forum_options'] = [
        '#type' => 'details',
        '#title' => $this->t('Forum settings'),
      ];
      $form['sitemap_forum_options']['forum_threshold'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Forum count threshold'),
        '#default_value' => $config->get('forum_threshold'),
        '#size' => 3,
        '#description' => $this->t('Only show forums whose node counts are greater than this threshold. Set to -1 to disable.'),
      );
    }

    $form['sitemap_menu_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Menu settings'),
    ];
    $form['sitemap_menu_options']['show_menus_hidden'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show disabled menu items'),
      '#default_value' => $config->get('show_menus_hidden'),
      '#description' => $this->t('When enabled, hidden menu links will also be shown.'),
    );

    if ($this->moduleHandler->moduleExists('taxonomy')) {
      $form['sitemap_taxonomy_options'] = [
        '#type' => 'details',
        '#title' => $this->t('Taxonomy settings'),
      ];
      $form['sitemap_taxonomy_options']['show_description'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Show vocabulary description'),
        '#default_value' => $config->get('show_description'),
        '#description' => $this->t('When enabled, this option will show the vocabulary description.'),
      ];
      $form['sitemap_taxonomy_options']['show_count'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Show node counts by taxonomy terms'),
        '#default_value' => $config->get('show_count'),
        '#description' => $this->t('When enabled, this option will show the number of nodes in each taxonomy term.'),
      ];
      $form['sitemap_taxonomy_options']['vocabulary_depth'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Vocabulary depth'),
        '#default_value' => $config->get('vocabulary_depth'),
        '#size' => 3,
        '#maxlength' => 10,
        '#description' => $this->t('Specify how many levels taxonomy terms should be included. Enter "-1" to include all terms, "0" not to include terms at all, or "1" to only include top-level terms.'),
      ];
      $form['sitemap_taxonomy_options']['term_threshold'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Term count threshold'),
        '#default_value' => $config->get('term_threshold'),
        '#size' => 3,
        '#description' => $this->t('Only show taxonomy terms whose node counts are greater than this threshold. Set to -1 to disable.'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('sitemap.settings');

    $keys = array(
      'page_title',
      array('message', 'value'),
      array('message', 'format'),
      'show_front',
      'show_titles',
      'show_menus',
      'show_menus_hidden',
      'show_vocabularies',
      'show_description',
      'show_count',
      'vocabulary_depth',
      'term_threshold',
      'forum_threshold',
      'rss_front',
      'show_rss_links',
      'rss_taxonomy',
      'css',
      'order',
    );

    if ($this->moduleHandler->moduleExists('book')) {
      $keys[] = 'show_books';
      $keys[] = 'books_expanded';
    }

    // Save config.
    foreach ($keys as $key) {
      if ($form_state->hasValue($key)) {
        $config->set(is_string($key) ? $key : implode('.', $key), $form_state->getValue($key));
      }
    }
    $config->save();

    drupal_flush_all_caches();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sitemap.settings'];
  }

}
