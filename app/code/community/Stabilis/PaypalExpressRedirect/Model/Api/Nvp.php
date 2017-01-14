<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Stabilis to newer
 * versions in the future. If you wish to customize Stabilis for your
 * needs please do so within the local code pool.
 *
 * @category    Stabilis
 * @package     Stabilis_PaypalExpressRedirect
 * @copyright  Copyright (c) 2007-2016 Luke A. Leber (https://www.thinklikeamage.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * This override has been put in place in order to ensure that the Magento 
 * instance correctly handles errors that are generated by the Express Checkout 
 * NVP gateway.  Some errors require that the user be redirected back to the 
 * PayPal site with the same token as the request that failed.  This will allow 
 * the customer to recover from the funding failure.
 * 
 * The following error conditions fall into this category:
 * 
 * Error 10736 - PayPal has determined that the shipping address does not exist
 * Error 10417 - Customer must choose another funding source from their wallet
 * Error 10422 - Customer must choose new funding sources
 * Error 10485 - Payment has not been authorized by the user
 * Error 10486 - The transaction could not be completed (for an unknown reason)
 * 
 * The following errors should also be redirected back to PayPal (after fixing 
 * something on our end):
 * 
 * Error 10411 - The Express Checkout session has expired
 * Error 10412 - Payment has already been made for this InvoiceID
 * 
 * The following errors *can* be redirected to PayPal, but it might not work:
 * 
 * Error 10445 - This is an internal PayPal error
 * 
 * @category   Stabilis
 * @package    Stabilis_PaypalExpressRedirect
 */
class Stabilis_PaypalExpressRedirect_Model_Api_Nvp extends Mage_Paypal_Model_Api_Nvp {

    /** @var string[] The array of error codes to be handled specially */
    protected static $_redirectErrors = array(
        '10417', 
        '10422', 
        '10485', 
        '10486', 
        '10736'
    );
    
    /**
     * Extends the functionality of the parent method by setting a redirect to 
     * PayPal in the event of certain error conditions.
     * 
     * @param array $response
     * 
     * @throws Exception if an error exists within the response
     */
    protected function _handleCallErrors($response) {
        try {

            /// Let the default functionality take its course
            parent::_handleCallErrors($response);

        } catch (Exception $ex) {

            /// Check if there is a single error code that is within our list
            if (count($this->_callErrors) == 1 && 
                    in_array($this->_callErrors[0], static::$_redirectErrors)) {

                /// Redirect the user back to PayPal (with the same Express Checkout token)
                Mage::app()->getFrontController()->getResponse()
                        ->setRedirect(Mage::getUrl('paypal/express/edit'))
                        ->sendResponse();
            }

            /// Rethrow the exception
            throw $ex;
        }
    }
}