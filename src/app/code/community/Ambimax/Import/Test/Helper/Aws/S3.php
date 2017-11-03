<?php

use Aws\Result;

class Ambimax_Import_Test_Helper_Aws_S3 extends Ambimax_Import_Test_Abstract
{
    protected $_testFiles = [];

    /**
     * setup
     */
    public function setUp()
    {
        parent::setUp();
        $io = new Varien_Io_File();
        $io->checkAndCreateFolder(Mage::getBaseDir('var').DS.'import');

        // Create files - otherwise these files are not returned from getImagesByName*
        foreach ($this->getPaginationResultValue() as $line) {
            $this->createTestfile('{{media_dir}}/import/' . $line['Key'], 'origin');
        }
    }

    /**
     * tear down
     */
    public function tearDown()
    {
        $io = new Varien_Io_File();
        foreach ($this->_testFiles as $file => $content) {
            $io->rm($file);
        }
    }

    /**
     * Create test file
     *
     * @param $filepath
     * @param string $content
     */
    public function createTestfile($filepath, $content = '')
    {
        // prepare
        $replace = array(
            '{{base_dir}}'  => Mage::getBaseDir(),
            '{{media_dir}}' => Mage::getBaseDir('media'),
        );

        $file = str_replace(array_keys($replace), $replace, $filepath);
        $io = new Varien_Io_File();
        $io->checkAndCreateFolder(dirname($file));  // @codingStandardsIgnoreLine
        $io->open(array('path' => dirname($file))); // @codingStandardsIgnoreLine
        $io->filePutContent($file, $content);
        $this->_testFiles[$file] = $content;
    }

    /**
     * Test helper instance
     */
    public function testCorrectHelperInstance()
    {
        /** @var Ambimax_Import_Helper_Aws_S3 $helper */
        $helper = Mage::helper('ambimax_import/aws_s3');

        $this->assertInstanceOf('Ambimax_Import_Helper_Aws', $helper->getAwsHelper());
    }

    /**
     * File does not exist. Downloaded expected.
     */
    public function testGetNonExistingFile()
    {
        $client = $this->getMockBuilder('Aws\S3\S3Client')
            ->disableOriginalConstructor()
            ->setMethods(['getObject'])
            ->getMock();

        /** @var Ambimax_Import_Helper_Aws $awsHelper */
        $awsHelper = Mage::helper('ambimax_import/aws');
        $awsHelper->setClient('default', $client);

        $params = array(
            'Bucket' => 'foobar',
            'Key'    => 'Subfolder/Picture1.jpg',
            'SaveAs' => Mage::getBaseDir('media') . '/import/Subfolder/Picture1.jpg'
        );

        $client->expects($this->once())
            ->method('getObject')
            ->with($params)
            ->will($this->returnValue(new Result()));

        /** @var Ambimax_Import_Helper_Aws_S3 $helper */
        $helper = Mage::helper('ambimax_import/aws_s3');
        $helper->getFile(
            $line = array(),
            $profile = 'default',
            $bucket = 'foobar',
            $basePath = 'Subfolder',
            $path = 'Picture1.jpg',
            $force = false
        );
    }

    /**
     * File exists. Download expected because storage file is newer.
     */
    public function testGetNewerFileAndOverwriteExistingFile()
    {
        $localFilename = Mage::getBaseDir('media') . '/import/Subfolder/testGetExistingFile.test.csv';
        $this->createTestfile($localFilename, 'origin', now()-86400);

        $this->assertFileExists($localFilename);
        $this->assertStringEqualsFile($localFilename, 'origin');

        $client = $this->getMockBuilder('Aws\S3\S3Client')
            ->disableOriginalConstructor()
            ->setMethods(['getObject'])
            ->getMock();

        /** @var Ambimax_Import_Helper_Aws $awsHelper */
        $awsHelper = Mage::helper('ambimax_import/aws');
        $awsHelper->setClient('default', $client);

        $params = array(
            'Bucket' => 'foobar',
            'Key'    => 'Subfolder/testGetExistingFile.test.csv',
        );

        $paramsSave = array(
            'Bucket' => 'foobar',
            'Key'    => 'Subfolder/testGetExistingFile.test.csv',
            'SaveAs' => $localFilename
        );

        $client
            ->expects($this->exactly(2))
            ->method('getObject')
            ->with(
                $this->logicalOr(
                    $this->equalTo($params),
                    $this->equalTo($paramsSave)
                )
            )
            ->will($this->returnCallback(array($this, 'awsResultCallback')));

        /** @var Ambimax_Import_Helper_Aws_S3 $helper */
        $helper = Mage::helper('ambimax_import/aws_s3');
        $helper->getFile(
            $line = array(),
            $profile = 'default',
            $bucket = 'foobar',
            $basePath = 'Subfolder',
            $path = 'testGetExistingFile.test.csv',
            $force = false
        );

        $this->assertFileExists($localFilename);
        $this->assertStringEqualsFile($localFilename, 'Aws\Result::modified');
    }

    /**
     * File exists. No download expected because storage file is older.
     */
    public function testGetOlderFileAndKeepExistingFile()
    {
        $localFilename = Mage::getBaseDir('media') . '/import/Subfolder/testGetOlderFileAndKeepExistingFile.test.csv';
        $this->createTestfile($localFilename, 'origin');

        $this->assertFileExists($localFilename);
        $this->assertStringEqualsFile($localFilename, 'origin');

        $client = $this->getMockBuilder('Aws\S3\S3Client')
            ->disableOriginalConstructor()
            ->setMethods(['getObject'])
            ->getMock();

        /** @var Ambimax_Import_Helper_Aws $awsHelper */
        $awsHelper = Mage::helper('ambimax_import/aws');
        $awsHelper->setClient('default', $client);

        $params = array(
            'Bucket' => 'foobar',
            'Key'    => 'Subfolder/testGetOlderFileAndKeepExistingFile.test.csv',
        );

        $client
            ->expects($this->once())
            ->method('getObject')
            ->with($params)
            ->will($this->returnCallback(array($this, 'awsResultCallback')));

        /** @var Ambimax_Import_Helper_Aws_S3 $helper */
        $helper = Mage::helper('ambimax_import/aws_s3');
        $helper->getFile(
            $line = array(),
            $profile = 'default',
            $bucket = 'foobar',
            $basePath = 'Subfolder',
            $path = 'testGetOlderFileAndKeepExistingFile.test.csv',
            $force = false
        );

        $this->assertFileExists($localFilename);
        $this->assertStringEqualsFile($localFilename, 'origin');
    }

    /**
     * Aws Result Callback
     *
     * @param $params
     * @return Result
     */
    public function awsResultCallback($params)
    {
        if ( !empty($params['SaveAs']) ) {
            // Simulate file changes
            $io = new Varien_Io_File();
            $io->open(array('path' => dirname($params['SaveAs']))); // @codingStandardsIgnoreLine
            $io->checkAndCreateFolder(dirname($params['SaveAs']));  // @codingStandardsIgnoreLine
            $io->filePutContent($params['SaveAs'], 'Aws\Result::modified');
        }

        $result = [];
        switch ($params['Key']) {
            case 'Subfolder/testGetExistingFile.test.csv':
                $result['LastModified'] = new \Aws\Api\DateTimeResult('now');
                break;
            case 'Subfolder/testGetOlderFileAndKeepExistingFile.test.csv':
                $result['LastModified'] = new \Aws\Api\DateTimeResult('2000-01-01');
                break;
        }

        return new Result($result);
    }

    /**
     * Should return an array with images
     *
     * @loadFixture ~Ambimax_Import/reset.yaml
     * @dataProvider dataProvider
     */
    public function testGetDirectoryListing($listObjectParams, $paginationResultValue)
    {
        $client = $this->getMockBuilder('Aws\S3\S3Client')
            ->disableOriginalConstructor()
            ->setMethods(['getPaginator'])
            ->getMock();

        /** @var Ambimax_Import_Helper_Aws $awsHelper */
        $awsHelper = Mage::helper('ambimax_import/aws');
        $awsHelper->setClient('default', $client);

        $paginationResult = $this->getMockBuilder('\Aws\ResultPaginator')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(['get'])
            ->getMock();

        $paginationResult->expects($this->once())
            ->method('get')
            ->with('Contents')
            ->will($this->returnValue($paginationResultValue));

        $client
            ->expects($this->once())
            ->method('getPaginator')
            ->with('ListObjects', $listObjectParams)
            ->will($this->returnValue([$paginationResult]));

        /** @var Ambimax_Import_Helper_Aws_S3 $helper */
        $helper = Mage::helper('ambimax_import/aws_s3');

        $ls = $helper->getDirectoryListing('foobar', 'default', '/');

        $this->assertEquals(3, count($ls));
        $this->assertArrayHasKey('item.jpg', $ls);
        $this->assertArrayHasKey('item2.jpg', $ls);
        $this->assertArrayHasKey('item3.jpg', $ls);
    }

    /**
     * Checks if returned items are as expected
     *
     * @dataProvider dataProvider
     * @loadFixture ~Ambimax_Import/reset.yaml
     *
     * @param $name
     * @param array $expectedItems
     * @param bool $limit
     */
    public function testFindImagesByName($name, $expectedItems = [], $limit = false)
    {
        $client = $this->getMockBuilder('Aws\S3\S3Client')
            ->disableOriginalConstructor()
            ->setMethods(['getPaginator', 'GetObject'])
            ->getMock();

        /** @var Ambimax_Import_Helper_Aws $awsHelper */
        $awsHelper = Mage::helper('ambimax_import/aws');
        $awsHelper->setClient('default', $client);

        $paginationResult = $this->getMockBuilder('\Aws\ResultPaginator')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(['get'])
            ->getMock();

        $paginationResult
            ->expects($this->once())
            ->method('get')
            ->with('Contents')
            ->will($this->returnValue($this->getPaginationResultValue()));

        $client
            ->expects($this->once())
            ->method('getPaginator')
            ->with('ListObjects', ['Bucket' => 'foobar', 'Prefix' => '/.TEST/Bilder'])
            ->will($this->returnValue([$paginationResult]));

        $client
            ->expects($this->never())
            ->method('GetObject');

        /** @var Ambimax_Import_Helper_Aws_S3 $helper */
        $helper = Mage::helper('ambimax_import/aws_s3');

        $images = $helper->findImagesByName(
            $line = array(),
            $profile = 'default',
            $bucket = 'foobar',
            $prefix = '/.TEST/Bilder',
            $name,
            $force = false,
            $limit,
            $pattern = ''
        );

        $this->assertEquals(count($expectedItems), count($images));
        $this->assertEquals($expectedItems, $images);
    }

    /**
     * Checks if returned items are as expected
     *
     * @covers Ambimax_Import_Helper_Aws_S3::downloadFile
     * @dataProvider dataProvider
     * @loadFixture ~Ambimax_Import/reset.yaml
     * @param $name
     * @param $fallbackName
     * @param array $expectedItems
     * @param bool $limit
     */
    public function testFindImagesByNameWithFallbackName($name, $fallbackName, $expectedItems = [],
                                                         $limit = false)
    {
        $client = $this->getMockBuilder('Aws\S3\S3Client')
            ->disableOriginalConstructor()
            ->setMethods(['getPaginator', 'GetObject'])
            ->getMock();

        /** @var Ambimax_Import_Helper_Aws $awsHelper */
        $awsHelper = Mage::helper('ambimax_import/aws');
        $awsHelper->setClient('default', $client);

        $paginationResult = $this->getMockBuilder('\Aws\ResultPaginator')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(['get'])
            ->getMock();

        $paginationResult
            ->expects($this->once())
            ->method('get')
            ->with('Contents')
            ->will($this->returnValue($this->getPaginationResultValue()));

        $client
            ->expects($this->once())
            ->method('getPaginator')
            ->with('ListObjects', ['Bucket' => 'foobar', 'Prefix' => '/.TEST/Bilder'])
            ->will($this->returnValue([$paginationResult]));

        $client
            ->expects($this->never())
            ->method('GetObject');

        /** @var Ambimax_Import_Helper_Aws_S3 $helper */
        $helper = Mage::helper('ambimax_import/aws_s3');

        $images = $helper->findImagesByNameWithFallbackName(
            $line = array(),
            $profile = 'default',
            $bucket = 'foobar',
            $prefix = '/.TEST/Bilder',
            $name,
            $fallbackName,
            $force = false,
            $limit
        );

        $this->assertEquals(count($expectedItems), count($images));
        $this->assertEquals($expectedItems, $images);
    }

    /**
     * @return array
     */
    public function getPaginationResultValue()
    {
        return [
            ['Key' => '.TEST/Bilder/2843G.jpg', 'Size' => 310, 'LastModified' => '2017-05-12'],
            ['Key' => '.TEST/Bilder/2843.jpg', 'Size' => 120, 'LastModified' => '2017-05-12'],
            ['Key' => '.TEST/Bilder/2844.jpg', 'Size' => 123, 'LastModified' => '2017-05-12'],
            ['Key' => '.TEST/Bilder/52844.jpg', 'Size' => 141, 'LastModified' => '2017-05-12'],
            ['Key' => '.TEST/Bilder/2844/neu.jpg', 'Size' => 45, 'LastModified' => '2017-05-12'],
            ['Key' => '.TEST/Bilder/3455G_anything.jpg', 'Size' => 88, 'LastModified' => '2017-05-12'],
            ['Key' => '.TEST/Bilder/3455G_2.jpg', 'Size' => 88, 'LastModified' => '2017-05-12'],
            ['Key' => '.TEST/Bilder/3455G.jpg', 'Size' => 88, 'LastModified' => '2017-05-12'],
            ['Key' => '.TEST/Bilder/3455G_1.jpg', 'Size' => 88, 'LastModified' => '2017-05-12'],
            ['Key' => '.TEST/Bilder/3455G_20.jpg', 'Size' => 88, 'LastModified' => '2017-05-12'],
            ['Key' => '.TEST/Bilder/3455G_12.jpg', 'Size' => 88, 'LastModified' => '2017-05-12'],
            ['Key' => '.TEST/Bilder/3455G-3.jpg', 'Size' => 88, 'LastModified' => '2017-05-12'],
            ['Key' => '.TEST/Bilder/aqua.jpg', 'Size' => 88, 'LastModified' => '2017-05-12'],
            ['Key' => '.TEST/Bilder/aqua_1.jpg', 'Size' => 88, 'LastModified' => '2017-05-12'],
            ['Key' => '.TEST/Bilder/aqua_2.jpg', 'Size' => 88, 'LastModified' => '2017-05-12'],
            ['Key' => '.TEST/Bilder/_underline.jpg', 'Size' => 88, 'LastModified' => '2017-05-12'],
            ['Key' => '.TEST/Bilder/-3443.jpg', 'Size' => 88, 'LastModified' => '2017-05-12'],
        ];
    }

}