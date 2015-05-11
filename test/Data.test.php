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
	$fieldThatHasQuotes = rand(0, count($headers) - 2);

	$headerName = $headers[$fieldThatHasQuotes];

	$row = $csv->get($rowThatHasQuotes);
	$fieldValue = "\"I am quoted\"";
	$row[$headerName] = $fieldValue;

	$csv->updateRow($rowThatHasQuotes, $row);

	$rowAfterUpdate = $csv->get($rowThatHasQuotes);
	$this->assertEquals($fieldValue, $rowAfterUpdate[$headerName]);
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testNewLine($filePath) {
	TestHelper::createCsv($filePath, 10);
	$csv = new Csv($filePath);
	$all = $csv->getAll();
	$numberOfRows = count($all);

	$csv->setIdField("rowNum");
	$headers = $csv->getHeaders();

	$rowThatHasNewLine = rand(0, 9);
	$fieldThatHasQuotes = rand(0, count($headers) - 2);

	$headerName = $headers[$fieldThatHasQuotes];

	$row = $csv->get($rowThatHasNewLine);
	$fieldValue = "New...\n...Line!";
	$row[$headerName] = $fieldValue;

	$csv->updateRow($rowThatHasNewLine, $row);

	$all = $csv->getAll(true);
	$this->assertEquals($numberOfRows, count($all),
		'Should have same number of rows after update');

	$rowAfterUpdate = $csv->get($rowThatHasNewLine);
	$this->assertEquals($fieldValue, $rowAfterUpdate[$headerName]);
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testUnicodeData($filePath) {
	$csv = new Csv($filePath);
	$data = [
		["EnglishWord" => "American", "ChineseWord" => "美国人"],
		["EnglishWord" => "French",	"ChineseWord" => "法国人"],
		["EnglishWord" => "German",	"ChineseWord" => "德国人"],
	];

	foreach ($data as $d) {
		$csv->add($d);
	}

	$this->assertEquals($data[1], $csv->get(1));
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testIdFieldCase($filePath) {
	$idFieldArray = [
		"id",
		"ID",
		"Id",
	];

	foreach($idFieldArray as $idField) {
		$data = [
			[$idField => 1, "number" => "one"],
			[$idField => 2, "number" => "two"],
			[$idField => 3, "number" => "three"],
		];

		if(!is_dir(dirname($filePath))) {
			mkdir(dirname($filePath), 0775, true);
		}

		$fh = fopen($filePath, "w");
		fputcsv($fh, [$idField, "number"]);
		foreach($data as $d) {
			fputcsv($fh, $d);
		}
		fclose($fh);

		$csv = new Csv($filePath);
		$row = $csv->getById(2);
		$this->assertEquals("two", $row["number"], print_r($row, true));
	}
}

}#
