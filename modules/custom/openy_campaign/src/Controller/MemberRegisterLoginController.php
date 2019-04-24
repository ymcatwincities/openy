<?php

namespace Drupal\openy_campaign\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class MembersController.
 */
class MemberRegisterLoginController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * Cache invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The ModalFormExampleController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   */
  public function __construct(
    FormBuilder $formBuilder,
    CacheTagsInvalidatorInterface $cache_tags_invalidator,
    RequestStack $requestStack
  ) {
    $this->formBuilder = $formBuilder;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('cache_tags.invalidator'),
      $container->get('request_stack')
    );
  }

  /**
   * Callback for opening the modal form.
   *
   * @param string $action
   *   Member action: 'login' or 'registration'.
   * @param string $campaign_id
   *   Campaign node ID.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse | \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function showModal($action = 'login', $campaign_id = NULL) {
    $actionsArray = ['login', 'registration'];
    $action = (in_array($action, $actionsArray)) ? $action : 'login';

    $is_ajax = $this->requestStack->isXmlHttpRequest();
    if (!$is_ajax) {
      return new RedirectResponse(Url::fromRoute('entity.node.canonical', ['node' => $campaign_id])->toString());
    }

    $response = new AjaxResponse();

    // Don't show popup for already logged in members.
    if (MemberCampaign::isLoggedIn($campaign_id)) {
      return $response;
    }

    // Registration.
    $formArg = 'Drupal\openy_campaign\Form\MemberRegisterForm';
    $modalTitle = $this->t('Registration');
    // Login.
    if ($action == 'login') {
      $modalTitle = $this->t('Sign in');
      $formArg = 'Drupal\openy_campaign\Form\MemberLoginForm';
    }

    $modalPopup = [
      '#theme' => 'openy_campaign_popup',
      '#form' => $this->formBuilder->getForm($formArg, $campaign_id),
    ];

    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(new OpenModalDialogCommand($modalTitle, $modalPopup, ['width' => '800']));
    $response->addCommand(new InvokeCommand('#drupal-modal', 'closeDialogByClick'));
    return $response;
  }

  /**
   * Callback for logout link.
   *
   * @param string $campaign_id
   *   Campaign node ID.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function logout($campaign_id = NULL) {
    $response = new AjaxResponse();

    // Logout member - clear Campaign ID from SESSION.
    MemberCampaign::logout($campaign_id);

    $logoutTitle = $this->t('Thank you!');
    $logoutMessage = $this->t('You were successfully logged out!');

    $response->addCommand(new OpenModalDialogCommand($logoutTitle, $logoutMessage, ['width' => 800]));

    // Close dialog and redirect ot Campaign main page.
    $response->addCommand(new InvokeCommand('#drupal-modal', 'closeDialog', ['<campaign-front>']));

    return $response;
  }

}
