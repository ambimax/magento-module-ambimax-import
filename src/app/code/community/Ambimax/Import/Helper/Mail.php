<?php

class Ambimax_Import_Helper_Mail extends Mage_Core_Helper_Abstract
{
    protected $_mailInstance;
    protected $_transport;

    /**
     * @param $transport Ho_Import_Model_Import_Transport
     * @return bool
     * @throws Exception
     */
    public function sendEmailReport($transport)
    {
        try {
            $this->log('Test');
        if ( !Mage::getStoreConfigFlag('ambimax_import/email_report/enabled') ) {
            return false;
        }

        $toEmails = $this->getEmailFromConfig('ambimax_import/email_report/to');
        if ( empty($toEmails) ) {
            throw new InvalidArgumentException('No email addresses defined');
        }

        /** @var AvS_FastSimpleImport_Model_Import $import */
        $import = $transport->getData('object');
        if ( !$import instanceof AvS_FastSimpleImport_Model_Import ) {
            throw new Exception('Wrong import model');
        }

        $hasErrors = $import->getInvalidRowsCount();
        if ( !$hasErrors && Mage::getStoreConfigFlag('ambimax_import/email_report/send_on_error_only') ) {
            $this->log('Successful import, no email sent');
            return false;
        }

        $this->log('Errors detected');
        $mail = $this->getMailInstance();
        $mail->setFrom(
            Mage::getStoreConfig('trans_email/ident_general/email'),
            Mage::getStoreConfig('trans_email/ident_general/name')
        );

        foreach ($toEmails as $email) {
            $mail->addTo($email);
        }

        $bccEmails = $this->getEmailFromConfig('ambimax_import/email_report/bcc');
        foreach ($bccEmails as $email) {
            $mail->addBcc($email);
        }

//        $subject = Mage::getStoreConfig('ambimax_import/email_report/subject');
//        if ( strpos($subject, '%s') !== false ) {
//            $subject = sprintf($subject, $import->getErrorsCount() ? 'error' : 'sucess');
//        }

            $this->log('more Errors detected');
//            $body = sprintf("Profile %s:", $this->getProfile());
//        if ( $hasErrors ) {
//            $subject = $this->__('%s: Import failed!', $this->getProfile());
//            $body .= $this->__('Errors found')
//        } else {
//            $subject = $this->__('Import of %s was successful', $this->getProfile());
//            $body .= $this->__('Import was successful')
//        }
//
//        $body .= PHP_EOL . PHP_EOL . $this->getImportLog();

        $subject = 'Subject';
        $body = 'Body';
        $mail->setSubject($subject);
        $mail->setBodyText($body);
        $this->log('even more Errors detected');
        $this->log(
            [
                'from'         => $mail->getFrom(),
                'recipients'   => $mail->getRecipients(),
                'subject'      => $subject,
                'body'         => $this->getImportLog(),
                'transport'    => get_class($this->getTransport()),
                'invalidRows'  => $import->getInvalidRowsCount(),
                'errors'       => $import->getErrors(),
                'errors_count' => $import->getErrorsCount(),
                'error_msg'    => $import->getErrorMessage(),
                'error_msgs'   => $import->getErrorMessages(),
                'import_data'  => $import->getData(),
//                'profile'      => $this->getProfile(),
            ]
        );

//        $mail->send($this->getTransport());

        } catch(Exception $e) {
            Mage::logException($e);
        }
        return true;
    }

    /**
     * Loads config value and returns a proper formatted array with email addresses
     *
     * @param $configPath
     * @return array
     */
    public function getEmailFromConfig($configPath)
    {
        $return = array();
        $config = str_replace(
            ['\r\n', '\r', '\n'],
            PHP_EOL,
            Mage::getStoreConfig($configPath)
        );

        $lines = explode(PHP_EOL, $config);
        $lines = array_map('trim', $lines);
        foreach ($lines as $email) {
            if ( !empty($email) ) {
                $return[] = $email;
            }
        }
        return array_unique($return);
    }

    /**
     * @return string
     */
    public function getImportLog()
    {
        return Mage::helper('ho_import/log')->getLogHtml();
    }

    public function getProfile()
    {
        if ( !$this->getData('_profile') ) {
            $importLog = implode(PHP_EOL, $this->getImportLog());
            preg_match('/(Fieldmapping\s(.*)\swith|Profile\s(.*)\sdone)/', $importLog, $matches);
            $this->log($matches);
//            if ( !isset($matches[0]) ) {
//                return '';
//            }
//            $this->setData('_profile', $matches[0]);
        }
        return $this->getData('_profile');
    }

    /**
     * @return Zend_Mail
     */
    public function getMailInstance()
    {
        if ( !$this->_mailInstance ) {
            $this->_mailInstance = new Zend_Mail(); // Mage::getSingleton('core/email'); //new Zend_Mail();
        }
        return $this->_mailInstance;
    }

    /**
     * @param Zend_Mail $instance
     * @return $this
     */
    public function setMailInstance(Zend_Mail $instance)
    {
        $this->_mailInstance = $instance;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTransport()
    {
        if ( !$this->_transport ) {
            if ( Mage::helper('core')->isModuleEnabled('Aschroder_SMTPPro') ) {
                $this->_transport = Mage::helper('smtppro')->getTransport();
            }
        }

        return $this->_transport;
    }

    /**
     * @param $transport
     * @return $this
     */
    public function setTransport($transport)
    {
        $this->_transport = $transport;
        return $this;
    }

    /**
     * Log wrapper
     *
     * @param string $message
     * @param int $level
     * @param string $file
     */
    public function log($message = '', $level = Zend_Log::INFO, $file = 'sendEmailReport.log')
    {
        return Mage::log($message, $level, $file);
    }
}