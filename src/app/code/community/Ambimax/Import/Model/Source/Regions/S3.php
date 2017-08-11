<?php

class Ambimax_Import_Model_Source_Regions_S3
{
    protected $_regions = [
        'us-east-2'      => 'US East (Ohio)',
        'us-east-1'      => 'US East (N. Virginia)',
        'us-west-1'      => 'US West (N. California)',
        'us-west-2'      => 'US West (Oregon)',
        'ca-central-1'   => 'Canada (Central)',
        'ap-south-1'     => 'Asia Pacific (Mumbai)',
        'ap-northeast-2' => 'Asia Pacific (Sydney)',
        'ap-southeast-1' => 'Asia Pacific (Singapore)',
        'ap-northeast-1' => 'Asia Pacific (Tokyo)',
        'eu-central-1'   => 'EU (Frankfurt)',
        'eu-west-1'      => 'EU (Ireland)',
        'eu-west-2'      => 'EU (London)',
        'sa-east-1'      => 'South America (São Paulo)',
    ];

    /**
     * Options getter
     *
     * @return array AWS S3 Regions as option array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('ambimax_import');

        $options = array();
        foreach ($this->_regions as $regionId => $label) {
            $options[$regionId] = array(
                'value' => $regionId,
                'label' => $helper->__($label),
            );
        }

        return $options;
    }

}