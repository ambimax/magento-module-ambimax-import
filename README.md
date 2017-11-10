
# ambimax® Import

[![Build Status](https://travis-ci.org/ambimax/magento-module-ambimax-import.svg?branch=master)](https://travis-ci.org/ambimax/magento-module-ambimax-import)

Extends functionality of [Ho_Import](https://github.com/ho-nl/magento1-Ho_Import)

## Additional Downloaders

#### AWS S3 Downloader
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
 
#### getFile (from S3 Bucket)
Combines basePath and path to bucket url (s3://bucket/basePath/path) and downloads file when 
local file does not exist or is older than the file in the bucket.

```XML
<image helper="ambimax_import/aws_s3::getFile">
    <profile>
        <profile>default</profile>
        <region>eu-central-1</region> <!-- optional when default region is set in backend -->
    </profile>
    <bucket>bucket-name</bucket>
    <basePath value="Category/Images"/>
    <path field="image_path"/>   
    <force>1</force> <!-- optional: Forces download of file -->
</image>
```
```XML
<thumbnail helper="ambimax_import/aws_s3::getFile">
    <profile>default</profile>
    <bucket>bucket-name</bucket>
    <basePath value="Category/Thumbnails"/>
    <path field="thumbnail_image_path"/>
</thumbnail>
```
 
#### findImagesByName (in S3 Bucket)
Find images on s3 bucket by name.

Default RegExp (string `__NAME__` will be replaced by input):
```REGEXP
/\/(__NAME__)(\.(jpg|jpeg|png)$|[\_].*\.(jpg|jpeg|png)$)/i
```

This pattern will find the following images when input is `2844`. The result will be returned in natural order:

 - 2844.jpg
 - 2844_1.jpg
 - 2844_2.jpg
 - 2844_12.jpg
 - 2844_20.jpg
 - 2844_anything.jpg

```XML
<_media_image helper="ambimax_import/aws_s3::findImagesByName">
    <profile>default</profile>
    <bucket>delphin-storage</bucket>
    <prefix value="Teich/Bilder" />
    <name field="sku"/>
    <force>1</force> <!-- optional: Forces download of file -->
    <limit>false</limit> <!-- optional -->
    <pattern></pattern> <!-- optional -->
</_media_image>
```

#### findImagesByNameWithFallbackName (in S3 Bucket)

Find images on s3 bucket by name or a second name when first name has no results.

Default RegExp is the same as `findImagesByName` function

```XML
<_media_image helper="ambimax_import/aws_s3::findImagesByNameWithFallbackName">
    <profile>default</profile>
    <bucket>delphin-storage</bucket>
    <prefix value="Teich/Bilder" />
    <name field="sku"/>
    <fallbackName helper="my_module/import::getChildSku">
        <name field="sku" />
    </fallbackName>
    <force>1</force> <!-- optional: Forces download of file -->
    <limit>false</limit> <!-- optional -->
    <pattern></pattern> <!-- optional -->
</_media_image>
```

#### getUrlKeyWithSku

This helper adds sku to product url. This can be used with [ambimax® ProductNotFoundHandler](https://github.com/ambimax/magento-module-ambimax-productnotfoundhandler)

```XML
<url_key helper="ambimax_import/import_product::getUrlKeyWithSku"> 
    <ident use="sku"/> 
    <name use="name"/> 
    <!-- additional fields --> 
    <ean field="ean"/> 
</url_key>
```

## Supported Magento Versions

 - Magento CE 1.9.2.4 and newer

## Supported PHP Versions

 - php 5.6
 - php 7.0

## License

[MIT License](http://choosealicense.com/licenses/mit/)

## Author Information

 - Tobias Schifftner, [ambimax® GmbH](https://www.ambimax.de)
