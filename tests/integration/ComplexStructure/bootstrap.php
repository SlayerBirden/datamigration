<?php

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOMySql\Driver;
use Doctrine\DBAL\Schema\Schema;
use Maketok\DataMigration\Action\ConfigInterface;

// find config
$file = dirname(__DIR__) . '/Storage/Db/assets/config.php';
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

    // order table
    $table = $schema->createTable('order');
    $table->addColumn('id', 'integer');
    $table->addColumn('total', 'float');
    $table->addColumn('tax', 'float');
    $table->addColumn('shipping', 'float');
    $table->addColumn('email', 'string');
    $table->addColumn('order_number', 'string');
    $table->setPrimaryKey(array("id"));

    // items table
    $table = $schema->createTable('order_item');
    $table->addColumn('id', 'integer');
    $table->addColumn('sku', 'string');
    $table->addColumn('price', 'float');
    $table->addColumn('qty', 'integer');
    $table->addColumn('row_total', 'float');
    $table->addColumn('parent_id', 'integer');
    $table->setPrimaryKey(array("id"));
    $table->addForeignKeyConstraint(
        'order',
        ['parent_id'],
        ['id'],
        ['onUpdate' => 'CASCADE', 'onDelete' => 'CASCADE']
    );

    // address table
    $table = $schema->createTable('address');
    $table->addColumn('id', 'integer');
    $table->addColumn('street', 'string');
    $table->addColumn('city', 'float');
    $table->addColumn('type', 'string');
    $table->addColumn('order_id', 'integer');
    $table->setPrimaryKey(array("id"));
    $table->addForeignKeyConstraint(
        'order',
        ['order_id'],
        ['id'],
        ['onUpdate' => 'CASCADE', 'onDelete' => 'CASCADE']
    );

    // shipment table
    $table = $schema->createTable('shipment');
    $table->addColumn('id', 'integer');
    $table->addColumn('shipment_number', 'string');
    $table->addColumn('order_id', 'integer');
    $table->setPrimaryKey(array("id"));
    $table->addForeignKeyConstraint(
        'order',
        ['order_id'],
        ['id'],
        ['onUpdate' => 'CASCADE', 'onDelete' => 'CASCADE']
    );

    // invoice table
    $table = $schema->createTable('invoice');
    $table->addColumn('id', 'integer');
    $table->addColumn('invoice_number', 'string');
    $table->addColumn('total', 'float');
    $table->addColumn('tax', 'float');
    $table->addColumn('shipping', 'float');
    $table->addColumn('order_id', 'integer');
    $table->setPrimaryKey(array("id"));
    $table->addForeignKeyConstraint(
        'order',
        ['order_id'],
        ['id'],
        ['onUpdate' => 'CASCADE', 'onDelete' => 'CASCADE']
    );

    // shipment items
    $table = $schema->createTable('shipment_item');
    $table->addColumn('id', 'integer');
    $table->addColumn('parent_id', 'integer');
    $table->addColumn('order_item_id', 'integer');
    $table->addColumn('price', 'float');
    $table->addColumn('qty', 'integer');
    $table->setPrimaryKey(array("id"));
    $table->addForeignKeyConstraint(
        'shipment',
        ['parent_id'],
        ['id'],
        ['onUpdate' => 'CASCADE', 'onDelete' => 'CASCADE']
    );
    $table->addForeignKeyConstraint(
        'order_item',
        ['order_item_id'],
        ['id'],
        ['onUpdate' => 'CASCADE', 'onDelete' => 'CASCADE']
    );

    // invoice items
    $table = $schema->createTable('invoice_item');
    $table->addColumn('id', 'integer');
    $table->addColumn('parent_id', 'integer');
    $table->addColumn('order_item_id', 'integer');
    $table->addColumn('price', 'float');
    $table->addColumn('tax', 'float');
    $table->addColumn('row_total', 'float');
    $table->addColumn('qty', 'integer');
    $table->setPrimaryKey(array("id"));
    $table->addForeignKeyConstraint(
        'invoice',
        ['parent_id'],
        ['id'],
        ['onUpdate' => 'CASCADE', 'onDelete' => 'CASCADE']
    );
    $table->addForeignKeyConstraint(
        'order_item',
        ['order_item_id'],
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
$dir = __DIR__ . '/assets/results';
if (!file_exists($dir)) {
    mkdir($dir);
}
