<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Context\Redirect;

// Helpers.
use Behat\DebugExtension\Message;
use Behat\Gherkin\Node\TableNode;
// Utils.
use Drupal\TqExtension\Cores\DrupalKernelPlaceholder;

class RedirectContext extends RawRedirectContext
{
    /**
     * @param string $page
     *   Expected page URL.
     *
     * @throws \Exception
     * @throws \OverflowException
     *
     * @Then /^(?:|I )should be redirected(?:| on "([^"]*)")$/
     */
    public function shouldBeRedirected($page = null)
    {
        $wait = $this->getTqParameter('wait_for_redirect');
        $pages = [];
        $seconds = 0;

        new Message('comment', 4, ['Waiting %s seconds for redirect...'], [$wait]);

        if (isset($page)) {
            $page = trim($page, '/');
            $pages = [$page, $this->locatePath($page)];
        }

        while ($wait >= $seconds++) {
            $url = $this->getCurrentUrl();
            $raw = explode('?', $url)[0];

            self::debug(['Expected URLs: %s', 'Current URL: %s'], [implode(', ', $pages), $raw]);

            if (in_array($raw, $pages, true) || in_array($url, $pages, true)) {
                return;
            }

            sleep(1);
        }

        throw new \OverflowException('Waiting time is over.');
    }

    /**
     * @example
     * Given user should have an access to the following pages
     *   | page/url |
     *
     * @param string $not
     * @param TableNode $paths
     *
     * @throws \Exception
     *
     * @Given /^user should(| not) have an access to the following pages:$/
     */
    public function checkUserAccessToPages($not, TableNode $paths)
    {
        $code = empty($not) ? 200 : 403;
        $fails = [];

        foreach (array_keys($paths->getRowsHash()) as $path) {
            if ($this->getStatusCode($path) !== $code) {
                $fails[] = $path;
            }
        }

        if (!empty($fails)) {
            throw new \Exception(sprintf(
                'The following paths: "%s" are %s accessible!',
                implode(', ', $fails),
                $not ? '' : 'not'
            ));
        }
    }

    /**
     * This step should be used instead of "I am at" if page should be checked
     * for accessibility before visiting.
     *
     * Also, this step can be replaced by:
     *   Then I am at "page/url"
     *
     * @param string $path
     *   Path to visit.
     * @param string|int $expectedCode
     *   Expected HTTP status code.
     *
     * @throws \Exception
     *
     * @Given /^I am on the "([^"]*)" page(?:| and HTTP code is "(\d+)")$/
     * @Given /^(?:|I )visit the "([^"]*)" page(?:| and HTTP code is "(\d+)")$/
     */
    public function visitPage($path, $expectedCode = 200)
    {
        $this->visitPath($path);

        self::debug(['Visited page: %s'], [$path]);

        $actualCode = $this->getStatusCode($path);

        if ($actualCode >= 500) {
            foreach (DrupalKernelPlaceholder::getWatchdogStackTrace() as $item) {
                self::debug(['5xx stack trace: %s'], [var_export($item, true)]);
            }
        }

        if ($actualCode !== (int) $expectedCode) {
            throw new \Exception(sprintf('Expected status is "%s", but "%s" returned.', $expectedCode, $actualCode));
        }
    }
}
