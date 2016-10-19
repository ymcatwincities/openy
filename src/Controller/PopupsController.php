<?php
/**
 * @file
 * Contains \Drupal\ygs_popups\Controller\PopupsController.
 */

namespace Drupal\ygs_popups\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\NodeInterface;
use Drupal\ygs_popups\Form\BranchesForm;
use Drupal\ygs_popups\Form\ClassBranchesForm;
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
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse Response.
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
   * @param string $js
   *   Nojs|ajax.
   * @param \Drupal\node\NodeInterface $node
   *   A node object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse Response.
   *   Response.
   */
  public function classBranchPopup(NodeInterface $node, $js = 'nojs') {
    if ($js == 'ajax') {
      $response = new AjaxResponse();
      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
      $response->setAttachments($form['#attached']);
      $modal = new OpenModalDialogCommand(t('Select location'), $this->buildPopupContent($node), $this->popupOptions());
      $response->addCommand($modal);
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
    return array(
      'dialogClass' => 'branch-popup',
      'width' => '50%',
      'closeOnEscape' => TRUE,
      'autoOpen' => TRUE,
    );
  }

  /**
   * Popup Content.
   */
  public function buildPopupContent($node = FALSE) {
    $destination = isset($_REQUEST['destination']) ? $_REQUEST['destination'] : '';
    $config = \Drupal::config('ygs_popups.settings');
    $img_src = '';
    if ($config->get('img')) {
      $file = File::load($config->get('img'));
      $img_src = ImageStyle::load('locations_popup')->buildUrl($file->getFileUri());
    }
    if ($node) {
      $form = \Drupal::formBuilder()->getForm(ClassBranchesForm::class, $node, $destination);
    }
    else {
      $form = \Drupal::formBuilder()->getForm(BranchesForm::class, $destination);
    }

    $content = [
      '#theme' => 'ygs_popup_content',
      '#image' => $img_src,
      '#description' => $config->get('description'),
      '#form' => $form,
    ];
    return $content;
  }

}
