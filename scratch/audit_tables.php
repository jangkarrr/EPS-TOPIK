<?php
require_once __DIR__ . '/../includes/db.php';
$db = getDB();

// Get existing tables
$existingTables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

// Scan all PHP files for table names
$phpFiles = array_merge(
    glob(__DIR__ . '/../*.php'),
    glob(__DIR__ . '/../includes/*.php'),
    glob(__DIR__ . '/../admin/*.php'),
    glob(__DIR__ . '/../api/*.php')
);

$referencedTables = [];
$ignore = ['select', 'set', 'where', 'values', 'on', 'as', 'by', 'and', 'or', 'now', 'curdate', 'limit', 'offset', 'order', 'group', 'having', 'asc', 'desc', 'left', 'right', 'inner', 'outer', 'cross', 'join', 'union'];

foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    preg_match_all('/(?:FROM|INTO|UPDATE|JOIN)\s+`?([a_zA-Z0-9_]+)`?/i', $content, $matches);
    foreach ($matches[1] as $tbl) {
        $tbl = strtolower($tbl);
        if (!in_array($tbl, $ignore)) {
            $referencedTables[$tbl] = true;
        }
    }
}

$referenced = array_keys($referencedTables);
$missing = array_diff($referenced, $existingTables);

echo "EXISTING TABLES (" . count($existingTables) . "):\n";
print_r($existingTables);

echo "\nREFERENCED IN CODE (" . count($referenced) . "):\n";
print_r($referenced);

echo "\nMISSING TABLES IN DATABASE (" . count($missing) . "):\n";
print_r(array_values($missing));
