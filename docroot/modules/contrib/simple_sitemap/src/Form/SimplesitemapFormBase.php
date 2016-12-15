<?php

namespace Drupal\simple_sitemap\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\Core\Path\PathValidator;

/**
 * Class SimplesitemapFormBase.
 *
 * @package Drupal\simple_sitemap\Form
 */
abstract class SimplesitemapFormBase extends ConfigFormBase {

  protected $generator;
  protected $formHelper;
  protected $pathValidator;

  /**
   * SimplesitemapFormBase constructor.
   *
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   * @param \Drupal\simple_sitemap\Form\FormHelper $form_helper
   * @param \Drupal\Core\Path\PathValidator $path_validator
   */
  public function __construct(
    Simplesitemap $generator,
    FormHelper $form_helper,
    PathValidator $path_validator
  ) {
    $this->generator = $generator;
    $this->formHelper = $form_helper;
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.form_helper'),
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simple_sitemap.settings'];
  }

  /**
   *
   */
  protected function getDonationText() {
    return "<div class='description'>" . $this->t("If you would like to say thanks and support the development of this module, a <a target='_blank' href='@url'>donation</a> is always appreciated.", ['@url' => 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5AFYRSBLGSC3W']) . "</div>";
  }

}
