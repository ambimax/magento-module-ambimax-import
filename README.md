
# ambimax® Import

Extends functionality of [Ho_Import](https://github.com/ho-nl/magento1-Ho_Import)

**// This project is under heavy development and not production ready! //**

### Additional Downloaders
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


## Author Information

 - Tobias Schifftner, [ambimax® GmbH](https://www.ambimax.de)
