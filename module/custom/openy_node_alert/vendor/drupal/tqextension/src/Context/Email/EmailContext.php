<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Context\Email;

// Helpers.
use Behat\DebugExtension\Message;
use Behat\Gherkin\Node\TableNode;
use WebDriver\Exception\NoSuchElement;

class EmailContext extends RawEmailContext
{
    const PARSE_STRING = '/^(.+?)$/i';

    /**
     * @example
     * I check that email for "test@example.com" was sent
     *
     * @param string $to
     *   Recipient.
     *
     * @throws \RuntimeException
     *   When any message was not sent.
     *
     * @Given /^(?:|I )check that email for "([^"]*)" was sent$/
     */
    public function wasSent($to)
    {
        $this->getEmailMessages($to);
    }

    /**
     * @example
     * I check that email for "test@example.com" contains:
     *   | subject | New email letter   |
     *   | body    | The body of letter |
     * I also check that email contains:
     *   | from    | admin@example.com  |
     *
     * @param string $to
     *   Recipient.
     * @param TableNode $values
     *   Left column - is a header key, right - value.
     *
     * @throws \RuntimeException
     *   When any message was not sent.
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @Given /^(?:|I )check that email for "([^"]*)" contains:$/
     */
    public function contains($to, TableNode $values)
    {
        $rows = $values->getRowsHash();

        foreach ($this->getEmailMessages($to) as $message) {
            foreach ($rows as $field => $value) {
                if (empty($message[$field])) {
                    throw new \InvalidArgumentException(sprintf('Message does not contain "%s" header.', $field));
                }

                if (strpos($message[$field], $value) === false) {
                    throw new \RuntimeException(sprintf('Value of "%s" does not contain "%s".', $field, $value));
                }
            }
        }
    }

    /**
     * @param string $link
     *   Link text or value of "href" attribute.
     * @param string $to
     *   Try to find in specific email.
     *
     * @Given /^(?:|I )click on link "([^"]*)" in email(?:| that was sent on "([^"]*)")$/
     */
    public function clickLink($link, $to = '')
    {
        foreach ($this->getEmailMessages($to) as $message) {
            if (!isset($message['links'][$link])) {
                $link = array_search($link, $message['links']);
            }

            if (isset($message['links'][$link])) {
                $this->visitPath($message['links'][$link]);
            }
        }
    }

    /**
     * @param string $to
     *
     * @throws \Exception
     *   When parameter "parse_mail_callback" was not specified.
     * @throws \InvalidArgumentException
     *   When parameter "parse_mail_callback" is not callable.
     * @throws NoSuchElement
     *   When "Log in" button cannot be found on the page.
     * @throws \RuntimeException
     *   When credentials cannot be parsed or does not exist.
     *
     * @Given /^(?:|I )login with credentials that was sent on (?:"([^"]*)"|email)$/
     */
    public function loginWithCredentialsThatWasSentByEmail($to = '')
    {
        /**
         * Function must return an associative array with two keys: "username" and "password". The
         * value of each key should be a string with placeholder that will be replaced with user
         * login and password from an account. In testing, placeholders will be replaced by regular
         * expressions for parse the message that was sent.
         *
         * @example
         * @code
         * function mail_account_strings($name, $pass) {
         *     return [
         *       'username' => t('Username: !mail', ['!mail' => $name]),
         *       'password' => t('Password: !pass', ['!pass' => $pass]),
         *     ];
         * }
         *
         * // Drupal module.
         * function hook_mail($key, &$message, $params) {
         *     switch ($key) {
         *         case 'account':
         *             $message['subject'] = t('Website Account');
         *             $message['body'][] = t('You can login on the site using next credentials:');
         *             $message['body'] += mail_account_strings($params['mail'], $params['pass']);
         *             break;
         *     }
         * }
         *
         * // Behat usage.
         * mail_account_strings('(.+?)', '(.+?)');
         * @endcode
         *
         * @var callable $callback
         */
        $param = 'email_account_strings';
        $callback = $this->getTqParameter($param);

        if (empty($callback)) {
            throw new \Exception(sprintf('Parameter "%s" was not specified in "behat.yml"', $param));
        }

        if (!is_callable($callback)) {
            throw new \InvalidArgumentException(sprintf('The value of "%s" parameter is not callable.', $param));
        }

        $regexps = array_filter(call_user_func($callback, self::PARSE_STRING, self::PARSE_STRING));

        if (count($regexps) < 2) {
            throw new \RuntimeException(sprintf('Unfortunately you have wrong "%s" function.', $callback));
        }

        $userContext = $this->getUserContext();

        foreach ($this->getEmailMessages($to) as $message) {
            if (!empty($message['body'])) {
                $matches = [];

                // Process every line.
                foreach (explode("\n", $message['body']) as $string) {
                    foreach ($regexps as $name => $regexp) {
                        preg_match($regexp, $string, $match);

                        if (!empty($match[1])) {
                            $matches[$name] = $match[1];
                        }
                    }
                }

                if (!empty($matches['username']) && !empty($matches['password'])) {
                    $userContext->fillLoginForm($matches);
                    break;
                }
            }
        }

        if (!$userContext->isLoggedIn()) {
            throw new \RuntimeException(
                'Failed to login because email does not contain user credentials or they are was not parsed correctly.'
            );
        }
    }

    /**
     * @BeforeScenario @email&&@api&&~@imap
     */
    public function beforeScenarioEmailApi()
    {
        new Message('comment', 2, [
            "Sending messages will be tested by storing them in a database instead of sending.",
            "This is the good choice, because you testing the application, not web-server.\n",
        ]);

        DrupalKernelPlaceholder::switchMailSystem(true);
    }

    /**
     * @AfterScenario @email&&@api&&~@imap
     */
    public function afterScenarioEmailApi()
    {
        DrupalKernelPlaceholder::switchMailSystem(false);
    }

    /**
     * @BeforeScenario @email&&@imap
     */
    public function beforeScenarioEmailImap()
    {
        if (!extension_loaded('imap')) {
            throw new \Exception('PHP configured without IMAP extension.');
        }

        new Message('comment', 2, [
            "Sending messages will be tested via IMAP protocol. You'll need to know, that the message",
            "simply cannot be delivered due to incorrect server configuration or third-party service",
            "problems. Would be better if you'll test this functionality using the <info>@api</info>.\n",
        ]);

        // Restore original mail system.
        $this->afterScenarioEmailApi();
    }

    /**
     * @AfterScenario @email&&@imap
     */
    public function afterScenarioEmailImap()
    {
        $this->closeConnections();
    }
}
