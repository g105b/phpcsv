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

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testGetByField($filePath) {
	$originalRows = TestHelper::createCsv($filePath);
	$headers = array_shift($originalRows);
	$csv = new Csv($filePath);

	$result = $csv->getBy("gender", "F");

	$filteredRows = array_filter($originalRows, function($row) use($headers) {
		$genderFieldNum = array_search("gender", $headers);
		return $row[$genderFieldNum] === "M";
	});

	$originalSource = $originalRows[$result["rowNum"]];
	$rowWithHeaders = $csv->toAssociative($originalSource);

	$this->assertEquals($rowWithHeaders, $result);
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testGetSetIdField($filePath) {
	$originalRows = TestHelper::createCsv($filePath, 10);
	$csv = new Csv($filePath);

	$id = "rowNum";
	$this->assertEquals($csv->setIdField($id), $id);

	$this->assertEquals($id, $csv->getIdField());
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testGetIdFieldWhenNotSet($filePath) {
	$originalRows = TestHelper::createCsv($filePath, 10);
	$csv = new Csv($filePath);

	// We know that the "ID" column is not existant within the CSV data, instead
	// the ID field is "rowNum"; getting the ID field should return null.
	$this->assertNull($csv->getIdField());
}
/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 * @expectedException \g105b\phpcsv\InvalidFieldException
 */
public function testGetInvalidDefaultIdField($filePath) {
	$originalRows = TestHelper::createCsv($filePath, 10);
	$csv = new Csv($filePath);

	$id = "this-field-does-not-exist";
	$csv->setIdField($id);
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testGetById($filePath) {
	$originalRows = TestHelper::createCsv($filePath);
	$headers = array_shift($originalRows);
	$csv = new Csv($filePath);

	$idField = "rowNum";
	$rowToCheck = rand(1, count($originalRows) - 1);

	$csv->setIdField($idField);
	$result = $csv->getById($rowToCheck);

	$filteredRows = array_filter($originalRows, function($row)
	use($headers, $rowToCheck, $idField) {
		$rowNumFieldIndex = array_search($idField, $headers);
		return $row[$rowNumFieldIndex] == $rowToCheck;
	});
	// Reset the indices of the filtered array:
	$filteredRows = array_values($filteredRows);
	$expectedResult = $csv->toAssociative($filteredRows[0]);

	$this->assertCount(1, $filteredRows, 'There should only be one of the ID');
	$this->assertEquals($expectedResult, $result);
}

}#