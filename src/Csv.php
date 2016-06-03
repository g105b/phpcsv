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
use \SplTempFileObject as TempFile;

class Csv implements \Iterator {

const TEMP_FILE_SIZE = 64000000;

private $file;
private $filePath;
private $headers;
private $idField = "ID";
private $autoCreate;
private $fileExistsAtStart;
private $changesMade = false;

public function __construct($filePath, $autoCreate = false) {
	$this->filePath = $filePath;
	$this->fileExistsAtStart = file_exists($filePath);

	if(!file_exists($filePath)) {
		if(!is_dir(dirname($filePath)) ) {
			mkdir(dirname($filePath), 0775, true);
		}

		touch($filePath);
	}

	if(is_dir($filePath)) {
		throw new InvalidPathException($filePath);
	}

	ini_set("auto_detect_line_endings", true);

	$this->autoCreate = $autoCreate;
	$this->file = new File($filePath, "r+");
	$this->file->setCsvControl();
	$this->file->setFlags(
		File::READ_CSV |
		File::READ_AHEAD |
		File::SKIP_EMPTY
	);

	$this->saveHeaders();
	$this->file->rewind();
}

public function __destruct() {
	$this->file->flock(LOCK_UN);
	$this->file = null;
	if($this->fileExistsAtStart) {
		return;
	}

	if(!$this->changesMade && !$this->autoCreate) {
		if(file_exists($this->filePath)) {
			unlink($this->filePath);
		}
	}
}

public function current() {
	return $this->toAssociative($this->file->current());
}

public function key() {
	return $this->file->key() - 1;
}

public function next() {
	$this->file->next();
	$this->fixEmpty();
}

public function rewind() {
	$this->saveHeaders();
}

public function valid() {
	$this->fixEmpty();
	return $this->file->valid();
}

private function lock() {
	$lockCounter = 0;
	$lockMax = 1000;

	while(!$this->file->flock(LOCK_EX)) {
		if($lockCounter > $lockMax) {
			throw new FileLockException();
		}

		usleep(100);
		$lockCounter++;
	}
}

/**
 * Check the header line for variations on the default ID field name, fixing
 * the case of the ID field.
 */
private function checkIdField() {
	if(is_null($this->headers)) {
		return;
	}
	if(in_array($this->idField, $this->headers)) {
		return;
	}

	foreach($this->headers as $header) {
		if(strtolower($this->idField) == strtolower($header)) {
			$this->setIdField($header);
		}
	}
}

/**
 * Ensures that the current line is not empty/malformed.
 */
private function fixEmpty() {
	$this->lock();

	$current = $this->file->current();
	while($this->file->valid() && is_null($current[0])) {
		$this->file->next();
		$current = $this->file->current();
	}

	$this->file->flock(LOCK_UN);
}

/**
 * Save the first line of the CSV to $this->headers, according to the current
 * CSV control settings.
 */
private function saveHeaders() {
	$this->lock();
	$this->file->rewind();
	$headers = $this->file->current();
	if(!empty($headers)) {
		$this->headers = $headers;
	}

	$this->file->next();
	$this->file->flock(LOCK_UN);

	$this->checkIdField();
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
 * @param bool $fillMissing True to fill missing indices
 *
 * @return array Indexed array of data in order of columns
 */
public function toIndexed($data, $fillMissing = false) {
	foreach ($data as $key => $value) {
		if(!in_array($key, $this->headers)) {
			throw new InvalidFieldException($key);
		}
		$headerIndex = (int)array_search($key, $this->headers);
		$data[$headerIndex] = $value;
		unset($data[$key]);
	}

	ksort($data);

	if($fillMissing) {
		$data = $this->fillMissing($data);
	}
	return $data;
}

/**
 * Fills any missing keys with blank fields, or merging with an existing data
 * set if provided.
 *
 * @param array $data Indexed or associative array containing row data
 * @param array $existingData Indexed or associative array of existing data to
 * fill in blank fields with
 *
 * @return array Array in the same format (indexed or associative) as the input
 * data array, but with all keys present
 */
private function fillMissing($data, $existingData = []) {
	if($this->isAssoc($data)) {
		foreach ($this->headers as $header) {
			if(!isset($data[$header])) {
				$replaceWith = isset($existingData[$header])
					? $existingData[$header]
					: "";
				$data[$header] = $replaceWith;
			}
		}
	}
	else {
		end($this->headers);
		for($i = 0, $max = key($this->headers); $i <= $max; $i++) {
			if(!isset($data[$i])) {
				$replaceWith = isset($existingData[$i])
					? $existingData[$i]
					: "";
				$data[$i] = $replaceWith;
			}
		}
		ksort($data);
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
 * @param null|int $index Zero-based row number (0 is the first row after the
 * header row)
 * @param array $fetchFields NOT IMPLEMENTED List of fields to include in
 * resulting rows
 *
 * @return array|bool Associative array of fields, or false if index is out
 * of bounds
 */
public function get($index = null, $fetchFields = []) {
	$this->lock();

	if(is_null($index)) {
		$index = $this->file->key();
	}
	else {
		if(!(is_int($index) || ctype_digit($index))
		|| $index < 0) {
			throw new InvalidIndexException($index);
		}
		$index = (int)$index;
	}

	if($index <= $this->file->key() + 1) {
		$this->file->rewind();
	}

	while($index >= $this->file->key()) {
		$this->file->next();
	}

	if(!$this->file->valid()) {
		$this->file->flock(LOCK_UN);
		return false;
	}

	$data = $this->file->current();
	$this->file->flock(LOCK_UN);

	$row = $this->toAssociative($data);
	return $row;
}

/**
 * Returns an array of all rows.
 *
 * @return array Indexed array, containing associative arrays of row data
 */
public function getAll() {
	$this->file->rewind();

	$data = [];
	while(false !== $row = $this->get()) {
		$data []= $row;
	}

	return $data;
}

/**
 * Returns a filtered array of rows matching the provided field name/value.
 *
 * @param string $fieldName Name of field to match on
 * @param string $fieldValue Value of field to match
 * @param array $fetchFields NOT IMPLEMENTED List of fields to include in
 * resulting rows
 *
 * @return array Array of associative array rows matching the given filter
 */
public function getAllBy($fieldName, $fieldValue,
$fetchFields = [], $count = 0) {
	$this->lock();
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
	$this->file->flock(LOCK_UN);

	return $result;
}

/**
 * Returns the first element in the matching rows, without iterating over the
 * whole data.
 *
 * @param string $fieldName Name of field to match on
 * @param string $fieldValue Value of field to match
 * @param string $fetchFields NOT IMPLEMENTED List of fields to include
 *
 * @return array|null Associative array of first matching row, or null if no
 * match found
 */
public function getBy($fieldName, $fieldValue, $fetchFields = []) {
	$result = $this->getAllBy($fieldName, $fieldValue, $fetchFields, 1);
	if(isset($result[0])) {
		return $result[0];
	}
	else {
		return null;
	}
}

/**
 * Sets the interal field used for ID.
 *
 * @param string $idField Name of field (must be within $this->headers)
 *
 * @return string The successfully-set ID field
 */
public function setIdField($idField) {
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
	if(!empty($this->headers)
	&& !in_array($this->idField, $this->headers)) {
		throw new InvalidFieldException($this->idField);
	}

	return $this->idField;
}

/**
 * Retrieves the first record with the currently set ID field (there should only
 * be one if the data source is used correctly).
 *
 * @param string $idValue Value of the ID field to search for
 * @param array $fetchFields NOT IMPLEMENTED List of fields to include
 *
 * @return array Associative array of first matching row, or null if no match
 * found
 */
public function getById($idValue, $fetchFields = []) {
	return $this->getBy($this->getIdField(), $idValue);
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
	$this->changesMade = true;
	$rowColumns = $row;
	$rowAssociative = $row;

	$this->lock();

	if($this->isAssoc($row)) {
		if(!$this->headers) {
			$this->headers = array_keys($row);
			$this->file->fputcsv($this->headers);
		}
		$rowColumns = $this->toIndexed($row, true);
	}
	else {
		$rowAssociative = $this->toAssociative($row);
	}

	if(!$this->headers) {
		throw new HeadersNotSetException();
	}

	$rowColumns = $this->fillMissing($rowColumns);

	$this->file->fseek(0, SEEK_END);
	$this->file->fputcsv($rowColumns);
	$this->file->fflush();

	$this->file->flock(LOCK_UN);
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
	$allIntegerKeys = true;
	foreach ($array as $key => $value) {
		if(!is_integer($key)) {
			$allIntegerKeys = false;
			break;
		}
	}

	return $allIntegerKeys === false;
}

/**
 * Gets the row index of the first match from the provided data. Not all fields
 * do have to be present. Will update the *first* match. If an ID field is set
 * in the supplied data, the match will be made on the field with the ID,
 * otherwise the first row in the CSV to match all provided fields will be used.
 *
 * @param array $data Associative array representing row
 *
 * @return int|false Index of the first matching row, or false if there is no
 * match
 */
public function getRowNumber($data) {
	foreach ($this as $rowNumber => $row) {
		if($rowNumber == 0) {
			// Don't match on the header row!
			continue;
		}
		// For speed, match on ID first, if set.
		if(isset($data[$this->idField])) {
			if($row[$this->idField] != $data[$this->idField]) {
				continue;
			}
			return (int)$rowNumber;
		}
		else {
			$foundMatch = true;
			foreach ($data as $key => $value) {
				// As soon as a field does not match, move to the next row.
				if($row[$key] != $value) {
					$foundMatch = false;
					break;
				}
			}

			if($foundMatch) {
				return (int)$rowNumber;
			}
		}
	}

	return false;
}

/**
 * Update the first matching row's contents. Not all fields do have to be
 * present. Will update the *first* match. If an ID field is set in the
 * supplied data, the match will be made on the field with the ID, otherwise
 * the first row in the CSV to match all provided fields will be used.
 *
 * @param array $data Associative array representing row
 *
 * @return boolean True if any changes were made, otherwise false
 */
public function update($data) {
	$rowNumber = $this->getRowNumber($data);
	return $this->updateRow($rowNumber, $data);
}

/**
 * Delete the first matching row. Not all fields do have to be present. Will
 * update the *first* match. If an ID field is set in the supplied data, the
 * match will be made on the field with the ID, otherwise the first row in the
 * CSV to match all provided fields will be used.
 *
 * @param array $data Associative array representing row
 *
 * @return boolean True if any changes were made, otherwise false
 */
public function delete($data) {
	$rowNumber = $this->getRowNumber($data);
	return $this->deleteRow($rowNumber, $data);
}

/**
 * Removes a row from the CSV file by streaming to a temporary file, ignoring
 * the specified line number(s).
 *
 * @param int|array $rowNumber The integer row number to remove, or an array of
 * integers to remove multiple rows
 *
 * @return boolean True if any changes were made, otherwise false
 */
public function deleteRow($rowNumber) {
	return $this->updateRow($rowNumber, null);
}

/**
 * Removes a row from the CSV file by streaming to a temporary file, ignoring
 * the specified line number(s).
 *
 * @param int|array $rowNumber The zero-based integer row number to remove,
 * or an array of integers to remove multiple rows (row 0 is header row)
 * @param mixed $replaceWith The row data to replace with, or null to just
 * remove to original row
 *
 * @return boolean True if any changes were made, otherwise false
 */
public function updateRow($rowNumber, $replaceWith) {
	$this->changesMade = true;
	$changed = false;
	$rowNumberArray = [];

	// Ensure we are working with an array.
	if(is_array($rowNumber)) {
		$rowNumberArray = $rowNumber;
	}
	else {
		array_push($rowNumberArray, $rowNumber);
	}

	$temp = new TempFile(self::TEMP_FILE_SIZE);
	$temp->setFlags(
		File::READ_CSV |
		File::READ_AHEAD |
		File::SKIP_EMPTY |
		File::DROP_NEW_LINE
	);

	$this->lock();
	$this->file->fseek(0);

	// Copy contents of file into temp:
	while(!$this->file->eof()) {
		$temp->fwrite($this->file->fread(1024));
	}

	$temp->rewind();
	$this->file->ftruncate(0);
	$this->file->fseek(0);
	foreach ($temp as $rowNumber => $row) {
		if(in_array($rowNumber - 1, $rowNumberArray)) {
			// Current row is to be updated or deleted. Do not write original
			// row back to file.
			if(!is_null($replaceWith)) {
				// Ensure that $replaceWidth is an indexed array.
				if($this->isAssoc($replaceWith)) {
					$replaceWith = $this->toIndexed($replaceWith);
				}
				$replaceWith = $this->fillMissing($replaceWith, $row);
				$this->file->fputcsv($replaceWith);
			}
			$changed = true;
			continue;
		}

		$this->file->fputcsv($row);
	}

	$this->file->flock(LOCK_UN);

	return $changed;
}

}#
