<?php
class RetsImport extends RetsAppModel {
	

/**
 * Additional find methods available
 *
 * @var string
 */
	public $_findMethods = array('latest' => true);

/**
 * find('latest') defaults
 *
 * @see Model::__construct()
 * @var array
 */
	public $findLatestDefaults = array();

/**
 * Default time to start importing from, strtotime() compatible string
 *
 * @see http://us3.php.net/manual/en/function.strtotime.php
 * @see http://us3.php.net/manual/en/datetime.formats.relative.php
 * @var string
 */
	public $startImport = '1 year ago';
	
/**
 * Default range to use for each import, strtotime() compatible string
 *
 * @see http://us3.php.net/manual/en/function.strtotime.php
 * @see http://us3.php.net/manual/en/datetime.formats.relative.php
 * @var string
 */
	public $defaultRange = '1 week';

/**
 * Number of listings to import in one query
 *
 * @var integer
 */
	public $batchSize = 500;

/**
 * Construct the model
 *
 * @param string $id 
 * @param string $table 
 * @param string $ds 
 * @author David Kullmann
 */
	public function __construct( $id = false, $table = NULL, $ds = NULL ) {
		parent::__construct($id, $table, $ds);
		$this->findLatestDefaults = array(
			'order' => array(
				sprintf('%s.finished', $this->alias) => 'ASC',
				sprintf('%s.created', $this->alias) => 'DESC'
			)
		);
	}

/**
 * Find the latest import
 *
 * @param string $state 
 * @param string $query 
 * @param string $resutls 
 * @return void
 * @author David Kullmann
 */
	protected function _findLatest($state, $query, $results = array()) {

		if ($state == 'before') {
			return array_merge($query, $this->findLatestDefaults);
		} elseif($state == 'after') {
			if(!empty($results[0])) {
				return $results[0];
			}
			return false;
		}
	}

/**
 * Start a new import
 *
 * @param array $data Start time, end time, and record count for this import
 * @return array This import record
 * @author David Kullmann
 */
	public function startImport($data) {
		$data['finished'] = false;
		$this->set($data);
		if(!$this->validates() || !$this->save($data)){
			throw new OutOfBoundsException('Unable to start import');
		}
		return $this->read();
	}

/**
 * Mark an import as finished
 *
 * @param array $data Import data 
 * @return array Import record
 * @author David Kullmann
 */
	public function finishImport($data) {
		$data['finished'] = true;
		$this->set($data);
		if(!$this->validates() || !$this->save($data)){
			throw new OutOfBoundsException('Unable to start import');
		}
		return $this->read();
	}

/**
 * Calculate the dates to use for this import
 *
 * @param mixed $lastImport last import record, last import time, or null 
 * @return array First value is start time, second value is end time
 * @author David Kullmann
 */
	public function getImportDates($lastImport = null) {
		if (empty($lastImport)) {
			$lastImport = date('Y-m-d H:i:s', strtotime($this->startImport));
		}
		
		if (is_array($lastImport)) {
			if (!$lastImport[$this->alias]['finished']) {
				return array($lastImport[$this->alias]['listing_modified_after'], $lastImport[$this->alias]['listing_modified_before']);
			} else {
				$lastImport = $import[$this->alias]['listing_modified_before'];
			}
		}
		
		$time = strtotime($this->defaultRange, strtotime($lastImport));
		
		$now  = strtotime('now');
		if($time > $now) {
			$time = $now;
		}
		
		$endDate = date('Y-m-d H:i:s', $time);
		
		return array($lastImport, $endDate);
	}
	
}
?>