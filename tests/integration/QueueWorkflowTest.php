<?php

namespace Maketok\DataMigration\IntegrationTest;

use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Action\Type\CreateTmpFiles;
use Maketok\DataMigration\Action\Type\Load;
use Maketok\DataMigration\Action\Type\Move;
use Maketok\DataMigration\ArrayMap;
use Maketok\DataMigration\Expression\LanguageAdapter;
use Maketok\DataMigration\Hashmap\ArrayHashmap;
use Maketok\DataMigration\Input\Csv;
use Maketok\DataMigration\QueueWorkflow;
use Maketok\DataMigration\Storage\Db\DBALMysqlResource;
use Maketok\DataMigration\Storage\Db\DBALMysqlResourceHelper;
use Maketok\DataMigration\Unit\SimpleBag;
use Maketok\DataMigration\Unit\Type\ImportDbUnit;
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
        $language->register('empty', function ($str) {
            return sprintf('empty(%1$s)', $str);
        }, function ($arguments, $str) {
            return empty($str);
        });
        $language->register('trim', function ($str) {
            return sprintf('trim(%1$s)', $str);
        }, function ($arguments, $str) {
            return trim($str);
        });
        $language->register('isset', function ($expr) {
            return sprintf('isset(%1$s)', $expr);
        }, function ($arguments, $expr) {
            return isset($expr);
        });
        $language->register('count', function ($expr) {
            return sprintf('count(%1$s)', $expr);
        }, function ($arguments, $expr) {
            return count($expr);
        });
        $language->register('explode', function ($str, $expression) {
            return sprintf('explode(%1$s)', $str, $expression);
        }, function ($arguments, $str, $expression) {
            return explode($str, $expression);
        });
        return new LanguageAdapter($language);
    }

    /**
     * @test
     * @throws \Doctrine\DBAL\DBALException
     */
    public function testSimpleImport()
    {
        $result = new Result();
        $workflow = new QueueWorkflow($this->config, $result);
        //=====================================================================
        // setting up customer Unit
        $customerUnit = new ImportDbUnit('customers');
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
        map.frozenIncr('customer_id', 3))
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
        //=====================================================================
        // setting up address unit
        $addressUnit = new ImportDbUnit("addresses");
        $addressUnit->setTable('addresses');
        $addressUnit->setMapping([
            'id' => 'map.incr("address_id", 4)',
            'street' => 'map.street',
            'city' => 'map.city',
            'parent_id' => 'map.customer_id',
        ]);
        $bag = new SimpleBag();
        //=====================================================================
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
}
