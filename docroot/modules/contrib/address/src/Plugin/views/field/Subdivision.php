<?php

namespace Drupal\address\Plugin\views\field;

use CommerceGuys\Addressing\LocaleHelper;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the subdivision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("subdivision")
 */
class Subdivision extends FieldPluginBase {

  /**
   * The subdivision repository.
   *
   * @var \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface
   */
  protected $subdivisionRepository;

  /**
   * Constructs a Subdivision object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The id of the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface $subdivision_repository
   *   The subdivision repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SubdivisionRepositoryInterface $subdivision_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->subdivisionRepository = $subdivision_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('address.subdivision_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    if (empty($value)) {
      return '';
    }

    $entity = $this->getEntity($values);
    /** @var \Drupal\address\AddressInterface $address */
    $address = $entity->{$this->definition['field_name']}->first();
    switch ($this->definition['property']) {
      case 'administrative_area':
        $code = $address->getAdministrativeArea();
        $parents = [
          $address->getCountryCode(),
        ];
        break;

      case 'locality':
        $code = $address->getLocality();
        $parents = [
          $address->getCountryCode(),
          $address->getAdministrativeArea(),
        ];
        break;

      case 'dependent_locality':
        $code = $address->getDependentLocality();
        $parents = [
          $address->getCountryCode(),
          $address->getAdministrativeArea(),
          $address->getLocality(),
        ];
        break;
    }
    /** @var \CommerceGuys\Addressing\Subdivision\Subdivision $subdivision */
    $subdivision = $this->subdivisionRepository->get($code, $parents);
    // @todo Allow a choice between subdivision code and name.
    if ($subdivision) {
      $use_local_name = LocaleHelper::match($address->getLocale(), $subdivision->getLocale());
      $value = $use_local_name ? $subdivision->getLocalName() : $subdivision->getName();
    }

    return $this->sanitizeValue($value);
  }

}
