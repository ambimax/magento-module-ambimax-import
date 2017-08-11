<?php

class Ambimax_Import_Helper_Aws_S3 extends Ho_Import_Helper_Import
{
    /**
     * Download file if local file does not exists or is older. Use force to always download files.
     *
     * @param $line
     * @param $profile
     * @param $bucket
     * @param $basePath
     * @param $path
     * @param bool $force
     * @return string
     */
    public function getFile($line, $profile, $bucket, $basePath, $path, $force = false)
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

        $bucketPath = trim($basePath, '/') . '/' . ltrim($path, '/');
        // use full path as name in case same name exists in different folder
        $localPath = DS . trim($basePath, '/') . DS . str_replace('/', '_', ltrim($path, '/'));
        $savePath = Mage::getBaseDir('media') . DS . 'import' . $localPath;

        $this->downloadFile(
            $profile,
            $bucket,
            $bucketPath,
            $savePath,
            $force
        );

        return is_file($savePath) ? $localPath : '';
    }

    /**
     * Find images by name
     *
     * @param $line
     * @param $profile
     * @param $bucket
     * @param $prefix
     * @param $name
     * @param bool $force
     * @param bool $limit
     * @param string $pattern
     * @return array
     */
    public function findImagesByName($line, $profile, $bucket, $prefix, $name, $force = false, $limit = false, $pattern = '') // @codingStandardsIgnoreLine
    {
        $name = $this->_getMapper()->mapItem($name) ? $this->_getMapper()->mapItem($name) : $name;
        $force = $this->_getMapper()->mapItem($force) ? $this->_getMapper()->mapItem($force) : $force;
        $limit = $this->_getMapper()->mapItem($limit) ? $this->_getMapper()->mapItem($limit) : $limit;
        $prefix = $this->_getMapper()->mapItem($prefix) ? $this->_getMapper()->mapItem($prefix) : $prefix;
        $profile = $this->_getMapper()->mapItem($profile) ? $this->_getMapper()->mapItem($profile) : $profile;
        $bucket = $this->_getMapper()->mapItem($bucket) ? $this->_getMapper()->mapItem($bucket) : $bucket;
        $pattern = $this->_getMapper()->mapItem($pattern) ? $this->_getMapper()->mapItem($pattern) : $pattern;

        if ( empty($name) ) {
            $name = '.*';
        }

        if ( empty($pattern) ) {
            $pattern = '/\/(__NAME__)(\.(jpg|jpeg|png)$|[\_-].*\.(jpg|jpeg|png)$)/i';
        }

        $pattern = str_replace('__NAME__', $name, $pattern);

        $ls = $this->getDirectoryListing($bucket, $profile, $prefix);

        $matches = preg_grep($pattern, array_keys($ls));

        if ( !count($matches) ) {
            return array();
        }

        if ( count($matches) > 1 ) {
            sort($matches);
        }

        $i = 0;
        $images = array();
        foreach ($matches as $bucketPath) {
            $info = $ls[$bucketPath];
            $savePath = Mage::getBaseDir('media') . DS . 'import' . $bucketPath;

            $this->downloadFile(
                $profile,
                $bucket,
                $bucketPath,
                $savePath,
                $force,
                $info['LastModified']
            );

            if ( !is_file($savePath) ) {
                continue;
            }

            $images[] = $bucketPath;

            if ( $limit && ++$i >= (int)$limit ) {
                break;
            }
        }

        return $images;
    }

    /**
     * @param $profile
     * @param $bucket
     * @param $bucketPath
     * @param $savePath
     * @param bool $force
     * @param null $remoteModified
     * @return bool
     */
    public function downloadFile($profile, $bucket, $bucketPath, $savePath, $force = false, $remoteModified = null)
    {
        try {
            $client = $this->getAwsHelper()->getClient($profile);
            $fileExists = !$force && is_file($savePath);
            $fileSize = $fileExists ? filesize($savePath) : 0; // @codingStandardsIgnoreLine
            $fileTime = new DateTime($fileExists ? date('Y-m-d H:i:s', filemtime($savePath)) : '1970-01-01'); // @codingStandardsIgnoreLine

            // is $remoteModified required for comparison
            if ( $fileExists && $fileSize && is_null($remoteModified) ) {
                $result = $client->getObject(array('Bucket' => $bucket, 'Key' => $bucketPath));
                $remoteModified = isset($result['LastModified']) ? $result['LastModified'] : null;
            }

            if ( !$fileExists || !$fileSize || $remoteModified > $fileTime ) {

                // Ensure local folder exist
                $io = new Varien_Io_File();
                $io->checkAndCreateFolder(dirname($savePath)); // @codingStandardsIgnoreLine

                $client->getObject(array('Bucket' => $bucket, 'Key' => $bucketPath, 'SaveAs' => $savePath));

                $prettyPath = str_replace(Mage::getBaseDir(), '', $savePath);
                $this->getHelper()->getHoImportLog()->log(
                    $this->getHelper()->__(
                        "Downloading file %s from s3://%s, to %s",
                        $bucketPath, $bucket, $prettyPath
                    )
                );
                return true;
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
        return false;
    }

    /**
     * Runs findImageByName twice if first name did not match
     * @param $line
     * @param $name
     * @param string $path
     * @param bool $limit
     * @param string $profile
     * @param null $pattern
     * @param string $fallbackName
     * @return array
     */
    public function findImagesByNameWithFallbackName($line, $profile, $bucket, $prefix, $name, $fallbackName,
                                                     $force = false, $limit = false, $pattern = '')
    {
        $name = (string)$this->_getMapper()->mapItem($name);
        $fallbackName = (string)$this->_getMapper()->mapItem($fallbackName);

        $images = $this->findImagesByName($line, $profile, $bucket, $prefix, $name, $force, $limit, $pattern);

        if ( count($images) || strcmp($name, $fallbackName) === 0 ) {
            return $images;
        }

        return $this->findImagesByName($line, $profile, $bucket, $prefix, $fallbackName, $force, $limit, $pattern);
    }

    /**
     * Saves result from Aws\Result into $_cache variable for faster searches
     *
     * @param $bucket
     * @param $profile
     * @param $prefix
     * @return array
     */
    public function getDirectoryListing($bucket, $profile, $prefix)
    {
        if ( isset($this->_cache[$bucket . $prefix]) ) {
            return $this->_cache[$bucket . $prefix];
        }

        $client = $this->getAwsHelper()->getClient($profile);
        $results = $client->getPaginator('ListObjects', ['Bucket' => $bucket, 'Prefix' => $prefix]);

        $ls = array();
        foreach ($results as $result) {
            foreach ($result->get('Contents') as $item) {
                if ( $item['Size'] == 0 ) {
                    continue;
                }
                $ls[$item['Key']] = [
                    'LastModified' => $item['LastModified'],
                    'Size'         => $item['Size'],
                ];
            }
        }
        return $this->_cache[$bucket . $prefix] = $ls;
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