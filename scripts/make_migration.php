
<?php
//
//The purpose of this script is to generate a migration boilerplate. My idea was to simulate the
//make:migration option available in Laravel.
//
//To keep things organized, I wanted to give specific context on the migration such as a brief description.
//
$dir   = __DIR__ . '/../database';
$files = glob("$dir/migration_*.sql");

$indexes = array_map(function($f){
    if (preg_match('/migration_(\d{4})\.sql$/', $f, $m)) {
        return (int)$m[1];
    }
    return 0;
}, $files);
$next = str_pad(max($indexes) + 1, 1, '0', STR_PAD_LEFT);


echo "Briefly describe the current migration: ";
$descr = trim(fgets(STDIN));

$name = "migration_{$next}.sql";
$path = "$dir/$name";
$template = <<<SQL
-- Migration name: $name
-- Migration handler: $descr
BEGIN TRANSACTION;

-- TODO: scrie aici SQL-ul de migrație

COMMIT;
SQL;

file_put_contents($path, $template);
echo "Fișier creat: migrations/$name\n";
