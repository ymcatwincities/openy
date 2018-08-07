<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Utils;

use Drupal\TqExtension\Cores\DrupalKernelPlaceholder;

/**
 * Trait BaseEntity.
 *
 * @package Drupal\TqExtension\Utils
 */
trait BaseEntity
{
    /**
     * @return string
     *   An entity type.
     */
    abstract protected function entityType();

    /**
     * @param string $argument1
     *   An argument to clarify the results.
     * @param string $argument2
     *   An argument to clarify the results.
     *
     * @return int
     *   An ID of the entity.
     */
    abstract public function getIdByArguments($argument1, $argument2);

    /**
     * Get identifier of current entity.
     *
     * @return int
     */
    public function getCurrentId()
    {
        // We have programmatically bootstrapped Drupal core, so able to use such functionality.
        $args = DrupalKernelPlaceholder::arg();

        return count($args) > 1 && $this->entityType() === $args[0] && $args[1] > 0 ? (int) $args[1] : 0;
    }

    /**
     * @param string $operation
     *   Allowable values: "edit", "view", "visit".
     * @param string $argument1
     *   An argument to clarify the result.
     * @param string $argument2
     *   An argument to clarify the result.
     *
     * @return string
     *   Entity URL.
     */
    public function entityUrl($operation, $argument1 = '', $argument2 = '')
    {
        // Drupal 8 don't have the "entity/{id}/view" local action instead of Drupal 7. So,
        // to support both of versions, assume that "entity/{id}" is correct for "view" operation
        // in each case.
        // @todo Maybe we should use "DRUPAL_CORE > 7 ? '' : 'view'"?
        if (in_array($operation, ['visit', 'view'])) {
            $operation = '';
        } else {
            $operation = "/$operation";
        }

        // An empty string could be passed when currently viewing entity expected.
        $id = '' === $argument1 ? $this->getCurrentId() : $this->getIdByArguments($argument1, $argument2);

        if (0 === $id) {
            throw new \RuntimeException('An ID cannot be zero.');
        }

        return $this->entityType() . "/$id$operation";
    }
}
