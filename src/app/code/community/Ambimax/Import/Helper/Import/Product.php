<?php

class Ambimax_Import_Helper_Import_Product extends Mage_Core_Helper_Abstract
{
    /**
     * @param $line
     * @param $sku
     * @param $strings
     * @return string
     * @throws Exception
     */
    public function getUrlKeyWithSku()
    {
        $args = func_get_args();
        $line = array_shift($args);
        $sku = trim($this->_getMapper()->mapItem(array_shift($args)));
        $fields = $args;

        if ( empty($sku) ) {
            throw new Exception('sku is not set');
        }

        $urlKey = array();
        foreach ($fields as $field) {
            $value = $this->_getMapper()->mapItem($field);
            if ( !empty($value) ) {
                $urlKey[] = $value;
            }
        }

        if ( empty($urlKey) ) {
            throw new Exception(sprintf('url-key must not be empty for sku "%s"', $sku));
        }

        // add sku and length
        $urlKey[] = $sku;
        $urlKey[] = strlen($sku);

        return $this->formatUrlKey(implode(' ', $urlKey));
    }

    /**
     * Format Key for URL
     *
     * @param string $str
     * @return string
     */
    public function formatUrlKey($str)
    {
        $urlKey = preg_replace('#[^0-9a-z]+#i', '-', strtolower(Mage::helper('catalog/product_url')->format($str)));
        $urlKey = trim($urlKey, '-');

        return $urlKey;
    }

    /**
     * @return Ho_Import_Model_Mapper
     */
    protected function _getMapper()
    {
        return Mage::getSingleton('ho_import/mapper');
    }
}