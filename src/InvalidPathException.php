<?php
/**
 * Thrown when the path to the CSV file is invalid, such as when a directory
 * is used rather than a file.
 *
 * @copyright Copyright Ⓒ 2015 Bright Flair Ltd. (http://brightflair.com)
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
namespace g105b\phpcsv;

class InvalidPathException extends \Exception {}#