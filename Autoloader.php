<?php

/**
 * Extended autoloader class
 * It supports PSR4 and Zend file naming standards currently
 *
 * @author Dmitry Stillman
 * @copyright 2014, Dmitry Stillman (https://github.com/stillman)
 * @version 1.0.0
 * @license BSD
 *
 * Example usage:
 *
 * First, you need to fill in Autoloader::$classmap variable
 *
 * IMPORTANT: You must use more specific prefixes before the more general ones to avoid possible side effects,
 *            ex. Vendor\Package\Subpackage should go before Vendor\Package
 *
 * Autoloader::$classmap = [
 *     self::STANDARD_PSR4 => [
 *         // Class prefix => paths and other options
 *         'Vendor\Package' => '/lib/vendor', // or
 *         'Vendor2\Package' => ['path' => '/lib/vendor', '/common/lib/vendor'], // multiple paths
 *         'Vendor2\Package' => ['path' => ['/lib/vendor2', '/common/lib/vendor2']], // multiple paths
 *         'Vendor3\Package' => ['path' => '/lib/vendor3', 'translate' => 'strtolower'], // class name translation
 *         '*' => '/lib/default' // You can use default path that works for all class names
 *     ],
 * ];
 *
 * Then, activate the autoloader
 *
 * spl_autoload_register(['Stillman\Utils\Autoloader', 'autoload']);
 *
 */

namespace Stillman\Utils;

class Autoloader
{
	const STANDARD_PSR4 = 'psr4';
	const STANDARD_ZEND = 'zend';

	public static $classmap = [
		self::STANDARD_PSR4 => [],
		self::STANDARD_ZEND => [],
	];

	public static $extension = '.php';

	public static function autoload($class_name)
	{
		$class_name = ltrim($class_name, '\\');

		$standard = (strpos($class_name, '\\') !== false)
			? static::STANDARD_PSR4
			: static::STANDARD_ZEND;

		$found = false;

		foreach (static::$classmap[$standard] as $prefix => $path)
		{
			if (strpos($class_name, $prefix) === 0)
			{
				$found = true;
				break;
			}
		}

		if ( ! $found)
		{
			if ( ! isset(static::$classmap[$standard]['*']))
			{
				// Prefix has not been found, and there is no default prefix
				return false;
			}

			// Use the default prefix
			$prefix = '';
			$path = static::$classmap[$standard]['*'];
		}

		if ($prefix)
		{
			$class_name = substr($class_name, strlen($prefix) + 1);
		}

		$class_name = ($standard === static::STANDARD_PSR4)
			? str_replace('\\', DIRECTORY_SEPARATOR, $class_name)
			: str_replace('_', DIRECTORY_SEPARATOR, $class_name);

		if (is_array($path))
		{
			if (isset($path['translate']))
			{
				$func = $path['translate'];
				$class_name = $func($class_name);
				$path = $path['path'];
			}
		}

		return static::tryFile($class_name, $path);
	}

	protected static function tryFile($filename, $path)
	{
		if (is_array($path))
		{
			foreach ($path as $_path)
			{
				if (static::tryFile($filename, $_path))
					return true;
			}

			return false;
		}

		$filename = $path.DIRECTORY_SEPARATOR.$filename.static::$extension;

		if (file_exists($filename))
		{
			require $filename;
			return true;
		}

		return false;
	}
}