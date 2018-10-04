<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Utils\DatePicker;

class JQuery extends DatePickerBase
{
    const DATE_ADJUSTER = 'window.__behatDatePickerDateAdjuster';

    /**
     * @var string
     */
    private $jsDate = '';

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        $this->jsDate = self::jsDate($this->date);
    }

    /**
     * {@inheritdoc}
     */
    public function setDate()
    {
        // @todo setDate will not work if browser window is not active.
        $this->datePicker([__FUNCTION__, '<date>']);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isDateSelected()
    {
        $value = $this->datePicker(['getDate']);
        $initial = $this->session->evaluateScript($this->jsDate);

        // By some reasons DatePicker could not return a date using "getDate" method
        // and we'll try to use it from input value directly. An issue could occur
        // after saving the form and/or reloading the page.
        if (empty($value)) {
            $value = $this->session->evaluateScript(self::jsDate($this->element->getValue()));
        } else {
            $value = $this->adjustTimezone($value);
        }

        self::debug(['Comparing "%s" with "%s".'], [$value, $initial]);

        if ($value !== $initial) {
            throw new \Exception(sprintf('DatePicker contains the "%s" but should "%s".', $value, $initial));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isDateAvailable()
    {
        // @todo For now, any out of scope variable inside of "beforeShowDay" method will be undefined.
        // @link https://github.com/refactoror/SelBlocks/issues/5#issuecomment-68511965
        $beforeShowDay = $this->datePicker(['option', 'beforeShowDay']);

        if (!empty($beforeShowDay) && !empty($this->session->evaluateScript("$beforeShowDay($this->jsDate)")[0])) {
            throw new \Exception(sprintf('The "%s" is not available for choosing.', $this->date));
        }

        return $this;
    }

    /**
     * @param array $arguments
     *   jQuery.fn.datepicker arguments.
     *
     * @return mixed
     *   Result of JS execution.
     */
    private function datePicker(array $arguments)
    {
        return $this->jQuery(sprintf("datepicker(%s);", implode(', ', array_map(function ($value) {
            return in_array($value, ['<date>']) ? $this->jsDate : "'$value'";
        }, $arguments))));
    }

    /**
     * Adjust timezone of date returned by DatePicker.
     *
     * @param string $date
     *   Date returned by "jQuery('#selector').datepicker('getDate')".
     *
     * @return string
     *   Date with correct timezone.
     *
     * @link http://stackoverflow.com/a/31560608
     */
    private function adjustTimezone($date)
    {
        $this->session->executeScript(sprintf(
            '%s=%s;%1$s.setMinutes(%1$s.getMinutes()-%1$s.getTimezoneOffset());',
            self::DATE_ADJUSTER,
            self::jsDate($date)
        ));

        return $this->session->evaluateScript(self::DATE_ADJUSTER);
    }

    /**
     * @param string $date
     *   The string to parse.
     *
     * @return string
     */
    protected static function jsDate($date)
    {
        return sprintf("new Date('%s')", date('c', strtotime($date)));
    }
}
