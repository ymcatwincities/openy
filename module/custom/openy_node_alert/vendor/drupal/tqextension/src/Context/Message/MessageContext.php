<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Context\Message;

// Helpers.
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
// Utils.
use Drupal\TqExtension\Cores\DrupalKernelPlaceholder;

class MessageContext extends RawMessageContext
{
    /**
     * Check that the page have no error messages and fields - error classes.
     *
     * @throws \RuntimeException
     * @throws \Exception
     *
     * @Then /^(?:|I )should see no errors(?:| on the page)$/
     */
    public function assertNoErrorMessages()
    {
        foreach ($this->getMessagesContainers('error') as $element) {
            // Some modules are inserted an empty container for errors before
            // they are arise. The "Clientside Validation" - one of them.
            $text = trim($element->getText());

            if ('' !== $text) {
                throw new \RuntimeException(sprintf(
                    'The page "%s" contains following error messages: "%s".',
                    self::$pageUrl,
                    $text
                ));
            }
        }

        /** @var NodeElement $formElement */
        foreach ($this->getSession()->getPage()->findAll('css', 'input, select, textarea') as $formElement) {
            if ($formElement->hasClass('error')) {
                throw new \Exception(sprintf(
                    'Element "#%s" has an error class.',
                    $formElement->getAttribute('id')
                ));
            }
        }
    }

    /**
     * @example
     * I should see the message "Hello, user."
     * I should see the error message "An error occured."
     * I should not see the success message "Everything fine."
     * I should not see the error message "!name field is required."
     *   | !name | E-mail address  |
     *
     * @param string $negate
     *   Indicates that user should or should not see message on the page.
     * @param string $type
     *   Message type: error, warning, success. Could be empty.
     * @param string $message
     *   Message to found. Placeholders allowed.
     * @param TableNode|array $args
     *   Placeholders conformity.
     *
     * @Then /^(?:|I )should(| not) see the (.* )message "([^"]*)"$/
     */
    public function assertMessage($negate, $type, $message, $args = [])
    {
        $type = trim($type);
        $negate = (bool) $negate;
        $elements = $this->getMessagesContainers($type);

        if (empty($elements) && !$negate) {
            throw new \UnexpectedValueException(sprintf("No $type messages on the page (%s).", self::$pageUrl));
        }

        if ($args instanceof TableNode) {
            $args = $args->getRowsHash();
        }

        $translated = DrupalKernelPlaceholder::t($message, $args);

        self::debug(['Input: %s', 'Translated: %s'], [$message, $translated]);

        /** @var NodeElement $element */
        foreach ($elements as $element) {
            $text = trim($element->getText());
            $result = strpos($text, $message) !== false || strpos($text, $translated) !== false;

            if ($negate ? $result : !$result) {
                throw new \RuntimeException(sprintf(
                    "The $type message%s found on the page (%s).",
                    $negate ? ' not' : '',
                    self::$pageUrl
                ));
            }
        }
    }

    /**
     * @example
     * Then should see the following error messages:
     *   | !name field is required.  | !name => E-mail address |
     *
     * @param string $negate
     *   Indicates that user should or should not see message on the page.
     * @param string $type
     *   Message type: error, warning, success. Could be empty.
     * @param TableNode $messages
     *   Messages to found. Placeholders allowed.
     *
     * @Then /^(?:|I )should(| not) see the following (.* )messages:$/
     */
    public function assertMessages($negate, $type, TableNode $messages)
    {
        foreach ($messages->getRowsHash() as $message => $placeholders) {
            $args = [];

            foreach ((array) $placeholders as $placeholder) {
                // Group values: !name => Text.
                $data = array_map('trim', explode('=>', $placeholder, 2));

                if (count($data) === 2) {
                    $args[$data[0]] = $data[1];
                }
            }

            $this->assertMessage($negate, $type, $message, $args);
        }
    }
}
