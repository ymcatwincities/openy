<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Context\Wysiwyg;

// Contexts.
use Drupal\TqExtension\Context\RawTqContext;
// Helpers.
use Behat\DebugExtension\Message;
// Utils.
use Drupal\TqExtension\Utils\Wysiwyg\Wysiwyg;

class RawWysiwygContext extends RawTqContext
{
    /**
     * @var Wysiwyg
     */
    private $wysiwyg;

    /**
     * @param string $wysiwyg
     *   An object name.
     * @param array $arguments
     *   Arguments for object constructor.
     *
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    protected function setEditor($wysiwyg, array $arguments = [])
    {
        if (empty($wysiwyg)) {
            throw new \InvalidArgumentException(
                'WYSIWYG name cannot be empty. You must mark your scenario with @wysiwyg' .
                'and @wysiwyg:<NAME> tags. For example: @wysiwyg @wysiwyg:CKEditor'
            );
        }

        try {
            $this->wysiwyg = Wysiwyg::instantiate($wysiwyg, $arguments);
        } catch (\Exception $e) {
            new Message('comment', 4, [
                'To describe a new editor you must create an object which will be extended',
                'by "%s" abstraction.',
            ], [
                Wysiwyg::class,
            ]);

            new Message('error', 4, ['%s'], [$e->getMessage()]);

            throw new \Exception(sprintf('The WYSIWYG editor "%s" does not exist.', $wysiwyg));
        }
    }

    /**
     * @throws \Exception
     *
     * @return Wysiwyg
     */
    protected function getEditor()
    {
        if (null === $this->wysiwyg) {
            throw new \Exception('The WYSIWYG editor was not set.');
        }

        return $this->wysiwyg;
    }
}
