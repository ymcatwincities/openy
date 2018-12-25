<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Utils;

use Drupal\TqExtension\Cores\DrupalKernelPlaceholder;

final class EntityDrupalWrapper
{
    /**
     * Entity type.
     *
     * @var string
     */
    private $type = '';
    /**
     * Entity bundle.
     *
     * @var string
     */
    private $bundle = '';
    /**
     * Entity object.
     *
     * @var object
     */
    private $entity;
    /**
     * @var array
     */
    private $fields = [
        'locators' => [],
        'required' => [],
    ];

    /**
     * @param string $entityType
     * @param string $bundle
     */
    public function __construct($entityType, $bundle = '')
    {
        $this->type = $entityType;
        $this->bundle = $bundle ?: $this->type;

        // The fields in "locators" array stored by machine name of a field and duplicated by field label.
        foreach (DrupalKernelPlaceholder::getFieldDefinitions($this->type, $this->bundle) as $name => $definition) {
            $this->fields['locators'][$definition['label']] = $this->fields['locators'][$name] = $name;

            if ($definition['required']) {
                $this->fields['required'][$name] = $definition['label'];
            }
        }
    }

    public function load($id)
    {
        if (null === $this->entity) {
            $this->entity = DrupalKernelPlaceholder::entityLoad($this->type, $id);

            /**
             * Metadata wrapper needed since placeholder for Drupal 7 requires
             * \EntityDrupalWrapper as an argument.
             *
             * @see \Drupal\TqExtension\Cores\Drupal7Placeholder::entityFieldValue()
             */
            if (DRUPAL_CORE < 8) {
                $this->entity = entity_metadata_wrapper($this->type, $this->entity);
            }
        }

        return $this->entity;
    }

    public function hasField($fieldName)
    {
        return DrupalKernelPlaceholder::entityHasField(
            $this->getEntity(),
            $this->getFieldNameByLocator($fieldName)
        );
    }

    public function getFieldValue($fieldName)
    {
        return DrupalKernelPlaceholder::entityFieldValue(
            $this->getEntity(),
            $this->getFieldNameByLocator($fieldName)
        );
    }

    /**
     * @param string $fieldName
     *   Machine name or label of a field.
     *
     * @return string
     */
    public function getFieldNameByLocator($fieldName)
    {
        return isset($this->fields['locators'][$fieldName]) ? $this->fields['locators'][$fieldName] : '';
    }

    /**
     * @return array[]
     */
    public function getRequiredFields()
    {
        return $this->fields['required'];
    }

    /**
     * @return object
     */
    protected function getEntity()
    {
        if (null === $this->entity) {
            throw new \RuntimeException('You have to load an entity before getting it.');
        }

        return $this->entity;
    }
}
