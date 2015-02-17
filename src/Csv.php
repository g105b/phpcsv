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
private $idField = "ID";

public function __construct($filePath) {
	$this->filePath = $filePath;

	if(!file_exists($filePath)) {
		if(!is_dir(dirname($filePath)) ) {
			mkdir(dirname($filePath), 0775, true);
		}

		touch($filePath);
	}

	$this->file = new File($filePath, "r+");
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
 * Retrieves an array of the CSV file headers, in the order they appear in the
 * file.
 *
 * @return array Indexed array of header names
 */
public function getHeaders() {
	return $this->headers;
}

/**
 * Converts an indexed array of data into an associative array with field names.
 *
 * @param array $data Indexed array of data representing row
 *
 * @return array Associative array of data with field names
 */
public function toAssociative($data) {
	foreach ($data as $i => $value) {
		$headerName = $this->headers[$i];
		$data[$headerName] = $value;
		unset($data[$i]);
	}

	return $data;
}

/**
 * Converts an associative array into an indexed array, according to the
 * currently stored headers.
 *
 * @param array $data Associative array of data representing row
 *
 * @return array Indexed array of data in order of columns
 */
public function toIndexed($data) {
	foreach ($data as $key => $value) {
		$headerIndex = (int)array_search($value, $this->headers);
		$data[$headerIndex] = $value;
		unset($data[$key]);
	}

	return $data;
}

public function getFilePath() {
	return $this->filePath;
}

/**
 * Returns the row at the given index, or the current file pointer position if
 * not supplied. Optionally supply the headers to retrieve, ignoring any others.
 *
 * @param null|int $index Zero-based row number
 * @param array $fetchFields List of fields to include in resulting rows
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

	$row = $this->toAssociative($data);
	return $row;
}

/**
 * Returns a filtered array of rows matching the provided field name/value.
 *
 * @param string $fieldName Name of field to match on
 * @param string $fieldValue Value of field to match
 * @param array $fetchFields List of fields to include in resulting rows
 *
 * @return array Array of associative array rows matching the given filter
 */
public function getAllBy($fieldName, $fieldValue,
$fetchFields = [], $count = 0) {
	$this->file->rewind();

	$result = [];

	while(false !== ($row = $this->get())
	&& ($count === 0 || count($result) < $count)) {
		if(!isset($row[$fieldName])) {
			throw new InvalidFieldException($fieldName);
		}
		if($row[$fieldName] == $fieldValue) {
			$result []= $row;
		}
	}

	return $result;
}

/**
 * Returns the first element in the matching rows, without iterating over the
 * whole data.
 *
 * @param string $fieldName Name of field to match on
 * @param string $fieldValue Value of field to match
 * @param string $fetchFields List of fields to include
 *
 * @return array|null Associative array of first matching row, or null if no
 * match found
 */
public function getBy($fieldName, $fieldValue, $fetchFields = []) {
	$result = $this->getAllBy($fieldName, $fieldValue, $fetchFields, 1);
	return $result[0];
}

/**
 * Sets the interal field used for ID.
 *
 * @param string $idField Name of field (must be within $this->headers)
 *
 * @return string The successfully-set ID field
 */
public function setIdField($idField) {
	if(!in_array($idField, $this->headers)) {
		throw new InvalidFieldException($idField);
	}

	$this->idField = $idField;
	return $idField;
}

/**
 * Retrieves the internally set field used for ID. By default, this is "ID",
 * but if there is no field with that name then this function returns null.
 *
 * @return string|null Name of field, or null if the default field does not
 * exist in the CSV
 */
public function getIdField() {
	if(!in_array($this->idField, $this->headers)) {
		return null;
	}

	return $this->idField;
}

/**
 * Retrieves the first record with the currently set ID field (there should only
 * be one if the data source is used correctly).
 *
 * @param string $idValue Value of the ID field to search for
 * @param array $fetchFields List of fields to include
 *
 * @return array Associative array of first matching row, or null if no match
 * found
 */
public function getById($idValue, $fetchFields = []) {
	return $this->getBy($this->idField, $idValue);
}

/**
 * Adds a single row to the CSV file, the values according to associative
 * array keys matching the currently stored headers. If there are no headers
 * stored, the headers will take the form of the current associative array's
 * keys, in the order they exist in the array.
 *
 * @param array $row Associative array containing key-value pairs. Alternatively
 * an indexed array can be passed in, which will be converted into an
 * associative array from the stored headers
 *
 * @return array Returns the added row in associative form
 */
public function add($row) {
	$rowColumns = $row;
	$rowAssociative = $row;

	if($this->isAssoc($row)) {
		if(!$this->headers) {
			$this->headers = array_keys($row);
		}
		$rowColumns = $this->toIndexed($row);
	}
	else {
		$rowAssociative = $this->toAssociative($row);
	}

	if(!$this->headers) {
		throw new HeadersNotSetException();
	}

	$this->file->fputcsv($rowColumns);
	return $rowAssociative;
}

/**
 * Checks whether a given array is associative or indexed.
 *
 * @param array $array The input array to check
 *
 * @return bool True if input array is associative, false if the input array is
 * indexed
 */
private function isAssoc($array) {
	return array_keys($array) !== range(0, count($array) - 1);
}

}#