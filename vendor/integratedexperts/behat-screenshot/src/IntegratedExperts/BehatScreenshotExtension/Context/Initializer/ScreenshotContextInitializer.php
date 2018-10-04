<?php
/**
 * @file
 * This file is part of the IntegratedExperts\BehatScreenshot package.
 */

namespace IntegratedExperts\BehatScreenshotExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use IntegratedExperts\BehatScreenshotExtension\Context\ScreenshotAwareContext;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Class ScreenshotContextInitializer
 */
class ScreenshotContextInitializer implements ContextInitializer
{

    /**
     * Screenshot directory name.
     *
     * @var string
     */
    protected $dir;

    /**
     * Makes screenshot when fail.
     *
     * @var bool
     */
    protected $fail;

    /**
     * Prefix for failed screenshot files.
     *
     * @var string
     */
    private $failPrefix;

    /**
     * Purge dir before start test.
     *
     * @var bool
     */
    protected $purge;

    /**
     * Check if need to actually purge.
     *
     * @var bool
     */
    protected $needsPurging;

    /**
     * ScreenshotContextInitializer constructor.
     *
     * @param string $dir        Screenshot dir.
     * @param bool   $fail       Screenshot when fail.
     * @param bool   $failPrefix File name prefix for a failed test.
     * @param bool   $purge      Purge dir before start script.
     */
    public function __construct($dir, $fail, $failPrefix, $purge)
    {
        $this->needsPurging = true;
        $this->dir = $dir;
        $this->purge = $purge;
        $this->fail = $fail;
        $this->failPrefix = $failPrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeContext(Context $context)
    {
        if ($context instanceof ScreenshotAwareContext) {
            $dir = $this->resolveDir();
            $context->setScreenshotParameters($dir, $this->fail, $this->failPrefix);
            if ($this->purge && $this->needsPurging) {
                $this->purgeFilesInDir();
                $this->needsPurging = false;
            }
        }
    }

    /**
     * Remove files in directory.
     */
    protected function purgeFilesInDir()
    {
        $fs = new Filesystem();
        $finder = new Finder();
        if ($fs->exists($this->dir)) {
            $fs->remove($finder->files()->in($this->dir));
        }
    }

    /**
     * Resolve directory using one of supported paths.
     */
    protected function resolveDir()
    {
        $dir = getenv('BEHAT_SCREENSHOT_DIR');
        if (!empty($dir)) {
            return $dir;
        }

        return $this->dir;
    }
}
