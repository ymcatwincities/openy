<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Utils\Wysiwyg;

// Contexts.
use Drupal\TqExtension\Context\RawTqContext;

class MarkItUp extends Wysiwyg
{
    public function __construct(RawTqContext $context)
    {
        $this->setContext($context);
        $this->setObject("jQuery.markItUp({target: '#%s'})");
    }

    /**
     * {@inheritdoc}
     */
    public function fill($text, $selector = '')
    {
        $this->execute('trigger', $selector, ['insertion', [(object) ['placeHolder' => $text]]]);
    }

    /**
     * {@inheritdoc}
     */
    public function type($text, $selector = '')
    {
        // Unfortunately, MarkItUp cannot type to an editor.
        $this->fill($text, $selector);
    }

    /**
     * {@inheritdoc}
     */
    public function read($selector = '')
    {
        return $this->execute('val', $selector);
    }
}
