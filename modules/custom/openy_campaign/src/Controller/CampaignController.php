<?php

namespace Drupal\openy_campaign\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\node\Entity\Node;
use Drupal\openy_campaign\Entity\MemberCampaign;

/**
 * Class CampaignMyProgressController.
 */
class CampaignController extends ControllerBase {

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
   * Render My progress tab content on Campaign node
   *
   * @param int $node Node ID of the current campaign.
   * @param int $landing_page_id Landing page node ID to get new content for replacement.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function showMyProgressContent($node, $landing_page_id) {
    $response = new AjaxResponse();

    // Show modal popup if user is not logged in.
    if (!MemberCampaign::isLoggedIn($node)) {
      // Get the modal form using the form builder.
      $modalPopup = [
        '#theme' => 'openy_campaign_popup',
        '#form' => $this->formBuilder->getForm('Drupal\openy_campaign\Form\MemberLoginForm', $node),
      ];

      // Add an AJAX command to open a modal dialog with the form as the content.
      $response->addCommand(new OpenModalDialogCommand($this->t('Sign in'), $modalPopup, ['width' => '800']));

      return $response;
    }

    $response = $this->replaceLandingPageParagraph($landing_page_id);

    return $response;
  }

  /**
   * Show needed content on Campaign node
   *
   * @param int $landing_page_id Landing page node ID to get new content for replacement.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function showPageContent($landing_page_id) {

    return $this->replaceLandingPageParagraph($landing_page_id);
  }

  /**
   * Place new landing page paragraph instead of current one.
   *
   * @param int $landing_page_id Landing page node ID to get new content for replacement.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  private function replaceLandingPageParagraph($landing_page_id) {
    $response = new AjaxResponse();

    // Replace field_content in Landing page.
    $node = Node::load($landing_page_id);

    /** @var \Drupal\Core\Entity\EntityInterface $fieldEntity */
    $fieldEntity = $node->get('field_content')->entity;

    $viewBuilder = \Drupal::entityTypeManager()->getViewBuilder($fieldEntity->getEntityTypeId());
    $fieldView = $viewBuilder->view($fieldEntity, 'default');

    $renderField =  \Drupal::service('renderer')->renderRoot($fieldView);

    // TODO Remove one extra div after $renderField
    // Replace every paragraph with default view in field-content of landing page node.
    $replacementClass = '.' . $fieldEntity->getEntityTypeId() . '--view-mode--default';
    $response->addCommand(new ReplaceCommand('.node__content > .container .wrapper-field-content ' . $replacementClass, $renderField));

    // Set 'active' class to menu link.
    $response->addCommand(new InvokeCommand('.campaign-menu a', 'removeClass', ['active']));
    $response->addCommand(new InvokeCommand('.campaign-menu a.node-' . $landing_page_id, 'addClass', ['active']));

    return $response;
  }

}