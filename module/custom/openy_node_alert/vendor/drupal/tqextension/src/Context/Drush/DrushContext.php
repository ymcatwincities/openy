<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Context\Drush;

use Drupal\TqExtension\Cores\DrupalKernelPlaceholder;

class DrushContext extends RawDrushContext
{
    /**
     * @example
     * Given I login with one time link[ (1)]
     * Then drush uli[ admin]
     *
     * @param string $argument
     *   User ID, email or name. Argument for "drush uli".
     *
     * @throws \Exception
     *
     * @Given /^(?:|I )login with one time link(?:| \(([^"]*)\))$/
     * @Then /^drush uli(?:| ([^"]*))$/
     */
    public function loginWithOneTimeLink($argument = '')
    {
        $userContext = $this->getUserContext();

        if (empty($argument)) {
            $userContext->logoutUser();
            $argument = $userContext->createTestUser()->name;
        }

        // Care about not-configured Drupal installations, when
        // the "$base_url" variable is not set in "settings.php".
        // Also, remove the last underscore symbol from link for
        // prevent opening the page for reset the password;
        $link = rtrim($this->getOneTimeLoginLink($argument), '_');
        $this->visitPath($link);

        if (
            // The "isLoggedIn" method must be called to set authorization cookie for "Goutte"
            // session. It must be set to be able to check status codes for the HTTP requests.
            !$userContext->isLoggedIn() &&
            !preg_match(
                sprintf("/%s/i", DrupalKernelPlaceholder::t('You have just used your one-time login link.')),
                $this->getWorkingElement()->getText()
            )
        ) {
            throw new \Exception(sprintf('Cannot login with one time link: "%s"', $link));
        }
    }
}
