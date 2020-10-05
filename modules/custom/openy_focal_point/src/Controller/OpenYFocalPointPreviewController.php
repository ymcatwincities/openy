<?php

namespace Drupal\openy_focal_point\Controller;

use Drupal\focal_point\Controller\FocalPointPreviewController;
use Drupal\openy_focal_point\Form\OpenYFocalPointCropForm;
use Drupal\openy_focal_point\Form\OpenYFocalPointEditForm;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;

/**
 * Class OpenYFocalPointPreviewController. We display only styles that are
 * going to be used by the formatter instead of all styles that use focal_point.
 *
 * @package Drupal\focal_point\Controller
 */
class OpenYFocalPointPreviewController extends FocalPointPreviewController {

  public function getFocalPointImageStyle() {
    $style = $this->request->get('image_style');
    return $this->entityTypeManager()->getStorage('image_style')->load($style);
  }

  /**
   * Callback to edit Manual Crop.
   */
  public function editCropContent($fid, $focal_point_value) {
    return $this->renderFormInDialog($fid, $focal_point_value, OpenYFocalPointCropForm::class, $this->t('Manual Crop'));
  }

  /**
   * Callback to set Focal Point.
   */
  public function editFocalPointContent($fid, $focal_point_value) {
    return $this->renderFormInDialog($fid, $focal_point_value, OpenYFocalPointEditForm::class, $this->t('Edit Focal Point'));
  }

  /**
   * Prepare ajax command displaying dialog with form.
   */
  protected function renderFormInDialog($fid, $focal_point_value, $form_class_name, $dialog_title) {
    $file = $this->fileStorage->load($fid);
    $image = $this->imageFactory->get($file->getFileUri());
    if (!$image->isValid()) {
      throw new InvalidArgumentException('The file with id = $fid is not an image.');
    }

    $style = $this->getFocalPointImageStyle();

    // Since we are about to create a new preview of this image, we first must
    // flush the old one. This should not be a performance hit since there is
    // no good reason for anyone to preview an image unless they are changing
    // the focal point value.
    image_path_flush($image->getSource());

    $form = \Drupal::formBuilder()->getForm($form_class_name, $file, $style, $focal_point_value);
    $html = render($form);

    $options = [
      'dialogClass' => 'popup-dialog-class',
      'width' => '80%',
    ];
    $response = new AjaxResponse();
    $response->addCommand(
      new OpenModalDialogCommand($dialog_title, $html, $options)
    );

    return $response;
  }

}
