#!/usr/bin/php
<?php

	function d($s) { printf("%s\n", $s); }
	function closeTable($tableColumns, $primaryKeyName) {
		if (!empty($primaryKeyName)) {
			$tableColumns[] = sprintf('PRIMARY KEY (`%s`)', $primaryKeyName);
		}
		
		printf("%s\n) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n\n", implode(",\n\t", $tableColumns));
	}
	
	$typeMap = array(
		'bi' => 'bigint',
		'ti' => 'tinyint',
		'i'  => 'int',
		'd'  => 'decimal',
		'c'  => 'char',
		'v'  => 'varchar',
		't'  => 'text',
		'dt' => 'datetime'
	);


	/***************************************************************************/
	if ($argc != 2) {
		die("Usage: {$argv[0]} <filename>\n");
	}
	
	$inFile = $argv[1];
	if (!file_exists($inFile)) {
		die("File {$inFile} does not exist!\n");
	}
	
	$fd = fopen($inFile, 'r');
	if (!$fd) {
		die("Cannot open {$inFile}.\n");
	}
	
	$commentOpen = false;
	$tableOpen = false;
	
	while (!feof($fd)) {
		$line = fgets($fd, 256);
		
		if ($line[0] == '/' && $line[1] == '*') {
			$commentOpen = true;
		}
		elseif ($line[0] == '*' && $line[1] == '/') {
			$commentOpen = false;
			continue;
		}
		
		if ('' == trim($line) || $commentOpen) {
			continue;
		}
		
		if ($line[0] != "\t") {
			if ($tableOpen) {
				closeTable($tableColumns, $primaryKeyName);
			}
			
			$tableOpen = true;
			$tableName = trim($line);
			$tableName = str_replace(' ', '_', $tableName);
			printf("DROP TABLE IF EXISTS `%s`;\n", $tableName);
			printf("CREATE TABLE `%s` (\n\t", $tableName);
			
			$primaryKeyName = '';
			$tableColumns = array();
			
			continue;
		}
		elseif ($line[0] == "\t") {
			$line = trim($line);
			
			$fieldType = '';
			$fieldLength = '';
			$fieldDefault = '';
			$isSigned = false;
			$isNullable = false;
			$isAutoIncrement = false;
			$defaultValueOpen = false;
			$fixDecimal = false;
			
			$colonPos = strpos($line, ':');
			if ($colonPos === false) {
				d("Invalid field type for column '{$line}', skipping");
				continue;
			}
			
			$fieldOptions = substr($line, 0, $colonPos);
			$fieldName = substr($line, $colonPos + 1);
			$fieldName = str_replace(' ', '_', $fieldName);
			
			for ($i = 0; $i < strlen($fieldOptions); $i++) {
				$char = $fieldOptions[$i];
				if ($char == '*') {
					$primaryKeyName = $fieldName;
					continue;
				}
				elseif ($char == '+') {
					$isAutoIncrement = true;
					continue;
				}
				elseif ($char == '-') {
					$isSigned = true;
					continue;
				}
				elseif ($char == '^') {
					$isNullable = true;
					continue;
				}
				elseif ($char == '(') {
					$defaultValueOpen = true;
					continue;
				}
				elseif ($char == ')') {
					$defaultValueOpen = false;
					continue;
				}
				elseif ((is_numeric($char) || $char == '.') && !$defaultValueOpen) {
					$fieldLength .= $char;
					if ($char == '.') {
						$fixDecimal = true;
					}
					continue;
				}
				
				if ($defaultValueOpen) {
					$fieldDefault .= $char;
				}
				else {
					$fieldType .= $char;
				}
			}
			
			if ($fixDecimal) {
				$bits = explode('.', $fieldLength);
				$fieldLength = ($bits[0] + $bits[1]) .'.'. $bits[1];
			}
			
			if (!isset($typeMap[$fieldType])) {
				d("ERROR: No such column type {$fieldType}! ({$line})");
				break;
			}
			
			$isNumeric = preg_match('/(int|decimal)/', $typeMap[$fieldType]);
			
			$tableColumns[] = trim(sprintf("`%s` %s%s %s %s %s %s",
				$fieldName,
				$typeMap[$fieldType],
				($fieldLength != '' ? "({$fieldLength})" : ''),
				(!$isSigned && $isNumeric ? 'unsigned' : ''),
				($isNullable ? '' : 'NOT NULL'),
				($fieldDefault != '' ? "DEFAULT '{$fieldDefault}'" : ''),
				($isAutoIncrement ? 'AUTO_INCREMENT' : '')
			));
		}
		
	}

	closeTable($tableColumns, $primaryKeyName);
	fclose($fd);
	
