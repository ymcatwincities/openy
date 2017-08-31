<?php

namespace Drupal\openy_campaign\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\node\Entity\Node;
use Drupal\openy_campaign\Entity\MemberCampaign;

/**
 * Class CampaignController.
 */
class CampaignController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The CampaignController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   */
  public function __construct(FormBuilder $formBuilder, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    $this->formBuilder = $formBuilder;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
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
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * Render My progress tab content on Campaign node
   *
   * @param int $campaign_id Node ID of the current campaign.
   * @param int $landing_page_id Landing page node ID to get new content for replacement.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function showMyProgressContent($campaign_id, $landing_page_id) {
    $response = new AjaxResponse();

    // Show modal popup if user is not logged in.
    if (!MemberCampaign::isLoggedIn($campaign_id)) {
      // Get the modal form using the form builder.
      $modalPopup = [
        '#theme' => 'openy_campaign_popup',
        '#form' => $this->formBuilder->getForm('Drupal\openy_campaign\Form\MemberLoginForm', $campaign_id),
      ];

      $options = [
        'width' => '800',
      ];
      // Add an AJAX command to open a modal dialog with the form as the content.
      $response->addCommand(new OpenModalDialogCommand($this->t('Sign in'), $modalPopup, $options));

      return $response;
    }

    $response = $this->replaceLandingPageParagraph($landing_page_id);

    return $response;
  }

  /**
   * Show needed content on Campaign node
   *
   * @param int $campaign_id Node ID of the current campaign.
   * @param int $landing_page_id Landing page node ID to get new content for replacement.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function showPageContent($campaign_id, $landing_page_id) {

    return $this->replaceLandingPageParagraph($landing_page_id);
  }

  /**
   * Place new landing page Content area paragraphs instead of current ones.
   *
   * @param int $landing_page_id Landing page node ID to get new content for replacement.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  private function replaceLandingPageParagraph($landing_page_id) {
    $response = new AjaxResponse();

    /** @var Node $node New landing page node to replace. */
    $node = Node::load($landing_page_id);

    $fieldsView = [];
    foreach ($node->field_content as $item) {
      /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
      $paragraph = $item->entity;
      $viewBuilder = $this->entityTypeManager->getViewBuilder($paragraph->getEntityTypeId());
      $fieldsView[] = $viewBuilder->view($paragraph, 'default');
    }
    $fieldsRender = '<section class="wrapper-field-content">' . $this->renderer->renderRoot($fieldsView) . '</section>';

    // Replace Content area of current landing page with all paragraphs from field-content of new landing page node.
    $response->addCommand(new ReplaceCommand('.node__content > .container .wrapper-field-content', $fieldsRender));

    // Set 'active' class to menu link.
    $response->addCommand(new InvokeCommand('.campaign-menu a', 'removeClass', ['active']));
    $response->addCommand(new InvokeCommand('.campaign-menu a.node-' . $landing_page_id, 'addClass', ['active']));

    return $response;
  }

}