<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Context\Redirect;

// Contexts.
use Drupal\TqExtension\Context\RawTqContext;
// Utils.
use Behat\Mink\Driver\GoutteDriver;

class RawRedirectContext extends RawTqContext
{
    /**
     * @param string $path
     *   An URL to visit (relative or absolute).
     *
     * @return int
     */
    public function getStatusCode($path)
    {
        // The "Goutte" session should be used because it provides HTTP status codes.
        // Visit path once again if current session driver is not Goutte.
        if (!($this->getSessionDriver() instanceof GoutteDriver)) {
            $this->visitPath($path, 'goutte');
        }

        $statusCode = (int) $this->getSession('goutte')->getStatusCode();

        self::debug(['HTTP status code: %s'], [$statusCode]);

        return $statusCode;
    }
}
