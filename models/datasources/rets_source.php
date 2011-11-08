<?php
App::import('Lib', 'Rets.Rets');
class RetsSource extends DataSource {
	
/**
 * A default Resource and Class to use based on a common Resource/Class in CARETS DB
 *
 * @var array
 */
	public $defaultResourceClass = array('Property', 'RES');

/**
 * A map of metadata values
 *
 * @var array
 */
	public $keyMap = array(
		'name' => 'SystemName',
		'type' => 'DataType',
		'interp' => 'Interpretation',
		'length' => 'MaximumLength',
		'precision' => 'Precision',
		'index' => 'Index'
	);

/**
 * DMQL DateTime operator conversion map
 *
 * @var array
 */
	public $dateTimeOpMap = array('>=' => '+', '<=' => '-', '=' => '');
	
/**
 * The maximum number of records we can safely convert to an array
 *
 * @var string
 */
	public $maxLimit = 1000;
	
	public $defaultOptions = array(
		'count' => 1,
		'format' => 'COMPACT-DECODED',
		'limit' => 1000,
		'offset' => null,
	);

/**
 * Construct the class
 *
 * @param string $config 
 * @author David Kullmann
 */
	public function __construct($config) {
		
		Configure::write('debug', 2);
		
		$this->RETS = new RETS();
		$this->RETS->connect();	
		$this->_sources = array_keys($this->RETS->GetMetadataResources());
	}
	
	public function describe($model = null) {
		list($resource, $class) = $this->_getResourceClass($model);

		if (empty($this->_schema[$resource][$class])) {
			$fields = $this->RETS->GetMetadataTable($resource, $class);
			foreach ($fields as $field) {
				foreach ($this->keyMap as $key => $value) {
					$name = $field[$this->keyMap['name']];
					if ($key == 'name') {
						continue;
					}
					$this->_schema[$resource][$class][$name][$key] = $field[$value];
				}
			}
		}
		
		return $this->_schema[$resource][$class];
	}

/**
 * Read data from phRets source
 *
 * @param Model $model 
 * @param array $query Query conditions
 * @return array Records found
 * @author David Kullmann
 */
	public function read($model, $query = array()) {
		list($resource, $class) = $this->_getResourceClass($model);

		$options = array_merge(
			$this->defaultOptions,
			Set::filter(array_intersect_key($query, $this->defaultOptions))
		);

		if ($options['limit'] > $this->maxLimit) {
			throw new OutOfRangeException(sprintf('Limit must be below %s', $this->maxLimit));
		}

		$options = $this->_DMQLOptions($options);

		if (!empty($query['conditions']))	 {
			$query['conditions'] = $this->_conditionsToDMQL($model, $query['conditions']);
		}

		$results = $this->RETS->Search($resource, $class, $query['conditions'], $options);
		/* Avoid some stupid error by reconnecting */
		if ($this->RETS->Error()) {
			$this->RETS->disconnect();	
			$this->RETS->connect();	
		}
		if ($options['Count'] == 2) {
			return $this->RETS->getRecordCount();
		}
		return $results;
	}
	
	public function calculate(&$model, $func, $params = array()) {
	}
		
	public function listSources() {
		return $this->_sources;
	}

/**
 * Get the RETS Resource and Class for a model
 *
 * @param string $model 
 * @return void
 * @author David Kullmann
 */
	protected function _getResourceClass($model) {
		$options = array();
		if (!$model || empty($model->resource)) {
			$options = $this->defaultResourceClass;
		} else {
			$options[0] = $model->resource;
			if (!empty($model->class)) {
				$options[1] = $model->class;
			} else {
				$options[1] = 'ALL';
			}
		}
		return $options;
	}
	
	protected function _conditionsToDMQL($model, $conditions = array()) {
		$schema = $this->describe($model);
		
		$return = array();
		
		foreach ($conditions as $key => $value) {
			$parts = preg_split('/\s+/', $key);
			
			if(empty($parts[1])) {
				$parts[1] = '=';
			}
			
			list($key, $operator) = $parts;
			
			$field = $schema[$key];
			$return[] = $this->_generateSearchClause($key, $value, $operator, $field);
		}
		return implode(',', $return);
	}

/**
 * Generate a DMQL fragment based on input
 *
 * @param string $key Fieldname
 * @param string $value The value set for the field
 * @param string $operator Operator, varies depending on field
 * @param string $fieldData Metadata for this field
 * @return string DMQL fragment
 * @author David Kullmann
 */
	protected function _generateSearchClause($key, $value, $operator = '=', $fieldData) {
		
		$clause = null;
		
		if ($fieldData['type'] == 'Long') {
			$clause = sprintf('(%s)', implode($operator, array($key, $value)));
		} elseif ($fieldData['type'] == 'DateTime') {
			
			if(!isset($this->dateTimeOpMap[$operator])) {
				throw new OutOfBoundsException(
					sprintf('Specified operator incompatible with DateTime datatype (%s)', implode(', ', array_keys($this->dateTimeOpMap)))
				);
			}
			
			$values = preg_split('/\D+/', $value);
			
			if(count($values) < 6) {
				$dates = preg_split('/\D+/', date('Y-m-d H:i:s', 0));
				$values = array_merge($values, array_diff_key($dates, $values));
			}
			
			$value = vsprintf('%04d-%02d-%02dT%02d:%02d:%02d', $values);
			
			$operator = $this->dateTimeOpMap[$operator];
			$clause = sprintf('(%s%s)', implode('=', array($key, $value)), $operator);
		}
		return $clause; 
	}
	
	protected function _DMQLOptions($options = array()) {
		$return = array();
		foreach ($options as $key => $value) {
			$return[ucfirst($key)] = $value;
		}
		return $return;
	}
}
?>