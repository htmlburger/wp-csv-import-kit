<?php

namespace Carbon_CSV;

use \Carbon_CSV\CsvFile as CsvFile;

abstract class Import_Process {

	protected $csv;

	public function set_csv(CsvFile $csv) {
		$this->csv = $csv;
	}

	public function get_csv() {
		return $this->csv;
	}

	abstract public function import_row( $row );

	public function setup_csv() {

	}

	public function will_start() {

	}

	public function ended() {

	}
}
