<?php

/**
 * @file
 * Behat context to enable Screenshot support in tests.
 */

namespace IntegratedExperts\BehatScreenshotExtension\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\MinkExtension\Context\RawMinkContext;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ScreenshotContext.
 */
class ScreenshotContext extends RawMinkContext implements SnippetAcceptingContext, ScreenshotAwareContext
{

    /**
     * Screenshot step filename.
     *
     * @var string
     */
    protected $featureFile;

    /**
     * Screenshot step line.
     *
     * @var int
     */
    protected $stepLine;

    /**
     * Screenshot directory name.
     *
     * @var string
     */
    private $dir;

    /**
     * Makes screenshot when fail.
     *
     * @var bool
     */
    private $fail;

    /**
     * Prefix for failed screenshot files.
     *
     * @var string
     */
    private $failPrefix;

    /**
     * {@inheritdoc}
     */
    public function setScreenshotParameters($dir, $fail, $failPrefix)
    {
        $this->dir = $dir;
        $this->fail = $fail;
        $this->failPrefix = $failPrefix;

        return $this;
    }

    /**
     * Init values required for snapshots.
     *
     * @param BeforeScenarioScope $scope Scenario scope.
     *
     * @BeforeScenario
     */
    public function beforeScenarioInit(BeforeScenarioScope $scope)
    {
        if ($scope->getScenario()->hasTag('javascript')) {
            if ($this->getSession()->getDriver() instanceof Selenium2Driver) {
                $this->getSession()->resizeWindow(1440, 900, 'current');
            }
        }
    }

    /**
     * Init values required for snapshot.
     *
     * @param BeforeStepScope $scope
     *
     * @BeforeStep
     */
    public function beforeStepInit(BeforeStepScope $scope)
    {
        $this->featureFile = $scope->getFeature()->getFile();
        $this->stepLine = $scope->getStep()->getLine();
    }

    /**
     * After scope event handler to print last response on error.
     *
     * @param AfterStepScope $event After scope event.
     *
     * @AfterStep
     */
    public function printLastResponseOnError(AfterStepScope $event)
    {
        if ($this->fail && !$event->getTestResult()->isPassed()) {
            $this->iSaveScreenshot(true);
        }
    }

    /**
     * Save debug screenshot.
     *
     * Handles different driver types.
     *
     * @param bool $fail Denotes if this was called in a context of the failed
     *                   test.
     *
     * @When save screenshot
     * @When I save screenshot
     */
    public function iSaveScreenshot($fail = false)
    {
        $data = null;
        $driver = $this->getSession()->getDriver();

        $ext = 'html';
        if ($driver instanceof Selenium2Driver) {
            $data = $this->getSession()->getScreenshot();
            $ext = 'png';
        } elseif ($this->getSession()->getDriver()->getClient()->getInternalResponse()) {
            $data = $this->getSession()->getDriver()->getContent();
            $ext = 'html';
        }

        if ($data) {
            if ($fail) {
                $this->saveScreenshotData($this->makeFileName($ext, $this->failPrefix), $data);
            } else {
                $this->saveScreenshotData($this->makeFileName($ext), $data);
            }
        }
    }

    /**
     * Save screenshot data into a file.
     *
     * @param string $filename
     *   File name to write.
     * @param string $data
     *   Data to write into a file.
     */
    protected function saveScreenshotData($filename, $data)
    {
        $this->prepareDir($this->dir);
        file_put_contents($this->dir.DIRECTORY_SEPARATOR.$filename, $data);
    }

    /**
     * Prepare directory.
     *
     * @param string $dir Name of preparing directory.
     */
    protected function prepareDir($dir)
    {
        $fs = new Filesystem();
        $fs->mkdir($dir, 0755);
    }

    /**
     * Make screenshot filename.
     *
     * Format: microseconds.featurefilename_linenumber.ext
     *
     * @param string $ext    File extension without dot.
     * @param string $prefix Optional file name prefix for a filed test.
     *
     * @return string Unique file name.
     */
    protected function makeFileName($ext, $prefix = '')
    {
        return sprintf('%01.2f.%s%s_%s.%s', microtime(true), $prefix, basename($this->featureFile), $this->stepLine, $ext);
    }
}
