<?php

namespace Drupal\domain_theme_switch\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Url;
use Drupal\domain\DomainLoader;

/**
 * Class DomainThemeSwitchConfigForm.
 *
 * @package Drupal\domain_theme_switch\Form
 */
class DomainThemeSwitchConfigForm extends ConfigFormBase {

  /**
   * Drupal\domain\DomainLoader definition.
   *
   * @var \Drupal\domain\DomainLoader
   */
  protected $domainLoader;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Construct function.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory load.
   * @param \Drupal\domain\DomainLoader $domain_loader
   *   The domain loader.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DomainLoader $domain_loader, ThemeHandlerInterface $theme_handler
  ) {
    parent::__construct($config_factory);
    $this->domainLoader = $domain_loader;
    $this->themeHandler = $theme_handler;
  }

  /**
   * Create function return static domain loader configuration.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Load the ContainerInterface.
   *
   * @return \static
   *   return domain loader configuration.
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory'), $container->get('domain.loader'), $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'domain_theme_switch.settings',
    ];
  }

  /**
   * Form ID is domain_theme_switch_config_form.
   *
   * @return string
   *   Return form ID.
   */
  public function getFormId() {
    return 'domain_theme_switch_config_form';
  }

  /**
   * Function to get the list of installed themes.
   *
   * @return array
   *   The complete theme registry data array.
   */
  public function getThemeList() {
    $themeName = array_keys($this->themeHandler->listInfo());
    return array_combine($themeName, $themeName);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('domain_theme_switch.settings');
    $defaultSiteTheme = $this->config('system.theme')->get('default');
    $defaultAdminTheme = $this->config('system.theme')->get('admin');

    $themeNames = $this->getThemeList();
    $domains = $this->domainLoader->loadMultipleSorted();
    foreach ($domains as $domain) {
      $domainId = $domain->id();
      $hostname = $domain->get('name');
      $form[$domainId] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Select Theme for "@domain"', ['@domain' => $hostname]),
      ];
      $form[$domainId][$domainId . '_site'] = [
        '#title' => $this->t('Site theme for domain'),
        '#type' => 'select',
        '#options' => $themeNames,
        '#default_value' => (NULL !== $config->get($domainId . '_site')) ? $config->get($domainId . '_site') : $defaultSiteTheme,
      ];
      $form[$domainId][$domainId . '_admin'] = [
        '#title' => $this->t('Admin theme for domain'),
        '#suffix' => $this->t('Change permission to allow domain admin theme @link.', [
          '@link' => $this->l($this->t('change permission'),
              Url::fromRoute('user.admin_permissions', [], ['fragment' => 'module-domain_theme_switch'])),
        ]),
        '#type' => 'select',
        '#options' => $themeNames,
        '#default_value' => (NULL !== $config->get($domainId . '_admin')) ? $config->get($domainId . '_admin') : $defaultAdminTheme,
      ];
    }
    if (count($domains) === 0) {
      $form['domain_theme_switch_message'] = [
        '#markup' => $this->t('Zero domain records found. Please @link to create the domain.', ['@link' => $this->l($this->t('click here'), Url::fromRoute('domain.admin'))]),
      ];
      return $form;
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * Validate function for the form.
   *
   * @param array $form
   *   Form items.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Formstate for validate.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $domains = $this->domainLoader->loadMultipleSorted();
    $config = $this->config('domain_theme_switch.settings');
    foreach ($domains as $domain) {
      $domainId = $domain->id();
      $config->set($domainId . '_site', $form_state->getValue($domainId . '_site'));
      $config->set($domainId . '_admin', $form_state->getValue($domainId . '_admin'));
    }
    $config->save();
  }

}
