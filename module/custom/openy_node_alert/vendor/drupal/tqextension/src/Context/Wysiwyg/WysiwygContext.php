<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Context\Wysiwyg;

// Helpers.
use Behat\Gherkin\Node\TableNode;

class WysiwygContext extends RawWysiwygContext
{
    /**
     * @param string $selector
     *
     * @Given /^(?:|I )work with "([^"]*)" WYSIWYG editor$/
     */
    public function workWithEditor($selector)
    {
        $this->getEditor()->setSelector($selector);
    }

    /**
     * @param string $text
     * @param string $selector
     *
     * @throws \Exception
     *   When editor was not found.
     *
     * @Given /^(?:|I )fill "([^"]*)" in (?:|"([^"]*)" )WYSIWYG editor$/
     */
    public function fill($text, $selector = '')
    {
        $this->getEditor()->fill($text, $selector);
    }

    /**
     * @param string $text
     * @param string $selector
     *
     * @throws \Exception
     *   When editor was not found.
     *
     * @When /^(?:|I )type "([^"]*)" in (?:|"([^"]*)" )WYSIWYG editor$/
     */
    public function type($text, $selector = '')
    {
        $this->getEditor()->type($text, $selector);
    }

    /**
     * @param string $condition
     * @param string $text
     * @param string $selector
     *
     * @throws \Exception
     *   When editor was not found.
     * @throws \RuntimeException
     *
     * @Then /^(?:|I )should(| not) see "([^"]*)" in (?:|"([^"]*)" )WYSIWYG editor$/
     */
    public function read($condition, $text, $selector = '')
    {
        $condition = (bool) $condition;
        $wysiwyg = $this->getEditor();
        $content = $wysiwyg->read($selector);

        if (!is_string($content)) {
            self::debug(['Returned value:', '%s'], [var_export($content, true)]);

            throw new \UnexpectedValueException('Could not read WYSIWYG content.');
        }

        self::debug(["Content from WYSIWYG: %s"], [$content]);

        if (strpos($content, $text) === $condition) {
            throw new \RuntimeException(sprintf(
                'The text "%s" was%s found in the "%s" WYSIWYG editor.',
                $text,
                $condition ? '' : ' not',
                $wysiwyg->getSelector()
            ));
        }
    }

    /**
     * @param TableNode $fields
     *   | Editor locator | Value |
     *
     * @Then /^(?:|I )fill in following WYSIWYG editors:$/
     */
    public function fillInMultipleEditors(TableNode $fields)
    {
        foreach ($fields->getRowsHash() as $selector => $value) {
            $this->fill($value, $selector);
        }
    }

    /**
     * @BeforeScenario @wysiwyg
     */
    public function beforeScenario()
    {
        $this->setEditor(self::getTag('wysiwyg', 'CKEditor'), [$this]);
    }

    /**
     * @AfterScenario @wysiwyg
     */
    public function afterScenario()
    {
        $this->getEditor()->setSelector('');
    }
}
