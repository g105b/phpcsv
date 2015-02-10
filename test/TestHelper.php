<?php
/**
 * @copyright Copyright Ⓒ 2015 Bright Flair Ltd. (http://brightflair.com)
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
namespace g105b\phpcsv;

class TestHelper {

const RANDOM_TEST_COUNT = 10;

private static $headers = [
	"firstName",
	"lastName",
	"age",
	"gender",
	"phone",
	"rowNum",
];
private static $nameLength = [4, 10];
private static $characterList = ["aeiou", "bcdfghjklmnprstuvwyz"];
private static $recordRandomLength = [10, 1000];

/**
 * Gets the temporary path to the directory used by test files.
 *
 * @return string Absolute file path to directory
 */
public static function getTempPath() {
	return sys_get_temp_dir() . "/g105b-phpcsv";
}

/**
 * Returns an array of randomised filepaths to CSV files within nested temp
 * directories.
 */
public static function data_randomFilePath() {
	$filePathArray = [];

	$nesting = 3;

	$basePath = self::getTempPath();

	for($i = 0; $i < self::RANDOM_TEST_COUNT; $i++) {
		$path = $basePath;

		for($nestLevel = 0; $nestLevel < $nesting; $nestLevel++) {
			$path .= "/" . uniqid("dir");
			$file = "/" . uniqid("file") . ".csv";
			$filePathArray []= [$path . $file];
		}
	}

	return $filePathArray;
}

/**
 * Creates a CSV file at the provided path and fills it with random data.
 *
 * @param string $filePath Absolute path to file on disk
 *
 * @return array Array of rows added to the CSV
 */
public static function createCsv($filePath, $records = 0) {
	$rows = [];

	if(!is_dir(dirname($filePath))) {
		mkdir(dirname($filePath), 0775, true);
	}
	$fp = fopen($filePath, "w");
	fputcsv($fp, self::$headers);

	$rows []= self::$headers;

	if($records === 0) {
		$records = rand(
			self::$recordRandomLength[0],
			self::$recordRandomLength[1]
		);
	}

	for($i = 0; $i < $records; $i++) {
		$name = self::generateName();

		$age = rand(18, 90);
		$gender = rand(0, 1);
		$gender = !!$gender ? "M" : "F";
		$phone = rand(1000000000, 1999999999);
		$phone = "0" . substr($phone, 0, 4) . " " . substr($phone, 4);

		$data = [
			$name[0],
			$name[1],
			$age,
			$gender,
			$phone,
			$i,
		];

		$rows []= $data;

		fputcsv($fp, $data);
	}

	fclose($fp);

	return $rows;
}

/**
 * Recursively deletes a whole directory tree (used for tidying up after tests).
 *
 * @param string $dir Absolute path to remove
 */
public static function removeDir($dir) {
	if(!is_dir($dir)) {
		return;
	}

	$fileArray = new \RecursiveIteratorIterator(
		new \RecursiveDirectoryIterator(
			$dir,
			\RecursiveDirectoryIterator::SKIP_DOTS),
		\RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ($fileArray as $fileinfo) {
		$func = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
		$func($fileinfo->getRealPath());
	}

	rmdir($dir);

}

/**
 * Generates a pronouncably-random first and last name.
 *
 * @return array Array containing two indices for the first and last name
 */
private static function generateName() {
	$firstNameLength = rand(self::$nameLength[0], self::$nameLength[1]);
	$lastNameLength = rand(self::$nameLength[0], self::$nameLength[1]);

	$firstName = "";
	$lastName = "";
	$currentCharacterset = rand(0, 1);

	for($i = 0; $i < $firstNameLength; $i++) {
		$set = self::$characterList[$currentCharacterset];
		$randC = rand(0, strlen($set) - 1);
		$firstName .= $set[$randC];
		$currentCharacterset = !$currentCharacterset;
	}

	for($i = 0; $i < $lastNameLength; $i++) {
		$set = self::$characterList[$currentCharacterset];
		$randC = rand(0, strlen($set) - 1);
		$lastName .= $set[$randC];
		$currentCharacterset = !$currentCharacterset;
	}

	$firstName = ucfirst($firstName);
	$lastName = ucfirst($lastName);

	return [$firstName, $lastName];
}

}#