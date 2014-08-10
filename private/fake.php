<?php
/*
 * This file will create the table in the given database and populate it with data. It requires the Faker package,
 * (https://github.com/fzaninotto/Faker) which can be installed with Composer by specifying it in the composer.json file.
 * This file should not be placed where it is accessible via the web. THIS FILE WILL ERASE ANYTHING THAT IS ALREADY IN
 * YOUR TABLE! Run it with the php command: php fake.php
*/
require_once 'vendor/fzaninotto/faker/src/autoload.php'; // Loads Faker.

$faker = \Faker\Factory::create();
$numOfRows = 50;
try {
    // Use PDO to open up a connection to the database.
    $db = new PDO(
        'mysql:host=address;dbname=dbName;',
        'username',
        'password',
        array(PDO::ATTR_PERSISTENT => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

    // Create the table.
    $createTable = $db->query("CREATE TABLE IF NOT EXISTS info (
    id INT AUTO_INCREMENT NOT NULL,
    creditor VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    fako INT NOT NULL,
    state VARCHAR(100) NOT NULL,
    income DECIMAL(10,2) NOT NULL,
    approval BOOLEAN NOT NULL,
    creditline DECIMAL(10,2) NOT NULL,
    interest DECIMAL(3, 2) NOT NULL,
    comments VARCHAR(160) NOT NULL,
    PRIMARY KEY (ID))
    ");

    if ($createTable) {
        echo "Table created!\n";
        $db->query("TRUNCATE TABLE info"); // Dump table data if it exists.
        // Prepared statement in PDO to insert new data.
        $sql = $db->prepare('INSERT INTO info (creditor, date, fako, state, income, approval, creditline, interest, comments)
                                VALUES (:creditor, :date, :fako, :state, :income, :approval, :creditline, :interest, :comments)');
        for ($i = 1; $i <= $numOfRows; $i++) { // Loop 50 times to create 50 records.
            $sql->execute([
                ':creditor' => $faker->company,
                ':date' => $faker->date('Y-m-d', 'now'),
                ':fako' => $faker->randomNumber(3),
                ':state' => $faker->state,
                ':income' => $faker->randomFloat(2, 10000),
                ':approval' => $faker->boolean(50),
                ':creditline' => $faker->randomFloat(2, 500, 10000),
                ':interest' => $faker->randomFloat(2, 0.01, 5),
                ':comments' => $faker->sentence(3)
            ]);
        }

        $db = null; // Close DB.
        echo "Finished!\n";
    } else {
        echo "Failed to create the table!";
    }
} catch (PDOException $e) { // Let the user know what's wrong.
    echo "Oops, trouble connecting to the database!\n Error Num: " . $e->getCode() . "\nError Msg: " . $e->getMessage() . "\n";
}

?>