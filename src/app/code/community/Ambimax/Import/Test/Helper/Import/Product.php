<?php

class Ambimax_Import_Test_Helper_Import_Product extends Ambimax_Import_Test_Abstract
{
    /**
     * @expectedException Exception
     * @expectedExceptionMessageRegExp /sku is not set/
     */
    public function testGetUrlKeyWithMissingSku()
    {
        $mapperMock = $this->getMockBuilder('Ho_Import_Model_Mapper')
            ->setMethods(['mapItem'])
            ->getMock();

        $mapperMock
            ->expects($this->any())
            ->method('mapItem')
            ->willReturnCallback(array($this, 'mapResult'));

        $this->replaceByMock('singleton', 'ho_import/mapper', $mapperMock);

        /** @var Ambimax_Import_Helper_Import_Product $helper */
        $helper = Mage::helper('ambimax_import/import_product');

        call_user_func_array(array($helper, 'getUrlKeyWithSku'), array($line = array(), $sku = '')); // @codingStandardsIgnoreLine
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessageRegExp /url-key must not be empty for sku "invalid"/
     */
    public function testInvalidGetUrlKeyWithSku()
    {
        $mapperMock = $this->getMockBuilder('Ho_Import_Model_Mapper')
            ->setMethods(['mapItem'])
            ->getMock();

        $mapperMock
            ->expects($this->any())
            ->method('mapItem')
            ->willReturnCallback(array($this, 'mapResult'));

        $this->replaceByMock('singleton', 'ho_import/mapper', $mapperMock);

        /** @var Ambimax_Import_Helper_Import_Product $helper */
        $helper = Mage::helper('ambimax_import/import_product');

        call_user_func_array(array($helper, 'getUrlKeyWithSku'), array($line = array(), $sku = 'invalid')); // @codingStandardsIgnoreLine
    }

    /**
     * @dataProvider dataProvider
     */
    public function testGetUrlKeyWithSku($data, $expected)
    {
        $mapperMock = $this->getMockBuilder('Ho_Import_Model_Mapper')
            ->setMethods(['mapItem'])
            ->getMock();

        $mapperMock
            ->expects($this->any())
            ->method('mapItem')
            ->willReturnCallback(array($this, 'mapResult'));

        $this->replaceByMock('singleton', 'ho_import/mapper', $mapperMock);

        /** @var Ambimax_Import_Helper_Import_Product $helper */
        $helper = Mage::helper('ambimax_import/import_product');

        $this->assertEquals($expected, call_user_func_array(array($helper, 'getUrlKeyWithSku'), $data)); // @codingStandardsIgnoreLine
    }

    /**
     * Input/Output Result for mocks
     *
     * @param $param
     * @return mixed
     */
    public function mapResult($param)
    {
        return $param;
    }
}