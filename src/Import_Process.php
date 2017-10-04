<?php

namespace Carbon_CSV;

use \Carbon_CSV\CsvFile as CsvFile;

abstract class Import_Process {

	private $csv;

	abstract public function import_row( $row );

	public function setup_csv() {

	}

	public function will_start() {

	}

	public function ended() {

	}
}
