<?php
/**
 * Mark8t VanillaJsConnect
 *
 * Adds jsconnect support for magento so you can login onto vanilla forums with your magento account
 *
 * @author Bastian Ike, modifications by Adrian Speyer
 * @license http://creativecommons.org/licenses/by/3.0/ CC-BY 3.0
 */
class Mark8t_VanillaJsConnect_IndexController extends Mage_Core_Controller_Front_Action
{
    /**
     * index action, checks for parameters and then returns json-encoded data
     */
    public function indexAction() {
        $clientId = Mage::getStoreConfig('vanillajsconnect/general/client_id');
        $clientSecret = Mage::getStoreConfig('vanillajsconnect/general/client_secret');

        /**
         * @var Mark8t_VanillaJsConnect_Model_Jsconnect $model
         */
        $model = Mage::getModel('vanillajsconnect/jsconnect');

        $result = array();

        // checking input parameters
        /**
         * @todo inplement input validation model?
         */

        if (!strlen($clientId) || !strlen($clientSecret)) {
            $result = array(
                'error' => 'invalid_request',
                'message' => 'Please set up VanillaJsConnect before using it!'
            );
        } else if (!$this->getRequest()->getParam('client_id')) {
            //no client_id
            $result = array(
                'error' => 'invalid_request',
                'message' => 'The client_id parameter is missing.'
            );
        } else if ($this->getRequest()->getParam('client_id') != $clientId) {
            // wrong client_id
            $result = array(
                'error' => 'invalid_request',
                'message' => 'unknown client_id'
            );
        } else if ((!$this->getRequest()->getParam('timestamp') || !is_numeric($this->getRequest()->getParam('timestamp'))) && 0){
            // no timestamp
            $result = array(
                'error' => 'invalid_request',
                'message' => 'The timestamp parameter is missing or invalid.'
            );
        } else if (!$this->getRequest('signature')) {
            // no signature
            $result = array(
                'error' => 'invalid_request',
                'message' => 'Missing  signature parameter.'
            );
        } else if (abs($this->getRequest()->getParam('timestamp') - time()) > (24 * 60) && 0) {
            // old timestamp
            $result = array(
                'error' => 'invalid_request',
                'message' => 'The timestamp is invalid.'
            );
        } else if (($model->signature($this->getRequest()->getParam('timestamp') . $clientSecret) != $this->getRequest()->getParam('signature')) && 0) {
            // wrong signature
            $result = array(
                'error' => 'access_denied',
                'message' => 'Signature invalid.'
            );
        }

        if(!isset($result['error'])) {
            $user = $model->buildUserArray();
            if(count($user)) {
                $result = $model->buildSignedResult($user, $clientId, $clientSecret);
            } else {
                $result = array('name' => '', 'photourl' => '');
            }
        }

        $result = json_encode($result);

        if ($this->getRequest()->getParam('callback')) {
            // put into callback call
            $result = $this->getRequest()->getParam('callback') . '(' . $result . ');';
        }

        $this->getResponse()->setBody($result);
    }

    public function jscontrolAction() {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('vanillajsconnect/jscontrol')
                ->setClientId(Mage::getStoreConfig('vanillajsconnect/general/client_id'))
                ->toHtml()
        );
    }
}
