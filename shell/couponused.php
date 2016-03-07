<?php

require_once 'abstract.php';

class Etailer_Shell_CouponUsed extends Mage_Shell_Abstract
{

    /**
     * Run the command
     *
     * @return Etailer_Shell_CouponUsed
     */
    public function run()
    {

        // mark-used command
        if ($this->getArg('mark-used')) {
            echo "\n";
            return $this->_markUsed();
        }

        // if nothing called, just do the help
        echo $this->usageHelp();

        return $this;
    }

    /**
     * Find all coupons used with $0 discount orders for processing
     *
     * @return void
     */
    protected function _markUsed()
    {

        $processedOrders = 0;

        $orderCollection = Mage::getModel('sales/order')
          ->getCollection()
          ->addFieldToSelect(array('discount_amount', 'coupon_code', 'increment_id'))
          ->addAttributeToFilter('discount_amount', 0)
          ->addAttributeToFilter('coupon_code', array('notnull' => true))
          ->setPageSize(100);

        $i = 1;
        while ($i <= $orderCollection->getLastPageNumber()) {
            $orderCollection->setCurPage($i);
            $orderCollection->load();
            foreach ($orderCollection as $order) {
                echo "Processing Order: " . $order->getIncrementId() . "\n";
                $this->_processOrder($order);
                $processedOrders++;
            }

            $i++;
            $orderCollection->clear();
        }
        echo "Processed Coupon Codes on " . $processedOrders . " Orders\n";

    }

    /**
     * Increment sales rule usage counter for an order
     *
     * @return void
     */
    protected function _processOrder(Mage_Sales_Model_Order $order) {
        $ruleIds = explode(',', $order->getAppliedRuleIds());
        $ruleIds = array_unique($ruleIds);

        $ruleCustomer = null;
        $customerId = $order->getCustomerId();

        foreach ($ruleIds as $ruleId) {
            if (!$ruleId) {
                continue;
            }
            $rule = Mage::getModel('salesrule/rule');
            $rule->load($ruleId);
            if ($rule->getId()) {
                $rule->setTimesUsed($rule->getTimesUsed() + 1);
                $rule->save();

                if ($customerId) {
                    $ruleCustomer = Mage::getModel('salesrule/rule_customer');
                    $ruleCustomer->loadByCustomerRule($customerId, $ruleId);

                    if ($ruleCustomer->getId()) {
                        $ruleCustomer->setTimesUsed($ruleCustomer->getTimesUsed() + 1);
                    }
                    else {
                        $ruleCustomer
                        ->setCustomerId($customerId)
                        ->setRuleId($ruleId)
                        ->setTimesUsed(1);
                    }
                    $ruleCustomer->save();
                }
            }
        }
        $coupon = Mage::getModel('salesrule/coupon');
        /** @var Mage_SalesRule_Model_Coupon */
        $coupon->load($order->getCouponCode(), 'code');
        if ($coupon->getId()) {
            $coupon->setTimesUsed($coupon->getTimesUsed() + 1);
            $coupon->save();
            if ($customerId) {
                $couponUsage = Mage::getResourceModel('salesrule/coupon_usage');
                $couponUsage->updateCustomerCouponTimesUsed($customerId, $coupon->getId());
            }
        }
    }

    /**
     * Retrieve Usage Help Message
     *
     * @return string
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php couponused.php -- [options]

  --mark-used    Increments coupon used counters for orders with no discount
  help           This help

USAGE;
    }
}

// run the shell script
$shell = new Etailer_Shell_CouponUsed();
$shell->run();

 /**
  *  _processOrder function dereived from Magento CE 1.9.2.3 - original license below
  *  original file: app/code/core/Mage/SalesRule/Model/Observer.php
  *  original class: Mage_SalesRule_Model_Observer original function: sales_order_afterPlace
  */

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
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category    Mage
 * @package     Mage_SalesRule
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
