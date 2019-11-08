<?php

namespace Drupal\openy_focal_point\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\focal_point\Controller\FocalPointPreviewController;
use Drupal\focal_point\Plugin\Field\FieldWidget\FocalPointImageWidget;
use Drupal\image\Entity\ImageStyle;
use Drupal\file\Entity\File;
use Drupal\Core\Session\AccountInterface;
use Drupal\openy_focal_point\Form\OpenYFocalPointCropForm;
use Drupal\openy_focal_point\Form\OpenYFocalPointEditForm;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Image\ImageFactory;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class OpenYFocalPointPreviewController. We display only styles that are
 * going to be used by the formatter instead of all styles that use focal_point.
 *
 * @package Drupal\focal_point\Controller
 */
class OpenYFocalPointPreviewController extends FocalPointPreviewController {

  public function getFocalPointImageStyles() {
    $styles = explode(':', $this->request->get('image_styles'));
    return $this->entityTypeManager()->getStorage('image_style')->loadMultiple($styles);
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
    $parameters = $this->request->attributes->all();
    // This means dynamic focal_point_value passed as 3rd argument.
    if (!strstr('field_', $parameters['field_name'])) {
      $focal_point_value = $parameters['field_name'];
    }
    $file = $this->fileStorage->load($fid);
    $image = $this->imageFactory->get($file->getFileUri());
    if (!$image->isValid()) {
      throw new InvalidArgumentException('The file with id = $fid is not an image.');
    }

    $styles = $this->getFocalPointImageStyles();

    // Since we are about to create a new preview of this image, we first must
    // flush the old one. This should not be a performance hit since there is
    // no good reason for anyone to preview an image unless they are changing
    // the focal point value.
    image_path_flush($image->getSource());

    $form = \Drupal::formBuilder()->getForm($form_class_name, $file, $styles, $focal_point_value);
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
