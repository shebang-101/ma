<?php

/**
 * Database connection settings (must be in env variable but OK for test).
 */
const DB_HOST = "mysql_db";
const DB_NAME = "mortgageautomator_db";
const DB_USER = "test_user";
const DB_PASS = "test_password";

/**
 * Generates excel style data and stores into `exceltable`.
 *
 * @param int $rowCount
 * @param int $columnCount
 * @param int $batchSize
 *
 * @return bool
 */
function generateExcelData(int $rowCount = 1000, int $columnCount = 1000, int $batchSize = 10000): bool
{
    try {
        $pdo = getPDOConnection();
        prepareDBTable($pdo);


        $batch = [];

        echo "[". date("Y-m-d H:i:s") ."] Generating data and inserting in batches...\n";
        /**
         * I tried to use generators for this data but didn't get performance improvements. But it could be efficient for memory usage:
         *
         * function generateData($rowCount, $columnCount)
         * {
         *      for ($row = 1; $row <= $rowCount; $row++) {
         *          for ($column = 1; $column <= $columnCount; $column++) {
         *              // Generate cell address and value
         *              $address = getColumnLetters($column);
         *              $value = "\$" . $address . "\$" . $row;
         *
         *              yield [$address, $row, $value];
         *          }
         *      }
         * }
         *
         * Let's leave direct data generation.
         */
        for ($row = 1; $row <= $rowCount; $row++) {
            for ($column = 1; $column <= $columnCount; $column++) {
                // Generate cell address and value
                $address = getColumnLetters($column);
                $value = "\$" . $address . "\$" . $row;

                // Add data to batch
                $batch[] = [$address, $row, $value];

                if (count($batch) >= $batchSize) {
                    insertBatch($pdo, $batch);
                    $batch = []; // Reset batch
                    echo "\r[". date("Y-m-d H:i:s") ."] Inserted $batchSize records... ";
                }
            }
        }

        if (!empty($batch)) {
            insertBatch($pdo, $batch);
            echo "\r[". date("Y-m-d H:i:s") ."] Inserted " . count($batch) . " records... ";
        }

        $pdo = null;

        return true;
    } catch (PDOException $e) {
        echo "[". date("Y-m-d H:i:s") ."] Error: " . $e->getMessage();

        return false;
    }
}

/**
 * Establishes DB connection.
 *
 * @return PDO
 */
function getPDOConnection()
{
    echo "[". date("Y-m-d H:i:s") ."] Connecting to the database...\n";
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);

    // Set PDO attributes for error mode and emulated prepared statements
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    return $pdo;
}

/**
 * Creates or recreates `exceltable` table.
 *
 * @param PDO $pdo
 *
 * @return void
 */
function prepareDBTable(PDO $pdo)
{
    echo "[". date("Y-m-d H:i:s") ."] Dropping existing 'exceltable' table if it exists...\n";
    $pdo->exec("DROP TABLE IF EXISTS exceltable");

    echo "[". date("Y-m-d H:i:s") ."] Creating 'exceltable' table...\n";
    $pdo->exec("CREATE TABLE exceltable (
            `column` VARCHAR(5) NOT NULL,
            `row` INT UNSIGNED NOT NULL,
            `value` VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (`column`, `row`)
        ) ENGINE=InnoDB");
}

/**
 * Inserts values into DB.
 *
 * @param PDO   $pdo
 * @param array $batch
 *
 * @return void
 */
function insertBatch(PDO $pdo,array $batch)
{
    // Prepare the SQL statement for batch insertion
    $sql = "INSERT INTO exceltable (`column`, `row`, `value`) VALUES ";
    $params = [];

    foreach ($batch as $data) {
        $sql .= "(?, ?, ?),";
        $params = array_merge($params, $data);
    }

    $sql = rtrim($sql, ',');
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

/**
 * Converts column number to excel-style column letters.
 *
 * @param int $column
 *
 * @return string
 */
function getColumnLetters(int $column): string
{
    $letters = '';

    while ($column > 0) {
        // The same logic as for question 1 where we tried to figure out 1000th column header.
        $modulo = ($column - 1) % 26;
        $letters = chr(65 + $modulo) . $letters;
        $column = ($column - $modulo - 1) / 26;
    }

    return $letters;
}


$startTime = microtime(true);
// Call the function to generate the "Excel spreadsheet" style data with a specified batch size (e.g., 1000 inserts at once)
generateExcelData();
$endTime = microtime(true);
$executionTime = $endTime - $startTime;
echo "\n[". date("Y-m-d H:i:s") ."] Data generation and insertion completed in $executionTime seconds.\n";
