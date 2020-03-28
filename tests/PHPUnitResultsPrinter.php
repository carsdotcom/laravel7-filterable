<?php


namespace Tests;


use LimeDeck\Testing\Printer;

class PHPUnitResultsPrinter extends Printer
{

	/**
	 * Replacement symbols for test statuses.
	 *
	 * @var array
	 */
	protected static $symbols = [
		'E' => "\e[31m!\e[0m", // red !
		'F' => "💩 ", // red X
		'W' => "\e[33mW\e[0m", // yellow W
		'I' => "\e[33mI\e[0m", // yellow I
		'R' => "⚠️ ", // yellow R
		'S' => "\e[36mS\e[0m", // cyan S
		'.' => "\e[32m\xe2\x9c\x94\e[0m ", // green checkmark
	];

	/**
	 * @var string
	 */
	protected $previousRowClassName = '';

	/**
	 * @param string $className
	 * @param string $methodName
	 * @param string $time
	 * @param string $color
	 */
	protected function buildTestRow($className, $methodName, $time, $color = 'fg-white')
	{
		if ($this->previousRowClassName !== $className) {
			$this->write(PHP_EOL . $this->colorizeTextBox('bold', $this->colorizeTextBox('fg-cyan', $className)) . PHP_EOL);
			$this->previousRowClassName = $className;
		}

		$this->testRow = sprintf(
			'%s [%s]',
			$this->colorizeTextBox('bold', $this->colorizeTextBox($color, $this->formatMethodName($methodName))),
			$this->formatTestDuration($time)
		);
	}

}
