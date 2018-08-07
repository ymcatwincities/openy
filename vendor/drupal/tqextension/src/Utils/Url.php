<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Utils;

/**
 * Class Url.
 *
 * @package Drupal\TqExtension\Utils
 */
class Url
{
    /**
     * URL to build.
     *
     * @var string
     */
    private $url = '';
    /**
     * URL components.
     *
     * @var string[]
     */
    private $components = [];

    /**
     * Url constructor.
     *
     * @param string $baseUrl
     *   Base URL.
     * @param string $path
     *   Path on a base URL.
     */
    public function __construct($baseUrl, $path = '')
    {
        if (empty($baseUrl)) {
            throw new \InvalidArgumentException('Set base URL before continue.');
        }

        // Start with base URL when path is empty, or not starts from "//" or "http".
        if (empty($path) || strpos($path, '//') !== 0 && strpos($path, 'http') !== 0) {
            $path = rtrim($baseUrl, '/') . '/' . trim($path, '/');
        }

        $this->setUrl($path)->scheme()->credentials()->host()->components();
    }

    /**
     * @return string
     *   Constructed URL.
     */
    public function __toString()
    {
        return $this->url;
    }

    /**
     * Initialize URL creation.
     *
     * @param string $url
     *
     * @return $this
     */
    private function setUrl($url)
    {
        $this->url = $url;
        $this->components = parse_url(strtolower($this->url));

        if (false === $this->components || !isset($this->components['host'])) {
            throw new \InvalidArgumentException(sprintf('%s - incorrect URL.', $this->url));
        }

        return $this;
    }

    /**
     * Append HTTP scheme.
     *
     * @return $this
     */
    private function scheme()
    {
        $this->components += [
            // When URL starts from "//" the "scheme" key will not exists.
            'scheme' => 'http',
        ];

        // Check scheme.
        if (!in_array($this->components['scheme'], ['http', 'https'])) {
            throw new \InvalidArgumentException(sprintf('%s - invalid scheme.', $this->components['scheme']));
        }

        $this->url = $this->components['scheme'] . '://';

        return $this;
    }

    /**
     * Append authentication credentials.
     *
     * @return $this
     */
    private function credentials()
    {
        if (isset($this->components['user'], $this->components['pass'])) {
            // Encode special characters in username and password. Useful
            // when some item contain something like "@" symbol.
            foreach (['user' => ':', 'pass' => '@'] as $part => $suffix) {
                $this->url .= rawurlencode($this->components[$part]) . $suffix;
            }
        }

        return $this;
    }

    /**
     * Append host.
     *
     * @return $this
     */
    private function host()
    {
        $this->url .= $this->components['host'];

        return $this;
    }

    /**
     * Append additional URL components.
     *
     * @return $this
     */
    private function components()
    {
        foreach (['port' => ':', 'path' => '', 'query' => '?', 'fragment' => '#'] as $part => $prefix) {
            if (isset($this->components[$part])) {
                $this->url .= $prefix . $this->components[$part];
            }
        }

        return $this;
    }
}
