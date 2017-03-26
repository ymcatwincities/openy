<?php

namespace Drupal\openy_socrates;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

/**
 * Openy Socrates Service Provider.
 */
class OpenySocratesServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // Add a compiler pass for adding openy_data_service tag handling.
    $container->addCompilerPass(new OpenySocratesCompilerPass());
  }

}
