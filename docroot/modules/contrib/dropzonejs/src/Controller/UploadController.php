<?php

namespace Drupal\dropzonejs\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\dropzonejs\UploadException;
use Drupal\dropzonejs\UploadHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Handles requests that dropzone issues when uploading files.
 */
class UploadController extends ControllerBase {

  /**
   * The upload handler service.
   *
   * @var \Drupal\dropzonejs\UploadHandlerInterface
   */
  protected $uploadHandler;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   *   The HTTP request object.
   */
  protected $request;

  /**
   * Constructs dropzone upload controller route controller.
   *
   * @param \Drupal\dropzonejs\UploadHandlerInterface $upload_handler
   *   Upload handler.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   */
  public function __construct(UploadHandlerInterface $upload_handler, Request $request) {
    $this->uploadHandler = $upload_handler;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('dropzonejs.upload_handler'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * Handles DropzoneJs uploads.
   */
  public function handleUploads() {
    $file = $this->request->files->get('file');
    if (!$file instanceof UploadedFile) {
      throw new AccessDeniedHttpException();
    }

    // @todo: Implement file_validate_size();
    try {
      // Return JSON-RPC response.
      return new AjaxResponse([
        'jsonrpc' => '2.0',
        'result' => basename($this->uploadHandler->handleUpload($file)),
        'id' => 'id',
      ]);
    }
    catch (UploadException $e) {
      return $e->getErrorResponse();
    }
  }

}
