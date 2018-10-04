<?php

namespace Drupal\fontyourface\Controller;

use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\fontyourface\Entity\Font;

/**
 * Controller routines for forum routes.
 */
class FontYourFaceController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function activateFont(Font $font, $js) {
    try {
      $font->activate();
      if ($js == 'ajax') {
        $url = Url::fromRoute('entity.font.deactivate', ['js' => 'nojs', 'font' => $font->id()], ['query' => \Drupal::destination()->getAsArray()]);
        $url->setOptions(['attributes' => ['id' => 'font-status-' . $font->id(), 'class' => ['font-status', 'enabled', 'use-ajax']]]);
        $text = $this->t('Enable');
        $link = \Drupal::l($text, $url);

        $response = new AjaxResponse();
        return $response->addCommand(new ReplaceCommand('#font-status-' . $font->id(), $link));
      }
      else {
        drupal_set_message($this->t('Font @font successfully enabled', ['@font' => $font->name->value]));
        return $this->redirect('entity.font.collection');
      }
    }
    catch (Exception $e) {
      $error = $e->getMessage();
      if ($js == 'ajax') {
        return new AjaxResponse([
          'response' => TRUE,
          'message' => $error,
        ], 503);
      }
      else {
        drupal_set_message($error, 'error');
        return $this->redirect('entity.font.collection');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deactivateFont(Font $font, $js) {
    try {
      $font->deactivate();
      if ($js == 'ajax') {
        $url = Url::fromRoute('entity.font.activate', ['js' => 'nojs', 'font' => $font->id()], ['query' => \Drupal::destination()->getAsArray()]);
        $url->setOptions(['attributes' => ['id' => 'font-status-' . $font->id(), 'class' => ['font-status', 'disabled', 'use-ajax']]]);
        $text = $this->t('Enable');
        $link = \Drupal::l($text, $url);

        $response = new AjaxResponse();
        return $response->addCommand(new ReplaceCommand('#font-status-' . $font->id(), $link));
      }
      else {
        drupal_set_message($this->t('Font @font successfully disabled', ['@font' => $font->name->value]));
        return $this->redirect('entity.font.collection');
      }
    }
    catch (Exception $e) {
      $error = $e->getMessage();
      if ($js == 'ajax') {
        return new AjaxResponse([
          'response' => TRUE,
          'message' => $error,
        ], 503);
      }
      else {
        drupal_set_message($error, 'error');
        return $this->redirect('entity.font.collection');
      }
    }
  }

}
