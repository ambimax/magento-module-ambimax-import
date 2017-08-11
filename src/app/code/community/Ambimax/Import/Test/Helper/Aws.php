<?php

use Aws\Result;

class Ambimax_Import_Test_Helper_Aws extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @dataProvider clientProvider
     */
    public function testClient($profile)
    {
        /** @var Ambimax_Import_Helper_Aws $helper */
        $helper = Mage::helper('ambimax_import/aws');

        $client = $helper->getClient($profile);
        $this->assertInstanceOf('Aws\S3\S3Client', $client);
        $this->assertEquals('eu-central-1', $client->getRegion());
    }

    public function clientProvider()
    {
        return [
            ['foobar'],
            [['profile' => 'foobar', 'region' => 'eu-central-1']],
            [['profile' => 'foobar']],
            [new Varien_Object(['profile' => 'foobar', 'region' => 'eu-central-1'])],
            [new Varien_Object(['profile' => 'foobar'])],
        ];
    }

    /**
     * @loadFixture customDefaultRegion
     */
    public function testDefaultRegion()
    {
        $this->assertEquals('sa-east-1', Mage::getStoreConfig('ambimax_import/general/aws_default_region'));

        /** @var Ambimax_Import_Helper_Aws $helper */
        $helper = Mage::helper('ambimax_import/aws');

        $client = $helper->getClient('default');
        $this->assertInstanceOf('Aws\S3\S3Client', $client);
        $this->assertNull($client->getRegion());
        $this->assertEquals('sa-east-1', $helper->getDefaultRegion());
    }

}