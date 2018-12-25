<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Utils\Wysiwyg;

// Contexts.
use Drupal\TqExtension\Context\RawTqContext;

class EpicEditor extends Wysiwyg
{
    public function __construct(RawTqContext $context)
    {
        $this->setContext($context);
        $this->setObject("jQuery('#%s').data('epiceditor')");
    }

    /**
     * {@inheritdoc}
     */
    public function fill($text, $selector = '')
    {
        $this->execute('importFile', $selector, ['', $text]);
    }

    /**
     * {@inheritdoc}
     */
    public function type($text, $selector = '')
    {
        // Unfortunately, EpicEditor cannot type to an editor.
        $this->fill($text, $selector);
    }

    /**
     * {@inheritdoc}
     */
    public function read($selector = '')
    {
        return $this->execute('exportFile', $selector);
    }
}
