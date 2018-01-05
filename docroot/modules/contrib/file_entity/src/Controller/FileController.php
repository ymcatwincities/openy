<?php

namespace Drupal\file_entity\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\file\FileInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FileController
 */
class FileController extends ControllerBase {

  /**
   * Upload
   */
  public function FileAddUpload() {

  }

  /**
   * File
   */
  public function FileAddUploadFile() {

  }

  /**
   * Usage
   *
   * @param $file
   */
  public function FileUsage($file) {
    //@TODO: File Usage here.
  }

  /**
   * Returns a HTTP response for a file being downloaded.
   *
   * @param FileInterface $file
   *   The file to download, as an entity.
   *
   * @return Response
   *   The file to download, as a response.
   */
  public function download(FileInterface $file) {
    // Ensure there is a valid token to download this file.
    if (!$this->config('file_entity.settings')->get('allow_insecure_download')) {
      if (!isset($_GET['token']) || $_GET['token'] !== $file->getDownloadToken()) {
        return new Response(t('Access to file @url denied', array('@url' => $file->getFileUri())), 403);
      }
    }

    $headers = array(
      'Content-Type' => Unicode::mimeHeaderEncode($file->getMimeType()),
      'Content-Disposition' => 'attachment; filename="' . Unicode::mimeHeaderEncode(drupal_basename($file->getFileUri())) . '"',
      'Content-Length' => $file->getSize(),
      'Content-Transfer-Encoding' => 'binary',
      'Pragma' => 'no-cache',
      'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
      'Expires' => '0',
    );

    // Let other modules alter the download headers.
    \Drupal::moduleHandler()->alter('file_download_headers', $headers, $file);

    // Let other modules know the file is being downloaded.
    \Drupal::moduleHandler()->invokeAll('file_transfer', array($file->getFileUri(), $headers));

    try {
      return new BinaryFileResponse($file->getFileUri(), 200, $headers);
    }
    catch (FileNotFoundException $e) {
      return new Response(t('File @uri not found', array('@uri' =>$file->getFileUri())), 404);
    }
  }

  /**
   * Return an Ajax dialog command for editing a file inline.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file being edited.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response with a command for opening or closing the a dialog
   *   containing the edit form.
   */
  public function inlineEdit(FileInterface $file) {
    // Build the file edit form.
    $form_object = $this->entityManager()->getFormObject('file', 'inline_edit');
    $form_object->setEntity($file);
    $form_state = (new FormState())
      ->setFormObject($form_object)
      ->disableRedirect();
    // Building the form also submits.
    $form = $this->formBuilder()->buildForm($form_object, $form_state);
    $dialog_selector = '#file-entity-inline-edit-' . $file->id();

    // Return a response, depending on whether it's successfully submitted.
    if (!$form_state->isExecuted()) {
      // Return the form as a modal dialog.
      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
      $title = $this->t('Edit file @file', ['@file' => $file->label()]);
      $response = AjaxResponse::create()->addCommand(new OpenDialogCommand($dialog_selector, $title, $form, ['width' => 800]));
      return $response;
    }
    else {
      // Return command for closing the modal.
      return AjaxResponse::create()->addCommand(new CloseDialogCommand($dialog_selector));
    }
  }
}
