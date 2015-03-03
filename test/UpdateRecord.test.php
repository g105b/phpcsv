<?php
/**
 * Tests updating existing rows in a CSV file.
 *
 * http://github.com/g105b/phpcsv
 * @copyright Copyright Ⓒ 2015 Bright Flair Ltd. (http://brightflair.com)
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
namespace g105b\phpcsv;

class UpdateRecord_Test extends \PHPUnit_Framework_TestCase {

public function tearDown() {
	TestHelper::removeDir(TestHelper::getTempPath());
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testUpdateSingleField($filePath) {
	TestHelper::createCsv($filePath, 10);
	$csv = new Csv($filePath);
	$csv->setIdField("rowNum");

	$row = $csv->get(1);
	$newFirstName = "Updated-" . $row["firstName"];
	$row["firstName"] = $newFirstName;

	$updated = $csv->update($row);
	$this->assertTrue($updated);

	$row = $csv->get(1);
	$this->assertEquals($newFirstName, $row["firstName"]);
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testUpdateRowByIndex($filePath) {
	TestHelper::createCsv($filePath);
	$csv = new Csv($filePath);

	$csv->setIdField("rowNum");
	$row = $csv->get(3);
	$newFirstName = "Updated-" . $row["firstName"];
	$row["firstName"] = $newFirstName;

	$updated = $csv->updateRow(3, $row);
	$this->assertTrue($updated);

	$row = $csv->get(3);
	$this->assertEquals($newFirstName, $row["firstName"]);
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testUpdateRowWithMissingFields($filePath) {
	TestHelper::createCsv($filePath);
	$csv = new Csv($filePath);
	$csv->setIdField("rowNum");
	$row = $csv->get(3);
	$newFirstName = "Updated-" . $row["firstName"];
	$row["firstName"] = $newFirstName;
	$existingLastName = $row["lastName"];
	unset($row["lastName"]);

	$updated = $csv->update($row);
	$this->assertTrue($updated);

	$row = $csv->get(3);
	$this->assertEquals($newFirstName, $row["firstName"]);
	$this->assertEquals($existingLastName, $row["lastName"]);
}

}#