<?php

class Ambimax_Import_Model_Downloader_S3 extends Ho_Import_Model_Downloader_Abstract
{
    /**
     * @var Aws\S3\S3Client
     */
    protected $_client;

    /**
     * Downloads files from s3 bucket during ho_import profiles
     *
     * @param Varien_Object $connectionInfo
     * @param $target
     * @return null
     * @throws Exception
     */
    public function download(Varien_Object $connectionInfo, $target)
    {
        if ( !is_writable(Mage::getBaseDir() . DS . $target) ) { // @codingStandardsIgnoreLine
            Mage::throwException(
                $this->_getLog()->__(
                    "Can not write file %s to %s, folder not writable (doesn't exist?)",
                    $connectionInfo->getFile(), $target
                )
            );
        }

        $bucket = $connectionInfo->getBucket();
        $file = $connectionInfo->getFile();
        $target .= DS . basename($file); // @codingStandardsIgnoreLine
        $targetpath = Mage::getBaseDir() . DS . $target;

        try {

            $this->_log($this->_getLog()->__("Connecting to AWS Bucket s3://%s", $bucket));
            $client = $this->getClient($connectionInfo->getData('profile'));

            $this->_log(
                $this->_getLog()->__(
                    "Downloading file %s from s3://%s, to %s",
                    $connectionInfo->getFile(), $bucket, $target
                )
            );

            $client->getObject(array('Bucket' => $bucket, 'Key' => $file, 'SaveAs' => $targetpath));

        } catch (Aws\S3\Exception\S3Exception $e) {
            throw new Exception(
                $this->_getLog()->__(
                    "Error downloading file %s from bucket s3://%s, to %s: %s",
                    $file, $bucket, $targetpath, $e->getAwsErrorMessage()
                )
            );
        }

        return null;
    }

    /**
     * Get aws s3 client
     *
     * @return Aws\S3\S3Client
     */
    public function getClient($profile)
    {
        if ( !$this->_client ) {
            $this->_client = Mage::helper('ambimax_import/aws')->getClient($profile);
        }
        return $this->_client;
    }

    /**
     * Set aws s3 client
     *
     * @param $client
     * @return $this
     */
    public function setClient(Aws\S3\S3Client $client)
    {
        $this->_client = $client;
        return $this;
    }
}