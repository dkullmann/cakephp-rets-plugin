<?php
class ListingTestCase extends CakeTestCase {
	
	public $Listing = null;

/**
 * Start a new test
 *
 * @return void
 * @author David Kullmann
 */
	public function startTest() {
		$this->Listing = ClassRegistry::init('Rets.Listing');
	}

/**
 * End the test
 *
 * @return void
 * @author David Kullmann
 */
	public function endTest() {
		unset($this->Listing);
		ClassRegistry::flush(); 
	}
	
	public function testFind() {
		$limit = 10;
		
		$query = array(
			'conditions' => array(
				'ModificationTimestamp >=' => '2011-01-01',
				'ModificationTimestamp <='  => '2011-01-02'
			),
			'limit' => $limit
		);
		
		$listings = $this->Listing->find('all', $query);

		$this->assertTrue(is_array($listings));
		$this->assertEqual($limit, count($listings));
	}
	
}
?>