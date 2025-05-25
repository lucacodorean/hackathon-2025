<?php
// This script ends the migration functionality.
// The purpose of this script is to check the migrations not run already, then run the migrations.

$dbFile = __DIR__ . '/../database/database.sqlite';
$pdo = new PDO("sqlite:$dbFile", null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);


$pdo->exec("
  CREATE TABLE IF NOT EXISTS migrations (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    filename    TEXT    NOT NULL UNIQUE,
    applied_at  DATETIME NOT NULL DEFAULT (datetime('now'))
  )
");


$files = glob(__DIR__ . '/../database/migration_*.sql');
sort($files);


$stmt    = $pdo->query("SELECT filename FROM migrations");
$applied = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);


foreach ($files as $path) {
    $name = basename($path);
    if (in_array($name, $applied, true)) {
        continue;
    }
    echo "Applying migration $name … ";
    $sql = file_get_contents($path);

    $pdo->beginTransaction();
    try {
        $pdo->exec($sql);
        $pdo->prepare("INSERT INTO migrations(filename) VALUES(?)")
            ->execute([$name]);
        $pdo->commit();
        echo "Migration $name has been applied.\n";
    } catch (\Throwable $e) {
        $pdo->rollBack();
        echo "Error while handling migration $name: " . $e->getMessage() . "\n";
        exit(1);
    }
}
