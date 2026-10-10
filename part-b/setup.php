<?php
// Initialize a new, empty demo database from the command line only.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Database setup is available from the command line only.');
}
if (($argv[1] ?? '') !== '--initialize-demo') {
    fwrite(STDERR, "Usage: php setup.php --initialize-demo\n");
    exit(1);
}

require_once __DIR__ . '/config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
    $connection->set_charset('utf8mb4');
    $tables = $connection->query('SHOW TABLES');
    if ($tables->num_rows !== 0) {
        throw new RuntimeException('Setup requires an empty database. Existing tables were preserved.');
    }
    $sql = file_get_contents(__DIR__ . '/../database/schema.sql');
    if ($sql === false) {
        throw new RuntimeException('Cannot read database/schema.sql.');
    }
    $connection->multi_query($sql);
    do {
        $result = $connection->store_result();
        if ($result) {
            $result->free();
        }
    } while ($connection->more_results() && $connection->next_result());
    $connection->close();
    echo "Demo database initialized. Open login.php to sign in.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'Setup failed: ' . $error->getMessage() . "\n");
    exit(1);
}
