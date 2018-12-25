<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Context\Message;

// Helpers.
use Behat\Mink\Element\NodeElement;
// Contexts.
use Drupal\TqExtension\Context\RawTqContext;

class RawMessageContext extends RawTqContext
{
    /**
     * @param string $type
     *   Message type: "error", "warning", "success" or nothing.
     *
     * @return NodeElement[]
     */
    protected function getMessagesContainers($type = '')
    {
        if ('' !== $type) {
            $type .= '_';
        }

        return $this->getSession()
            ->getPage()
            ->findAll('css', $selector = $this->getDrupalSelector($type . 'message_selector'));
    }
}
