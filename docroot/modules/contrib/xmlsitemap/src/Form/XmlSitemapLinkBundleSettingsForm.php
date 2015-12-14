<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Form\XmlSitemapLinkBundleSettingsForm.
 */

namespace Drupal\xmlsitemap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Configure what entities will be included in sitemap.
 */
class XmlSitemapLinkBundleSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  private $entity_type;
  private $bundle_type;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlsitemap_link_bundle_settings_form';
  }

  /**
   * Constructs a XmlSitemapLinkBundleSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityManagerInterface $entity_manager) {
    parent::__construct($config_factory);
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory'), $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['xmlsitemap.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity = NULL, $bundle = NULL) {
    $this->entity_type = $entity;
    $this->bundle_type = $bundle;
    $config = $this->config('xmlsitemap.settings');
    $request = $this->getRequest();

    if (!$request->isXmlHttpRequest() && $admin_path = xmlsitemap_get_bundle_path($entity, $bundle)) {
      // If this is a non-ajax form, redirect to the bundle administration page.
      $destination = drupal_get_destination();
      $request->query->remove('destination');
      $url = Url::fromUri($admin_path, array('query' => array($destination)));
      return new RedirectResponse($url);
    }
    else {
      $form['#title'] = $this->t('@bundle XML sitemap settings', array('@bundle' => $bundle));
    }

    xmlsitemap_add_link_bundle_settings($form, $form_state, $entity, $bundle);
    $form['xmlsitemap']['#type'] = 'markup';
    $form['xmlsitemap']['#value'] = '';
    $form['xmlsitemap']['#access'] = TRUE;
    $form['xmlsitemap']['#show_message'] = TRUE;

    $destination = $request->get('destination');

    $form['actions']['cancel'] = array(
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#href' => isset($destination) ? $destination : 'admin/config/search/xmlsitemap/settings',
      '#weight' => 10,
    );
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $form['xmlsitemap']['#entity'];
    $bundle = $form['xmlsitemap']['#bundle'];

    // Handle new bundles by fetching the proper bundle key value from the form
    // state values.
    if (empty($bundle)) {
      $entity_info = $form['xmlsitemap']['#entity_info'];
      if (isset($entity_info['bundle keys']['bundle'])) {
        $bundle_key = $entity_info['bundle keys']['bundle'];
        if ($form_state->hasValue($bundle_key)) {
          $bundle = $form_state->getValue($bundle_key);
          $form['xmlsitemap']['#bundle'] = $bundle;
        }
      }
    }

    $xmlsitemap = $form_state->getValue('xmlsitemap');
    xmlsitemap_link_bundle_settings_save($this->entity_type, $this->bundle_type, $xmlsitemap, TRUE);

    $entity_info = $form['xmlsitemap']['#entity_info'];
    if (!empty($form['xmlsitemap']['#show_message'])) {
      drupal_set_message($this->t('XML sitemap settings for the %bundle have been saved.', array('%bundle' => $entity_info['bundles'][$bundle]['label'])));
    }

    // Unset the form values since we have already saved the bundle settings and
    // we don't want these values to get saved as configuration, depending on how
    // the form saves the form values.
    $form_state->unsetValue('xmlsitemap');
    parent::submitForm($form, $form_state);
  }

}
