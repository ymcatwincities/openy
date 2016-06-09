<?php

/**
 * @file
 * Contains \Drupal\entity_browser\Permissions.
 */

namespace Drupal\entity_browser;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates routes for entity browsers.
 */
class Permissions implements ContainerInjectionInterface {

  /**
   * The entity browser storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $browserStorage;

  /**
   * Translation manager service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;

  /**
   * Constructs Permissions object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   Entity manager service.
   * @param \Drupal\Core\StringTranslation\TranslationManager $translation
   */
  public function __construct(EntityManagerInterface $entity_manager, TranslationManager $translation) {
    $this->browserStorage = $entity_manager->getStorage('entity_browser');
    $this->translationManager = $translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * Dynamically set permissions for entity browsers with routes.
   */
  public function permissions() {
    $permissions = [];
    /** @var \Drupal\entity_browser\EntityBrowserInterface[] $browsers */
    $browsers = $this->browserStorage->loadMultiple();

    foreach ($browsers as $browser) {
      if ($browser->route()) {
        $permissions['access ' . $browser->id() . ' entity browser pages'] = array(
          'title' => $this->translationManager->translate('Access @name pages', array('@name' => $browser->label())),
          'description' => $this->translationManager->translate('Access pages that %browser uses to operate.', array('%browser' => $browser->label())),
        );
      }
    }

    return $permissions;
  }

}
