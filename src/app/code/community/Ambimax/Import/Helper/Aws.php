<?php

use Aws\S3\S3Client;

class Ambimax_Import_Helper_Aws extends Mage_Core_Helper_Abstract
{
    protected $_client = array();

    const AWS_API_VERSION = '2006-03-01';

    /**
     * @param string|array|Varien_Object $profile
     * @return Aws\S3\S3Client mixed
     */
    public function getClient($profile)
    {
        if ( is_string($profile) ) {
            $profile = array('profile' => $profile);
        }

        if ( is_array($profile) ) {
            $profile = new Varien_Object($profile);
        }

        if ( !isset($this->_client[$profile->getProfile()]) ) {

            $profile->setVersion(self::AWS_API_VERSION);
            if ( !$profile->hasData('region') ) {
                $profile->setData('region', $this->getDefaultRegion());
            }
            $this->_client[$profile->getProfile()] = new S3Client($profile->toArray());
        }

        return $this->_client[$profile->getProfile()];
    }

    /**
     * @param $client
     * @return $this
     */
    public function setClient($profile, S3Client $client)
    {
        $this->_client[$profile] = $client;
        return $this;
    }

    /**
     * Returns default region from backend
     *
     * @return string
     */
    public function getDefaultRegion()
    {
        return Mage::getStoreConfig('ambimax_import/general/aws_default_region');
    }
}