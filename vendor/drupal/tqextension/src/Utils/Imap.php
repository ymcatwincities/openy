<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Utils;

trait Imap
{
    /**
     * @var resource[]
     */
    private $connections = [];

    public function closeConnections()
    {
        array_map('imap_close', $this->connections);
    }

    public function setConnection($email, $imap, $username, $password)
    {
        if (empty($this->connections[$email])) {
            $connection = imap_open('{' . $imap . '}INBOX', $username, $password);

            if (false === $connection) {
                throw new \RuntimeException('IMAP connection cannot be open. Maybe some parameters are incorrect.');
            }

            $this->connections[$email] = $connection;
        }
    }

    public function getMessages($email)
    {
        $messages = [];

        foreach ($this->query(['to' => $email, 'on' => date('d F Y'), 'unseen' => false]) as $messageId) {
            $structure = imap_fetchstructure($this->connections[$email], $messageId);
            $encoding = isset($structure->parts) ? reset($structure->parts) : $structure;
            $message = imap_fetch_overview($this->connections[$email], $messageId);
            $message = reset($message);

            $processFunction = $this->detectProcessFunction($encoding->encoding);
            $message->subject = $processFunction($message->subject);
            $message->body = $this->getMessageBody($email, $messageId, $processFunction, reset($structure->parameters));

            foreach (['from', 'to'] as $direction) {
                $address = imap_rfc822_parse_adrlist(imap_utf8($message->$direction), '');
                $address = reset($address);

                $message->$direction = "$address->mailbox@$address->host";
            }

            $messages[] = (array) $message;

            imap_delete($this->connections[$email], $messageId);
        }

        return $messages;
    }

    private function query(array $arguments)
    {
        if (empty($arguments['to'])) {
            throw new \Exception('The "to" argument is required!');
        }

        $query = [];

        foreach ($arguments as $name => $value) {
            $name = strtoupper($name);

            if (false === $value) {
                $query[] = $name;
            } elseif (!empty($value)) {
                $query[] = sprintf('%s "%s"', $name, $value);
            }
        }

        $result = imap_search($this->connections[$arguments['to']], implode(' ', $query));

        return is_array($result) ? $result : [];
    }

    private function detectProcessFunction($encoding)
    {
        $process = [
            1 => 'imap_utf8',
            2 => 'imap_binary',
            3 => 'imap_base64',
            4 => 'imap_qprint',
        ];

        return isset($process[$encoding]) ? $process[$encoding] : function ($string) {
            return $string;
        };
    }

    private function getMessageBody($email, $messageId, callable $process, $parameter)
    {
        foreach ([1, 2] as $option) {
            $data = $process(imap_fetchbody($this->connections[$email], $messageId, $option));

            if (!empty($data)) {
                if ('charset' === strtolower($parameter->attribute) && 'utf-8' !== strtolower($parameter->value)) {
                    $data = iconv($parameter->value, 'utf-8', $data);
                }

                return quoted_printable_decode($data);
            }
        }

        return '';
    }
}
