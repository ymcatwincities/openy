<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Utils\Wysiwyg;

// Contexts.
use Drupal\TqExtension\Context\RawTqContext;

class JWysiwyg extends Wysiwyg
{
    public function __construct(RawTqContext $context)
    {
        $this->setContext($context);
        $this->setObject("jQuery('#%s')");
    }

    /**
     * {@inheritdoc}
     */
    public function fill($text, $selector = '')
    {
        $this->execute('wysiwyg', $selector, ['setContent', $text]);
    }

    /**
     * {@inheritdoc}
     */
    public function type($text, $selector = '')
    {
        $this->execute('wysiwyg', $selector, ['insertHtml', $text]);
    }

    /**
     * {@inheritdoc}
     */
    public function read($selector = '')
    {
        return $this->execute('wysiwyg', $selector, ['getContent']);
    }
}
