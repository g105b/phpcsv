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

}#