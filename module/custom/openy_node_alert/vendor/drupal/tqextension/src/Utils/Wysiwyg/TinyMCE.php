<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Utils\Wysiwyg;

// Contexts.
use Drupal\TqExtension\Context\RawTqContext;

class TinyMCE extends Wysiwyg
{
    public function __construct(RawTqContext $context)
    {
        $this->setContext($context);
        $this->setObject("tinyMCE.get('%s')");
    }

    /**
     * {@inheritdoc}
     */
    public function fill($text, $selector = '')
    {
        $this->execute('setContent', $selector, [$text]);
    }

    /**
     * {@inheritdoc}
     */
    public function type($text, $selector = '')
    {
        // Unfortunately, TinyMCE cannot type to an editor.
        $this->fill($text, $selector);
    }

    /**
     * {@inheritdoc}
     */
    public function read($selector = '')
    {
        return $this->execute('getContent', $selector);
    }
}
