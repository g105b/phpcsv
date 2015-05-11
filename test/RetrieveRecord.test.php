<?php
/**
 * Tests the manipulation of CSV fields is performed as expected.
 *
 * http://github.com/g105b/phpcsv
 * @copyright Copyright Ⓒ 2015 Bright Flair Ltd. (http://brightflair.com)
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
namespace g105b\phpcsv;

class RetrieveRecord_Test extends \PHPUnit_Framework_TestCase {

public function tearDown() {
	TestHelper::removeDir(TestHelper::getTempPath());
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testGetDoesNotGiveHeaderRow($filePath) {
	TestHelper::createCsv($filePath);
	$csv = new Csv($filePath);
	$headers = $csv->getHeaders();
	$firstRow = $csv->get(0);

	$this->assertNotEquals($headers, array_values($firstRow));
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testGetAllDoesNotGiveHeaderRow($filePath) {
	TestHelper::createCsv($filePath, 10);
	$csv = new Csv($filePath);
	$headers = $csv->getHeaders();
	$allRows = $csv->getAll();

	$this->assertNotEquals($headers, array_values($allRows[0]));
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testGetCalledTwiceRetrievesCorrectRows($filePath) {
	TestHelper::createCsv($filePath);
	$csv = new Csv($filePath);
	$row0 = $csv->get(0);
	$row5 = $csv->get(5);

	$this->assertNotEquals($row5, $row0);

	$row0again = $csv->get(0);

	$this->assertEquals($row0again, $row0);
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
 * @expectedException \g105b\phpcsv\InvalidFieldException
 */
public function testGetIdFieldWhenNotSet($filePath) {
	$originalRows = TestHelper::createCsv($filePath, 10);
	$csv = new Csv($filePath);

	// We know that the "ID" column is not existant within the CSV data, instead
	// the ID field is "rowNum"; getting the ID field should return null.
	$csv->getIdField();
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

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testGetRowNumber($filePath) {
	TestHelper::createCsv($filePath);
	$csv = new Csv($filePath);
	$csv->setIdField("rowNum");
	$rowNumber = $csv->getRowNumber([
		"rowNum" => 2,
	]);

	$this->assertEquals($rowNumber, 2);
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testGetRowNumberWithoutId($filePath) {
	TestHelper::createCsv($filePath);
	$csv = new Csv($filePath);
	$rowNumber = $csv->getRowNumber([
		"rowNum" => 3,
	]);

	$this->assertEquals(3, $rowNumber);
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testGetRowNumberFromOtherColumns($filePath) {
	$originalRows = TestHelper::createCsv($filePath);
	$headers = array_shift($originalRows);
	$csv = new Csv($filePath);

	$randomRowNumber = array_rand($originalRows);
	$row = [];
	foreach($originalRows[$randomRowNumber] as $headerI => $value) {
		if($headerI > 3) {
			// Don't add all the columns.
			break;
		}
		$row[$headers[$headerI]] = $value;
	}

	$this->assertEquals($csv->getRowNumber($row), $randomRowNumber);
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testGetRowThatDoesNotExist($filePath) {
	TestHelper::createCsv($filePath, 10);
	$csv = new Csv($filePath);

	// There are only 10 rows.
	$this->assertFalse($csv->get(100));
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testGetNonIntegerIndex_stringInt($filePath) {
	TestHelper::createCsv($filePath, 10);
	$csv = new Csv($filePath);

	// "5" as a string is treated as a digit character
	$this->assertNotEmpty($csv->get("5"));
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 * @expectedException \g105b\phpcsv\InvalidIndexException
 */
public function testGetNonIntegerIndex_stringFloat($filePath) {
	TestHelper::createCsv($filePath, 10);
	$csv = new Csv($filePath);

	$csv->get("5.6");
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 * @expectedException \g105b\phpcsv\InvalidIndexException
 */
public function testGetNonIntegerIndex_string($filePath) {
	TestHelper::createCsv($filePath, 10);
	$csv = new Csv($filePath);

	$csv->get("hello");
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 * @expectedException \g105b\phpcsv\InvalidIndexException
 */
public function testGetNonIntegerIndex_negative($filePath) {
	TestHelper::createCsv($filePath, 10);
	$csv = new Csv($filePath);

	$csv->get(-5);
}

/**
 * @dataProvider \g105b\phpcsv\TestHelper::data_randomFilePath
 */
public function testEmptyLine($filePath) {
	TestHelper::createCsv($filePath);

	// Force a few empty lines into the file by reading it as an array,
	// clearing 10 random lines, then writing the file again.
	$lines = file($filePath);
	$totalLinesIncludingEmptyAndHeaders = count($lines);
	// Generate 10 random keys:
	$emptyRowArray = array_rand($lines, 10);
	// Make sure none of them are the header row:
	foreach($emptyRowArray as $i => $emptyRow) {
		if($emptyRow == 0) {
			do {
				$emptyRow = array_rand($lines);
			} while(in_array($emptyRow, $emptyRowArray));
			$emptyRowArray[$i] = $emptyRow;
		}
	}
	foreach($emptyRowArray as $emptyRow) {
		$lines[$emptyRow] = "\n";
	}
	// Write back the file.
	file_put_contents($filePath, implode("", $lines));

	$csv = new Csv($filePath);

	$this->assertInstanceOf("\g105b\phpcsv\Csv", $csv);

	$rowCount = 1;
	try {

		foreach($csv as $rowNumber => $columns) {
			$rowCount++;
			$this->assertNotEmpty($columns);
			$this->assertNotEmpty($columns["firstName"]);
		}
	}
	catch(Exception $e) {
		die("WHAT>???????????????");
	}

	$this->assertEquals($totalLinesIncludingEmptyAndHeaders - 10, $rowCount,
		"Should be 10 rows missing");
}

}#
