<?php

class Ambimax_Import_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @return Ho_Import_Helper_Log
     */
    public function getHoImportLog()
    {
        return Mage::helper('ho_import/log');
    }
}