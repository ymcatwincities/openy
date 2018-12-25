<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Utils;

/**
 * Trait JavaScript.
 *
 * @package Drupal\TqExtension\Utils
 */
trait JavaScript
{
    /**
     * @param string $filename
     *   Name of file in "src/JavaScript" without ".js" extension.
     *
     * @return string
     *   Contents of file.
     */
    protected static function getJavaScriptFileContents($filename)
    {
        static $files = [];

        if (empty($files[$filename])) {
            $path = str_replace('Utils', 'JavaScript', __DIR__) . "/$filename.js";

            if (!file_exists($path)) {
                throw new \RuntimeException(sprintf('File "%s" does not exists!', $path));
            }

            $files[$filename] = file_get_contents($path);
        }

        return $files[$filename];
    }
}
