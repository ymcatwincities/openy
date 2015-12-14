<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap_custom\Form\XmlSitemapCustomEditForm.
 */

namespace Drupal\xmlsitemap_custom\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Language\LanguageInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\xmlsitemap\XmlSitemapLinkStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for editing a custom link.
 */
class XmlSitemapCustomEditForm extends ConfigFormBase {

  /**
   * The path of the custom link.
   *
   * @var string
   */
  protected $custom_link;

  /**
   * The language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The alias manager service.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The xmlsitemap link storage handler.
   *
   * @var \Drupal\xmlsitemap\XmlSitemapLinkStorageInterface
   */
  protected $linkStorage;

  /**
   * Constructs a new XmlSitemapCustomAddForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager service.
   * @param \Drupal\xmlsitemap\XmlSitemapLinkStorageInterface $link_storage
   *   The xmlsitemap link storage service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager, AliasManagerInterface $alias_manager, XmlSitemapLinkStorageInterface $link_storage) {
    parent::__construct($config_factory);
    $this->languageManager = $language_manager;
    $this->aliasManager = $alias_manager;
    $this->linkStorage = $link_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory'), $container->get('language_manager'), $container->get('path.alias_manager'), $container->get('xmlsitemap.link_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlsitemap_custom_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $link = '') {
    $query = db_select('xmlsitemap');
    $query->fields('xmlsitemap');
    $query->condition('type', 'custom');
    $query->condition('id', $link);
    $result = $query->execute();
    $link = $result->fetchAssoc();
    if (!$link) {
      drupal_set_message(t('No valid custom link specified.'), 'error');
      return new RedirectResponse('/admin/config/search/xmlsitemap/custom/');
    }
    else {
      $this->custom_link = $link;
    }
    $query = db_select('xmlsitemap', 'x');
    $query->addExpression('MAX(id)');
    $id = $query->execute()->fetchField();
    $this->custom_link += array(
      'id' => $id + 1,
      'loc' => '',
      'priority' => XMLSITEMAP_PRIORITY_DEFAULT,
      'lastmod' => 0,
      'changefreq' => 0,
      'changecount' => 0,
      'language' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    );

    $form['type'] = array(
      '#type' => 'value',
      '#value' => 'custom',
    );
    $form['id'] = array(
      '#type' => 'value',
      '#value' => $this->custom_link['id'],
    );
    $form['loc'] = array(
      '#type' => 'textfield',
      '#title' => t('Path to link'),
      '#field_prefix' => Url::fromRoute('<front>', [], array('absolute' => TRUE)),
      '#default_value' => $this->custom_link['loc'],
      '#required' => TRUE,
      '#size' => 30,
    );
    $form['priority'] = array(
      '#type' => 'select',
      '#title' => t('Priority'),
      '#options' => xmlsitemap_get_priority_options(),
      '#default_value' => number_format($this->custom_link['priority'], 1),
      '#description' => t('The priority of this URL relative to other URLs on your site.'),
    );
    $form['changefreq'] = array(
      '#type' => 'select',
      '#title' => t('Change frequency'),
      '#options' => array(0 => t('None')) + xmlsitemap_get_changefreq_options(),
      '#default_value' => $link['changefreq'],
      '#description' => t('How frequently the page is likely to change. This value provides general information to search engines and may not correlate exactly to how often they crawl the page.'),
    );
    $languages = $this->languageManager->getLanguages();
    $languages_list = array();
    foreach ($languages as $key => $value) {
      $languages_list[$key] = $value->getName();
    }
    $form['language'] = array(
      '#type' => 'select',
      '#title' => t('Language'),
      '#default_value' => $this->custom_link['language'],
      '#options' => array(LanguageInterface::LANGCODE_NOT_SPECIFIED => t('Language neutral')) + $languages_list,
      '#access' => $languages_list,
    );

    $form['actions'] = array(
      '#type' => 'actions',
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#weight' => 5,
    );
    $cancel_link = Url::fromRoute('xmlsitemap_custom.list');
    $form['actions']['cancel'] = array(
      '#markup' => l(t('Cancel'), $cancel_link),
      '#weight' => 10,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $link = $form_state->getValues();
    $link['loc'] = trim($link['loc']);
    $link['loc'] = $this->aliasManager->getPathByAlias($link['loc'], $link['language']);
    $form_state->setValue('loc', $link['loc']);
    try {
      $client = new Client();
      $res = $client->get(Url::fromRoute('<front>', [], array('absolute' => TRUE)) . $link['loc']);
    }
    catch (ClientException $e) {
      $form_state->setErrorByName('loc', t('The custom link @link is either invalid or it cannot be accessed by anonymous users.', array('@link' => $link['loc'])));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $link = $form_state->getValues();
    $this->linkStorage->save($link);
    drupal_set_message(t('The custom link for %loc was saved.', array('%loc' => $link['loc'])));

    $form_state->setRedirect('xmlsitemap_custom.list');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['xmlsitemap.link_storage'];
  }

}
