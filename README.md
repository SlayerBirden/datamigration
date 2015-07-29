# Data-Migration

[![Build Status](https://travis-ci.org/SlayerBirden/datamigration.svg?branch=master)](https://travis-ci.org/SlayerBirden/datamigration)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/SlayerBirden/datamigration/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/SlayerBirden/datamigration/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/SlayerBirden/datamigration/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/SlayerBirden/datamigration/?branch=master)

This package is aimed at migrating large chunks of data across different resources.

Some of the influences:

* [mysqlimport](https://dev.mysql.com/doc/refman/5.0/en/mysqlimport.html)
* [Ddeboer Data Import library](https://github.com/ddeboer/data-import)

It's perfect to plan and execute a complex import or export.
It also can be used as a transport tool to migrate your data from one structure to another.
The unit (workers) structure allows it to parse a complex import file and
 extract a multi-level data structure from it.

The same's true for reverse process as well: the multi-level data structure
 can be flattened and inserted into a single file.


## Installation

Use composer to include it in your project:

```
composer require maketok/datamigration
```

## Structure

### Units
**Unit** is the smallest structure unit of each Workflow, and the most important one.
Each unit contains data required for different actions to work.
Unit by itself doesn't do any work, however the configuration of units is the most important part of each workflow.
The `Maketok\DataMigration\Unit\Type\Unit` class encapsulates all config properties needed for every Action.

### Action
**Action** is the logical division of complex processes like *import* or *export*.
The typical import consists of `CreateTmpFiles`, `Load` and `Move`.
Export: `ReverseMove`, `Dump`, `AssembleInput`.
There are a couple of reasons why both import and export are moving data using temporary CSV files.

* MySQL's `LOAD DATA INFILE` only works with CSV-like files (the delimiter doesn't necessarily need to be a comma).
And it's really important to have that in place for big files.
That allows utilize this powerful feature for other input formats like XML,
 YAML or even different (non-file) resources entirely
* Allows to debug the intermediate state of mapped data safely.

There's also a Generate action that can be utilized both to generate data and populate DB
 and to create a sample import file without the need to have anything ready in the DB to export.

### Workflow
**Workflow** consists of Actions that it utilizes in predefined order. The is the wrapper entity.
Workflow accepts config instance and `Result` object that will contain meta info after Workflow is executed.

## Examples

There are few examples in `tests/integration/QueueWorkflowTest` integration test.
Here's the typical uses.
### Import

Import customers and addresses. The flat structure can be possible only if customer data is either duplicated or
 omitted for consequent address entities.

```php
use Maketok\DataMigration\MapInterface;
use Maketok\DataMigration\QueueWorkflow;
use Maketok\DataMigration\Unit\SimpleBag;
use Maketok\DataMigration\Unit\Type\Unit;
use Maketok\DataMigration\Storage\Db\ResourceHelperInterface;
use Maketok\DataMigration\Action\Type\CreateTmpFiles;
use Maketok\DataMigration\Action\Type\Load;
use Maketok\DataMigration\Action\Type\Move;

$customerUnit = new Unit('customers');
$customerUnit->setTable('customers');
$customerUnit->setMapping([
    'id' => 'map.customer_id'
    // ExpressionLanguage is used to interpret string expressions
    'email' => 'mail.email',
    // closure or any other callable is also acceptable
    'name' => function (MapInterface $map) {
        return $map['name'];
    },
    'age' => 'map.age',
]);
/**
 * the is_entity condition resolves whether
 *  unit should consider current row as the entity
 * some utility functions are available in
 *  Maketok\DataMigration\Expression\HelperExpressionsProvider
 */
$customerUnit->setIsEntityCondition("trim(map.email) is not empty");
/**
 * the contributions is the way for unit to
 *  add some data into general pool for every other unit to use
 */
$customerUnit->addContribution(function (
    MapInterface $map,
    ResourceHelperInterface $resource,
    array $hashmaps
    ) {
        if (isset($hashmaps['email-id'][trim($map->email)])) {
            $map['customer_id'] = $hashmaps['email-id'][trim($map->email)];
        } else {
            $map['customer_id'] = $map->frozenIncr(
                'new_customer_id',
                 $resource->getLastIncrementId('customers')
             )
        }
    });
/**
 * This is really complex logic for assigning customer_id
 * First it checks if it exists in the pre-compiled Hashmap
 * If it does not, it's calling for frozen increment for "new_customer_id" key
 * and assign the last increment id if it's non existent
 * The frozenIncr is different from incr in that it's incremented only once
 *  is_entity condition resolves for current row
 * So it's perfect for incrementing "parent" entities
 */

$addressUnit = new Unit('addresses');
$addressUnit->setTable('addresses');
$addressUnit->setMapping([
    'id' => 'map.incr('address_id', resource.getLastIncrementId('addresses'))'
    'street' => 'map.street',
    'city' => 'map.city',
    'zip' => 'map.zip',
    'parent_id' => 'map.customer_id',
]);
$addressUnit->setParent($customerUnit);
$bag = new SimpleBag();
$bag->addSet([$customerUnit, $addressUnit]);

$workflow = new QueueWorkflow($config, $result);
$workflow->add(new CreateTmpFiles($bag, $config, $languageAdapter,
    $input, new ArrayMap(), $helperResource));
$workflow->add(new Load($bag, $config, $resource));
$workflow->add(new Move($bag, $config, $resource));
$workflow->execute();
```

## FAQ
