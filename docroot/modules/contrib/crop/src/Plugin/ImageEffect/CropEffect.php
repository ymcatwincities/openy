<?php

/**
 * @file
 * Contains \Drupal\crop\Plugin\ImageEffect\CropEffect.
 */

namespace Drupal\crop\Plugin\ImageEffect;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\crop\CropStorageInterface;
use Drupal\image\ConfigurableImageEffectBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Crops an image resource.
 *
 * @ImageEffect(
 *   id = "crop_crop",
 *   label = @Translation("Manual crop"),
 *   description = @Translation("Applies manually provided crop to the image.")
 * )
 */
class CropEffect extends ConfigurableImageEffectBase implements ContainerFactoryPluginInterface {

  /**
   * Crop entity storage.
   *
   * @var \Drupal\crop\CropStorageInterface
   */
  protected $storage;

  /**
   * Crop type entity storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $typeStorage;

  /**
   * Crop entity.
   *
   * @var \Drupal\crop\CropInterface
   */
  protected $crop;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, CropStorageInterface $crop_storage, ConfigEntityStorageInterface $crop_type_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);
    $this->storage = $crop_storage;
    $this->typeStorage = $crop_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('image'),
      $container->get('entity.manager')->getStorage('crop'),
      $container->get('entity.manager')->getStorage('crop_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    if (empty($this->configuration['crop_type']) || !$this->typeStorage->load($this->configuration['crop_type'])) {
      $this->logger->error('Manual image crop failed due to misconfigured crop type on %path.', ['%path' => $image->getSource()]);
      return FALSE;
    }

    if ($crop = $this->getCrop($image)) {
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

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = [
      '#theme' => 'crop_crop_summary',
      '#data' => $this->configuration,
    ];
    $summary += parent::getSummary();

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'crop_type' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];
    foreach ($this->typeStorage->loadMultiple() as $type) {
      $options[$type->id()] = $type->label();
    }

    $form['crop_type'] = [
      '#type' => 'select',
      '#title' => t('Crop type'),
      '#default_value' => $this->configuration['crop_type'],
      '#options' => $options,
      '#description' => t('Crop type to be used for the image style.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['crop_type'] = $form_state->getValue('crop_type');
  }

  /**
   * Gets crop entity for the image.
   *
   * @param ImageInterface $image
   *   Image object.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\crop\CropInterface|FALSE
   *   Crop entity or FALSE if crop doesn't exist.
   */
  protected function getCrop(ImageInterface $image) {
    if (!isset($this->crop)) {
      $this->crop = FALSE;

      $id = $this->storage->getQuery()
        ->condition('uri', $image->getSource())
        ->condition('type', $this->configuration['crop_type'])
        ->sort('cid')
        ->range(0, 1)
        ->execute();

      if (!empty($id) && ($crop = $this->storage->load(current($id)))) {
        $this->crop = $crop;
      }
    }

    return $this->crop;
  }

}
