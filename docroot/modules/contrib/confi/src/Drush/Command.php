<?php

namespace Drupal\config_import\Drush;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;

/**
 * Class Command.
 *
 * @package Drupal\config_import\Drush
 */
class Command {

  /**
   * Command definition.
   *
   * @var array
   */
  private $info = [];
  /**
   * Command alias.
   *
   * @var string
   */
  private $alias = '';

  /**
   * Command constructor.
   *
   * @param string $alias
   *   Alias of the command.
   * @param array $info
   *   List of properties for command definition.
   *
   * @see hook_drush_command()
   */
  public function __construct($alias, array $info) {
    $this->info = $info;
    $this->alias = $alias;

    $this->info['class'] = static::class;
    $this->info['aliases'] = [$this->alias];

    $this->options();
    $this->examples();
  }

  /**
   * Returns definition of a command.
   *
   * @return array
   *   Definition of Drush command.
   */
  public function getDefinition() {
    return $this->info;
  }

  /**
   * Filter the options list for specific command.
   */
  protected function options() {
    $this->info += [__FUNCTION__ => []];

    // A list of all possible options for any command of "config_import" group.
    // Execute the "drush help --filter=config_import" to see them all.
    $options = [
      'type' => [
        'example-value' => 'menu',
        'description' => dt('Config type.'),
      ],
      'name' => [
        'example-value' => 'main',
        'description' => dt('Config name.'),
      ],
      'destination' => [
        'example-value' => 'sites/default/config/prod',
        'description' => dt('Destination path to put configuration file to. Name of configuration folder can be used as well.'),
      ],
    ];

    foreach ($this->info[__FUNCTION__] as $option => $is_required) {
      // Add an option to the command if it's specified.
      if (isset($options[$option])) {
        $this->info[__FUNCTION__][$option] = $options[$option] + ['required' => $is_required];
      }
    }
  }

  /**
   * Add prefix for every example.
   */
  protected function examples() {
    $this->info += [__FUNCTION__ => []];

    foreach ($this->info[__FUNCTION__] as $example => $description) {
      $this->info[__FUNCTION__][sprintf('drush %s %s', $this->alias, $example)] = $description;
      unset($this->info[__FUNCTION__][$example]);
    }
  }

  /**
   * Process a list of options for the current command.
   *
   * @param array $options
   *   An associative array of options for the current command.
   *
   * @return array
   *   Processed options list.
   *
   * @see _drush_config_import_get_options()
   */
  public static function processOptions(array $options) {
    if (!empty($options['type'])) {
      $options['prefix'] = static::getConfigPrefix($options['type']);

      // The "name" option depends on "type" option.
      if (!empty($options['name'])) {
        $options['name'] = $options['prefix'] . $options['name'];

        // The "destination" option depends on both of above.
        if (!empty($options['destination'])) {
          $options['destination'] = static::getDestination($options['destination'], $options['name']);
        }
      }
    }

    return $options;
  }

  /**
   * Returns prefix for the type of configuration.
   *
   * @param string $type
   *   Type of configuration.
   *
   * @return string
   *   Prefix for the type of configuration.
   */
  protected static function getConfigPrefix($type) {
    if ('system.simple' === $type) {
      return '';
    }

    // If type does not exists an exception will be thrown.
    $definition = \Drupal::entityTypeManager()->getDefinition($type);

    // For instance, the entities of "user" type cannot be exported.
    if (!($definition instanceof ConfigEntityTypeInterface)) {
      throw new \RuntimeException(dt('Export is available only for entities of "@type" type.', [
        '@type' => ConfigEntityTypeInterface::class,
      ]));
    }

    return $definition->getConfigPrefix() . '.';
  }

  /**
   * Returns a path to configuration file.
   *
   * @param string $destination
   *   Path to directory or the name of configurations directory.
   * @param string $name
   *   The name of file.
   *
   * @return string
   *   Path to the configuration file.
   */
  protected static function getDestination($destination, $name) {
    $destination = rtrim($destination, '/');

    if (!is_dir($destination)) {
      // Exception will be thrown if directory cannot be determined by type.
      $destination = config_get_config_directory($destination);
    }

    if (!is_dir($destination)) {
      throw new \RuntimeException(dt('Destination directory "@destination" does not exists!', [
        '@destination' => $destination,
      ]));
    }

    return "$destination/$name.yml";
  }

}
