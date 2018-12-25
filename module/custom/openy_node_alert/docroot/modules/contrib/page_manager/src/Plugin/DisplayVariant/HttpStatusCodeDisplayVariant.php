<?php

/**
 * @file
 * Contains \Drupal\page_manager\Plugin\DisplayVariant\HttpStatusCodeDisplayVariant.
 */

namespace Drupal\page_manager\Plugin\DisplayVariant;

use Drupal\Core\Display\VariantBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides a variant that returns a response with an HTTP status code.
 *
 * @DisplayVariant(
 *   id = "http_status_code",
 *   admin_label = @Translation("HTTP status code")
 * )
 */
class HttpStatusCodeDisplayVariant extends VariantBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Don't call VariantBase::buildConfigurationForm() on purpose, because it
    // adds a 'Label' field that we don't actually want to use - we store the
    // label on the page variant entity.
    //$form = parent::buildConfigurationForm($form, $form_state);

    // Get all possible status codes defined by Symfony.
    $options = Response::$statusTexts;
    // Move 403/404/500 to the top.
    $options = [
      '404' => $options['404'],
      '403' => $options['403'],
      '500' => $options['500'],
    ] + $options;

    // Add the HTTP status code, so it's easier for people to find it.
    array_walk($options, function($title, $code) use (&$options) {
      $options[$code] = $this->t('@code (@title)', ['@code' => $code, '@title' => $title]);
    });

    $form['status_code'] = [
      '#title' => $this->t('HTTP status code'),
      '#type' => 'select',
      '#default_value' => $this->configuration['status_code'],
      '#options' => $options,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['status_code'] = '404';
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['status_code'] = $form_state->getValue('status_code');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $status_code = $this->configuration['status_code'];
    if ($status_code == 200) {
      return [];
    }
    else {
      throw new HttpException($status_code);
    }
  }

}
