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
 can be inserted into a single file.

## Installation

Use composer to include it in your project:

```
composer require maketok/datamigration
```

## Examples

There are few examples in `tests/integration/QueueWorkflowTest` integration test.
Here's the typical uses.
### Import

Import customers and addresses from flat CSV file.

| email | name | age | street | city | zip |
| ----- | ---- | --- | ------ | ---- | --- |
| bart22@example.com | Bart Robbinson | 32 | 20 Chestnut Terrace | New York | 07003 |
| vilkron@aim.at.com | Kale D | 15 | 123 Manson | LA | 90023 |
| | | | 111 Dale str. | Chicago | 60333 |

```php
use Maketok\DataMigration\MapInterface;
use Maketok\DataMigration\QueueWorkflow;
use Maketok\DataMigration\Unit\SimpleBag;
use Maketok\DataMigration\Unit\Type\Unit;
use Maketok\DataMigration\Storage\Db\ResourceHelperInterface;
use Maketok\DataMigration\Action\Type\CreateTmpFiles;
use Maketok\DataMigration\Action\Type\Load;
use Maketok\DataMigration\Action\Type\Move;
use Maketok\DataMigration\Input\Shaper\Processor\Nulls;

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
/*
 * the is_entity condition resolves whether
 *  unit should consider current row as the entity
 * some utility functions are available in
 *  Maketok\DataMigration\Expression\HelperExpressionsProvider
 */
$customerUnit->setIsEntityCondition("trim(map.email) is not empty");
/*
 * the contributions is the way for unit to
 *  add some data into general pool for every other unit to use
 *
 * This is the logic for assigning customer_id
 * First it checks if it exists in the pre-compiled Hashmap
 * If it does not, it's calling for frozen increment for "new_customer_id" key
 *  and assign the last increment id if it's non existent
 * The frozenIncr is different from incr in that it's incremented only once
 *  is_entity condition resolves for current row
 * So it's perfect for incrementing "parent" entities
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

/*
 * Last but not least, since we're using CSV file, we need a Shaper
 * instance to shape up our flat file before feeding it to CreateTmpFiles action
 */
$input = new Csv($fname, 'r', new Nulls($bag, new ArrayMap(), $this->getLanguageAdapter()));

$workflow = new QueueWorkflow($config, $result);
$workflow->add(new CreateTmpFiles($bag, $config, $languageAdapter,
    $input, new ArrayMap(), $helperResource));
$workflow->add(new Load($bag, $config, $resource));
$workflow->add(new Move($bag, $config, $resource));
$workflow->execute();
```

### Export
We have 3 DB tables for customers and their addresses.

customer

| id | email | age |
| --- | ----- | --- |
| 1 | bart22@example.com | 32 |
| 2 | vilkron@aim.at.com | 15 |

customer_data

| id | parent_id | firstname | lastname |
| --- | --------- | --------- | -------- |
| 1 | 1 | Bart | Robinson |
| 2 | 2 | Kale | Dager |

address

| id | customer_id | street | city | zip |
| --- | ---------- | ------- | ---- | --- |
| 1 | 1 | 20 Chestnut Terrace | New York | 07003 |
| 2 | 1 | 123 Manson | LA | 90023 |
| 3 | 2 | 111 Dale str. | Chicago | 60333 |

We want to get next output:

customers.csv

| email | name | age | street | city | zip |
| ----- | ---- | --- | ------ | ---- | --- |
| bart22@example.com | Bart Robbinson | 32 | 20 Chestnut Terrace | New York | 07003 |
| vilkron@aim.at.com | Kale D | 15 | 123 Manson | LA | 90023 |
| | | | 111 Dale str. | Chicago | 60333 |

```php
use Maketok\DataMigration\MapInterface;
use Maketok\DataMigration\QueueWorkflow;
use Maketok\DataMigration\Unit\SimpleBag;
use Maketok\DataMigration\Unit\Type\Unit;
use Maketok\DataMigration\Storage\Db\ResourceHelperInterface;
use Maketok\DataMigration\Action\Type\AssembleInput;
use Maketok\DataMigration\Action\Type\Load;
use Maketok\DataMigration\Action\Type\Move;
use Maketok\DataMigration\Input\Shaper\Processor\Nulls;

$customerUnit = new Unit('customers');
$customerUnit->setTable('customer');
$customerUnit->setMapping([
    'id' => 'map.customer_id'
    'email' => 'mail.email',
    'age' => 'map.age',
]);
$customerUnit->setIsEntityCondition("trim(map.email) is not empty");
$customerUnit->addContribution("map.offsetSet(
    'customer_id',
    (isset(hashmaps['email-id'][trim(map.email)]) ?
        hashmaps['email-id'][trim(map.email)] :
        map.frozenIncr(
            'new_customer_id',
            resource.getLastIncrementId('customer')
        )
    )
)");
$customerUnit->setReversedConnection([
  'customer_id' => 'id',
]);
$customerUnit->setReversedMapping([
    'email' => 'map.email',
    'age' => 'map.age',
]);

$customerDataUnit = new Unit('customer_data');
$customerDataUnit->setTable('customer_data');
$customerDataUnit->setMapping([
    'id' => 'map.incr('customer_data_id', resource.getLastIncrementId('customer_data'))'
    'parent_id' => 'map.customer_id'
    'firstname' => 'map.firstname',
    'lastname' => 'map.lastname',
]);
$customerDataUnit->addContribution("map.offsetSet(
    'complexName',
    explode(' ', map.name)
)");
$customerDataUnit->addContribution("map.offsetSet(
    'firstname',
    (count(map.complexName) >= 2 && isset(map.complexName[0]) ? map.complexName[0] : map.name)
)");
$customerDataUnit->addContribution("map.offsetSet(
    'lastname',
    (count(map.complexName) >= 2 && isset(map.complexName[1]) ? map.complexName[1] : '')
)");
$customerDataUnit->setReversedConnection([
    'customer_id' => 'parent_id',
]);
$customerDataUnit->setReversedMapping([
    'name' => 'map.firstname ~ " " ~ map.lastname',
]);
$customerUnit->addSibling($customerDataUnit);

$addressUnit = new Unit('addresses');
$addressUnit->setTable('address');
$addressUnit->setMapping([
    'id' => 'map.incr('address_id', resource.getLastIncrementId('address'))'
    'customer_id' => 'map.customer_id',
    'street' => 'map.street',
    'city' => 'map.city',
    'zip' => 'map.zip',
]);
$addressUnit->setReversedConnection([
    'customer_id' => 'customer_id',
]);
$addressUnit->setReversedMapping([
    'street' => 'map.street',
    'city' => 'map.city',
    'zip' => 'map.zip',
]);
$addressUnit->setParent($customerUnit);
$bag = new SimpleBag();
$bag->addSet([$customerUnit, $customerDataUnit, $addressUnit]);

$input = new Csv($fname, 'w', new Nulls($bag, new ArrayMap(), $this->getLanguageAdapter()));

$result = new Result();
$workflow = new QueueWorkflow($this->config, $result);
$workflow->add(new ReverseMove($bag, $config, $resource));
$workflow->add(new Dump($bag, $config, $resource));
$workflow->add(new AssembleInput($bag, $config, $languageAdapter, $input, new ArrayMap()));
$workflow->execute();
```

## FAQ
