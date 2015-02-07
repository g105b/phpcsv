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
	$position = $this->file->ftell();
	$this->file->rewind();
	$this->headers = $this->file->current();
	$this->file->seek($position);
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

}#