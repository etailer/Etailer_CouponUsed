<?php

class Etailer_CouponUsed_Model_Observer
{

    /**
     * Registered callback: called after an order is placed
     *
     * @param Varien_Event_Observer $observer
     */
    public function sales_order_afterPlace(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        if (!$order) {
            return $this;
        }

        // lookup rule ids
        $ruleIds = explode(',', $order->getAppliedRuleIds());
        $ruleIds = array_unique($ruleIds);

        $ruleCustomer = null;
        $customerId = $order->getCustomerId();

        // only process if DiscountAmount is 0 as there is a core observer in Mage_SalesRule_Observer runs on >0 discounts.
        // use each rule (and apply to customer, if applicable)
        if ($order->getDiscountAmount() == 0) {
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
    }

}

/**
 *  sales_order_afterPlace function dereived from Magento CE 1.9.2.3 - original license below
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
