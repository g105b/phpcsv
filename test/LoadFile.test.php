<?php
/**
 * Tests the loading of CSV files is done as expected.
 *
 * http://github.com/g105b/phpcsv
 * @copyright Copyright Ⓒ 2015 Bright Flair Ltd. (http://brightflair.com)
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
namespace g105b\phpcsv;

class LoadFile_Test extends \PHPUnit_Framework_TestCase {

const RANDOM_TEST_COUNT = 10;
private $tempPath;

public function setUp() {
	$this->setTempPath();
}
public function tearDown() {
	TestHelper::removeDir($this->tempPath);
}

public function setTempPath() {
	$this->tempPath = sys_get_temp_dir() . "/g105b-phpcsv";
}

/**
 * Returns an array of randomised filepaths to CSV files within nested temp
 * directories.
 */
public function data_randomFilePath() {
	$this->setTempPath();
	$filePathArray = [];

	$nesting = 3;

	$basePath = $this->tempPath;

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
 * @dataProvider data_randomFilePath
 */
public function testLoadCsvFile($filePath) {
	TestHelper::createCsv($filePath);
	$csv = new Csv($filePath);
	$this->assertEquals($filePath, $csv->getFilePath());
}

/**
 * @dataProvider data_randomFilePath
 */
public function testHeaderRowLoad($filePath) {
	$rows = TestHelper::createCsv($filePath);
	$csv = new Csv($filePath);

	$headerRow = $csv->getHeaders();
	$this->assertEquals($rows[0], $headerRow);
}

/**
 * @dataProvider data_randomFilePath
 */
public function testFirstRowLoad($filePath) {
	$rows = TestHelper::createCsv($filePath);
	$csv = new Csv($filePath);

	$firstRow = $csv->get(0);

	foreach ($rows[1] as $i => $value) {
		$headerName = $rows[0][$i];
		$this->assertEquals($firstRow[$headerName], $rows[1][$i]);
	}
}

}#