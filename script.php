<?php

/**
 * Database connection settings (must be in env variable but OK for test).
 */
const DB_HOST = "mysql_db";
const DB_NAME = "mortgageautomator_db";
const DB_USER = "test_user";
const DB_PASS = "test_password";

/**
 * Generates excel style data and stores into CSV file.
 *
 * @param int $rowCount
 * @param int $columnCount
 *
 * @return bool
 */
function generateExcelData(int $rowCount = 1000, int $columnCount = 1000): bool
{
    try {
        $file = tempnam(sys_get_temp_dir(), 'excel_data');

        echo "[". date("Y-m-d H:i:s") ."] Generating data and writing to CSV file...\n";

        $fp = fopen($file, 'w');

        if (!$fp) {
            throw new Exception("Failed to open temporary file.");
        }

        for ($row = 1; $row <= $rowCount; $row++) {
            for ($column = 1; $column <= $columnCount; $column++) {
                // Generate cell address and value
                $address = getColumnLetters($column);
                $value = "\$" . $address . "\$" . $row;

                // Write data to CSV file
                fputcsv($fp, [$address, $row, $value]);
            }
        }

        fclose($fp);

        // Load data into the database
        loadDataInfile($file);

        // Cleanup
        unlink($file);

        return true;
    } catch (PDOException $e) {
        echo "[". date("Y-m-d H:i:s") ."] Error: " . $e->getMessage();

        return false;
    } catch (Exception $e) {
        echo "[". date("Y-m-d H:i:s") ."] Error: " . $e->getMessage();

        return false;
    }
}

/**
 * Load data from a file into the database using LOAD DATA LOCAL INFILE.
 *
 * @param string $file
 *
 * @return void
 */
function loadDataInfile(string $file)
{
    $pdo = getPDOConnection();

    echo "[". date("Y-m-d H:i:s") ."] Loading data into the database...\n";
    try {
        // Truncate the table before loading new data
        $pdo->exec("TRUNCATE TABLE exceltable");
        $pdo->exec("LOAD DATA LOCAL INFILE '$file' INTO TABLE exceltable FIELDS TERMINATED BY ','");

        // Close the connection
        $pdo = null;
    } catch (PDOException $e) {
        echo "[". date("Y-m-d H:i:s") ."] Error: " . $e->getMessage();
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
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [
            PDO::MYSQL_ATTR_LOCAL_INFILE => true,
        ]
    );

    // Set PDO attributes for error mode and emulated prepared statements
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    try {
        // Create the table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS exceltable (
            `column` VARCHAR(5) NOT NULL,
            `row` INT UNSIGNED NOT NULL,
            `value` VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (`column`, `row`)
        ) ENGINE=InnoDB");
    } catch (PDOException $e) {
        echo "[". date("Y-m-d H:i:s") ."] Error: " . $e->getMessage();
    }

    return $pdo;
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
// Call the function to generate the "Excel spreadsheet" style data
generateExcelData();
$endTime = microtime(true);
$executionTime = $endTime - $startTime;
echo "\n[". date("Y-m-d H:i:s") ."] Data generation and insertion completed in $executionTime seconds.\n";
