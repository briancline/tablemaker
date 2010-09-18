#!/usr/bin/php
<?php

	/***************************************************************************/
	$typeAliasMap = array();
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

	function d($s) { printf("%s\n", $s); }
	function closeTable($tableColumns, $primaryKeyName) {
		if (!empty($primaryKeyName)) {
			$tableColumns[] = sprintf('PRIMARY KEY (`%s`)', $primaryKeyName);
		}
	
		printf("%s\n) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n\n", implode(",\n\t", $tableColumns));
	}


	/***************************************************************************/
	class ColumnInfo {
		public $type = '';
		public $length = '';
		public $default = '';
		public $isNumeric = false;
		public $isSigned = false;
		public $isNullable = false;
		public $isAutoIncrement = false;
		public $isPrimaryKey = false;
		
		public static function fromString($line) {
			global $typeMap, $typeAliasMap;
			$column = new ColumnInfo();
			
			$colonPos = strpos($line, ':');
			if ($colonPos === false) {
				return false;
			}
			
			$fixDecimal = false;
			$defaultValueOpen = false;
			$options = substr($line, 0, $colonPos);
			$column->name = substr($line, $colonPos + 1);
			$column->name = str_replace(' ', '_', $column->name);
			
			for ($i = 0; $i < strlen($options); $i++) {
				$char = $options[$i];
				if ($char == '*') {
					$column->isPrimaryKey = true;
					continue;
				}
				elseif ($char == '+') {
					$column->isAutoIncrement = true;
					continue;
				}
				elseif ($char == '-') {
					$column->isSigned = true;
					continue;
				}
				elseif ($char == '^') {
					$column->isNullable = true;
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
					$column->length .= $char;
					if ($char == '.') {
						$fixDecimal = true;
					}
					continue;
				}
				
				if ($defaultValueOpen) {
					$column->default .= $char;
				}
				else {
					$column->type .= $char;
				}
			}
			
			if ($fixDecimal) {
				$bits = explode('.', $column->length);
				$column->length = ($bits[0] + $bits[1]) .','. $bits[1];
			}
			
			if (isset($typeAliasMap[$column->type])) {
				$newLine = sprintf("%s%s%s%s%s(%s):%s",
					($column->isPrimaryKey ? '*' : ''),
					($column->isAutoIncrement ? '+' : ''),
					($column->isSigned ? '-' : ''),
					($column->isNullable ? '^' : ''),
					$typeAliasMap[$column->type],
					$column->default,
					$column->name
				);
				
				return self::fromString($newLine);
			}
			
			if (!isset($typeMap[$column->type])) {
				d("ERROR: No such column type {$column->type}! ({$line})");
				break;
			}
			
			$column->type = $typeMap[$column->type];
			$column->isNumeric = preg_match('/(int|decimal|float)/', $column->type);
			
			return $column;
		}
	}
	
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
	$lineNum = 0;
	
	while (!feof($fd)) {
		$lineNum++;
		$line = fgets($fd, 256);
		$line = rtrim($line);
		
		if (strlen($line) < 2) {
			continue;
		}
		
		if ($line[0] == '/' && $line[1] == '*') {
			$commentOpen = true;
		}
		elseif ($line[0] == '*' && $line[1] == '/') {
			$commentOpen = false;
			continue;
		}
		elseif ($line[0] == '#') {
			if (strlen($line) < 11 || substr($line, 1, 6) != 'define') {
				d("Invalid #directive on line {$lineNum}");
				continue;
			}
			
			$args = explode(' ', $line);
			if (count($args) != 3) {
				d("Invalid number of arguments to #define");
				continue;
			}
			
			$shortName = $args[1];
			$fullType = $args[2];
			
			$typeAliasMap[$shortName] = $fullType;
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
			$tableName = ltrim($line);
			$tableName = str_replace(' ', '_', $tableName);
			printf("DROP TABLE IF EXISTS `%s`;\n", $tableName);
			printf("CREATE TABLE `%s` (\n\t", $tableName);
			
			$primaryKeyName = '';
			$tableColumns = array();
			
			continue;
		}
		elseif ($line[0] == "\t") {
			$line = trim($line);
			$column = ColumnInfo::fromString($line);
			
			if (!$column) {
				d("/* Invalid column info for line '{$line}'. */");
				break;
			}
			
			if ($column->isPrimaryKey) {
				$primaryKeyName = $column->name;
			}
			
			$tableColumns[] = sprintf("`%s` %s%s%s%s%s%s",
				$column->name,
				$column->type,
				($column->length != '' ? "({$column->length})" : ''),
				(!$column->isSigned && $column->isNumeric ? ' unsigned' : ''),
				($column->isNullable ? '' : ' NOT NULL'),
				($column->default != '' ? " DEFAULT '{$column->default}'" : ''),
				($column->isAutoIncrement ? ' AUTO_INCREMENT' : '')
			);
		}
		
	}

	closeTable($tableColumns, $primaryKeyName);
	fclose($fd);
	
