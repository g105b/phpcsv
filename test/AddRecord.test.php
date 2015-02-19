<?php
/**
 * Tests the manipulation of CSV fields is performed as expected.
 *
 * http://github.com/g105b/phpcsv
 * @copyright Copyright Ⓒ 2015 Bright Flair Ltd. (http://brightflair.com)
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
namespace g105b\phpcsv;

class AddRecord_Test extends \PHPUnit_Framework_TestCase {

private $details = [
	[
		"Company" 	=> "Microsoft",
		"OS" 		=> "Windows",
		"Version" 	=> "7",
		"Share"		=> "55.92%",
	],
	[
		"Company" 	=> "Microsoft",
		"OS" 		=> "Windows",
		"Version" 	=> "XP",
		"Share"		=> "18.93%",
	],
	[
		"Company" 	=> "Apple",
		"OS" 		=> "Macintosh",
		"Version" 	=> "10.9",
		"Share"		=> "16.57%",
	],
	[
		"Company" 	=> "Microsoft",
		"OS" 		=> "Windows",
		"Version" 	=> "8",
		"Share"		=> "7.24%",
	],
	[
		"Company" 	=> "Canonical",
		"OS" 		=> "Ubuntu",
		"Version" 	=> "14.04",
		"Share"		=> "1.34%",
	],
];

public function tearDown() {
	TestHelper::removeDir(TestHelper::getTempPath());
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testCsvCreatedWhenDoesNotExist($filePath) {
	$csv = new Csv($filePath);
	$this->assertFileExists($filePath);
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testCsvSetsData($filePath) {
	$csv = new Csv($filePath);

	$fileSize = filesize($filePath);
	$this->assertEquals(0, $fileSize);

	foreach ($this->details as $i => $detail) {
		$addedRow = $csv->add($detail);
		$this->assertEquals($detail, $addedRow);
		clearstatcache();
		$newFileSize = filesize($filePath);

		$this->assertGreaterThan($fileSize, $newFileSize);
		$fileSize = $newFileSize;
	}
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 * @expectedException \g105b\phpcsv\HeadersNotSetException
 */
public function testCsvThrowsErrorWithNoHeaders($filePath) {
	$csv = new Csv($filePath);
	$csv->add(["Alan", "Statham", "Consultant Radiologist"]);
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testCsvAddsIndexedRowsAfterAssociative($filePath) {
	$csv = new Csv($filePath);
	$csv->add([
		"firstName" => "Alan",
		"lastName" => "Statham",
		"Job Title" => "Consultant Radiologist",
	]);

	$csv->add(["Caroline", "Todd", "Surgical Registrar"]);
	$csv->add(["Guy", "Secretan", "Anaesthetist"]);

	$lines = file($filePath);
	$this->assertCount(4, $lines, 'Should have three lines plus the header');
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testAssociativeArrayOrderIsNotFixed($filePath) {
	$csv = new Csv($filePath);
	$csv->add([
		"firstName" => "Alan",
		"lastName" => "Statham",
		"Job Title" => "Consultant Radiologist",
	]);

	$csv->add([
		"Job Title" => "Surgical Registrar",
		"firstName" => "Caroline",
		"lastName" => "Todd",
	]);

	$csv->add([
		"lastName" => "Secretan",
		"firstName" => "Guy",
		"Job Title" => "Anaesthetist",
	]);

	foreach($csv as $row) {
		$this->assertContains($row["firstName"], [
			"Guy",
			"Alan",
			"Caroline",
		]);
	}
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testCsvAddsCorrectNumberOfColumns($filePath) {
	$csv = new Csv($filePath);
	foreach ($this->details as $rowDetail) {
		$csv->add($rowDetail);
	}

	$lines = file($filePath);
	foreach ($lines as $line) {
		$numberOfCommas = substr_count($line, ",");
		$this->assertEquals(count($this->details[0]) - 1, $numberOfCommas);
	}
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testCsvGetsAfterAdding($filePath) {
	$csv = new Csv($filePath);
	foreach ($this->details as $rowDetail) {
		$csv->add($rowDetail);
	}

	$microsoftRows = $csv->getAllBy("Company", "Microsoft");
	$count = 0;
	foreach ($this->details as $rowDetail) {
		if($rowDetail["Company"] === "Microsoft") {
			$count++;

			$this->assertContains($rowDetail, $microsoftRows);
		}
	}

	$this->assertCount($count, $microsoftRows);
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testCsvIteratesAfterAdding($filePath) {
	$csv = new Csv($filePath);
	foreach ($this->details as $rowDetail) {
		$csv->add($rowDetail);
	}

	foreach ($csv as $rowNumber => $row) {
		// Row number starts at 1 (ignoring header row).
		$this->assertEquals($this->details[$rowNumber - 1], $row);
	}
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 * @expectedException \g105b\phpcsv\InvalidPathException
 */
public function testConstructsWithDirectory($filePath) {
	TestHelper::createCsv($filePath, 1);
	$filePath = dirname($filePath);
	$csv = new Csv($filePath);
}

}#