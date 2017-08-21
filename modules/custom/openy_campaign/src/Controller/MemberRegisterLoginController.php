<?php

namespace Drupal\openy_campaign\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\openy_campaign\Entity\MemberCampaign;

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
   * The ModalFormExampleController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   */
  public function __construct(FormBuilder $formBuilder) {
    $this->formBuilder = $formBuilder;
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
      $container->get('form_builder')
    );
  }

  /**
   * Callback for opening the modal form.
   *
   * @param string $action Member action: 'login' or 'registration'.
   * @param string $campaign_id Campaign node ID.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function showModal($action = 'login', $campaign_id = NULL) {
    $actionsArray = ['login', 'registration'];
    $action = (in_array($action, $actionsArray)) ? $action : 'login';

    $response = new AjaxResponse();

    // Get the modal form using the form builder.
    $modalPopup = [
      '#theme' => ($action == 'login') ? 'openy_campaign_login' : 'openy_campaign_register',
      '#form' => $this->formBuilder->getForm('Drupal\openy_campaign\Form\MemberLoginRegisterForm', $action, $campaign_id),
    ];

    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(new OpenModalDialogCommand($this->addTitle($action), $modalPopup, ['width' => '800']));

    return $response;
  }

  /**
   * Callback for logout link.
   *
   * @param string $campaign_id Campaign node ID.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function logout($campaign_id = NULL) {
    $response = new AjaxResponse();

    // Logout member - clear Campaign ID from SESSION
    MemberCampaign::logout($campaign_id);

    $logoutTitle = $this->t('Thank you!');
    $logoutMessage = $this->t('You were successfully logged out!');

    $response->addCommand(new OpenModalDialogCommand($logoutTitle, $logoutMessage, ['width' => 800]));

    // Set redirect to Campaign page
    $fullPath = \Drupal::request()->getSchemeAndHttpHost() . '/node/' . $campaign_id;
    $response->addCommand(new RedirectCommand($fullPath));

    return $response;
  }

  /**
   * Title callback for popup.
   *
   * @param string $action Member action: 'login' or 'registration'.
   * @param string $campaign_id Campaign node ID.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup Popup title.
   */
  public function addTitle($action = 'login', $campaign_id = NULL) {

    if ($action == 'login') {
      return $this->t('Sign in');
    }
    return $this->t('Registration');
  }

}