<?php
/**
 * Thrown when a field is used that doesn't exist in the currently open CSV.
 *
 * @copyright Copyright Ⓒ 2015 Bright Flair Ltd. (http://brightflair.com)
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
namespace g105b\phpcsv;

class InvalidFieldException extends \Exception {}#