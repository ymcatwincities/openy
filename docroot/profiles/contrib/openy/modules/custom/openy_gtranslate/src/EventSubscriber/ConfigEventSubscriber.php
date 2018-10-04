<?php

namespace Drupal\openy_gtranslate\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Database\Connection;

/**
 * Class ConfigEventsSubscriber to react on changing default theme.
 */
class ConfigEventSubscriber implements EventSubscriberInterface {

  /**
   * The Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * ConfigEventSubscriber constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    return [
      ConfigEvents::SAVE => 'configSave',
    ];
  }

  /**
   * React to a config object being saved.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   Config crud event.
   */
  public function configSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    $original = $config->getOriginal();
    // Skip new and not theme config.
    if (empty($original) || ($config->getName() !== 'system.theme')) {
      return;
    }
    $originalTheme = $original['default'];
    $updatedTheme = $config->get('default');

    // Act only with default theme changes.
    if ($originalTheme == $updatedTheme || !in_array($updatedTheme, ['openy_rose', 'openy_lily'])) {
      return;
    }

    // Delete previous theme menu link.
    $originalMenuName = ($originalTheme == 'openy_lily') ? 'main' : 'account';
    $query = $this->connection->select('menu_link_content_data', 'm');
    $query->condition('bundle', 'menu_link_content');
    $query->condition('enabled', 1);
    $query->condition('m.menu_name', $originalMenuName);
    $query->condition('title', "%Language%", 'LIKE');
    $query->condition('link__options', "%language%", 'LIKE');
    $query->fields('m', ['id']);
    $res = $query->execute()->fetchField();
    if (!empty($res)) {
      $menuLink = MenuLinkContent::load($res);
      $menuLink->delete();
    }

    // Create new menu link.
    $updatedMenuName = ($updatedTheme == 'openy_lily') ? 'main' : 'account';
    $menuLink = MenuLinkContent::create([
      'title' => t('Language'),
      'link' => [
        'uri' => 'internal:/',
        'options' => [
          'attributes' => [
            'class' => ['language hidden-md hidden-lg d-block d-md-none'],
          ],
        ],
      ],
      'menu_name' => $updatedMenuName,
      'weight' => 50,
    ]);
    $menuLink->save();
  }

}
