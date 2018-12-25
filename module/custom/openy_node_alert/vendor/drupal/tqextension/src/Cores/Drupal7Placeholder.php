<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Cores;

use Behat\Behat\Hook\Scope\BeforeFeatureScope;
use Drupal\TqExtension\Utils\Database\FetchField;

final class Drupal7Placeholder extends DrupalKernelPlaceholder
{
    /**
     * {@inheritdoc}
     */
    public static function beforeFeature(BeforeFeatureScope $scope)
    {
        // Set to "false", because the administration menu will not be rendered.
        // @see https://www.drupal.org/node/2023625#comment-8607207
        variable_set('admin_menu_cache_client', false);
    }

    /**
     * {@inheritdoc}
     */
    public static function t($string, array $arguments = [], array $options = [])
    {
        return t($string, $arguments, $options);
    }

    /**
     * {@inheritdoc}
     */
    public static function formatString($string, array $arguments = [])
    {
        return format_string($string, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public static function arg()
    {
        return arg();
    }

    /**
     * {@inheritdoc}
     */
    public static function tokenReplace($text, array $data = [], array $options = [])
    {
        return token_replace($text, $data, $options);
    }

    /**
     * {@inheritdoc}
     */
    public static function jsonEncode($data)
    {
        return drupal_json_encode($data);
    }

    /**
     * {@inheritdoc}
     *
     * @return \stdClass
     */
    public static function getCurrentUser()
    {
        return $GLOBALS['user'];
    }

    /**
     * {@inheritdoc}
     *
     * @param \stdClass $user
     */
    public static function setCurrentUser($user)
    {
        $GLOBALS['user'] = $user;
    }

    /**
     * {@inheritdoc}
     */
    public static function setCurrentPath($path)
    {
        $_GET['q'] = $path;
    }

    /**
     * {@inheritdoc}
     */
    public static function getUidByName($username)
    {
        return (int) (new FetchField('users', 'uid'))
            ->condition('name', $username)
            ->execute();
    }

    /**
     * {@inheritdoc}
     */
    public static function deleteUser($user_id)
    {
        user_delete($user_id);
    }

    /**
     * {@inheritdoc}
     *
     * @return \SelectQuery
     */
    public static function selectQuery($table, $alias = null, array $options = [])
    {
        return db_select($table, $alias, $options);
    }

    /**
     * {@inheritdoc}
     */
    public static function getFieldDefinitions($entityType, $bundle)
    {
        // Load entity properties because in Drupal 8 they are assumed as regular fields.
        $entityProperties = entity_get_property_info($entityType);
        $fieldInstances = field_info_instances($entityType, $bundle);
        $definitions = [];

        if (!empty($entityProperties['properties'])) {
            $fieldInstances += $entityProperties['properties'];
        }

        foreach ($fieldInstances as $name => $definition) {
            $definitions[$name] = [
                'label' => $definition['label'],
                'required' => !empty($definition['required']),
            ];
        }

        return $definitions;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDatabaseConnectionInfo($connection)
    {
        return \Database::getConnectionInfo($connection);
    }

    /**
     * {@inheritdoc}
     */
    public static function entityCreate($entityType, array $values)
    {
        $entity = entity_create($entityType, $values);

        entity_save($entityType, $entity);
        list($entityId, , $bundle) = entity_extract_ids($entityType, $entity);

        return [$entityId, $entityType, $bundle];
    }

    /**
     * {@inheritdoc}
     */
    public static function entityLoad($entityType, $id)
    {
        $entities = entity_load($entityType, [$id]);

        return empty($entities) ? null : reset($entities);
    }

    /**
     * {@inheritdoc}
     */
    public static function entityHasField($entity, $fieldName)
    {
        return isset($entity->{$fieldName});
    }

    /**
     * {@inheritdoc}
     *
     * @param \EntityDrupalWrapper $entity
     */
    public static function entityFieldValue($entity, $fieldName)
    {
        if ($entity instanceof \EntityDrupalWrapper) {
            return $entity->{$fieldName}->value();
        }

        throw new \InvalidArgumentException(sprintf(
            'First argument for "%s" method must be of "%s" type.',
            __METHOD__,
            \EntityDrupalWrapper::class
        ));
    }

    /**
     * {@inheritdoc}
     */
    public static function switchMailSystem($useTesting)
    {
        static $original = [
            'default-system' => 'DefaultMailSystem',
        ];

        if ($useTesting) {
            // Store original mail system to restore it after scenario.
            $original = variable_get('mail_system', $original);
            // Set the mail system for testing. It will store an emails in
            // "drupal_test_email_collector" Drupal variable instead of sending.
            $value = array_merge($original, [
                'default-system' => 'TestingMailSystem',
            ]);
        } else {
            // Bring back the original mail system.
            $value = $original;
            // Flush the email buffer to be able to reuse it from scratch.
            // @see \TestingMailSystem
            variable_set('drupal_test_email_collector', []);
        }

        variable_set('mail_system', $value);
    }

    /**
     * {@inheritdoc}
     */
    public static function getEmailMessages()
    {
        // We can't use variable_get() because Behat has another bootstrapped
        // variable $conf that is not updated from cURL bootstrapped Drupal instance.
        $result = (new FetchField('variable', 'value'))
            ->condition('name', 'drupal_test_email_collector')
            ->execute();

        return empty($result) ? [] : unserialize($result);
    }

    /**
     * {@inheritdoc}
     */
    public static function getContentTypeName($contentType)
    {
        if (isset(node_type_get_types()[$contentType])) {
            return $contentType;
        }

        return (string) (new FetchField('node_type', 'type'))
            ->condition('name', $contentType)
            ->execute();
    }
}
