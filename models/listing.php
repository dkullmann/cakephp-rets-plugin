<?php
class Listing extends RetsAppModel {

/**
 * Don't use a table, use RETS
 *
 * @var string
 */
	public $useTable = false;

/**
 * Use the RETS datasource, add to app/config/database.php
 *
 * @var string
 */
	public $useDbConfig = 'rets';
}
?>