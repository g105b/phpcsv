<?php
/**
 * Tests unusual data requirements.
 *
 * http://github.com/g105b/phpcsv
 * @copyright Copyright Ⓒ 2015 Bright Flair Ltd. (http://brightflair.com)
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
namespace g105b\phpcsv;

class Data_Test extends \PHPUnit_Framework_TestCase {

public function tearDown() {
	TestHelper::removeDir(TestHelper::getTempPath());
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testQuotes($filePath) {
	TestHelper::createCsv($filePath);
	$csv = new Csv($filePath);
	$csv->setIdField("rowNum");
	$headers = $csv->getHeaders();

	$rowThatHasQuotes = rand(0, 10);
	$fieldThatHasQuotes = rand(0, count($headers) - 1);
	$fieldValue = null;

	$headerName = $headers[$fieldThatHasQuotes];

	$row = $csv->get($rowThatHasQuotes);
	$i = 0;
	$fieldValue = "\"I am quoted\"";
	$row[$headerName] = $fieldValue;

	$csv->updateRow($rowThatHasQuotes, $row);

	$rowAfterUpdate = $csv->get($rowThatHasQuotes);
	$this->assertEquals($fieldValue, $rowAfterUpdate[$headerName]);
}

}#