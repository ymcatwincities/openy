<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Utils;

/**
 * Trait LogicalAssertion.
 *
 * @package Drupal\TqExtension\Utils
 */
trait LogicalAssertion
{
    /**
     * @param mixed $value
     *   Value to check.
     * @param bool $negate
     *   Negate the condition.
     *
     * @return int
     *   - 0: Everything is fine.
     *   - 1: Value is found, but should not be.
     *   - 2: Value is not found, but should be.
     */
    public static function assertion($value, $negate)
    {
        $negate = (bool) $negate;

        if ($value) {
            if ($negate) {
                return 1;
            }
        } else {
            if (!$negate) {
                return 2;
            }
        }

        return 0;
    }
}
