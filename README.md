
# ambimax® Import

Extends functionality of [Ho_Import](https://github.com/ho-nl/magento1-Ho_Import)

**// This project is under heavy development and not production ready! //**

## Additional Downloaders
 - s3

#### AWS S3 Example
```XML
<downloader model="ambimax_import/downloader_s3">
    <profile>
        <profile>default</profile>
        <region>eu-central-1</region>
    </profile>
    <bucket>bucket-name</bucket>
    <!-- the downloader defaults to var/import -->
    <!--<target>custom/download/path/filename.xml</target>-->
</downloader>
```

## Additional Helpers

 - ambimax_import/aws_s3::getFile
 
#### S3 Get File
Combines basePath and path to bucket url (s3://bucket/basePath/path) and downloads file when 
local file does not exist or is older than the file in the bucket.

```XML
<image helper="ambimax_import/aws_s3::getFile">
    <basePath value="Category/Images"/>
    <path field="image_path"/>
    <bucket>bucket-name</bucket>
    <profile>
        <profile>default</profile>
        <region>eu-central-1</region> <!-- optional when default region is set in backend -->
    </profile>
    <force>1</force> <!-- optional: Forces download of file -->
</image>
<thumbnail helper="ambimax_import/aws_s3::getFile">
    <basePath value="Category/Thumbnails"/>
    <path field="thumbnail_image_path"/>
    <bucket>bucket-name</bucket>
    <profile>default</profile>
</thumbnail>
```
 

## Author Information

 - Tobias Schifftner, [ambimax® GmbH](https://www.ambimax.de)
