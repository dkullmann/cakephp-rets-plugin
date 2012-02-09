<?php
/**
 * Import the phRETS library
 *
 * @author David Kullmann
 */
App::import('Vendor', 'Rets.phRETS', array('file' => 'vendors'.DS.'phrets.php'));

/**
 * Whatever
 */
define('RETS_VERSION_HEADER', 'RETS-Version');
define('USERAGENT_HEADER', 'User-Agent');

/**
 * Class for connecting via phRETS and forwarding methods to phRETS
 *
 * @package rets.lib.rets
 * @author David Kullmann
 */
class RETS {
	
	/**
	 * phRETS object
	 *
	 * @author David Kullmann
	 */
	public static $phRETS;
	
	/**
	 * Configuration options
	 *
	 * @author David Kullmann
	 */
	public static $config;
	
	/**
	 * Connection status
	 *
	 * @author David Kullmann
	 */
	public static $connection;
	
	/**
	 * Available RETS resources and their class information
	 *
	 * @author David Kullmann
	 */
	public static $resources;
	
	/**
	 * Get the config and initialize the phRETS object
	 *
	 * @author David Kullmann
	 */
	public function __construct() {
		if (empty(self::$phRETS)) {
			self::$phRETS = new phRETS;
		}
		self::$config = Configure::read('RETS');
		if (!is_array(self::$config)) {
			throw new Exception('Configure::read("RETS") was not able to load your settings');
		}
	}
	
	/**
	 * Connect via phRETS and save the connection
	 *
	 * @param string $options 
	 * @return void
	 * @author David Kullmann
	 */
	public function connect($options = array()) {
		$options = array_merge(self::$config, $options);
		
		if (!empty($options['rets_version'])) {
			self::$phRETS->AddHeader(RETS_VERSION_HEADER, $options['rets_version']);
		}
		
		if (!empty($options['useragent'])) {
			self::$phRETS->AddHeader(USERAGENT_HEADER, $options['useragent']);
		}
		
		self::$connection = self::$phRETS->Connect($options['login_url'], $options['username'], $options['password']);

		if (!self::$connection) {
			$exception = self::$phRETS->error();
			throw new Exception($exception['text']);
		}

		self::$resources = Set::combine(self::$phRETS->GetMetadataTypes(), '{n}.Resource', '{n}.Data.0');
	}
	
	/**
	 * Disconnect phRETS and unset the connection
	 *
	 * @return void
	 * @author David Kullmann
	 */
	public function disconnect() {
		self::$phRETS->Disconnect();
		self::$connection = false;
	}
	
	public function getRecordCount() {
		return self::$phRETS->search_data[ self::$phRETS->getResultPointer() ]['total_records_found'];
	}
	
	public function getResources() {
		return self::$resources;
	}
	
	/**
	 * Forward methods to phRETS
	 *
	 * @param string $method 
	 * @param string $params 
	 * @return void
	 * @author David Kullmann
	 */
	public function __call($method, $params) {
		try {
			return call_user_func_array(array(self::$phRETS, $method), $params);
		} catch (Exception $e) {
			error_log($e);
		}
	}
	
	/**
	 * Forward static methods to phRETS
	 *
	 * @param string $method 
	 * @param string $params 
	 * @return void
	 * @author David Kullmann
	 */
	public static function __callstatic($method, $params) {
		try {
			return call_user_func_array(array(self::$phRETS, $method), $params);
		} catch (Exception $e) {
			error_log($e);
		}
	}
}
?>
