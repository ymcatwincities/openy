<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Utils\Wysiwyg;

// Contexts.
use Drupal\TqExtension\Context\RawTqContext;

class CKEditor extends Wysiwyg
{
    public function __construct(RawTqContext $context)
    {
        $this->setContext($context);
        $this->setObject("CKEDITOR.instances['%s']");
    }

    /**
     * {@inheritdoc}
     */
    public function fill($text, $selector = '')
    {
        $this->execute('setData', $selector, [$text]);
    }

    /**
     * {@inheritdoc}
     */
    public function type($text, $selector = '')
    {
        $this->execute('insertText', $selector, [$text]);
    }

    /**
     * {@inheritdoc}
     */
    public function read($selector = '')
    {
        return $this->execute('getData', $selector);
    }
}
