<?php

namespace Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPlugin;

use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Link;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginBase;
use Drupal\rabbit_hole\Exception\InvalidRedirectResponseException;
use Drupal\rabbit_hole\BehaviorSettingsManagerInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;



/**
 * Redirects to another page.
 *
 * @RabbitHoleBehaviorPlugin(
 *   id = "page_redirect",
 *   label = @Translation("Page redirect")
 * )
 */
class PageRedirect extends RabbitHoleBehaviorPluginBase implements ContainerFactoryPluginInterface {
  use StringTranslationTrait;

  const RABBIT_HOLE_PAGE_REDIRECT_DEFAULT = '';
  const RABBIT_HOLE_PAGE_REDIRECT_RESPONSE_DEFAULT = 301;

  const REDIRECT_MOVED_PERMANENTLY = 301;
  const REDIRECT_FOUND = 302;
  const REDIRECT_SEE_OTHER = 303;
  const REDIRECT_NOT_MODIFIED = 304;
  const REDIRECT_USE_PROXY = 305;
  const REDIRECT_TEMPORARY_REDIRECT = 307;

  /**
   * The redirect path.
   *
   * @var string
   */
  private $path;

  /**
   * The HTTP response code.
   *
   * @var string
   */
  private $code;

  /**
   * The behavior settings manager.
   *
   * @var Drupal\rabbit_hole\BehaviorSettingsManagerInterface
   */
  protected $rhBehaviorSettingsManager;

  /**
   * The entity plugin manager.
   *
   * @var Drupal\rabbit_hole\Entity\RabbitHoleEntityPluginManager;
   */
  protected $rhEntityPluginManager;

  /**
   * The module handler.
   *
   * @var Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The token service.
   *
   * @var Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    BehaviorSettingsManagerInterface $bsm,
    RabbitHoleEntityPluginManager $rhepm,
    ModuleHandlerInterface $mhi,
    Token $token) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->rhBehaviorSettingsManager = $bsm;
    $this->rhEntityPluginManager = $rhepm;
    $this->moduleHandler = $mhi;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static (
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('rabbit_hole.behavior_settings_manager'),
      $container->get('plugin.manager.rabbit_hole_entity_plugin'),
      $container->get('module_handler'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function performAction(Entity $entity, Response $current_response = NULL) {
    $target = $entity->get('rh_redirect')->value;
    $response_code = NULL;

    $bundle_entity_type = $entity->getEntityType()->getBundleEntityType();
    $bundle_settings = $this->rhBehaviorSettingsManager
      ->loadBehaviorSettingsAsConfig(
        $bundle_entity_type ?: $entity->getEntityType()->id(),
        $bundle_entity_type ? $entity->bundle() : NULL);

    if (empty($target)) {
      $target = $bundle_settings->get('redirect');
      $response_code = $bundle_settings->get('redirect_code');
    }
    else {
      $response_code = $entity->get('rh_redirect_response')->value;
    }

    // Replace any tokens if applicable.
    $langcode = $entity->language()->getId();

    if ($langcode == LanguageInterface::LANGCODE_NOT_APPLICABLE) {
      $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    }

    if ($this->moduleHandler->moduleExists('token')) {
      $target = $this->token->replace($target,
        array(
          $entity->getEntityTypeId() => $entity,
        ),
        array(
          'clear' => TRUE,
          'langcode' => $langcode,
        ), new BubbleableMetadata()
      );
    }

    if (substr($target, 0, 4) == '<?php') {
      // TODO: Evaluate PHP code.
    }
    elseif ($target === '<front>' || $target === '/<front>') {
      // Special case for redirecting to the front page.
      $target = \Drupal::service('url_generator')->generateFromRoute('<front>', [], []);
    }

    switch ($response_code) {
      case self::REDIRECT_MOVED_PERMANENTLY:
      case self::REDIRECT_FOUND:
      case self::REDIRECT_SEE_OTHER:
      case self::REDIRECT_TEMPORARY_REDIRECT:
        if ($current_response === NULL) {
          return new TrustedRedirectResponse($target, $response_code);
        }
        else {
          // If a response already exists we don't need to do anything with it.
          return $current_response;
        }
        // TODO: I don't think this is the correct way to handle a 304 response.
      case self::REDIRECT_NOT_MODIFIED:
        if ($current_response === NULL) {
          $not_modified_response = new Response();
          $not_modified_response->setStatusCode(self::REDIRECT_NOT_MODIFIED);
          $not_modified_response->headers->set('Location', $target);
          return $not_modified_response;
        }
        else {
          // If a response already exists we don't need to do anything with it.
          return $current_response;
        }
        // TODO: I have no idea if this is actually the correct way to handle a
        // 305 response in Symfony/D8. Documentation on it seems a bit sparse.
      case self::REDIRECT_USE_PROXY:
        if ($current_response === NULL) {
          $use_proxy_response = new Response();
          $use_proxy_response->setStatusCode(self::REDIRECT_USE_PROXY);
          $use_proxy_response->headers->set('Location', $target);
          return $use_proxy_response;
        }
        else {
          // If a response already exists we don't need to do anything with it.
          return $current_response;
        }
      default:
        throw new InvalidRedirectResponseException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(&$form, &$form_state, $form_id, Entity $entity = NULL,
    $entity_is_bundle = FALSE, ImmutableConfig $bundle_settings = NULL) {

    $redirect = NULL;
    $redirect_code = NULL;

    if ($entity_is_bundle) {
      $redirect = $bundle_settings->get('redirect');
      $redirect_code = $bundle_settings->get('redirect_code');
    }
    elseif (isset($entity)) {
      $redirect = isset($entity->rh_redirect->value)
        ? $entity->rh_redirect->value
        : self::RABBIT_HOLE_PAGE_REDIRECT_DEFAULT;
      $redirect_code = isset($entity->rh_redirect_response->value)
        ? $entity->rh_redirect_response->value
        : self::RABBIT_HOLE_PAGE_REDIRECT_RESPONSE_DEFAULT;
    }
    else {
      $redirect = NULL;
      $redirect_code = NULL;
    }

    $form['rabbit_hole']['redirect'] = array(
      '#type' => 'fieldset',
      '#title' => t('Redirect settings'),
      '#attributes' => array('class' => array('rabbit-hole-redirect-options')),
      '#states' => array(
        'visible' => array(
          ':input[name="rh_action"]' => array('value' => $this->getPluginId()),
        ),
      ),
    );

    // Get the default value for the redirect path.
    // Build the descriptive text. Add some help text for PHP, if the user has
    // the permission to use PHP for evaluation.
    $description = array();
    $description[] = t('Enter the relative path or the full URL that the user should get redirected to. Query strings and fragments are supported, such as %example.', array('%example' => 'http://www.example.com/?query=value#fragment'));

    if ($this->moduleHandler->moduleExists('token')) {
      $description[] = t('You may enter tokens in this field.');
    }

    $form['rabbit_hole']['redirect']['rh_redirect'] = array(
      '#type' => /*rabbit_hole_access_php($module) ? 'textarea' :*/ 'textfield',
      '#title' => t('Redirect path'),
      '#default_value' => $redirect,
      '#description' => '<p>' . implode('</p><p>', $description) . '</p>',
      '#attributes' => array('class' => array('rabbit-hole-redirect-setting')),
      '#rows' => substr_count($redirect, "\r\n") + 2,
      '#element_validate' => array(),
      '#after_build' => array(),
    );

    $entity_type_id = NULL;
    if (isset($entity)) {
      $entity_type_id = $entity_is_bundle
        ? $entity->getEntityType()->getBundleOf()
        : $entity->getEntityTypeId();
    }
    else {
      $entity_type_id = $this->rhEntityPluginManager
        ->loadSupportedGlobalForms()[$form_id];
    }

    $entity_type_for_tokens = NULL;
    if ($this->moduleHandler->moduleExists('token')) {
      $token_map = $this->rhEntityPluginManager->loadEntityTokenMap();
      $entity_type_for_tokens = $token_map[$entity_type_id];

      $form['rabbit_hole']['redirect']['rh_redirect']['#element_validate'][]
        = 'token_element_validate';
      $form['rabbit_hole']['redirect']['rh_redirect']['#after_build'][]
        = 'token_element_validate';
      $form['rabbit_hole']['redirect']['rh_redirect']['#token_types']
        = array($entity_type_for_tokens);
    }

    // Add the redirect response setting.
    $form['rabbit_hole']['redirect']['rh_redirect_response'] = array(
      '#type' => 'select',
      '#title' => $this->t('Response code'),
      '#options' => array(
        301 => $this->t('301 (Moved Permanently)'),
        302 => $this->t('302 (Found)'),
        303 => $this->t('303 (See other)'),
        304 => $this->t('304 (Not modified)'),
        305 => $this->t('305 (Use proxy)'),
        307 => $this->t('307 (Temporary redirect)'),
      ),
      '#default_value' => $redirect_code,
      '#description' => $this->t('The response code that should be sent to the users browser. Follow @link for more information on response codes.',
        array('@link' => Link::fromTextAndUrl(t('this link'), Url::fromUri('http://api.drupal.org/api/drupal/includes--common.inc/function/drupal_goto/7'))->toString())),
      '#attributes' => array('class' => array('rabbit-hole-redirect-response-setting')),
    );

    // Display a list of tokens if the Token module is enabled.
    if ($this->moduleHandler->moduleExists('token')) {
      $form['rabbit_hole']['redirect']['token_help'] = array(
        '#theme' => 'token_tree_link',
        '#token_types' => array($entity_type_for_tokens),
      );
    }

    // If the redirect path contains PHP, and the user doesn't have permission
    // to use PHP for evaluation, we'll disable access to the path setting, and
    // print some helpful information about what's going on.
  }

  /**
   * {@inheritdoc}
   */
  public function alterExtraFields(array &$fields) {
    $fields['rh_redirect'] = BaseFieldDefinition::create('string')
      ->setName('rh_redirect')
      ->setLabel($this->t('Rabbit Hole redirect path or code'))
      ->setDescription($this->t('The path to where the user should get redirected to.'));
    $fields['rh_redirect_response'] = BaseFieldDefinition::create('integer')
      ->setName('rh_redirect_response')
      ->setLabel($this->t('Rabbit Hole redirect response code'))
      ->setDescription($this->t('Specifies the HTTP response code that should be used when perform a redirect.'));
  }

}
