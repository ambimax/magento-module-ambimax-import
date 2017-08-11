<?php

use Aws\Result;

class Ambimax_Import_Test_Helper_Aws_S3 extends EcomDev_PHPUnit_Test_Case
{
    protected $_testFiles = [];

    public function tearDown()
    {
        $io = new Varien_Io_File();
        foreach ($this->_testFiles as $file => $content) {
            $io->rm($file);
        }
    }

    public function createTestfile($filepath, $content = '')
    {
        // prepare
        $replace = array(
            '{{base_dir}}'  => Mage::getBaseDir(),
            '{{media_dir}}' => Mage::getBaseDir('media'),
        );

        $file = str_replace(array_keys($replace), $replace, $filepath);
        $io = new Varien_Io_File();
        $io->open(array('path' => dirname($file))); // @codingStandardsIgnoreLine
        $io->checkAndCreateFolder(dirname($file));  // @codingStandardsIgnoreLine
        $io->filePutContent($file, $content);
        $this->_testFiles[$file] = $content;
    }

    public function testHelper()
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

}