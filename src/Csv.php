<?php
/**
 * Wraps SplFileObject's CSV capabilities with a more human approach, taking
 * into account the header row and iteration.
 *
 * @copyright Copyright Ⓒ 2015 Bright Flair Ltd. (http://brightflair.com)
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
namespace g105b\phpcsv;

class Csv {

private $filePath;

public function __construct($filePath) {
	$this->filePath = $filePath;
}

public function getFilePath() {
	return $this->filePath;
}

}#