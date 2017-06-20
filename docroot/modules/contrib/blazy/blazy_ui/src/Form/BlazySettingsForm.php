<?php

namespace Drupal\blazy_ui\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines blazy admin settings form.
 */
class BlazySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'blazy_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['blazy.settings'];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('blazy.settings');

    $form['admin_css'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Admin CSS'),
      '#default_value' => $config->get('admin_css'),
      '#description'   => $this->t('Uncheck to disable blazy related admin compact form styling, only if not compatible with your admin theme.'),
    ];

    $form['responsive_image'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Support Responsive image'),
      '#default_value' => $config->get('responsive_image'),
      '#description'   => $this->t('Check to support lazyloading for the core Responsive image module. Be sure to use Blazy formatter to have relevant styling.'),
    ];

    $form['one_pixel'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('One pixel placeholder'),
      '#default_value' => $config->get('one_pixel'),
      '#description'   => $this->t('By default a one pixel image is the placeholder for lazyloaded Responsive image. Useful to perform a lot better. Uncheck to disable, and use Drupal-managed smallest/fallback image style instead. Be sure to add proper dimensions or at least min-height/min-width via CSS accordingly to avoid layout reflow since Aspect ratio is not supported with Responsive image yet. Disabling this will result in downloading fallback image as well for non-PICTURE element (double downloads).'),
    ];

    $form['blazy'] = [
      '#type'        => 'container',
      '#tree'        => TRUE,
      '#title'       => $this->t('Blazy JS settings'),
      '#description' => $this->t('The following are JS related settings.'),
    ];

    $form['blazy']['loadInvisible'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Load invisible'),
      '#default_value' => $config->get('blazy.loadInvisible'),
      '#description'   => $this->t('Set to true if you want to load invisible (hidden) elements.'),
    ];

    $form['blazy']['offset'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Offset'),
      '#default_value' => $config->get('blazy.offset'),
      '#description'   => $this->t("The offset controls how early you want the elements to be loaded before they're visible. Default is <strong>100</strong>, so 100px before an element is visible it'll start loading."),
      '#field_suffix'  => 'px',
      '#maxlength'     => 5,
      '#size'          => 10,
    ];

    $form['blazy']['saveViewportOffsetDelay'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Save viewport offset delay'),
      '#default_value' => $config->get('blazy.saveViewportOffsetDelay'),
      '#description'   => $this->t('Delay for how often it should call the saveViewportOffset function on resize. Default is <strong>50</strong>ms.'),
      '#field_suffix'  => 'ms',
      '#maxlength'     => 5,
      '#size'          => 10,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('blazy.settings')
      ->set('admin_css', $form_state->getValue('admin_css'))
      ->set('responsive_image', $form_state->getValue('responsive_image'))
      ->set('one_pixel', $form_state->getValue('one_pixel'))
      ->set('blazy.loadInvisible', $form_state->getValue(['blazy', 'loadInvisible']))
      ->set('blazy.offset', $form_state->getValue(['blazy', 'offset']))
      ->set('blazy.saveViewportOffsetDelay', $form_state->getValue(['blazy', 'saveViewportOffsetDelay']))
      ->save();

    // Invalidate the library discovery cache to update the responsive image.
    \Drupal::service('library.discovery')->clearCachedDefinitions();

    drupal_set_message($this->t('Be sure to <a href=":clear_cache">clear the cache</a> if trouble to see the updated settings', [':clear_cache' => Url::fromRoute('system.performance_settings')->toString()]));

    parent::submitForm($form, $form_state);
  }

}
