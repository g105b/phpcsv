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

public function tearDown() {
	TestHelper::removeDir(TestHelper::getTempPath());
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testLoadCsvFile($filePath) {
	TestHelper::createCsv($filePath);
	$csv = new Csv($filePath);
	$this->assertEquals($filePath, $csv->getFilePath());
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testHeaderRowLoad($filePath) {
	$rows = TestHelper::createCsv($filePath);
	$csv = new Csv($filePath);

	$headerRow = $csv->getHeaders();
	$this->assertEquals($rows[0], $headerRow);
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
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
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testIterator($filePath) {
	$originalRows = TestHelper::createCsv($filePath, 10);
	$headers = array_shift($originalRows);
	$csv = new Csv($filePath);

	foreach ($csv as $rowNumber => $row) {
		foreach ($row as $fieldName => $value) {
			$fieldIndex = array_search($fieldName, $headers);
			$this->assertEquals(
				$value,
				$originalRows[$rowNumber][$fieldIndex]
			);
		}
	}

	foreach ($csv as $rowNumber => $row) {
		foreach ($row as $fieldName => $value) {
			$fieldIndex = array_search($fieldName, $headers);
			$this->assertEquals(
				$value,
				$originalRows[$rowNumber][$fieldIndex]
			);
		}
	}
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testGetIncrements($filePath) {
	$originalRows = TestHelper::createCsv($filePath, 10);
	$headers = array_shift($originalRows);
	$csv = new Csv($filePath);

	$rowNumber = 0;
	while($row = $csv->get()) {
		foreach ($row as $fieldName => $value) {
			$fieldIndex = array_search($fieldName, $headers);
			$this->assertEquals(
				$originalRows[$rowNumber][$fieldIndex],
				$value
			);
		}
		$rowNumber++;
	}
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testGetAllByField($filePath) {
	$originalRows = TestHelper::createCsv($filePath);
	$headers = array_shift($originalRows);
	$csv = new Csv($filePath);

	$result = $csv->getAllBy("gender", "M");

	$filteredRows = array_filter($originalRows, function($row) use($headers) {
		$genderFieldNum = array_search("gender", $headers);
		return $row[$genderFieldNum] === "M";
	});

	foreach ($filteredRows as $i => $row) {
		$rowWithHeaders = $csv->toAssociative($row);
		$this->assertContains($rowWithHeaders, $result);
	}
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testConstructWithPathToDirectory($filePath) {
	$filePath = dirname($filePath);
	$csv = new Csv($filePath);
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testFileNotCreatedWhenNoChanges($filePath) {
	$csv = new Csv($filePath);
	$csv->getAll();
	$csv = null;

	$this->assertFileNotExists($filePath);
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testFileNotDeletedWhenChanges($filePath) {
	$csv = new Csv($filePath);
	$csv->add(["key" => "value"]);
	$csv = null;

	$this->assertFileExists($filePath);
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testFileNotDeletedWhenExists($filePath) {
	TestHelper::createCsv($filePath, 10);
	$csv = new Csv($filePath);
	$csv->getAll();
	$csv = null;

	$this->assertFileExists($filePath);
}

}#
