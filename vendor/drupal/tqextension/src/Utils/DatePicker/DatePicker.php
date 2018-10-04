<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Utils\DatePicker;

// Contexts.
use Drupal\TqExtension\Context\RawTqContext;
// Helpers.
use Behat\DebugExtension\Debugger;

class DatePicker implements DatePickerInterface
{
    use Debugger;

    /**
     * @var DatePickerInterface
     */
    private $datePicker;

    /**
     * {@inheritdoc}
     *
     * @see DatePickerBase::__construct()
     */
    public function __construct(RawTqContext $context, $selector, $date)
    {
        $session = $context->getSession();

        if (empty($session->evaluateScript('jQuery.fn.datepicker'))) {
            throw new \RuntimeException('jQuery DatePicker is not available on the page.');
        }

        // Drupal 8 will use native "date" field if available.
        $class = $session->evaluateScript('Modernizr && Modernizr.inputtypes.date') ? Native::class : JQuery::class;

        self::debug(['The "%s" date picker will be used.'], [$class]);

        $this->datePicker = new $class($context, $session, $context->element('*', $selector), $date);
    }

    /**
     * {@inheritdoc}
     */
    public function setDate()
    {
        return $this->datePicker->{__FUNCTION__}();
    }

    /**
     * {@inheritdoc}
     */
    public function isDateSelected()
    {
        return $this->datePicker->{__FUNCTION__}();
    }

    /**
     * {@inheritdoc}
     */
    public function isDateAvailable()
    {
        return $this->datePicker->{__FUNCTION__}();
    }
}
