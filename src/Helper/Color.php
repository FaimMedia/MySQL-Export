<?php

namespace FaimMedia\MySQLJSONExport\Helper;

class Color {

	/**
	 * Returns a parsed colored string for CLI output
	 */
	public static function parse($string, $foregroundColor = null, $backgroundColor = null, $bold = false) {
	// Set up shell colors
		$colors = [
			'black'  => 30,
			'red'    => 31,
			'green'  => 32,
			'yellow' => 33,
			'blue'   => 34,
			'purple' => 35,
			'cyan'   => 36,
			'white'  => 37,
		];

		$returnString = "";

	// Check if given foreground color found
		if (isset($colors[$foregroundColor])) {
			$returnString .= "\033[" . ($bold ? '1' : '0') . ';' . $colors[$foregroundColor] . "m";
		}

	// Check if given background color found
		if (isset($colors[$backgroundColor])) {
			$returnString .= "\033[" . ($colors[$backgroundColor] + 10) . "m";
		}

		$returnString .=  $string . "\033[0m";

		return $returnString;
	}
}