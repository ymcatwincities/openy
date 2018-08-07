<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Utils;

// Contexts.
use Drupal\TqExtension\Context\RawTqContext;
// Helpers.
use Behat\DebugExtension\Debugger;
use Behat\Mink\Element\NodeElement;

class FormValueAssertion
{
    use LogicalAssertion;
    use Debugger;

    /**
     * @var RawTqContext
     */
    private $context;
    /**
     * @var string
     *   Field selector.
     */
    private $selector = '';
    /**
     * Found element.
     *
     * @var NodeElement
     */
    private $element;
    /**
     * Expected value.
     *
     * @var string
     */
    private $expected = '';
    /**
     * Field element value.
     *
     * @var string
     */
    private $value = '';
    /**
     * Tag name of found element.
     *
     * @var string
     */
    private $tag = '';
    /**
     * Negate the condition.
     *
     * @var bool
     */
    private $not = false;

    /**
     * @param RawTqContext $context
     *   Behat context.
     * @param string $selector
     *   Field selector.
     * @param bool $not
     *   Negate the condition.
     * @param string $expected
     *   Expected value.
     */
    public function __construct(RawTqContext $context, $selector, $not, $expected = '')
    {
        $this->not = $not;
        $this->context = $context;
        $this->selector = $selector;
        $this->expected = $expected;

        $this->element = $this->context->element('field', $selector);
        $this->value = $this->element->getValue();
        $this->tag = $this->element->getTagName();
    }

    /**
     * Check value in inputs and text areas.
     */
    public function textual()
    {
        $this->restrictElements([
            'textarea' => [],
            'input' => [],
        ]);

        self::debug([
            'Expected: %s',
            'Value: %s',
            'Tag: %s',
        ], [
            $this->expected,
            $this->value,
            $this->tag,
        ]);

        $this->assert(trim($this->expected) === $this->value);
    }

    /**
     * Ensure option is selected.
     */
    public function selectable()
    {
        $this->restrictElements(['select' => []]);
        $data = [$this->value, $this->element->find('xpath', "//option[@value='$this->value']")->getText()];

        self::debug([
            'Expected: %s',
            'Value: %s',
            'Tag: %s',
        ], [
            $this->expected,
            implode(' => ', $data),
            $this->tag,
        ]);

        $this->assert(in_array($this->expected, $data), 'selected');
    }

    /**
     * Ensure that checkbox/radio button is checked.
     */
    public function checkable()
    {
        $this->restrictElements(['input' => ['radio', 'checkbox']]);

        if (!in_array($this->element->getAttribute('type'), ['radio', 'checkbox'])) {
            throw new \RuntimeException('Element cannot be checked.');
        }

        self::debug(['%s'], [$this->element->getOuterHtml()]);

        $this->assert($this->element->isChecked(), 'checked');
    }

    /**
     * @param array[] $allowedElements
     *   Element machine names.
     */
    private function restrictElements(array $allowedElements)
    {
        // Match element tag with allowed.
        if (!isset($allowedElements[$this->tag])) {
            throw new \RuntimeException("Tag is not allowed: $this->tag.");
        }

        // Restrict by types only if they are specified.
        if (!empty($allowedElements[$this->tag])) {
            $type = $this->element->getAttribute('type');

            if (!in_array($type, $allowedElements[$this->tag])) {
                throw new \RuntimeException(sprintf('Type "%s" is not allowed for "%s" tag', $type, $this->tag));
            }
        }
    }

    /**
     * @param bool $value
     *   Value for checking.
     * @param string $word
     *   A word for default message (e.g. "checked", "selected", etc).
     *
     * @throws \Exception
     */
    private function assert($value, $word = '')
    {
        switch (static::assertion($value, $this->not)) {
            case 1:
                throw new \Exception(
                    empty($word)
                        ? 'Field contain a value, but should not.'
                        : "Element is $word, but should not be."
                );

            case 2:
                throw new \Exception(
                    empty($word)
                        ? 'Field does not contain a value.'
                        : "Element is not $word."
                );
        }
    }
}
