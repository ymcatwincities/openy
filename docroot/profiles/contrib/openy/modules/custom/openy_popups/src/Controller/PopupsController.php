<?php

namespace Drupal\openy_popups\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CommandInterface;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\NodeInterface;
use Drupal\openy_popups\Form\BranchesForm;
use Drupal\openy_popups\Form\ClassBranchesForm;
use Drupal\file\Entity\File;

/**
 * {@inheritdoc}
 */
class PopupsController extends ControllerBase {

  /**
   * Branch Popup.
   *
   * @param string $js
   *   Nojs|ajax.
   *
   * @return AjaxResponse
   *   Response.
   */
  public function branchPopup($js = 'nojs') {
    if ($js == 'ajax') {
      $response = new AjaxResponse();
      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
      $response->setAttachments($form['#attached']);
      $modal = new OpenModalDialogCommand(t('Select location'), $this->buildPopupContent(FALSE), $this->popupOptions());
      $response->addCommand($modal);
      return $response;
    }
    else {
      return $this->redirect('user.page');
    }
  }

  /**
   * ClassBranch Popup.
   *
   * @param NodeInterface $node
   *   A node object.
   * @param string $js
   *   Nojs|ajax.
   *
   * @return AjaxResponse
   *   Response.
   */
  public function classBranchPopup(NodeInterface $node, $js = 'nojs') {
    if ($js == 'ajax') {
      $response = new AjaxResponse();
      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
      $response->setAttachments($form['#attached']);
      $content = $this->buildPopupContent($node);
      if ($content instanceof CommandInterface) {
        $response->addCommand($content);
      }
      else {
        $modal = new OpenModalDialogCommand(t('Select location'), $content, $this->popupOptions());
        $response->addCommand($modal);
      }
      return $response;
    }
    else {
      return $this->redirect('user.page');
    }
  }

  /**
   * Popup Options.
   */
  public function popupOptions() {
    return [
      'dialogClass' => 'branch-popup',
      'width' => '65%',
      'closeOnEscape' => FALSE,
      'autoOpen' => TRUE,
      'position' => [
        'my' => 'center center',
        'at' => 'center center',
      ],
    ];
  }

  /**
   * Popup Content.
   */
  public function buildPopupContent($node = FALSE) {
    $destination = isset($_REQUEST['destination']) ? $_REQUEST['destination'] : '';

    if ($node) {
      // Check special cases (0 or 1 available location).
      $locations = ClassBranchesForm::getBranchesList($node);
      $locations = $locations['branch'] + $locations['camp'];
      if (!$locations) {
        // Show 'Class unavailable' popup.
        return new InvokeCommand('#class-expired-modal', 'modal', ['show']);
      }
      elseif (count($locations) == 1) {
        // Automatically select the only available location.
        $nid = array_keys($locations)[0];
        $event_params = [
          'location' => $nid,
          'only' => TRUE,
        ];
        $params = ['location-changed', [$event_params]];
        return new InvokeCommand('body', 'trigger', $params);
      }
      $form = \Drupal::formBuilder()->getForm(ClassBranchesForm::class, $node, $destination);
    }
    else {
      $form = \Drupal::formBuilder()->getForm(BranchesForm::class, $destination);
    }

    $config = \Drupal::config('openy_popups.settings');
    $img_src = '';
    if ($config->get('img')) {
      $file = File::load($config->get('img'));
      $img_src = ImageStyle::load('locations_popup')->buildUrl($file->getFileUri());
    }

    return [
      '#theme' => 'openy_popup',
      '#image' => $img_src,
      '#description' => $config->get('description'),
      '#form' => $form,
    ];
  }

}
