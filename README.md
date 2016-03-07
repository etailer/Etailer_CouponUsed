# Etailer_CouponUsed

Core Magento 1 functionality doesn't count a Coupon Code as used if the order `discount_amount` is _$0_.
Coupons like _"Free Shipping"_ often will have a _$0_ `discount_amount` and therefore not marked as used.

This is especially problematic if unique coupons have been generated on the assumption they can be used a finite number of times.

This module includes a fix for this behavior and a script to process previously placed orders that will not has been processed.

I strongly suggest you read the rest of this document to understand what this script does, test the output and backup before running on production data.

No warranty is included and there is no reversing the process.


## The issue

Core module `Mage_SalesRule` registers a global observer to run after each sales order is placed.

This observer increments the `times_used` counter on coupon codes, this is used for enforcing limits.

The code to increment `times_used` is inside the following if statement:

```php

if ($order->getDiscountAmount() != 0) {
    // snip
}

```

This means that if the order `discount_amount` is 0 no processing happens.

Source: https://github.com/OpenMage/magento-mirror/blob/magento-1.9/app/code/core/Mage/SalesRule/Model/Observer.php#L72-L133

### Free Shipping coupons

Typically a Sales Rule set up to allow free shipping with no other discount will be set up as below:



One or many coupons are then created, a typical use case is multiple coupons valid for a single use per coupon.

Because of the logic around incrementing `times_used` when these coupons are used, if no other discounts apply, then the coupon will not be marked as used.

The Coupon Codes will however provide the correct "Free Shipping discount" and continue to work for later transactions. This means the merchant is discounting more than they intended with the free shipping coupon.

## This Module

This module provides:

* A global `sales_order_place_after` observer that processes coupons according to the core logic only if `$order->getDiscountAmount() == 0`.
* It does not rewrite or otherwise prevent the core observer doing it's own processing if `$order->getDiscountAmount() != 0`.
* A Magento "shell" script to re-process past orders where the `discount_amount` is `0` and a `coupon_code` has been used.

I've elected not to overwrite or patch core functionality so that processing remains as consistent is possible. This means the core observer processes orders with a `discount_amount` and this module's observer processes orders with no `discount_amount`.

I have not refactored from core or split out any of the logic in my observer into helpers. This means the shell script, which uses the same logic, has duplication of code. This is intentional so that the code is easier to audit and compare with core.


### Usage

* The `sales_order_place_after` observer has no configuration. Once this module is installed and activated it'll process when new orders are placed.

* The shell script is usable in the same ways other Magento scripts:

```shell
$ cd /path/to/magento
$ php ./shell/couponused.php
Usage: php couponused.php -- [options]

  --mark-used    Increments coupon used counters for orders with no discount
  help           This help
```

The shell script does not have any dry-run option.
It does not store any state. It's designed to be a single use one-time process.

It creates an order collection where order `discount_amount` is `0` and order `coupon_code` is not `NULL`.

It then loads 100 orders at a time for processing, it increments the coupon `times_used` counters for each order found.

It prints out all the `increment_id` for all orders processed and the total number of orders processed.

This script is self contained can be used without the rest of the module by placing it in your Magento `./shell` directory.

**Warning:** It is possible to run this script multiple times. It will continue to increment because it doesn't store any state.

It will also apply to orders processed after this module's observer is installed.


# References

http://magento.stackexchange.com/questions/33598/magento-coupons-limit-per-use-o-per-customer-not-working
