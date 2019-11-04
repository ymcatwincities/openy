<?php

namespace Drupal\openy_focal_point\Plugin\ImageEffect;

use Drupal\focal_point\FocalPointEffectBase;
use Drupal\Core\Image\ImageInterface;
use Drupal\focal_point\Plugin\ImageEffect\FocalPointScaleAndCropImageEffect;

/**
 * Scales and crops image while keeping its focal point close to centered.
 *
 * @ImageEffect(
 *   id = "openy_focal_point_scale_and_crop",
 *   label = @Translation("OpenY Focal Point Scale and Crop"),
 *   description = @Translation("Scales and crops image while keeping its focal point close to centered.")
 * )
 */
class OpenYFocalPointScaleAndCropImageEffect extends FocalPointScaleAndCropImageEffect {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    if (isset($image->stopPropagation)) {
      return;
    }
    return parent::applyEffect($image);
  }

}
