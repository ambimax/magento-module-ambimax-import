<?php

use Aws\S3\S3Client;

class Ambimax_Import_Helper_Aws extends Mage_Core_Helper_Abstract
{
    protected $_client = array();

    const AWS_API_VERSION = '2006-03-01';

    public function getProfile($line, $profile)
    {
        print_r($profile);
    }

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
            $this->_client[$profile->getProfile()] = new S3Client($profile->toArray());
        }

        return $this->_client[$profile->getProfile()];
    }
}