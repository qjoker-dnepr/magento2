<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Elasticsearch\Test\Unit\SearchAdapter\Aggregation;

use Magento\Elasticsearch\SearchAdapter\Aggregation\Interval;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Elasticsearch\SearchAdapter\ConnectionManager;
use Magento\Elasticsearch\SearchAdapter\FieldMapperInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Elasticsearch\Model\Config;
use Magento\Elasticsearch\Model\Client\Elasticsearch as ElasticsearchClient;
use Magento\Store\Api\Data\StoreInterface;

class IntervalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Interval
     */
    protected $model;

    /**
     * @var ConnectionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionManager;

    /**
     * @var FieldMapperInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldMapper;

    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $clientConfig;

    /**
     * @var StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManager;

    /**
     * @var CustomerSession|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerSession;

    /**
     * @var ElasticsearchClient|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $clientMock;

    /**
     * @var StoreInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeMock;

    /**
     * Set up test environment.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->connectionManager = $this->getMockBuilder('Magento\Elasticsearch\SearchAdapter\ConnectionManager')
            ->setMethods(['getConnection'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->fieldMapper = $this->getMockBuilder('Magento\Elasticsearch\SearchAdapter\FieldMapperInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->clientConfig = $this->getMockBuilder('Magento\Elasticsearch\Model\Config')
            ->setMethods([
                'getIndexName',
                'getEntityType',
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager = $this->getMockBuilder('Magento\Store\Model\StoreManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerSession = $this->getMockBuilder('Magento\Customer\Model\Session')
            ->setMethods(['getCustomerGroupId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerSession->expects($this->any())
            ->method('getCustomerGroupId')
            ->willReturn(1);
        $this->storeMock = $this->getMockBuilder('Magento\Store\Api\Data\StoreInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(1);
        $this->storeMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->clientConfig->expects($this->any())
            ->method('getIndexName')
            ->willReturn('indexName');
        $this->clientConfig->expects($this->any())
            ->method('getEntityType')
            ->willReturn('product');
        $this->clientMock = $this->getMockBuilder('Magento\Elasticsearch\Model\Client\Elasticsearch')
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->connectionManager->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->clientMock);

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->model = $objectManagerHelper->getObject(
            '\Magento\Elasticsearch\SearchAdapter\Aggregation\Interval',
            [
                'connectionManager' => $this->connectionManager,
                'fieldMapper' => $this->fieldMapper,
                'storeManager' => $this->storeManager,
                'customerSession' => $this->customerSession,
                'clientConfig' => $this->clientConfig
            ]
        );
    }

    /**
     * @dataProvider loadParamsProvider
     * @param string $limit
     * @param string $offset
     * @param string $lower
     * @param string $upper
     * Test load() method
     */
    public function testLoad($limit, $offset, $lower, $upper)
    {
        $this->storeMock = $this->getMockBuilder('Magento\Store\Api\Data\StoreInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(1);

        $expectedResult = ['25', '26'];

        $this->clientMock->expects($this->once())
            ->method('query')
            ->willReturn([
                'hits' => [
                    'hits' => [
                        [
                            'sort' => ['25'],
                        ],
                        [
                            'sort' => ['26'],
                        ]
                    ],
                ],
            ]);
        $this->assertEquals(
            $expectedResult,
            $this->model->load($limit, $offset, $lower, $upper)
        );
    }

    /**
     * @dataProvider loadPrevParamsProvider
     * @param string $data
     * @param string $index
     * @param string $lower
     * Test loadPrevious() method with offset
     */
    public function testLoadPrevArray($data, $index, $lower)
    {
        $queryResult = ['hits' => ['total'=> '1','hits' => [['sort' => '25'],['sort' =>'26']]]];

        $this->storeMock = $this->getMockBuilder('Magento\Store\Api\Data\StoreInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(1);

        $expectedResult = ['2.0', '2.0'];

        $this->clientMock->expects($this->any())
            ->method('query')
            ->willReturn($queryResult);
        $this->assertEquals(
            $expectedResult,
            $this->model->loadPrevious($data, $index, $lower)
        );
    }

    /**
     * @dataProvider loadPrevParamsProvider
     * @param string $data
     * @param string $index
     * @param string $lower
     * Test loadPrevious() method without offset
     */
    public function testLoadPrevFalse($data, $index, $lower)
    {
        $queryResult = ['hits' => ['total'=> '0']];

        $this->storeMock = $this->getMockBuilder('Magento\Store\Api\Data\StoreInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(1);

        $this->clientMock->expects($this->any())
            ->method('query')
            ->willReturn($queryResult);
        $this->assertFalse(
            $this->model->loadPrevious($data, $index, $lower)
        );
    }

    /**
     * @dataProvider loadNextParamsProvider
     * @param string $data
     * @param string $rightIndex
     * @param string $upper
     * Test loadNext() method with offset
     */
    public function testLoadNextArray($data, $rightIndex, $upper)
    {
        $queryResult = ['hits' => ['total'=> '1','hits' => [['sort' => '25'],['sort' =>'26']]]];

        $this->storeMock = $this->getMockBuilder('Magento\Store\Api\Data\StoreInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(1);

        $expectedResult = ['2.0', '2.0'];

        $this->clientMock->expects($this->any())
            ->method('query')
            ->willReturn($queryResult);
        $this->assertEquals(
            $expectedResult,
            $this->model->loadNext($data, $rightIndex, $upper)
        );
    }

    /**
     * @dataProvider loadNextParamsProvider
     * @param string $data
     * @param string $rightIndex
     * @param string $upper
     * Test loadNext() method without offset
     */
    public function testLoadNextFalse($data, $rightIndex, $upper)
    {
        $queryResult = ['hits' => ['total'=> '0']];

        $this->storeMock = $this->getMockBuilder('Magento\Store\Api\Data\StoreInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(1);

        $this->clientMock->expects($this->any())
            ->method('query')
            ->willReturn($queryResult);
        $this->assertFalse(
            $this->model->loadNext($data, $rightIndex, $upper)
        );
    }

    /**
     * @return array
     */
    public static function loadParamsProvider()
    {
        return [
            ['6', '2', '24', '42'],
        ];
    }

    /**
     * @return array
     */
    public static function loadPrevParamsProvider()
    {
        return [
            ['24', '1', '24'],
        ];
    }

    /**
     * @return array
     */
    public static function loadNextParamsProvider()
    {
        return [
            ['24', '2', '42'],
        ];
    }
}
