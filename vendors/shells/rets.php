<?php

App::import('Lib', 'Rets.Rets');

class RetsShell extends Shell {
	
	public function main() {
		$this->RETS = new RETS();
		try {
			$this->RETS->connect();			
		} catch (Exception $e) {
			$this->out(sprintf('Unable to connect to RETS server: %s', $e->getMessage()));
		}
		
		$this->out('Connected to RETS.');

	}
}
?>