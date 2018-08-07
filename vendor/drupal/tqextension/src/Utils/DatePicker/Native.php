<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Utils\DatePicker;

class Native extends DatePickerBase
{
    /**
     * @var string
     */
    private $format = '';
    /**
     * @var string
     */
    private $time = '';
    /**
     * @var string
     */
    private $formatted = '';

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        $this->format = $this->jQuery("data('drupalDateFormat')");

        if (empty($this->format)) {
            throw new \RuntimeException('Unknown date format.');
        }

        $this->time = strtotime($this->date);
        $this->formatted = date($this->format, $this->time);
    }

    /**
     * {@inheritdoc}
     */
    public function isDateAvailable()
    {
        $ranges = [];

        foreach (['min', 'max'] as $range) {
            $value = $this->element->getAttribute($range);
            // If no range was set then use the original date as its value.
            $ranges[$range] = null === $value ? $this->time : strtotime($value);
        }

        if ($this->time < $ranges['min'] || $this->time > $ranges['max']) {
            throw new \Exception(sprintf('The "%s" is not available for choosing.', $this->date));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDate()
    {
        $this->jQuery("val('$this->formatted')");

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isDateSelected()
    {
        $value = $this->jQuery('val()');

        self::debug(['Comparing "%s" with "%s".'], [$value, $this->formatted]);

        if ($value !== $this->formatted) {
            throw new \Exception(sprintf('DatePicker contains the "%s" but should "%s".', $value, $this->formatted));
        }

        return $this;
    }
}
