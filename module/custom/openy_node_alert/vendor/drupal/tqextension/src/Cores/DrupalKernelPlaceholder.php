<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Cores;

use Behat\Behat\Hook\Scope\BeforeFeatureScope;
use Drupal\TqExtension\Context\TqContext;

class DrupalKernelPlaceholder
{
    /**
     * Version-related implementation of @BeforeFeature hook for TqContext.
     *
     * @param BeforeFeatureScope $scope
     *
     * @see TqContext::beforeFeature()
     */
    public static function beforeFeature(BeforeFeatureScope $scope)
    {
        self::requireContext(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $string
     * @param array $arguments
     * @param array $options
     *
     * @return string
     */
    public static function t($string, array $arguments = [], array $options = [])
    {
        return self::requireContext(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $string
     * @param array $arguments
     *
     * @return string
     */
    public static function formatString($string, array $arguments = [])
    {
        return self::requireContext(__FUNCTION__, func_get_args());
    }

    /**
     * @return string[]
     */
    public static function arg()
    {
        return self::requireContext(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $text
     *   Input text.
     * @param array $data
     *   Data for replacements.
     * @param array $options
     *   Replacement configuration.
     *
     * @return string
     *   Processed text.
     */
    public static function tokenReplace($text, array $data = [], array $options = [])
    {
        return self::requireContext(__FUNCTION__, func_get_args());
    }

    /**
     * @param mixed $data
     *
     * @return string
     */
    public static function jsonEncode($data)
    {
        return self::requireContext(__FUNCTION__, func_get_args());
    }

    /**
     * @return object
     */
    public static function getCurrentUser()
    {
        return self::requireContext(__FUNCTION__, func_get_args());
    }

    public static function setCurrentUser($user)
    {
        self::requireContext(__FUNCTION__, func_get_args());
    }

    public static function setCurrentPath($path)
    {
        self::requireContext(__FUNCTION__, func_get_args());
    }

    /**
     * Locate user ID by its name.
     *
     * @param string $username
     *
     * @return int
     */
    public static function getUidByName($username)
    {
        return self::requireContext(__FUNCTION__, func_get_args());
    }

    /**
     * @param int $user_id
     */
    public static function deleteUser($user_id)
    {
        self::requireContext(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $table
     * @param string $alias
     * @param array $options
     *
     * @return object
     */
    public static function selectQuery($table, $alias = null, array $options = [])
    {
        return self::requireContext(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $entityType
     * @param string $bundle
     *
     * @return array[]
     *   An associative array where key - machine-name of a field and
     *   value - an array with two keys: "label" and "required".
     */
    public static function getFieldDefinitions($entityType, $bundle)
    {
        return self::requireContext(__FUNCTION__, func_get_args());
    }

    /**
     * Get information about database connections.
     *
     * Impossible to use $GLOBALS['databases'] in Drupal 8 since {@link https://www.drupal.org/node/2176621}.
     *
     * @param string $connection
     *   Connection name.
     *
     * @return array[]
     */
    public static function getDatabaseConnectionInfo($connection)
    {
        return (array) self::requireContext(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $entityType
     *   The type of entity.
     * @param array $values
     *   Values for entity creation.
     *
     * @return string[]
     *   List with three items in order: entity ID, type and bundle.
     */
    public static function entityCreate($entityType, array $values)
    {
        return self::requireContext(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $entityType
     *   The type of entity.
     * @param int $id
     *
     * @return object|null
     */
    public static function entityLoad($entityType, $id)
    {
        return self::requireContext(__FUNCTION__, func_get_args());
    }

    /**
     * @param object $entity
     * @param string $fieldName
     *
     * @return bool
     */
    public static function entityHasField($entity, $fieldName)
    {
        return self::requireContext(__FUNCTION__, func_get_args());
    }

    /**
     * @param object $entity
     * @param string $fieldName
     *
     * @return mixed
     */
    public static function entityFieldValue($entity, $fieldName)
    {
        return self::requireContext(__FUNCTION__, func_get_args());
    }

    /**
     * Switching the mail system.
     *
     * @param bool $useTesting
     *   Whether testing or standard mail system should be used.
     */
    public static function switchMailSystem($useTesting)
    {
        self::requireContext(__FUNCTION__, func_get_args());
    }

    /**
     * Get a list of emails, collected by testing mail system.
     *
     * @return array
     */
    public static function getEmailMessages()
    {
        return self::requireContext(__FUNCTION__, func_get_args());
    }

    /**
     * Check existence of the content type by its machine name or title.
     *
     * @param string $contentType
     *   Machine name or title of the content type.
     *
     * @return string
     *   Machine name.
     */
    public static function getContentTypeName($contentType)
    {
        return self::requireContext(__FUNCTION__, func_get_args());
    }

    /**
     * @param int $count
     *   Number of entries to fetch.
     * @param string[] $types
     *   Any set of Watchdog error types.
     *
     * @return array[]
     */
    public static function getWatchdogStackTrace($count = 10, array $types = ['php'])
    {
        return array_map('unserialize', static::selectQuery('watchdog', 'w')
            ->fields('w', ['variables'])
            ->condition('type', $types, 'IN')
            ->range(0, $count)
            ->execute()
            ->fetchCol());
    }

    /**
     * @param string $file
     *   Existing file from "src/JavaScript" without ".js" extension.
     * @param bool $delete
     *   Whether injection should be deleted.
     */
    final public static function injectCustomJavascript($file, $delete = false)
    {
        // Append extension.
        $file .= '.js';
        // Do manipulations with "system" module.
        $moduleName = 'system';
        // Get the relative path to module.
        $modulePath = drupal_get_path('module', $moduleName);
        // Put custom JS directly in the module folder.
        $destination = "$modulePath/$file";

        if (DRUPAL_CORE > 7) {
            // Find an unique line.
            $search = 'js/system.js: {}';
            // Insert after unique line.
            $injection = "    $file: {}";
            // Do injection in the "system.*?" file.
            $extension = 'libraries.yml';
        } else {
            $search = 'system_add_module_assets();';
            $injection = "drupal_add_js('$destination', ['every_page' => TRUE]);";
            $extension = 'module';
        }

        // Do an insertion on a new line.
        $injection = "\n$injection";
        // Form the filename for manipulations.
        $target = "$modulePath/$moduleName.$extension";

        if ($delete) {
            // Remove the file.
            unlink($destination);
            // Find an injection and replace it by emptiness.
            $search = $injection;
            $replace = '';
        } else {
            // Copy the file.
            copy(str_replace('Cores', 'JavaScript', __DIR__) . '/' . $file, $destination);
            // Find an unique line and append injection.
            $replace = $search . $injection;
        }

        file_put_contents($target, str_replace($search, $replace, file_get_contents($target)));
    }

    /**
     * Require method execution from context.
     *
     * @param string $method
     *   The name of method.
     * @param array $arguments
     *   Method's arguments.
     *
     * @return mixed
     */
    private static function requireContext($method, array $arguments)
    {
        $context = str_replace('Kernel', DRUPAL_CORE, __CLASS__);

        if (method_exists($context, $method)) {
            return call_user_func_array([$context, $method], $arguments);
        }

        throw new \BadMethodCallException(sprintf('Method "%s" is not implemented in "%s".', $method, $context));
    }
}
