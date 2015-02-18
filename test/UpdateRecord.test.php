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
	$newFirstName = "UpdatedFirstName" . uniqid();

	TestHelper::createCsv($filePath);
	$csv = new Csv($filePath);
	$row = $csv->get(0);
	$row["firstName"] = $newFirstName;

	$csv->update($row);

	$csv = null;
	$csv = new Csv($filePath);
	$row = $csv->get(0);
	$this->assertEquals($newFirstName, $row["firstName"]);
}

}#