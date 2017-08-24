<?php

class Ambimax_Import_Test_Helper_Mail extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @dataProvider dataProvider
     * @loadExpectations
     * @param $expectation
     * @param $config
     * @internal param $email
     * @internal param $expected
     */
    public function testGetEmailFromConfig($expectation, $config)
    {
        Mage::app()->getStore()->setConfig('ambimax_import/email_report/to', $config);

        /** @var Ambimax_Import_Helper_Mail $helper */
        $helper = Mage::helper('ambimax_import/mail');

        $this->assertEquals(
            $this->expected($expectation)->getResult(),
            $helper->getEmailFromConfig('ambimax_import/email_report/to')
        );
    }

    /**
     * @loadFixture ~Ambimax_Import/reset.yaml
     * @loadProvider dataProvider
     */
    public function testSendEmailReportWhenDisabled()
    {
        /** @var Ambimax_Import_Helper_Mail $helper */
        $helper = Mage::helper('ambimax_import/mail');
        $transport = $this->getMockBuilder('Ho_Import_Model_Import_Transport');

        $this->assertFalse($helper->sendEmailReport($transport));
    }

    /**
     * @loadFixture ~Ambimax_Import/reset.yaml
     * @loadProvider dataProvider
     * @expectedException
     * @expectedExceptionMessageRegExp /email addresses/
     */
    public function testSendEmailReportWithMissingToEmail()
    {
        /** @var Ambimax_Import_Helper_Mail $helper */
        $helper = Mage::helper('ambimax_import/mail');
        $transport = $this->getMockBuilder('Ho_Import_Model_Import_Transport');

        $this->assertFalse($helper->sendEmailReport($transport));
    }

    /**
     * @loadFixture ~Ambimax_Import/reset.yaml
     * @loadFixture
     * @loadExpectation
     * @dataProvider dataProvider
     */
    public function testSendEmailReport($expectation, $to, $bcc, $subject = 'Subject', $getLogHtml = 'getLogHtml',
                                        $getInvalidRowsCount = 10, $sendOnErrorOnly = false)
    {
        $expect = $this->expected($expectation);
        $returnValue = $expect->getData('result');

        Mage::app()->getStore()
            ->setConfig('ambimax_import/email_report/enabled', true)
            ->setConfig('ambimax_import/email_report/to', $to)
            ->setConfig('ambimax_import/email_report/bcc', $bcc)
            ->setConfig('ambimax_import/email_report/subject', $subject)
            ->setConfig('ambimax_import/email_report/send_on_error_only', $sendOnErrorOnly);

        $this->assertTrue(
            Mage::getStoreConfigFlag('ambimax_import/email_report/enabled'),
            'Module not enabled'
        );

        $this->assertEquals(
            $sendOnErrorOnly,
            Mage::getStoreConfigFlag('ambimax_import/email_report/send_on_error_only'),
            'config send_on_error_only not set corredctly'
        );

        $logger = $this->getMockBuilder('Ho_Import_Helper_Log')
            ->setMethods(['getLogHtml'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->replaceByMock('helper', 'ho_import/log', $logger);

        $logger
            ->expects($this->any())
            ->method('getLogHtml')
            ->willReturn($getLogHtml);

//        $zendMail = $this->getMockBuilder('Zend_Mail')
//            ->setMethods(['addTo', 'addBcc', 'setSubject', 'setBodyText', 'send'])
//            ->getMock();

        /** @var Mage_Core_Model_Email $zendMail */
        $zendMail = $this->getMockBuilder('Mage_Core_Model_Email')
//            ->setMethods(['addTo', 'addBcc', 'setSubject', 'setBodyText', 'send'])
            ->setMethods(['addTo', 'addBcc', 'setSubject', 'setBodyText', 'send'])
            ->getMock();

        $this->replaceByMock('singleton', 'core/email', $zendMail);

        $import = $this->getMockBuilder('AvS_FastSimpleImport_Model_Import')
            ->setMethods(['getInvalidRowsCount'])
            ->getMock();

        $import->expects($this->once())
            ->method('getInvalidRowsCount')
            ->will($this->returnValue($getInvalidRowsCount));

        $zendMail
            ->expects($this->exactly(count($expect->getData('addTo'))))
            ->method('addTo')
            ->will($this->returnSelf());

        $zendMail
            ->expects($this->exactly(count($expect->getData('addBcc'))))
            ->method('addBcc')
            ->will($this->returnSelf());

        $zendMail
            ->expects($returnValue ? $this->once() : $this->never())
            ->method('setSubject')
            ->with($expect->getData('subject'))
            ->will($this->returnSelf());

        $zendMail
            ->expects($returnValue ? $this->once() : $this->never())
            ->method('setBodyText')
            ->with($getLogHtml)
            ->will($this->returnSelf());

        $zendMail
            ->expects($returnValue ? $this->once() : $this->never())
            ->method('send')
            ->with(null)
            ->will($this->returnSelf());

        /** @var Ambimax_Import_Helper_Mail $helper */
        $helper = Mage::helper('ambimax_import/mail');
        $helper->setMailInstance($zendMail);

        $transport = $this->getMockBuilder('Ho_Import_Model_Import_Transport')
            ->setMethods(['getData'])
            ->getMock();

        $transport->expects($this->once())
            ->method('getData')
            ->with('object')
            ->will($this->returnValue($import));

        $this->assertEquals(
            $getLogHtml,
            $helper->getImportLog(),
            'Import log is wrong'
        );

        $this->assertEquals(
            $returnValue,
            $helper->sendEmailReport($transport)
        );
    }

}