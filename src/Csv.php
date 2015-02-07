<?php
/**
 * Wraps SplFileObject's CSV capabilities with a more human approach, taking
 * into account the header row and iteration.
 *
 * @copyright Copyright Ⓒ 2015 Bright Flair Ltd. (http://brightflair.com)
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
namespace g105b\phpcsv;

use \SplFileObject as File;
// use \SplTempFileObject as Temp;

class Csv {

private $file;
private $filePath;
private $headers;

public function __construct($filePath) {
	$this->filePath = $filePath;
	$this->file = new File($filePath);
	$this->file->setFlags(
		File::READ_CSV |
		File::READ_AHEAD |
		File::SKIP_EMPTY |
		File::DROP_NEW_LINE
	);
	$this->saveHeaders();
}

/**
 * Save the first line of the CSV to $this->headers, according to the current
 * CSV control settings.
 */
private function saveHeaders() {
	$this->file->rewind();
	$this->headers = $this->file->current();
	$this->file->next();
}

/**
 * Converts an indexed array of data into an associative array with field names.
 *
 * @param array $data Indexed array of data representing row
 *
 * @return array Associative array of data with field names
 */
private function buildRow($data) {
	foreach ($data as $i => $value) {
		$headerName = $this->headers[$i];
		$data[$headerName] = $value;
		unset($data[$i]);
	}

	return $data;
}

public function getFilePath() {
	return $this->filePath;
}

/**
 * Returns an indexed array of headers in the CSV, in the same order as their
 * columns are stored.
 *
 * @return array An array with an element for each header column
 */
public function getHeaders() {
	return $this->headers;
}

/**
 * Returns the row at the given index, or the current file pointer position if
 * not supplied. Optionally supply the headers to retrieve, ignoring any others.
 *
 * @param null|int $index Zero-based row number
 * @param array $fetchFields List of fields to fetch
 *
 * @return array|bool Associative array of fields, or false if index is out
 * of bounds
 */
public function get($index = null, $fetchFields = []) {
	if(is_null($index)) {
		$index = $this->file->key() - 1;
	}

	while($index >= $this->file->key()) {
		$this->file->next();
	}

	if(!$this->file->valid()) {
		return false;
	}

	$data = $this->file->current();
	$this->file->next();

	$row = $this->buildRow($data);
	return $row;
}

}#