<?php

namespace Drupal\openy_focal_point\Plugin\ImageEffect;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Image\ImageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\crop\CropInterface;
use Drupal\crop\CropStorageInterface;
use Drupal\crop\Entity\Crop;
use Drupal\crop\Plugin\ImageEffect\CropEffect;
use Drupal\image\ConfigurableImageEffectBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Crops an image resource.
 *
 * @ImageEffect(
 *   id = "openy_crop_crop",
 *   label = @Translation("OpenY Manual crop"),
 *   description = @Translation("Applies manually provided crop to the image.")
 * )
 */
class OpenYCropEffect extends CropEffect {


  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    if (empty($this->configuration['crop_type']) || !$this->typeStorage->load($this->configuration['crop_type'])) {
      $this->logger->error('Manual image crop failed due to misconfigured crop type on %path.', ['%path' => $image->getSource()]);
      return FALSE;
    }

    if ($crop = $this->getCrop($image)) {

      $image->stopPropagation = TRUE;

      $anchor = $crop->anchor();
      $size = $crop->size();

      if (!$image->crop($anchor['x'], $anchor['y'], $size['width'], $size['height'])) {
        $this->logger->error('Manual image crop failed using the %toolkit toolkit on %path (%mimetype, %width x %height)', [
          '%toolkit' => $image->getToolkitId(),
          '%path' => $image->getSource(),
          '%mimetype' => $image->getMimeType(),
          '%width' => $image->getWidth(),
          '%height' => $image->getHeight(),
        ]
        );
        return FALSE;
      }
    }

    return TRUE;
  }


}
