<?php

class Ambimax_Import_Test_Abstract extends EcomDev_PHPUnit_Test_Case
{
    public function setUp()
    {
        $io = new Varien_Io_File();

        $io->checkAndCreateFolder(Mage::getBaseDir('media').DS.'import'.DS.'Subfolder'.DS, 0777);
        $io->checkAndCreateFolder(Mage::getBaseDir('var').DS.'import'.DS, 0777);
    }
}