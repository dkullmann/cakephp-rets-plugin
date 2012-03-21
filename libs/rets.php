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
	public static $resources = array();
	
	
	/**
	 * When a list of classes is found for a resource, cache it here
	 *
	 * @author David Kullmann
	 */
	public static $resourceClasses = array();
	
	/**
	 * Used to cache a list of table elements for a class
	 *
	 * @author David Kullmann
	 */
	public static $tables = array();

	/**
	 * Map of field types to CakePHP DBOMySQL field types
	 *
	 * @var string
	 */
	public static $typeMap = array(
		'Character' => 'string',
		'Long' => 'string',
		'DateTime' => 'datetime',
		'Date' => 'date',
		'Boolean' => 'boolean',
		'Decimal' => 'decimal',
		'Tiny' => 'integer',
		'Small' => 'integer',
		'Int' => 'integer'
	);
	
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
		
		self::connect();
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
	
	/**
	 * Get the latest record count
	 *
	 * @param string $index Index of the query
	 * @return void
	 * @author David Kullmann
	 */
	public function getRecordCount($index = null) {
		if($index === null) {
			$index = self::$phRETS->LastRequestPointer();
		}
		return self::$phRETS->search_data[$index]['total_records_found'];
	}

	/**
	 * Get a list of resources for this RETS feed
	 *
	 * @return array List of resources for this RETS connection
	 * @author David Kullmann
	 */
	public function getResources() {
		return self::$resources;
	}
	
	/**
	 * Get a list of the classes available for a particular resource
	 *
	 * @param string $resource Rets resource name 
	 * @return array Array of classes for the resource given
	 * @author David Kullmann
	 */
	public function getClassesForResouces($resource = null) {
		if(empty(self::$resourceClasses[$resource])) {
			self::$resourceClasses[$resource] = self::$phRETS->GetMetadataClasses($resource);
		}
		return self::$resourceClasses[$resource];
	}
	
	/**
	 * Get table elements for a particular class-resource
	 *
	 * @param string $resource Rets resource name 
	 * @param string $class Class name
	 * @return array Array of data representing the table information
	 * @author David Kullmann
	 */
	public function getTable($resource = null, $class = null) {
		if(empty(self::$tables[$resource][$class])) {
			self::$tables[$resource][$class] = self::$phRETS->GetMetadataTable($resource, $class);
		}
		return self::$tables[$resource][$class];
	}

	/**
	 * Return the length of a db column based on the type of field
	 *
	 * @param array $fieldSpec 
	 * @return integer max field length
	 * @author David Kullmann
	 */
	public function convertField($fieldSpec, $resource) {
		$type = self::$typeMap[ $fieldSpec['DataType'] ];
		$length = null;
		$field = $fieldSpec['SystemName'];
		
		$maxSelect = $fieldSpec['MaxSelect'] ? $fieldSpec['MaxSelect'] : 1;
		$maxLength = $fieldSpec['MaximumLength'];
		$interp = $fieldSpec['Interpretation'];
		
		if(in_array($interp, array('Lookup', 'LookupMulti'))) {
			$values = self::$phRETS->GetLookupValues($resource, $fieldSpec['LookupName']);
			foreach($values as $value) {
				if(strlen($value['LongValue']) > $maxLength) {
					$maxlength = strlen($value['LongValue']);
				}
			}
			$length  = ( $maxSelect * ( $maxLength + 3)  ) - 1;
			if($length > 255 && $type === 'string') $type = 'text';
		} else {
			switch($fieldSpec['DataType']) {
				case 'Boolean' :
					$length = 1;
					break;
				case 'Tiny' :
					$length = 2;
					break;
				case 'Small' :
					$length = 6;
					break;
				case 'Int' :
					$length = 11;
					break;
				case 'Long': 
					$length = 20;
					break;
				case 'Decimal' :
					$precision = $fieldSpec['Precision'];
					$length = implode(',', array_filter(array($maxLength, $precision)));
					break;
				case 'Character' :
					if($maxLength > 255) $type = 'text';
					$length = $maxLength;
				case 'DateTime' :
				case 'Date' :
				default:
			};
		}
		return compact('length', 'type');
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
