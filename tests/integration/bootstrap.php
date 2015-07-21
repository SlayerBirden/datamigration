<?php

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOMySql\Driver;
use Doctrine\DBAL\Schema\Schema;
use Maketok\DataMigration\Action\ConfigInterface;

// find config
$file = __DIR__ . '/Storage/Db/assets/config.php';
if (file_exists($file)) {
    $config = include $file;
    if (isset($config) && $config instanceof ConfigInterface) {
        $connection = new Connection([
            'dbname' => $config['db_name'],
            'user' => $config['db_user'],
            'password' => $config['db_password'],
            'host' => $config['db_host'],
        ], new Driver());
    }
}
if (!isset($connection)) {
    return;
}
// try to create db
try {
    $connection->connect();
    $schema = new Schema();
    // add customers table
    $table = $schema->createTable('customers');
    $table->addColumn('id', 'integer');
    $table->addColumn('firstname', 'string');
    $table->addColumn('lastname', 'string');
    $table->addColumn('age', 'integer');
    $table->addColumn('email', 'string');
    $table->setPrimaryKey(array("id"));
    $addressTable = $schema->createTable('addresses');
    $addressTable->addColumn('id', 'integer');
    $addressTable->addColumn('street', 'text');
    $addressTable->addColumn('city', 'string');
    $addressTable->addColumn('parent_id', 'integer');
    $addressTable->setPrimaryKey(array("id"));
    $addressTable->addForeignKeyConstraint(
        'customers',
        ['parent_id'],
        ['id'],
        ['onUpdate' => 'CASCADE', 'onDelete' => 'CASCADE']
    );
    $manager = $connection->getSchemaManager();
    $currentSchema = $manager->createSchema();
    $sql = $currentSchema->getMigrateToSql($schema, $manager->getDatabasePlatform());
    foreach ($sql as $script) {
        $connection->executeUpdate($script);
    }
    $connection->close();
    $connection = null;
} catch (Exception $e) {
    $connection->close();
    $connection = null;
    // passing on exception, letting Unit suite to run
}
