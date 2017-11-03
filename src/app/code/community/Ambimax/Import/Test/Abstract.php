<?php

class Ambimax_Import_Test_Abstract extends EcomDev_PHPUnit_Test_Case
{
    protected $_testFiles = [];

    public function setUp()
    {
        $io = new Varien_Io_File();

        $io->checkAndCreateFolder(Mage::getBaseDir('media') . DS . 'import' . DS . 'Subfolder' . DS, 0777);
        $io->checkAndCreateFolder(Mage::getBaseDir('var') . DS . 'import' . DS, 0777);
    }

    public function tearDown()
    {
        $io = new Varien_Io_File();
        foreach ($this->_testFiles as $file => $content) {
            $io->rm($file);
        }
    }

    /**
     * Helper to create test file
     *
     * @param $filepath
     * @param string $content
     * @param null $filemtime
     */
    public function createTestfile($filepath, $content = '', $filemtime = null)
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
        if ( $filemtime ) {
            @touch($file, $filemtime); // @codingStandardsIgnoreLine
        }
        $this->_testFiles[$file] = $content;
    }

}