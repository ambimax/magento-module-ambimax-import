<?php

use Aws\Result;

class Ambimax_Import_Test_Model_Downloader_S3 extends Ambimax_Import_Test_Abstract
{
    /**
     * setup
     */
    public function setUp()
    {
        $io = new Varien_Io_File();
        $io->checkAndCreateFolder(Mage::getBaseDir('var').DS.'import');
    }

    /**
     * @dataProvider dataProvider
     */
    public function testDownload($provider)
    {
        $client = $this->getMockBuilder('Aws\S3\S3Client')
            ->disableOriginalConstructor()
            ->setMethods(['getObject'])
            ->getMock();

        $return = $this->returnValue(new Result());
        $params = array(
            'Bucket' => 'foobar',
            'Key' => 'products.csv',
            'SaveAs' => Mage::getBaseDir().'/var/import/products.csv'
        );

        $client->expects($this->once())
            ->method('getObject')
            ->with($params)
            ->will($return);

        $connectionInfo = new Varien_Object($provider);
        $downloader = Mage::getSingleton('ambimax_import/downloader_s3');
        $downloader->setClient($client);
        $result = $downloader->download($connectionInfo, 'var/import');
        $this->assertNull($result);
    }

    /**
     * @dataProvider dataProvider
     * @expectedException Exception
     * @expectedExceptionMessageRegExp /Error downloading file/
     */
    public function testNoFileDownload($provider)
    {
        $client = $this->getMockBuilder('Aws\S3\S3Client')
            ->disableOriginalConstructor()
            ->setMethods(['getObject'])
            ->getMock();

        $return = $this->throwException(
            new \Aws\S3\Exception\S3Exception(
                'Invalid Key',
                new \Aws\Command('getObject')
            )
        );

        $params = array(
            'Bucket' => 'foobar',
            'Key' => 'products.csv',
            'SaveAs' => Mage::getBaseDir().'/var/import/products.csv'
        );

        $client->expects($this->once())
            ->method('getObject')
            ->with($params)
            ->will($return);

        $connectionInfo = new Varien_Object($provider);
        $downloader = Mage::getSingleton('ambimax_import/downloader_s3');
        $downloader->setClient($client);
        $downloader->download($connectionInfo, 'var/import');
    }
}