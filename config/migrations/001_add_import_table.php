<?php
class M4eb819ffe47446c8a5daffe6f6da17fe extends CakeMigration {

/**
 * Migration description
 *
 * @var string
 * @access public
 */
	public $description = 'Add the rets_imports table to the database';

/**
 * Actions to be performed
 *
 * @var array $migration
 * @access public
 */
	public $migration = array(
		'up' => array(
			'create_table' => array(
				'rets_imports' => array(
					'id' => array('type' => 'string', 'length' => 36, 'key' => 'primary', 'null' => false, 'default' => null),
					'records_found' => array('type' => 'integer', 'length' => 11, 'default' => null),
					'finished' => array('type' => 'boolean', 'default' => false),
					'listing_modified_after' => array('type' => 'datetime'),
					'listing_modified_before' => array('type' => 'datetime'),
					'modified' => array('type' => 'datetime'),
					'created' => array('type' => 'datetime')
				)
			)
		),
		'down' => array(
			'drop_table' => 'rets_imports'
		),
	);

/**
 * Before migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	public function before($direction) {
		return true;
	}

/**
 * After migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	public function after($direction) {
		return true;
	}
}
?>