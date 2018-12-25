<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Context\Email;

// Contexts.
use Drupal\TqExtension\Context\RawTqContext;
// Utils.
use Behat\DebugExtension\Message;
use Drupal\TqExtension\Utils\Imap;
use Drupal\TqExtension\Cores\DrupalKernelPlaceholder;

class RawEmailContext extends RawTqContext
{
    use Imap;

    private $messages = [];
    private $email = '';

    /**
     * @param string $to
     *
     * @throws \RuntimeException
     *
     * @return array
     */
    public function getEmailMessages($to = '')
    {
        // Update address for checking.
        if (!empty($to) && $this->email !== $to) {
            $this->email = $to;
        }

        if (empty($this->messages[$this->email])) {
            $messages = self::hasTag('imap')
              ? $this->getMessagesViaImap($this->email)
              : $this->getMessagesFromDb();

            if (empty($messages)) {
                throw new \RuntimeException(sprintf('The message for "%s" was not sent.', $this->email));
            }

            foreach ($messages as &$message) {
                if ($message['to'] === $this->email) {
                    $message['links'] = $this->parseLinksText($message['body']);
                }
            }

            $this->messages[$this->email] = $messages;
        }

        // The debug messages may differ due to testing testing mode:
        // Drupal mail system collector or IMAP protocol.
        self::debug(['%s'], [var_export($this->messages[$this->email], true)]);

        return $this->messages[$this->email];
    }

    /**
     * @param string $email
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    public function getAccount($email)
    {
        $accounts = $this->getTqParameter('email_accounts');

        if (empty($accounts[$email])) {
            throw new \InvalidArgumentException(sprintf(
                'An account for "%s" email address is not defined. Available addresses: "%s".',
                $email,
                implode(', ', array_keys($accounts))
            ));
        }

        return $accounts[$email];
    }

    public function parseLinksText($string)
    {
        $links = [];

        /** @var \DOMElement $link */
        foreach ((new \DOMXPath(self::parseHTML($string)))->query('//a[@href]') as $link) {
            $links[$link->textContent] = $link->getAttribute('href');
        }

        if (empty($links)) {
            preg_match_all('/((?:http(?:s)?|www)[^\s]+)/i', $string, $matches);

            if (!empty($matches[1])) {
                $links = $matches[1];
            }
        }

        return $links;
    }

    private function getMessagesViaImap($email)
    {
        $account = $this->getAccount($email);
        $timeout = $this->getTqParameter('wait_for_email');

        $this->setConnection($email, $account['imap'], $account['username'], $account['password']);

        if ($timeout > 0) {
            new Message('comment', 4, ['Waiting %s seconds for letter...'], [$timeout]);
            sleep($timeout);
        }

        return $this->getMessages($email);
    }

    private function getMessagesFromDb()
    {
        $result = DrupalKernelPlaceholder::getEmailMessages();

        self::debug(['Emails from the database: %s'], [var_export($result, true)]);

        return $result;
    }

    private static function parseHTML($string)
    {
        $document = new \DOMDocument();

        // Handle errors/warnings and don't mess up output of your script.
        // @see http://stackoverflow.com/a/17559716
        $libxml_state = libxml_use_internal_errors(true);
        $document->loadHTML($string);

        libxml_clear_errors();
        libxml_use_internal_errors($libxml_state);

        return $document;
    }
}
