<?php

namespace Maketok\DataMigration\IntegrationTest\ComplexStructure;

use Maketok\DataMigration\Action\ConfigInterface;
use Maketok\DataMigration\Action\Type\AssembleInput;
use Maketok\DataMigration\Action\Type\Dump;
use Maketok\DataMigration\Action\Type\ReverseMove;
use Maketok\DataMigration\ArrayMap;
use Maketok\DataMigration\Expression\HelperExpressionsProvider;
use Maketok\DataMigration\Expression\LanguageAdapter;
use Maketok\DataMigration\Input\ArrayInput;
use Maketok\DataMigration\QueueWorkflow;
use Maketok\DataMigration\Storage\Db\DBALMysqlResource;
use Maketok\DataMigration\Unit\SimpleBag;
use Maketok\DataMigration\Unit\Type\Unit;
use Maketok\DataMigration\Workflow\Result;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ComplexTreeTest extends \PHPUnit_Extensions_Database_TestCase
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
    protected function setUp()
    {
        $config = include dirname(__DIR__) . '/Storage/Db/assets/config.php';
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
     * @return LanguageAdapter
     */
    public function getLanguageAdapter()
    {
        $language = new ExpressionLanguage();
        $language->registerProvider(new HelperExpressionsProvider());
        return new LanguageAdapter($language);
    }

    /**
     * Set Up Schema
     */
    public static function setUpBeforeClass()
    {
        require_once __DIR__ . '/bootstrap.php';
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
    protected function getDataSet()
    {
        return $this->createXMLDataSet(__DIR__ . '/assets/initialStructure.xml');
    }

    /**
     *           ===========order=========
     *          /      /      |          \
     *      invoice   item   shipment    address
     *         |    //    \\   |
     *         item        item
     *
     * | == parent-child
     * || == sibling
     */
    public function testExport1()
    {
        // SET THESE TO TRUE TO DEBUG
        $this->config['db_debug'] = false;
        $this->config['file_debug'] = false;
        //===========================
        $order = new Unit('order');
        $order->setMapping([]);
        $order->setReversedMapping([]);
        $invoice = new Unit('invoice');
        $invoice->setMapping([]);
        $shipment = new Unit('shipment');
        $shipment->setMapping([]);
        $address = new Unit('address');
        $address->setMapping([]);
        $orderItem = new Unit('order_item');
        $orderItem->setMapping([]);
        $invoiceItem = new Unit('invoice_item');
        $invoiceItem->setMapping([]);
        $shipmentItem = new Unit('shipment_item');
        $shipmentItem->setMapping([]);

        $invoiceItem->setParent($invoice);
        $invoice->setParent($order);
        $shipmentItem->setParent($shipment);
        $shipment->setParent($invoice);
        $address->setParent($order);
        $orderItem->setParent($order);
        $orderItem->addSibling($invoiceItem);
        $orderItem->addSibling($shipmentItem);

        $bag = new SimpleBag();
        $bag->addSet([$order, $invoice, $shipment, $address, $orderItem, $invoiceItem, $shipmentItem]);

        $input = new ArrayInput();

        $reverseMove = new ReverseMove($bag, $this->config, $this->resource);
        $dump = new Dump($bag, $this->config, $this->resource);
        $assemble = new AssembleInput($bag, $this->config, $this->getLanguageAdapter(), $input, new ArrayMap());

        $result = new Result();
        $workflow = new QueueWorkflow($this->config, $result);
        $workflow->add($reverseMove);
        $workflow->add($dump);
        $workflow->add($assemble);

//        $workflow->execute();
        //=====================================================================
        // assert that customers are in the file

        // TODO assertions
    }
}
