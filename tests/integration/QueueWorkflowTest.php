<?php

namespace Maketok\DataMigration\IntegrationTest;

use Faker\Generator;
use Faker\Provider\Address;
use Faker\Provider\Base;
use Faker\Provider\Internet;
use Faker\Provider\Lorem;
use Faker\Provider\Person;
use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Action\Type\AssembleInput;
use Maketok\DataMigration\Action\Type\CreateTmpFiles;
use Maketok\DataMigration\Action\Type\Delete;
use Maketok\DataMigration\Action\Type\Dump;
use Maketok\DataMigration\Action\Type\Generate;
use Maketok\DataMigration\Action\Type\Load;
use Maketok\DataMigration\Action\Type\Move;
use Maketok\DataMigration\Action\Type\ReverseMove;
use Maketok\DataMigration\ArrayMap;
use Maketok\DataMigration\Expression\HelperExpressionsProvider;
use Maketok\DataMigration\Expression\LanguageAdapter;
use Maketok\DataMigration\Hashmap\ArrayHashmap;
use Maketok\DataMigration\Input\Csv;
use Maketok\DataMigration\QueueWorkflow;
use Maketok\DataMigration\Storage\Db\DBALMysqlResource;
use Maketok\DataMigration\Storage\Db\DBALMysqlResourceHelper;
use Maketok\DataMigration\Unit\SimpleBag;
use Maketok\DataMigration\Unit\Type\Unit;
use Maketok\DataMigration\Workflow\Result;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class QueueWorkflowTest extends \PHPUnit_Extensions_Database_TestCase
{
    /**
     * @var ConfigInterface
     */
    private $config;
    /**
     * @var DBALMysqlResource
     */
    private $resource;
    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $config = include __DIR__ . '/Storage/Db/assets/config.php';
        if (isset($config) && $config instanceof ConfigInterface) {
            $this->config = $config;
            $this->config['tmp_folder'] = __DIR__ . '/assets';
            $this->config['tmp_file_mask'] = 'tmp%2$s/%1$s.csv';
            $this->config['tmp_table_mask'] = 'tmp_%1$s_%2$s';
            $this->config['local_infile'] = true;
            $this->resource = new DBALMysqlResource($this->config);
            $ref1 = new \ReflectionProperty(get_class($this->resource->getConnection()), '_conn');
            $ref1->setAccessible(true);
            $this->pdo = $ref1->getValue($this->resource->getConnection());
        } else {
            throw new \Exception("Can't find config file.");
        }

        parent::setUp();

        // assert that 2 pdo's are same
        $pdo1 = $this->getConnection()->getConnection();
        $pdo2 = $ref1->getValue($this->resource->getConnection());

        $this->assertSame($pdo1, $pdo2);
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->resource->close();
    }

    /**
     * {@inheritdoc}
     */
    protected function getConnection()
    {
        if (isset($this->pdo)) {
            return $this->createDefaultDBConnection($this->pdo);
        }
        throw new \Exception("Can't find pdo in config.");
    }

    /**
     * {@inheritdoc}
     */
    protected function getTearDownOperation()
    {
        if ($this->config['db_debug']) {
            return \PHPUnit_Extensions_Database_Operation_Factory::NONE();
        }
        return \PHPUnit_Extensions_Database_Operation_Factory::TRUNCATE();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataSet()
    {
        return $this->createXMLDataSet(__DIR__ . '/assets/initialStructure.xml');
    }

    /**
     * @return LanguageAdapter
     */
    public function getLanguageAdapter()
    {
        $language = new ExpressionLanguage();
        $language->registerProvider(new HelperExpressionsProvider());
        return new LanguageAdapter($language);
    }

    /**
     * @return Unit
     * @throws \Doctrine\DBAL\DBALException
     */
    public function prepareCustomerImportUnit()
    {
        $customerUnit = new Unit('customers');
        $customerUnit->setTable('customers');
        $hashMap = new ArrayHashmap('email-id');
        $hashMap->load($this->resource->getConnection()
            ->executeQuery("SELECT email,id FROM customers")
            ->fetchAll(\PDO::FETCH_KEY_PAIR));
        $customerUnit->addHashmap($hashMap);
        $contribution1 = <<<CONTRIBUTION
map.offsetSet(
    'customer_id',
    (isset(hashmaps['email-id'][trim(map.email)]) ?
        hashmaps['email-id'][trim(map.email)] :
        map.frozenIncr('new_customer_id', 3))
)
CONTRIBUTION;
        $customerUnit->addContribution($contribution1);
        $contribution2 = <<<CONTRIBUTION
map.offsetSet(
    'complexName',
    explode(' ', map.name)
)
CONTRIBUTION;
        $customerUnit->addContribution($contribution2);
        $contribution3 = <<<CONTRIBUTION
map.offsetSet(
    'firstname',
    (count(map.complexName) >= 2 && isset(map.complexName[0]) ? map.complexName[0] : map.name)
)
CONTRIBUTION;
        $customerUnit->addContribution($contribution3);
        $contribution4 = <<<CONTRIBUTION
map.offsetSet(
    'lastname',
    (count(map.complexName) >= 2 && isset(map.complexName[1]) ? map.complexName[1] : '')
)
CONTRIBUTION;
        $customerUnit->addContribution($contribution4);
        $customerUnit->setMapping([
            'id' => 'map.customer_id',
            'firstname' => 'map.firstname',
            'lastname' => 'map.lastname',
            'age' => 'map.age',
            'email' => 'trim(map.email)',
        ]);
        $customerUnit->setIsEntityCondition("trim(map.email) != trim(oldmap.email)");
        return $customerUnit;
    }

    /**
     * @return Unit
     */
    public function prepareAddressImportUnit()
    {
        $addressUnit = new Unit("addresses");
        $addressUnit->setTable('addresses');
        $addressUnit->setMapping([
            'id' => 'map.incr("address_id", 4)',
            'street' => 'map.street',
            'city' => 'map.city',
            'parent_id' => 'map.customer_id',
        ]);
        return $addressUnit;
    }

    /**
     * @test
     * @throws \Doctrine\DBAL\DBALException
     */
    public function testSimpleImport()
    {
        $customerUnit = $this->prepareCustomerImportUnit();
        $addressUnit = $this->prepareAddressImportUnit();
        //=====================================================================
        $bag = new SimpleBag();
        // order matters ;)
        $bag->add($customerUnit);
        $bag->add($addressUnit);
        //=====================================================================
        $input = new Csv(__DIR__ . '/assets/customers_1.csv', 'r');
        $createTmpFiles = new CreateTmpFiles($bag, $this->config, $this->getLanguageAdapter(),
            $input, new ArrayMap(), new DBALMysqlResourceHelper($this->resource));
        $load = new Load($bag, $this->config, $this->resource);
        $move = new Move($bag, $this->config, $this->resource);
        //=====================================================================
        $result = new Result();
        $workflow = new QueueWorkflow($this->config, $result);
        $workflow->add($createTmpFiles);
        $workflow->add($load);
        $workflow->add($move);
        $workflow->execute();
        //=====================================================================
        // time to assert things

        // assert schema
        $expected = $this->createXMLDataSet(__DIR__ . '/assets/afterSimpleImportStructure.xml');
        $actual = $this->getConnection()->createDataSet(['customers', 'addresses']);
        $this->assertDataSetsEqual($expected, $actual);
    }

    /**
     * @test
     */
    public function testImportWithExisting()
    {
        // SET THESE TO TRUE TO DEBUG
        $this->config['db_debug'] = false;
        $this->config['file_debug'] = false;
        //=====================================================================
        $customerUnit = $this->prepareCustomerImportUnit();
        $addressUnit = $this->prepareAddressImportUnit();
        //=====================================================================
        // order matters ;)
        $bag = new SimpleBag();
        $bag->add($customerUnit);
        $bag->add($addressUnit);
        //=====================================================================
        $input = new Csv(__DIR__ . '/assets/customers_2.csv', 'r');
        $createTmpFiles = new CreateTmpFiles($bag, $this->config, $this->getLanguageAdapter(),
            $input, new ArrayMap(), new DBALMysqlResourceHelper($this->resource));
        $load = new Load($bag, $this->config, $this->resource);
        $move = new Move($bag, $this->config, $this->resource);
        //=====================================================================
        $result = new Result();
        $workflow = new QueueWorkflow($this->config, $result);
        $workflow->add($createTmpFiles);
        $workflow->add($load);
        $workflow->add($move);
        $workflow->execute();
        //=====================================================================
        // time to assert things

        // assert schema
        $expected = $this->createXMLDataSet(__DIR__ . '/assets/afterImportWithExistingStructure.xml');
        $actual = $this->getConnection()->createDataSet(['customers', 'addresses']);
        $this->assertDataSetsEqual($expected, $actual);
    }

    /**
     * @test
     */
    public function testImportDeleteAddresses()
    {
        // SET THESE TO TRUE TO DEBUG
        $this->config['db_debug'] = false;
        $this->config['file_debug'] = false;
        //=====================================================================
        $customerUnit = $this->prepareCustomerImportUnit();
        $addressUnit = $this->prepareAddressImportUnit();
        //=====================================================================
        // order matters ;)
        $bag = new SimpleBag();
        $bag->add($customerUnit);
        $bag->add($addressUnit);
        //=====================================================================
        $input = new Csv(__DIR__ . '/assets/customers_2.csv', 'r');
        $createTmpFiles = new CreateTmpFiles($bag, $this->config, $this->getLanguageAdapter(),
            $input, new ArrayMap(), new DBALMysqlResourceHelper($this->resource));
        $load = new Load($bag, $this->config, $this->resource);
        $move = new Move($bag, $this->config, $this->resource);
        //=====================================================================
        $deleteAddressUnit = new Unit('deleteAddress');
        $deleteAddressUnit->setTable('addresses');
        $deleteAddressUnit->addWriteCondition("isset(map.address_id)");
        $contribution = <<<CONTRIBUTION
map.offsetSet(
    'address_id',
    (isset(hashmaps['email-address'][trim(map.email)]) ?
        hashmaps['email-address'][trim(map.email)] :
        null)
)
CONTRIBUTION;

        $deleteAddressUnit->addContribution($contribution);
        $deleteAddressUnit->setMapping(['id' => 'explode(",", map.address_id)']);

        $hashMap = new ArrayHashmap('email-address');
        $sql = <<<MYSQL
SELECT c.email,GROUP_CONCAT(a.id) FROM customers c
LEFT JOIN addresses a ON a.parent_id = c.id
GROUP BY c.email
MYSQL;

        $hashMap->load($this->resource->getConnection()
            ->executeQuery($sql)
            ->fetchAll(\PDO::FETCH_KEY_PAIR));
        $deleteAddressUnit->addHashmap($hashMap);
        $deleteAddressUnit->setPk('id');

        $deleteBag = new SimpleBag();
        $deleteBag->add($deleteAddressUnit);

        $delCreateTmpFiles = new CreateTmpFiles($deleteBag, $this->config, $this->getLanguageAdapter(),
            $input, new ArrayMap(), new DBALMysqlResourceHelper($this->resource));
        $delLoad = new Load($deleteBag, $this->config, $this->resource);
        $delete = new Delete($deleteBag, $this->config, $this->resource);
        //=====================================================================
        $result = new Result();
        $workflow = new QueueWorkflow($this->config, $result);
        $workflow->add($createTmpFiles);
        $workflow->add($load);
        $workflow->add($delCreateTmpFiles);
        $workflow->add($delLoad);
        $workflow->add($delete);
        $workflow->add($move);
        try {
            $this->resource->startTransaction();
            $workflow->execute();
            $this->resource->commit();
        } catch (\Exception $e) {
            $this->resource->rollback();
            throw $e;
        }
        //=====================================================================
        // time to assert things

        // assert schema
        $expected = $this->createXMLDataSet(__DIR__ . '/assets/afterImportDeleteAddressStructure.xml');
        $actual = $this->getConnection()->createDataSet(['customers', 'addresses']);
        $this->assertDataSetsEqual($expected, $actual);
    }

    /**
     * @test
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function testImportGenerate()
    {
        // SET THESE TO TRUE TO DEBUG
        $this->config['db_debug'] = false;
        $this->config['file_debug'] = false;
        //=====================================================================
        $bag = new SimpleBag();
        $bag->add($this->getGenerateCustomerUnit());
        $bag->add($this->getGenerateAddressUnit());

        $generator = new Generator();
        $generator->addProvider(new Base($generator));
        $generator->addProvider(new Lorem($generator));
        $generator->addProvider(new Person($generator));
        $generator->addProvider(new Address($generator));
        $generator->addProvider(new Internet($generator));

        // truncate all info beforehand to not run into issue with duplicate email
        $this->resource->getConnection()->executeUpdate("DELETE FROM customers");

        $generate = new Generate($bag, $this->config, $this->getLanguageAdapter(),
            $generator, 100, new ArrayMap(), new DBALMysqlResourceHelper($this->resource));
        $load = new Load($bag, $this->config, $this->resource);
        $move = new Move($bag, $this->config, $this->resource);

        $result = new Result();
        $workflow = new QueueWorkflow($this->config, $result);
        $workflow->add($generate);
        $workflow->add($load);
        $workflow->add($move);
        try {
            $this->resource->startTransaction();
            $workflow->execute();
            $this->resource->commit();
        } catch (\Exception $e) {
            $this->resource->rollback();
            throw $e;
        }

        $this->assertTableRowCount('customers', 100);
    }

    /**
     * @return Unit
     */
    protected function getGenerateCustomerUnit()
    {
        $customerUnit = new Unit('customers');
        $customerUnit->setGeneratorMapping([
            'id' => 'map.incr("customer_id", resource.getLastIncrement("customers"))',
            'firstname' => 'generator.firstName',
            'lastname' => 'generator.lastName',
            'age' => 'generator.numberBetween(10, 60)',
            'email' => 'generator.unique().email',
        ]);
        $customerUnit->setMapping([
            'id' => "",
            'firstname' => "",
            'lastname' => "",
            'age' => "",
            'email' => "",
        ]);
        $customerUnit->setTable("customers");
        return $customerUnit;
    }

    protected function getGenerateAddressUnit()
    {
        $addressUnit = new Unit('addresses');
        $seed = new \SplFixedArray(2);
        $seed[0] = 4;
        $seed[1] = 1;
        $addressUnit->setGenerationSeed($seed);
        $addressUnit->setGeneratorMapping([
            'id' => 'map.incr("address_id", resource.getLastIncrement("addresses"))',
            'parent_id' => 'map.customer_id',
            'street' => 'generator.streetAddress',
            'city' => 'generator.city',
        ]);
        $addressUnit->setMapping([
            'id' => '',
            'parent_id' => '',
            'street' => '',
            'city' => '',
        ]);
        $addressUnit->setTable('addresses');
        return $addressUnit;
    }

    /**
     * @test
     */
    public function testGenerateExport()
    {
        // SET THESE TO TRUE TO DEBUG
        $this->config['db_debug'] = false;
        $this->config['file_debug'] = false;
        //=====================================================================
        $cUnit = $this->getGenerateCustomerUnit();
        $cUnit->setReversedConnection([
            'customer_id' => 'id',
        ]);
        $cUnit->setReversedMapping([
            'email' => 'map.email',
            'name' => 'map.firstname ~ " " ~ map.lastname',
            'age' => 'map.age',
        ]);
        $aUnit = $this->getGenerateAddressUnit();
        $aUnit->setReversedConnection([
            'customer_id' => 'parent_id',
        ]);
        $aUnit->setReversedMapping([
            'street' => 'map.street',
            'city' => 'map.city',
        ]);
        $aUnit->setParent($cUnit);

        $bag = new SimpleBag();
        $bag->add($cUnit);
        $bag->add($aUnit);

        $generator = new Generator();
        $generator->addProvider(new Base($generator));
        $generator->addProvider(new Lorem($generator));
        $generator->addProvider(new Person($generator));
        $generator->addProvider(new Address($generator));
        $generator->addProvider(new Internet($generator));

        // truncate all info beforehand to not run into issue with duplicate email
        $this->resource->getConnection()->executeUpdate("DELETE FROM customers");

        $fname = __DIR__ . '/assets/customers_generated.csv';
        $input = new Csv($fname, 'w');

        $generate = new Generate($bag, $this->config, $this->getLanguageAdapter(),
            $generator, 100, new ArrayMap(), new DBALMysqlResourceHelper($this->resource));
        $assemble = new AssembleInput($bag, $this->config, $this->getLanguageAdapter(), $input, new ArrayMap());

        $result = new Result();
        $workflow = new QueueWorkflow($this->config, $result);
        $workflow->add($generate);
        $workflow->add($assemble);

        $workflow->execute();
        //=====================================================================
        // assert that customers are in the file

        $this->assertFileExists($fname);
        $this->assertGreaterThan(100, $this->getNumberOfLines($fname));
    }

    /**
     * @test
     */
    public function testGenerateExport2Branches()
    {
        // SET THESE TO TRUE TO DEBUG
        $this->config['db_debug'] = false;
        $this->config['file_debug'] = false;
        //=====================================================================
        $cUnit = $this->getGenerateCustomerUnit();
        $cUnit->setReversedConnection([
            'customer_id' => 'id',
        ]);
        $cUnit->setReversedMapping([
            'email' => 'map.email',
            'age' => 'map.age',
        ]);
        $cdUnit = new Unit('customer_data');
        $seed = new \SplFixedArray(2);
        $seed[0] = 1;
        $seed[1] = 1;
        $cdUnit->setGenerationSeed($seed);
        $cdUnit->setGeneratorMapping([
            'id' => 'map.incr("gen_id", 1)',
            'parent_id' => 'map.customer_id',
            'firstname' => 'generator.firstName',
            'lastname' => 'generator.lastName',
        ]);
        $cdUnit->setMapping([
            'id' => '',
            'parent_id' => '',
            'firstname' => '',
            'lastname' => '',
        ]);
        $cdUnit->setTable('customers');
        $cdUnit->setReversedConnection([
            'customer_id' => 'parent_id',
        ]);
        $cdUnit->setReversedMapping([
            'name' => 'map.firstname ~ " " ~ map.lastname',
        ]);
        $cdUnit->addSibling($cUnit);

        $bag = new SimpleBag();
        $bag->add($cUnit);
        $bag->add($cdUnit);

        $generator = new Generator();
        $generator->addProvider(new Base($generator));
        $generator->addProvider(new Lorem($generator));
        $generator->addProvider(new Person($generator));
        $generator->addProvider(new Address($generator));
        $generator->addProvider(new Internet($generator));

        // truncate all info beforehand to not run into issue with duplicate email
        $this->resource->getConnection()->executeUpdate("DELETE FROM customers");

        $fname = __DIR__ . '/assets/customers_data_generated.csv';
        $input = new Csv($fname, 'w');

        $generate = new Generate($bag, $this->config, $this->getLanguageAdapter(),
            $generator, 100, new ArrayMap(), new DBALMysqlResourceHelper($this->resource));
        $assemble = new AssembleInput($bag, $this->config, $this->getLanguageAdapter(), $input, new ArrayMap());

        $result = new Result();
        $workflow = new QueueWorkflow($this->config, $result);
        $workflow->add($generate);
        $workflow->add($assemble);

        $workflow->execute();
        //=====================================================================
        // assert that customers are in the file

        $this->assertFileExists($fname);
        $this->assertEquals(101, $this->getNumberOfLines($fname));
    }

    /**
     * @test
     */
    public function testSimpleExport()
    {
        // SET THESE TO TRUE TO DEBUG
        $this->config['db_debug'] = false;
        $this->config['file_debug'] = false;
        //=====================================================================
        $this->config['dump_limit'] = 100;

        $cUnit = new Unit('customers');
        $aUnit = new Unit('addresses');
        //=====================================================================
        $cUnit->setTable('customers');
        $aUnit->setTable('addresses');
        $cUnit->setReverseMoveOrder(['id']);
        $cUnit->setReverseMoveDirection('asc');
        $aUnit->setReverseMoveOrder(['parent_id']);
        $aUnit->setReverseMoveDirection('asc');
        //=====================================================================
        $cUnit->setMapping([
            'id' => "",
            'firstname' => "",
            'lastname' => "",
            'age' => "",
            'email' => "",
        ]);
        $aUnit->setMapping([
            'id' => '',
            'parent_id' => '',
            'street' => '',
            'city' => '',
        ]);
        $cUnit->setReversedConnection([
            'customer_id' => 'id',
        ]);
        $cUnit->setReversedMapping([
            'email' => 'map.email',
            'name' => 'map.firstname ~ " " ~ map.lastname',
            'age' => 'map.age',
        ]);
        $aUnit->setReversedConnection([
            'customer_id' => 'parent_id',
        ]);
        $aUnit->setReversedMapping([
            'street' => 'map.street',
            'city' => 'map.city',
        ]);
        $aUnit->setParent($cUnit);
        //=====================================================================
        $bag = new SimpleBag();
        $bag->addSet([$cUnit, $aUnit]);

        $fname = __DIR__ . '/assets/customers_data_exported.csv';
        $input = new Csv($fname, 'w');

        $reverseMove = new ReverseMove($bag, $this->config, $this->resource);
        $dump = new Dump($bag, $this->config, $this->resource);
        $assemble = new AssembleInput($bag, $this->config, $this->getLanguageAdapter(), $input, new ArrayMap());

        $result = new Result();
        $workflow = new QueueWorkflow($this->config, $result);
        $workflow->add($reverseMove);
        $workflow->add($dump);
        $workflow->add($assemble);

        $workflow->execute();
        //=====================================================================
        // assert that customers are in the file

        $this->assertFileExists($fname);
        $this->assertEquals(4, $this->getNumberOfLines($fname));
    }

    public function testExportNull()
    {
        // SET THESE TO TRUE TO DEBUG
        $this->config['db_debug'] = false;
        $this->config['file_debug'] = false;
        //=====================================================================
        $this->config['dump_limit'] = 100;

        $cUnit = new Unit('customers');
        //=====================================================================
        $cUnit->setTable('customers');
        $cUnit->setReverseMoveOrder(['id']);
        $cUnit->setReverseMoveDirection('asc');
        //=====================================================================
        $cUnit->setMapping([
            'id' => "",
            'firstname' => "",
            'lastname' => "",
            'age' => "",
            'email' => "",
        ]);
        $cUnit->setReversedConnection([
            'customer_id' => 'id',
        ]);
        $cUnit->setReversedMapping([
            'email' => 'map.email',
            'name' => 'map.firstname ~ " " ~ map.lastname',
            'age' => 'map.age',
        ]);
        //=====================================================================
        $bag = new SimpleBag();
        $bag->add($cUnit);

        // truncate all info beforehand to not run into issue with duplicate email
        $this->resource->getConnection()->executeUpdate("DELETE FROM customers");
        $this->resource->getConnection()->insert('customers', [
            'id' => 1,
            'firstname' => 'test1',
            'lastname' => 'test1',
            'age' => null,
            'email' => 'test@example.com',
        ]);

        $fname = __DIR__ . '/assets/customers_data_exported_with_nulls.csv';
        $input = new Csv($fname, 'w');

        $reverseMove = new ReverseMove($bag, $this->config, $this->resource);
        $dump = new Dump($bag, $this->config, $this->resource);
        $assemble = new AssembleInput($bag, $this->config, $this->getLanguageAdapter(), $input, new ArrayMap());

        $result = new Result();
        $workflow = new QueueWorkflow($this->config, $result);
        $workflow->add($reverseMove);
        $workflow->add($dump);
        $workflow->add($assemble);

        $workflow->execute();
        //=====================================================================
        // assert that customers are in the file

        $this->assertFileExists($fname);
        $this->assertEquals(2, $this->getNumberOfLines($fname));
        $csvObject = new \SplFileObject($fname, 'r');
        $csvObject->fgetcsv();
        $actual = $csvObject->fgetcsv();
        $this->assertEquals([
            'test@example.com',
            'test1 test1',
            '\N',
        ], $actual);
    }

    /**
     * @param string $fname
     * @return int
     */
    public function getNumberOfLines($fname)
    {
        $fileObject = new \SplFileObject($fname, 'r');
        $i = 0;
        while (!$fileObject->eof()) {
            $fileObject->next();
            $current = $fileObject->current();
            if (!empty($current)) {
                $i++;
            }
        }
        return $i;
    }
}
