# phpcsv
Wraps SplFileObject's CSV capabilities with a more human approach

[![Build status](https://img.shields.io/circleci/project/g105b/phpcsv.svg?style=flat-square)](https://circleci.com/gh/g105b/phpcsv)
[![Composer Version](http://img.shields.io/packagist/v/g105b/phpcsv.svg?style=flat-square)](https://packagist.org/packages/brightflair/php.gt)
[![Download Stats](http://img.shields.io/packagist/dm/g105b/phpcsv.svg?style=flat-square)](https://packagist.org/packages/brightflair/php.gt)

## Features at a glance

* Enhances PHP's SplFileObject, a memory-efficient file stream.
* Simple filtering of rows by field value (`getAllBy("fieldName", "fieldValue")`).
* Results are associative arrays, the indices are the CSV header names.
* Iterate over CSV files by row.
* Reference CSV rows by row number.
* Reference CSV rows by ID value.

## Screenshot in action

![Screenshot of phpcsv](https://raw.githubusercontent.com/g105b/phpcsv/master/screenshot.png)

## Todo list

* Write capability (v2)
* Requesting only certain fields in result (v2)
* Type handling (v3)
* Sorting (v4)
* Faster retrieval of indexed rows (v4)
