<?php
/**
 * Tests deleting existing rows from a CSV file.
 *
 * http://github.com/g105b/phpcsv
 * @copyright Copyright Ⓒ 2015 Bright Flair Ltd. (http://brightflair.com)
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
namespace g105b\phpcsv;

class DeleteRecord_Test extends \PHPUnit_Framework_TestCase {

public function tearDown() {
	TestHelper::removeDir(TestHelper::getTempPath());
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testDeleteSingleRow($filePath) {
	TestHelper::createCsv($filePath, 10);
	$csv = new Csv($filePath);
	$allRows = $csv->getAll();

	$csv->deleteRow(2);
	$allRowsAfterDelete = $csv->getAll();

	$this->assertNotSameSize($allRowsAfterDelete, $allRows,
		"Rows should not be same size after delete");

	$this->assertCount(count($allRows) - 1, $allRowsAfterDelete,
		"Should be -1 row after delete");
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testDeleteRemovesExpectedRow($filePath) {
	TestHelper::createCsv($filePath, 10);
	$csv = new Csv($filePath);
	$rowThree = $csv->get(3);
	$allRows = $csv->getAll();
	$this->assertContains($rowThree, $allRows);

	$csv->deleteRow(3);
	$allRowsAfterDelete = $csv->getAll();
	$this->assertNotContains($rowThree, $allRowsAfterDelete);
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testDeleteByReferenceRemovesExpectedRow($filePath) {
	TestHelper::createCsv($filePath, 10);
	$csv = new Csv($filePath);
	$rowThree = $csv->get(3);
	$allRows = $csv->getAll();
	$this->assertContains($rowThree, $allRows);

	$csv->delete($rowThree);
	$allRowsAfterDelete = $csv->getAll();
	$this->assertNotContains($rowThree, $allRowsAfterDelete);

	$searchResult = $csv->getAllBy("firstName", $rowThree["firstName"]);
	$this->assertNotContains($rowThree, $searchResult);
}

}#