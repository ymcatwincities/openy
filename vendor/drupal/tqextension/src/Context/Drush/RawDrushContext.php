<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Context\Drush;

// Contexts.
use Drupal\TqExtension\Context\RawTqContext;

class RawDrushContext extends RawTqContext
{
    /**
     * @param string $username
     *
     * @return string
     */
    public function getOneTimeLoginLink($username)
    {
        return trim($this->getDrushDriver()->drush('uli', [
            $username,
            '--browser=0',
            '--uri=' . $this->locatePath(),
        ]));
    }
}
