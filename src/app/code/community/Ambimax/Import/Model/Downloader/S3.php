<?php

class Ambimax_Import_Model_Downloader_S3 extends Ho_Import_Model_Downloader_Abstract
{

    public function download(Varien_Object $connectionInfo, $target)
    {
//        print_r($connectionInfo->toArray());

        if ( !is_writable(Mage::getBaseDir() . DS . $target) ) { // @codingStandardsIgnoreLine
            Mage::throwException(
                $this->_getLog()->__(
                    "Can not write file %s to %s, folder not writable (doesn't exist?)",
                    $connectionInfo->getFile(), $target
                )
            );
        }

        /** @var Ambimax_Import_Helper_Aws $awsHelper */
        $awsHelper = Mage::helper('ambimax_import/aws');
        $bucket = $connectionInfo->getBucket();
        $file = $connectionInfo->getFile();

        $this->_log($this->_getLog()->__("Connecting to s3 Bucket %s", $bucket));
        $client = $awsHelper->getClient($connectionInfo->getData('profile'));

        $this->_log(
            $this->_getLog()->__(
                "Downloading file %s from %s, to %s",
                $connectionInfo->getFile(), $connectionInfo->getBucket(), $target
            )
        );
        $result = $client->getObject(array('Bucket' => $bucket, 'Key' => $file, 'SaveAs' => $target));

        return null;
    }
}