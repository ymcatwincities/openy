<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Utils\Wysiwyg;

// Contexts.
use Drupal\TqExtension\Context\RawTqContext;
// Utils.
use Drupal\TqExtension\Cores\DrupalKernelPlaceholder;

abstract class Wysiwyg
{
    /**
     * @var RawTqContext
     */
    private $context;
    /**
     * @var string
     *   JS code that return an instance of WYSIWYG editor.
     */
    private $object = '';
    /**
     * @var string
     *   Field selector.
     */
    private $selector = '';
    /**
     * @var array
     */
    private $instances = [];

    /**
     * @param RawTqContext $context
     *   Context of page. Needs to interact with browser.
     */
    protected function setContext(RawTqContext $context)
    {
        $this->context = $context;
    }

    /**
     * @see TinyMCE::__construct()
     * @see CKEditor::__construct()
     *
     * @param string $javascript
     *   Must a string of JS code that return an instance of editor. String will be
     *   processed by sprintf() and "%s" placeholder will be replaced by field ID.
     */
    protected function setObject($javascript)
    {
        $this->object = (string) $javascript;
    }

    /**
     * @param string $selector
     */
    public function setSelector($selector)
    {
        if (!empty($selector)) {
            $this->selector = (string) $selector;
        }
    }

    /**
     * @return string
     */
    public function getSelector()
    {
        return $this->selector;
    }

    /**
     * Get the editor instance for use in Javascript.
     *
     * @param string $selector
     *   Any selector of a form field.
     *
     * @throws \RuntimeException
     * @throws \Exception
     * @throws \WebDriver\Exception\NoSuchElement
     *
     * @return string
     *   A Javascript expression that representing WYSIWYG instance.
     */
    protected function getInstance($selector = '')
    {
        if (empty($this->object)) {
            throw new \RuntimeException('Editor instance was not set.');
        }

        if (empty($this->selector) && empty($selector)) {
            throw new \RuntimeException('No such editor was not selected.');
        }

        $this->setSelector($selector);

        if (empty($this->instances[$this->selector])) {
            $instanceId = $this->context->element('field', $this->selector)->getAttribute('id');
            $instance = sprintf($this->object, $instanceId);

            if (!$this->context->executeJs("return !!$instance")) {
                throw new \Exception(sprintf('Editor "%s" was not found.', $instanceId));
            }

            $this->instances[$this->selector] = $instance;
        }

        return $this->instances[$this->selector];
    }

    /**
     * @param string $method
     *   WYSIWYG editor method.
     * @param string $selector
     *   Editor selector.
     * @param array $arguments
     *   Arguments for method of WYSIWYG editor.
     *
     * @throws \Exception
     *   Throws an exception if the editor does not exist.
     *
     * @return string
     *   Result of JS evaluation.
     */
    protected function execute($method, $selector = '', array $arguments = [])
    {
        return $this->context->executeJs(sprintf(
            "return %s.$method(%s);",
            $this->getInstance($selector),
            // Remove "[" character from start and "]" from the end of string.
            substr(DrupalKernelPlaceholder::jsonEncode($arguments), 1, -1)
        ));
    }

    /**
     * @param string $wysiwyg
     * @param array $arguments
     *
     * @throws \Exception
     *
     * @return self
     */
    public static function instantiate($wysiwyg, array $arguments = [])
    {
        $classes = [$wysiwyg, sprintf('%s\%s', __NAMESPACE__, $wysiwyg)];

        foreach ($classes as $class) {
            if (class_exists($class) && get_parent_class($class) === self::class) {
                return (new \ReflectionClass($class))->newInstanceArgs($arguments);
            }
        }

        throw new \Exception(sprintf(
            'Editor not defined in any of these namespaces: "%s".',
            implode('", "', $classes)
        ));
    }

    /**
     * @param string $text
     *   Text to insert.
     * @param string $selector
     *   Editor selector.
     */
    abstract public function fill($text, $selector = '');

    /**
     * @param string $text
     *   Text to insert.
     * @param string $selector
     *   Editor selector.
     */
    abstract public function type($text, $selector = '');

    /**
     * @param string $selector
     *   Editor selector.
     *
     * @return string
     *   Editor content.
     */
    abstract public function read($selector = '');
}
