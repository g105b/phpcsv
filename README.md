# phpcsv
Wraps SplFileObject's CSV capabilities with a more human approach

[![Build status](https://img.shields.io/circleci/project/g105b/phpcsv.svg?style=flat-square)](https://circleci.com/gh/g105b/phpcsv)
[![Composer Version](http://img.shields.io/packagist/v/g105b/phpcsv.svg?style=flat-square)](https://packagist.org/packages/g105b/phpcsv)
[![Download Stats](http://img.shields.io/packagist/dm/g105b/phpcsv.svg?style=flat-square)](https://packagist.org/packages/g105b/phpcsv)

## Features at a glance

* Enhances PHP's SplFileObject, a memory-efficient file stream.
* Simple filtering of rows by field value (`getAllBy("fieldName", "fieldValue")`).
* Results are associative arrays, the indices are the CSV header names.
* Iterate over CSV files by row.
* Reference CSV rows by row number.
* Reference CSV rows by ID value.

## Screenshot in action

![Screenshot of phpcsv](https://raw.githubusercontent.com/g105b/phpcsv/master/screenshot.png)

## Usage

Here are a few use cases to best show the functionality of the library. For a complete guide, [visit the documentation](https://github.com/g105b/phpcsv/wiki).

### Add rows
```php
$csv = new Csv("/path/to/file.csv");
$csv->add([
    "firstName" => "Alan",
    "lastName" => "Statham",
    "Job Title" => "Consultant Radiologist",
]);
$csv->add([
    "firstName" => "Caroline",
    "lastName" => "Todd",
    "Job Title" => "Surgical Registrar",
]);
```

### Get rows
```php
$csv = new Csv("/path/to/file.csv");
$resultRows = $csv->getAllBy("gender", "F"); // array of all matching rows.
$firstRow = $csv->getBy("gender", "F"); // single row, first matching.
```

### Iterate over rows
```php
$csv = new Csv("/path/to/file.csv");

foreach ($csv as $rowNumber => $row) {
    // $row is an associative array with CSV headers as each key.
    // $rowNumber starts from 1 (ignoring header row).
}
```

### Update row
```php
$csv = new Csv("/path/to/file.csv");
$row = $csv->getBy("email", "barack@whitehouse.gov");

// Update the matching row with provided fields, keeping any
// existing field data on the existing row.
$csv->update($row, [
    "dateOfBirth" => "1961-08-04",
]);
```

### Delete row
```php
$csv = new Csv("/path/to/file.csv");

// Delete a row by its index.
$csv->deleteRow(22);
```

## Future feature ideas

* Requesting only certain fields in result (v2)
* Type handling (v3)
* Sorting (v4)
* Faster retrieval of indexed rows (v4)
