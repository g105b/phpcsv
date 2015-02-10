<?php
/**
 * Tests the manipulation of CSV fields is performed as expected.
 *
 * http://github.com/g105b/phpcsv
 * @copyright Copyright Ⓒ 2015 Bright Flair Ltd. (http://brightflair.com)
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
namespace g105b\phpcsv;

class FieldUsage_Test extends \PHPUnit_Framework_TestCase {

const RANDOM_TEST_COUNT = 10;
private $tempPath;

public function tearDown() {
	TestHelper::removeDir(TestHelper::getTempPath());
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 * @expectedException \g105b\phpcsv\InvalidFieldException
 */
public function testGetAllByFieldThatDoesNotExist($filePath) {
	TestHelper::createCsv($filePath);
	$csv = new Csv($filePath);
	$csv->getAllBy("this-field-does-not-exist", "it's true!");
}

// /**
//  * @dataProvider data_randomFilePath
//  */
// public function testGetByField($filePath) {
// 	$originalRows = TestHelper::createCsv($filePath);
// 	$headers = array_shift($originalRows);
// 	$csv = new Csv($filePath);

// 	$result = $csv->getBy("gender", "F");

// 	$filteredRows = array_filter($originalRows, function($row) use($headers) {
// 		$genderFieldNum = array_search("gender", $headers);
// 		return $row[$genderFieldNum] === "M";
// 	});

// 	$originalSource = $originalRows[$result["rowNum"]];
// 	$rowWithHeaders = $csv->buildRow($originalSource);

// 	$this->assertEquals($rowWithHeaders, $result);
// }

// /**
//  * @dataProvider data_randomFilePath
//  */
// public function testGetSetIdField($filePath) {
// 	$originalRows = TestHelper::createCsv($filePath, 10);
// 	$csv = new Csv($filePath);

// 	$id = "rowNum";
// 	$this->assertEquals($csv->setIdField($id), $id);

// 	$this->assertEquals($id, $csv->getIdField());
// }

// /**
//  * @dataProvider data_randomFilePath
//  */
// public function testGetInvalidDefaultIdField($filePath) {
// 	$originalRows = TestHelper::createCsv($filePath, 10);
// 	$csv = new Csv($filePath);

// 	$id = "this-field-does-not-exist";
// 	$csv->setIdField($id);
// }

// /**
//  * @dataProvider data_randomFilePath
//  */
// public function testGetById($fieldPath) {
// 	$originalRows = TestHelper::createCsv($filePath);
// 	$headers = array_shift($originalRows);
// 	$csv = new Csv($filePath);

// 	$id = "rowNum";
// 	$rowToCheck = rand(0, count($originalRows) - 1);

// 	$csv->setIdField($id);
// 	$result = $csv->getById($rowToCheck);

// 	$filteredRows = array_filter($originalRows, function($row)
// 	use($headers, $rowToCheck, $id) {
// 		$rowNumFieldNum = array_search($id, $headers);
// 		return $row[$rowNumFieldNum] == $id;
// 	});

// 	$this->assertCount(1, $filteredRows, 'There should only be one of the ID');
// 	$this->assertEquals($filteredRows[0], $result);
// }

}#