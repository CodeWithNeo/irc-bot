<?php

/**
 * IRC Bot
 *
 * LICENSE: This source file is subject to Creative Commons Attribution
 * 3.0 License that is available through the world-wide-web at the following URI:
 * http://creativecommons.org/licenses/by/3.0/.  Basically you are free to adapt
 * and use this script commercially/non-commercially. My only requirement is that
 * you keep this header as an attribution to my work. Enjoy!
 *
 * @license http://creativecommons.org/licenses/by/3.0/
 *
 * @package WildPHP
 */
namespace WildPHP\core;

class Autoloader
{
	public $fixes = array(
		'Nette\\Neon\\Neon' => 'Neon',
		'Nette\\Neon\\Encoder' => 'Neon\\',
			
		'\\' => '/',
	);
	static function load($class)
	{
		$fixes = array(
			'WildPHP\\' => '',
			'Nette\\Neon' => 'Neon',
				
			'\\' => '/',
		);
		
		// We'll be checking for the last bit of the class string.
		$class = str_replace(array_keys($fixes), array_values($fixes), $class);
		
		echo var_dump($class);
		
		// Check for lib/Class.php...
		if (file_exists(WPHP_ROOT . '/' . $class . '.php'))
			require_once(WPHP_ROOT . '/' . $class . '.php');
			
		// lib/Class/Class.php maybe?
		elseif (file_exists(WPHP_ROOT . '/lib/' . $class . '.php'))
			require_once(WPHP_ROOT . '/lib/' . $class . '.php');
	}
}