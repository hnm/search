<?php

use n2n\core\TypeLoader;
use n2n\core\N2N;
use n2n\core\cache\impl\FileN2nCache;
use n2n\util\io\IoUtils;

ini_set('display_errors', 1);
error_reporting(E_ALL);

$pubPath = realpath(dirname(__FILE__));
$appPath = realpath($pubPath . '/../app');
$libPath = realpath($pubPath . '/../lib');
$testPath = realpath($pubPath . '/../test');
$varPath = realpath($pubPath . '/../var');

set_include_path(implode(PATH_SEPARATOR, array($appPath, $libPath, $testPath, get_include_path())));

define('N2N_STAGE', 'test');

require __DIR__ . '/../vendor/autoload.php';

TypeLoader::register(true,
		require __DIR__ . '/../vendor/composer/autoload_psr4.php',
		require __DIR__ . '/../vendor/composer/autoload_classmap.php');

N2N::initialize($pubPath, $varPath, new FileN2nCache());

$testSqlFsPath = N2N::getVarStore()->requestFileFsPath('etc', 'search', null, 'install.my.sql', false, false, false);

$sql = IoUtils::getContents($testSqlFsPath);

$sql = preg_replace('/^(INSERT|VALUES|\().*/m', '', $sql);

$sql = preg_replace('/^ALTER TABLE .* ADD (INDEX|UNIQUE|FULLTEXT).*/m', '', $sql);

$sql = preg_replace('/ENGINE=InnoDB[^;]*/', '', $sql);
$sql = preg_replace('/DEFAULT CHARSET=utf8[^;]*/', '', $sql);
$sql = preg_replace('/COLLATE [^;,\)]*/', '', $sql);

$sql = preg_replace('/varchar\(\d+\)/i', 'TEXT', $sql);
$sql = preg_replace('/text/i', 'TEXT', $sql);
$sql = preg_replace('/datetime/i', 'TEXT', $sql);

$sql = preg_replace('/`([^`]+)`/', '$1', $sql);

$sql = preg_replace('/INT\s+(UNSIGNED\s+)?NOT NULL AUTO_INCREMENT/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
$sql = preg_replace('/INT\s+(NOT NULL|UNSIGNED|DEFAULT [^,\)]+)*/i', 'INTEGER', $sql);

$sql = preg_replace('/,\s*PRIMARY KEY\s*\([^)]+\)/i', '', $sql);

$sql = preg_replace('/,\s*(UNIQUE\s+)?KEY\s+[^(]+\([^)]+\)/i', '', $sql);
$sql = preg_replace('/,\s*FULLTEXT\s+KEY\s+[^(]+\([^)]+\)/i', '', $sql);

$sql = preg_replace('/UNSIGNED\s+/i', '', $sql);

$sql = preg_replace('/ENUM\([^)]+\)/i', 'TEXT', $sql);

$sql = preg_replace('/DEFAULT\s+NULL/i', '', $sql);
$sql = preg_replace('/NULL\s+DEFAULT/i', 'DEFAULT', $sql);

$sql = preg_replace("/[\r\n]+/", "\n", $sql);
$sql = preg_replace("/\n\s*\n/", "\n", $sql);

$sql = trim($sql);

file_put_contents('converted.sql', $sql);

N2N::getPdoPool()->getPdo()->exec($sql);