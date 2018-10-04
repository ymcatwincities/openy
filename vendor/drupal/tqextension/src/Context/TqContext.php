<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Context;

// Scope definitions.
use Behat\Behat\Hook\Scope;
// Utils.
use Drupal\TqExtension\Utils\Database\Database;
use Drupal\TqExtension\Utils\LogicalAssertion;
use Drupal\TqExtension\Cores\DrupalKernelPlaceholder;

class TqContext extends RawTqContext
{
    use LogicalAssertion;

    /**
     * The name and working element of main window.
     *
     * @var array
     */
    private $mainWindow = [];
    /**
     * @var string[]
     */
    private static $featureTags = [];
    /**
     * @var Database
     */
    private static $database;

    /**
     * Supports switching between the two windows only.
     *
     * @Given /^(?:|I )switch to opened window$/
     * @Then /^(?:|I )switch back to main window$/
     */
    public function iSwitchToWindow()
    {
        $windows = $this->getWindowNames();

        // If the window was not switched yet, then store it name and working element for future switching back.
        if (empty($this->mainWindow)) {
            $this->mainWindow['name'] = array_shift($windows);
            $this->mainWindow['element'] = $this->getWorkingElement();

            $window = reset($windows);
        } else {
            $window = $this->mainWindow['name'];
            $element = $this->mainWindow['element'];

            $this->mainWindow = [];
        }

        $this->getSession()->switchToWindow($window);
        $this->setWorkingElement(isset($element) ? $element : $this->getBodyElement());
    }

    /**
     * @Given /^(?:|I )switch to CKFinder window$/
     *
     * @javascript
     */
    public function switchToCKFinderWindow()
    {
        $this->iSwitchToWindow();
        $this->executeJsOnElement(
            $this->element('css', 'iframe'),
            "{{ELEMENT}}.setAttribute('id', 'behat_ckfinder');"
        );
        $this->iSwitchToAnIframe('behat_ckfinder');
    }

    /**
     * @param string $name
     *   An iframe name (null for switching back).
     *
     * @Given /^(?:|I )switch to an iframe "([^"]*)"$/
     * @Then /^(?:|I )switch back from an iframe$/
     */
    public function iSwitchToAnIframe($name = null)
    {
        $this->getSession()->switchToIFrame($name);
    }

    /**
     * Open the page with specified resolution.
     *
     * @param string $width_height
     *   String that satisfy the condition "<WIDTH>x<HEIGHT>".
     *
     * @example
     * Given I should use the "1280x800" resolution
     *
     * @Given /^(?:|I should )use the "([^"]*)" screen resolution$/
     */
    public function useScreenResolution($width_height)
    {
        list($width, $height) = explode('x', $width_height);

        $this->getSessionDriver()->resizeWindow((int) $width, (int) $height);
    }

    /**
     * @param string $action
     *   The next actions can be: "press", "click", "double click" and "right click".
     * @param string $selector
     *   CSS, inaccurate text or selector name from behat.yml can be used.
     *
     * @throws \WebDriver\Exception\NoSuchElement
     *   When element was not found.
     *
     * @Given /^(?:|I )((?:|(?:double|right) )click|press) on "([^"]*)"$/
     */
    public function pressElement($action, $selector)
    {
        // 1. Get the action, divide string by spaces and put it parts into an array.
        // 2. Apply the "ucfirst" function for each array element.
        // 3. Make string from an array.
        // 4. Set the first letter of a string to lower case.
        $this->element('*', $selector)->{lcfirst(implode(array_map('ucfirst', explode(' ', $action))))}();
    }

    /**
     * @Given /^(?:|I )wait until AJAX is finished$/
     *
     * @javascript
     */
    public function waitUntilAjaxIsFinished()
    {
        $this->waitAjaxAndAnimations();
    }

    /**
     * @param string $selector
     *   CSS selector or region name.
     *
     * @Then /^(?:|I )work with elements in "([^"]*)"(?:| region)$/
     */
    public function workWithElementsInRegion($selector)
    {
        if (in_array($selector, ['html', 'head'])) {
            $element = $this->getSession()->getPage()->find('css', $selector);
        } else {
            $element = $this->element('css', $selector);
        }

        $this->setWorkingElement($element);
    }

    /**
     * @Then /^(?:|I )checkout to whole page$/
     */
    public function unsetWorkingElementScope()
    {
        $this->unsetWorkingElement();
    }

    /**
     * @param int $seconds
     *   Amount of seconds when nothing to happens.
     *
     * @Given /^(?:|I )wait (\d+) seconds$/
     */
    public function waitSeconds($seconds)
    {
        sleep($seconds);
    }

    /**
     * @param string $selector
     *   Text or CSS.
     *
     * @throws \Exception
     *
     * @Given /^(?:|I )scroll to "([^"]*)" element$/
     *
     * @javascript
     */
    public function scrollToElement($selector)
    {
        if (!self::hasTag('javascript')) {
            throw new \Exception('Scrolling to an element is impossible without JavaScript.');
        }

        $this->executeJsOnElement($this->findElement($selector), '{{ELEMENT}}.scrollIntoView(true);');
    }

    /**
     * @param string $message
     *   JS error.
     * @param bool $negate
     *   Whether page should or should not contain the error.
     * @param string $file
     *   File where error appears.
     *
     * @throws \RuntimeException
     * @throws \Exception
     *
     * @example
     * Then check that "TypeError: cell[0] is undefined" JS error appears in "misc/tabledrag.js" file
     * Then check that "TypeError: cell[0] is undefined" JS error appears on the page
     *
     * @Then /^check that "([^"]*)" JS error(| not) appears (?:in "([^"]*)" file|on the page)$/
     *
     * @javascript
     */
    public function checkJavaScriptError($message, $negate, $file = '')
    {
        $errors = $this->getSession()->evaluateScript('return JSON.stringify(window.errors);');
        $negate = (bool) $negate;

        if (empty($errors)) {
            if (!$negate) {
                throw new \RuntimeException('Page does not contain JavaScript errors.');
            }
        } else {
            $base_url = $this->locatePath();

            // The "$error" object contains two properties: "message" and "location".
            // @see CatchErrors.js
            foreach (json_decode($errors) as $error) {
                $error->location = str_replace($base_url, '', $error->location);

                self::debug(['JS error "%s" in "%s" file'], [$error->message, $error->location]);

                switch (static::assertion(
                    strpos($error->message, $message) === 0 && ('' === $file ?: strpos($error->location, $file) === 0),
                    $negate
                )) {
                    case 1:
                        throw new \Exception(sprintf('The "%s" error found, but should not be.', $message));

                    case 2:
                        throw new \Exception(sprintf('The "%s" error not found, but should be.', $message));
                }
            }
        }
    }

    /**
     * @param string $selector
     * @param string $attribute
     * @param string $expectedValue
     *
     * @throws \Exception
     *
     * @example
     * Then I should see the "#table_cell" element with "colspan" attribute having "3" value
     *
     * @Then /^(?:|I )should see the "([^"]*)" element with "([^"]*)" attribute having "([^"]*)" value$/
     */
    public function assertElementAttribute($selector, $attribute, $expectedValue)
    {
        foreach ($this->findAll($selector) as $element) {
            if ($element->getAttribute($attribute) === $expectedValue) {
                return;
            }
        }

        throw new \InvalidArgumentException(sprintf(
            'No elements with "%s" attribute have been found by "%s" selector.',
            $attribute,
            $selector
        ));
    }

    /**
     * @param Scope\BeforeFeatureScope $scope
     *   Scope of the processing feature.
     *
     * @BeforeFeature
     */
    public static function beforeFeature(Scope\BeforeFeatureScope $scope)
    {
        self::$featureTags = $scope->getFeature()->getTags();

        // Database will be cloned for every feature with @cloneDB tag.
        if (self::hasTag('clonedb')) {
            self::$database = clone new Database(self::getTag('clonedb', 'default'));
        }

        DrupalKernelPlaceholder::beforeFeature($scope);
        DrupalKernelPlaceholder::injectCustomJavascript('CatchErrors');
    }

    /**
     * @AfterFeature
     */
    public static function afterFeature()
    {
        // Restore initial database when feature is done (call __destruct).
        self::$database = null;

        // Remove injected script.
        DrupalKernelPlaceholder::injectCustomJavascript('CatchErrors', true);
    }

    /**
     * @param Scope\BeforeScenarioScope $scope
     *   Scope of the processing scenario.
     *
     * @BeforeScenario
     */
    public function beforeScenario(Scope\BeforeScenarioScope $scope)
    {
        self::clearTags();
        self::collectTags(self::$featureTags + $scope->getScenario()->getTags());

        // No need to keep working element between scenarios.
        $this->unsetWorkingElement();
        // Any page should be visited to be able check cookies.
        $this->getRedirectContext()->visitPage('/');
    }

    /**
     * Track XMLHttpRequest starts and finishes using pure JavaScript.
     *
     * @see RawTqContext::waitAjaxAndAnimations()
     *
     * @BeforeScenario @javascript
     */
    public function beforeScenarioJS()
    {
        $this->executeJs(static::getJavaScriptFileContents('TrackXHREvents'));
    }

    /**
     * IMPORTANT! The "BeforeStep" hook should not be tagged, because steps has no tags!
     *
     * @param Scope\StepScope|Scope\BeforeStepScope $scope
     *   Scope of the processing step.
     *
     * @BeforeStep
     */
    public function beforeStep(Scope\StepScope $scope)
    {
        self::$pageUrl = $this->getCurrentUrl();
        // To allow Drupal use its internal, web-based functionality, such as "arg()" or "current_path()" etc.
        DrupalKernelPlaceholder::setCurrentPath(ltrim(parse_url(self::$pageUrl)['path'], '/'));
    }

    /**
     * IMPORTANT! The "AfterStep" hook should not be tagged, because steps has no tags!
     *
     * @param Scope\StepScope|Scope\AfterStepScope $scope
     *   Scope of the processing step.
     *
     * @AfterStep
     */
    public function afterStep(Scope\StepScope $scope)
    {
        // If "mainWindow" variable is not empty that means that additional window has been opened.
        // Then, if number of opened windows equals to one, we need to switch back to original window,
        // otherwise an error will occur: "Window not found. The browser window may have been closed".
        // This happens due to auto closing window by JavaScript (CKFinder does this after choosing a file).
        if (!empty($this->mainWindow) && count($this->getWindowNames()) == 1) {
            $this->iSwitchToWindow();
        }

        if (self::isStepImpliesJsEvent($scope)) {
            $this->waitAjaxAndAnimations();
        }
    }
}
