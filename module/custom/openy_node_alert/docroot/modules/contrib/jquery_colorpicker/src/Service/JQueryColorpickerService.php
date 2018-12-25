<?php

namespace Drupal\jquery_colorpicker\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\jquery_colorpicker\Service\JQueryColorpickerServiceInterface;

class JQueryColorpickerService implements JQueryColorpickerServiceInterface
{
	use StringTranslationTrait;

	/**
	 * {@inheritdoc}
	 */
	public function formatColor($color)
	{
		if(is_scalar($color))
		{
			$color = (string) $color;
			if(strlen($color))
			{
				if(preg_match('/^#/', $color))
				{
					$color = substr($color, 1);
				}
			}
		}
		else
		{
			$color = '';
		}

		return $color;
	}

	/**
	 * {@inheritdoc}
	 */
	public function validateColor($color)
	{
		$error = FALSE;

		if(is_string($color) || is_int($color))
		{
			if(strlen($color) != 6)
			{
				$error = $this->t('Color values must be exactly six characters in length');
			}
			// All values must be hexadecimal values.
			elseif(!preg_match('/^[0-9a-fA-F]{6}$/i', $color))
			{
				$error = $this->t("You entered an invalid value for the color. Colors must be hexadecimal, and can only contain the characters '0-9', 'a-f' and/or 'A-F'.");
			}
		}
		else
		{
			$error = $this->t('Color must be a string or an integer');
		}

		return $error;
	}
}
