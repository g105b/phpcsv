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
public function testGetSingleRow($filePath) {
	$rows = TestHelper::createCsv($filePath);
	$csv = new Csv($filePath);

	$firstRow = $csv->get(0);

	foreach ($rows[1] as $i => $value) {
		$headerName = $rows[0][$i];
		$this->assertEquals($firstRow[$headerName], $rows[1][$i]);
	}

	$thirdRow = $csv->get(2);

	foreach ($rows[3] as $i => $value) {
		$headerName = $rows[0][$i];
		$this->assertEquals($thirdRow[$headerName], $rows[3][$i]);
	}
}

/**
 * @dataProvider data_randomFilePath
 */
public function testIterator($filePath) {
	$originalRows = TestHelper::createCsv($filePath, 10);
	$csv = new Csv($filePath);

	foreach ($csv as $rowNumber => $row) {
		foreach ($row as $fieldName => $value) {
			$fieldIndex = array_search($fieldName, $originalRows[0]);
			$this->assertEquals($value, $originalRows[$rowNumber][$fieldIndex]);
		}
	}

	// Do it again to check that pointer is reset.
	foreach ($csv as $rowNumber => $row) {
		foreach ($row as $fieldName => $value) {
			$fieldIndex = array_search($fieldName, $originalRows[0]);
			$this->assertEquals($value, $originalRows[$rowNumber][$fieldIndex]);
		}
	}
}

/**
 * @dataProvider data_randomFilePath
 */
public function testGetIncrements($filePath) {
	$originalRows = TestHelper::createCsv($filePath, 10);
	$csv = new Csv($filePath);

	$rowNumber = 0;
	while($row = $csv->get()) {
		$rowNumber++;

		foreach ($row as $fieldName => $value) {
			$fieldIndex = array_search($fieldName, $originalRows[0]);
			$this->assertEquals(
				$originalRows[$rowNumber][$fieldIndex],
				$value
			);
		}
	}
}

/**
 * @dataProvider data_randomFilePath
 */
public function testGetAllByField($filePath) {
	$originalRows = TestHelper::createCsv($filePath);
	$headers = array_shift($originalRows);
	$csv = new Csv($filePath);

	$result = $csv->getAllBy("gender", "M");

	$filteredRows = array_filter($originalRows, function($row) use ($headers) {
		$genderFieldNum = array_search("gender", $headers);
		return $row[$genderFieldNum] === "M";
	});

	foreach ($filteredRows as $i => $row) {
		$rowWithHeaders = $csv->buildRow($row);
		$this->assertContains($rowWithHeaders, $result);
	}
}

/**
 * @dataProvider data_randomFilePath
 */
public function testGetAllByFieldThatDoesNotExist($filePath) {
	TestHelper::createCsv($filePath);
	$csv = new Csv($filePath);
	$result = $csv->getAllBy("this-field-does-not-exist", "it's true!");
	$this->assertEmpty($result);
}

/**
 * @dataProvider data_randomFilePath
 */
public function testGetByField($filePath) {
	$originalRows = TestHelper::createCsv($filePath);
	$headers = array_shift($originalRows);
	$csv = new Csv($filePath);

	$result = $csv->getBy("gender", "F");

	$filteredRows = array_filter($originalRows, function($row) use ($headers) {
		$genderFieldNum = array_search("gender", $headers);
		return $row[$genderFieldNum] === "M";
	});

	$originalSource = $originalRows[$result["rowNum"]];
	$rowWithHeaders = $csv->buildRow($originalSource);

	$this->assertEquals($rowWithHeaders, $result);
}

}#