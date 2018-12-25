<?php

namespace Drupal\ymca_alters\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_sitemap\Form\SimplesitemapFormBase;

/**
 * Class SimplesitemapSkipLinksForm.
 *
 * @package Drupal\ymca_alters\Form
 */
class SimplesitemapSkipLinksForm extends SimplesitemapFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_sitemap_skip_links_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ymca_alters.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ymca_alters.config');

    $form['simple_sitemap_skip'] = [
      '#title' => $this->t('Skip links'),
      '#type' => 'fieldset',
      '#markup' => '<p>' . $this->t('Add custom internal drupal paths to skip from the XML sitemap.') . '</p>',
    ];

    $form['simple_sitemap_skip']['sitemap_xml_skip_uris'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Relative Drupal paths'),
      '#default_value' => implode("\n", $config->get('sitemap_xml_skip_uris')),
      '#description' => $this->t("Please specify drupal internal (relative) paths, one per line. Do not forget to prepend the paths with a '/'."),
    ];

    $this->formHelper->displayRegenerateNow($form['simple_sitemap_skip']);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $custom_links = $this->getCustomLinksFromString($form_state->getValue('sitemap_xml_skip_uris'));

    $config = $this->config('ymca_alters.config');
    $config->set('sitemap_xml_skip_uris', $custom_links);
    $config->save();

    parent::submitForm($form, $form_state);

    // Regenerate sitemap according to user setting.
    if ($form_state->getValue('simple_sitemap_regenerate_now')) {
      $this->generator->generateSitemap();
    }
  }

  /**
   * Get links from string.
   *
   * @param string $string
   *   String with links.
   *
   * @return array
   *   A list of links.
   */
  private function getCustomLinksFromString($string) {
    // Unify newline characters and explode into array.
    $lines = explode("\n", str_replace("\r\n", "\n", $string));
    // Remove empty values and whitespaces from array.
    $links = array_filter(array_map('trim', $lines));
    return $links;
  }

}
