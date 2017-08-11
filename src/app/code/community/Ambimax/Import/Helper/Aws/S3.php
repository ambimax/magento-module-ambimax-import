<?php

class Ambimax_Import_Helper_Aws_S3 extends Ho_Import_Helper_Import
{
    /**
     * Download file if local file does not exists or is older. Use force to always download files.
     *
     * @param $line
     * @param $basePath
     * @param $path
     * @param $bucket
     * @param $profile
     * @param bool $force
     * @return string
     */
    public function getFile($line, $basePath, $path, $bucket, $profile, $force = false)
    {
        $basePath = $this->_getMapper()->mapItem($basePath) ? $this->_getMapper()->mapItem($basePath) : $basePath;
        $path = $this->_getMapper()->mapItem($path) ? $this->_getMapper()->mapItem($path) : $path;
        $profile = $this->_getMapper()->mapItem($profile) ? $this->_getMapper()->mapItem($profile) : $profile;
        $bucket = $this->_getMapper()->mapItem($bucket) ? $this->_getMapper()->mapItem($bucket) : $bucket;
        $force = $this->_getMapper()->mapItem($force) ? $this->_getMapper()->mapItem($force) : $force;

        if ( is_array($path) ) {
            // field is empty
            return '';
        }

        try {

            // use full path as name in case same name exists in different folder
            $localPath = DS . trim($basePath, '/') . DS . str_replace('/', '_', ltrim($path, '/'));
            $savePath = Mage::getBaseDir('media') . DS . 'import' . $localPath;

            // Ensure local folder exist
            $io = new Varien_Io_File();
            $io->checkAndCreateFolder(dirname($savePath)); // @codingStandardsIgnoreLine

            $bucketPath = trim($basePath, '/') . '/' . ltrim($path, '/');
            $client = $this->getAwsHelper()->getClient($profile);

            $fileExists = !$force && is_file($savePath);
            $fileSize = $fileExists ? filesize($savePath) : 0; // @codingStandardsIgnoreLine
            $fileTime = new DateTime($fileExists ? date('Y-m-d H:i:s', filemtime($savePath)) : '1970-01-01'); // @codingStandardsIgnoreLine

            /** @var Aws\Result $result */
            $result = $fileExists ? $client->getObject(array('Bucket' => $bucket, 'Key' => $bucketPath)) : null;
            if ( !$fileExists || !$fileSize || $result->get('LastModified') > $fileTime ) {
                $client->getObject(array('Bucket' => $bucket, 'Key' => $bucketPath, 'SaveAs' => $savePath));
                $this->getHelper()->getHoImportLog()->log(
                    $this->getHelper()->__(
                        "Downloading file %s from s3://%s, to %s",
                        $bucketPath, $bucket, $localPath
                    )
                );
            }

        } catch (Aws\S3\Exception\S3Exception $e) {
            $this->getHelper()->getHoImportLog()->log(
                $this->getHelper()->__(
                    "Error downloading file %s: %s",
                    $bucketPath, $e->getAwsErrorMessage()
                ),
                Zend_Log::ERR
            );
        }

        return is_file($savePath) ? $localPath : '';
    }

    /**
     * @return Ambimax_Import_Helper_Aws|Mage_Core_Helper_Abstract
     */
    public function getAwsHelper()
    {
        return Mage::helper('ambimax_import/aws');
    }

    /**
     * @return Ambimax_Import_Helper_Data|Mage_Core_Helper_Abstract
     */
    public function getHelper()
    {
        return Mage::helper('ambimax_import');
    }

}