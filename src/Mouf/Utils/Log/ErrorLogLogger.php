<?php
/*
 * Copyright (c) 2012 David Negrier
 * 
 * See the file LICENSE.txt for copying permission.
 */

namespace Mouf\Utils\Log;

use Mouf\Utils\Log\LogInterface;

/**
 * A logger class that writes messages into the php error_log.
 * Note: any parameter passed in third parameter (in the $additional_parameters array) will be ignored
 * by this logger.
 *
 * @Component
 */
class ErrorLogLogger implements LogInterface {
	
	public static $TRACE = 1;
	public static $DEBUG = 2;
	public static $INFO = 3;
	public static $WARN = 4;
	public static $ERROR = 5;
	public static $FATAL = 6;
	
	/**
	 * The minimum level that will be tracked by this logger.
	 * Any log with a level below this level will not be logger.
	 *
	 * @Property
	 * @Compulsory 
	 * @OneOf "1","2","3","4","5","6"
	 * @OneOfText "TRACE","DEBUG","INFO","WARN","ERROR","FATAL"
	 * @var int
	 */
	public $level;
	
	public function trace($string, \Exception $e=null, array $additional_parameters=array()) {
		if($this->level<=self::$TRACE) {
			self::logMessage("TRACE", $string, $e);
		}
	}
	public function debug($string, \Exception $e=null, array $additional_parameters=array()) {
		if($this->level<=self::$DEBUG) {
			self::logMessage("DEBUG", $string, $e);
		}
	}
	public function info($string, \Exception $e=null, array $additional_parameters=array()) {
		if($this->level<=self::$INFO) {
			self::logMessage("INFO", $string, $e);
		}
	}
	public function warn($string, \Exception $e=null, array $additional_parameters=array()) {
		if($this->level<=self::$WARN) {
			self::logMessage("WARN", $string, $e);
		}
	}
	public function error($string, \Exception $e=null, array $additional_parameters=array()) {
		if($this->level<=self::$ERROR) {
			self::logMessage("ERROR", $string, $e);
		}
	}
	public function fatal($string, \Exception $e=null, array $additional_parameters=array()) {
		if($this->level<=self::$FATAL) {
			self::logMessage("FATAL", $string, $e);
		}
	}

	private static function logMessage($level, $string, $e=null) {
		if ($e == null) {
			if (!$string instanceof \Exception) {
				$trace = debug_backtrace();
				error_log($level.': '.$trace[1]['file']."(".$trace[1]['line'].") ".(isset($trace[2])?($trace[2]['class'].$trace[2]['type'].$trace[2]['function']):"")." -> ".$string);
			} else {
				error_log($level.': '.self::getTextForException($string));
			}
		} else {
			$trace = debug_backtrace();
			error_log($level.': '.$trace[1]['file']."(".$trace[1]['line'].") ".(isset($trace[2])?($trace[2]['class'].$trace[2]['type'].$trace[2]['function']):"")." -> ".$string."\n".self::getTextForException($e));
		}

	}
	
	
	/**
	 * Function called to display an exception if it occurs.
	 * It will make sure to purge anything in the buffer before calling the exception displayer.
	 *
	 * @param \Exception $exception
	 */
	static function getTextForException(\Exception $exception) {
		// Now, let's compute the same message, but without the HTML markup for the error log.
		$textTrace = "Message: ".$exception->getMessage()."\n";
		$textTrace .= "File: ".$exception->getFile()."\n";
		$textTrace .= "Line: ".$exception->getLine()."\n";
		$textTrace .= "Stacktrace:\n";
		$textTrace .= self::getTextBackTrace($exception->getTrace());
		return $textTrace;
	}
	
	/**
	 * Returns the Exception Backtrace as a text string.
	 *
	 * @param unknown_type $backtrace
	 * @return unknown
	 */
	static private function getTextBackTrace($backtrace) {
		$str = '';
	
		foreach ($backtrace as $step) {
			if ($step['function']!='getTextBackTrace' && $step['function']!='handle_error')
			{
				if (isset($step['file']) && isset($step['line'])) {
					$str .= "In ".$step['file'] . " at line ".$step['line'].": ";
				}
				if (isset($step['class']) && isset($step['type']) && isset($step['function'])) {
					$str .= $step['class'].$step['type'].$step['function'].'(';
				}
	
				if (is_array($step['args'])) {
					$drawn = false;
					$params = '';
					foreach ( $step['args'] as $param)
					{
						$params .= self::getPhpVariableAsText($param);
						//$params .= var_export($param, true);
						$params .= ', ';
						$drawn = true;
					}
					$str .= $params;
					if ($drawn == true)
					$str = substr($str, 0, strlen($str)-2);
				}
				$str .= ')';
				$str .= "\n";
			}
		}
	
		return $str;
	}
	
	/**
	 * Used by the debug function to display a nice view of the parameters.
	 *
	 * @param unknown_type $var
	 * @return unknown
	 */
	private static function getPhpVariableAsText($var) {
		if( is_string( $var ) )
		return( '"'.str_replace( array("\x00", "\x0a", "\x0d", "\x1a", "\x09"), array('\0', '\n', '\r', '\Z', '\t'), $var ).'"' );
		else if( is_int( $var ) || is_float( $var ) )
		{
			return( $var );
		}
		else if( is_bool( $var ) )
		{
			if( $var )
			return( 'true' );
			else
			return( 'false' );
		}
		else if( is_array( $var ) )
		{
			$result = 'array( ';
			$comma = '';
			foreach( $var as $key => $val )
			{
				$result .= $comma.self::getPhpVariableAsText( $key ).' => '.self::getPhpVariableAsText( $val );
				$comma = ', ';
			}
			$result .= ' )';
			return( $result );
		}
	
		elseif (is_object($var)) return "Object ".get_class($var);
		elseif(is_resource($var)) return "Resource ".get_resource_type($var);
		return "Unknown type variable";
	}
}

?>