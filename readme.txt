=== Transactium Wallet for WooCommerce ===
Contributors: transactium
Donate link: http://transactium.com
Tags: transactium, form, forms, woocommerce, woo, commerce, ecommerce, wc, wallet, bitcoin, visa, iban, mastercard, payment, payments, stripe, paypal, authorize.net, credit cards, online payment, ecommerce, transact
Requires at least: 3.9
Tested up to: 4.9
Stable tag: 1.3
License: GPLv2 or later
WC requires at least: 2.4
WC tested up to: 3.4.5

Spark the most flexible eCommerce solution for WordPress, WooCommerce, and process payments externally via the Transactium Wallet!

== Description ==

Accept and Refund one-time secure and anonymous payments from your WordPress site with [Transactium Wallet](http://transactium.com) - no coding required.

More to add in future versions!


Current Features
 
* accept one-time secure and anonymous payments
* issue refunds with a click of a button
* transact with BitCoin, IBAN, VISA or MasterCard payment methods
* all payments are *PCI compliant*

Straight forward set-up. No coding required.

> **Transactium Wallet for WooCommerce integrates with _[WooCommerce](https://www.woocommerce.com)_ — the most popular WordPress platform for eCommerce - to allow customers to checkout using the Transactium Wallet.**
>
> **[Download](https://downloads.wordpress.org/plugin/transactium-wallet-for-woocommerce.zip)**

== Support ==

> **Problems? Require special customisations? [Contact Us](http://support.transactium.com/support/tickets/new)**

== Current Limitations ==

* Only one-time payments are supported

== Installation ==

This section describes how to install and setup the Transactium Wallet for WooCommerce. Be sure to follow *all* of the instructions in order for the Add-On to work properly. If you're unsure of any step, there are [screenshots](https://wordpress.org/plugins/transactium-wallet-for-woocommerce/screenshots/).

### Requirements

Requires at least WordPress 3.9, PHP 5.5 and _[WooCommerce](https://www.woocommerce.com)_ 3.

### Steps
 
1. Make sure you have your own copy of _[WooCommerce](https://www.woocommerce.com)_ set up and running.

2. You'll also need a [Transactium Wallet](http://support.transactium.com/support/tickets/new) account

3. Upload the plugin to your WordPress site. There are three ways to do this:

    * **WordPress dashboard search**

        - In your WordPress dashboard, go to the **Plugins** menu and click the _Add New_ button
        - Search for `Transactium Wallet for WooCommerce`
        - Click to install the plugin

    * **WordPress dashboard upload**

        - Download the plugin zip file by clicking the orange download button on this page
        - In your WordPress dashboard, go to the **Plugins** menu and click the _Add New_ button
        - Click the _Upload_ link
        - Click the _Choose File_ button to upload the zip file you just downloaded

    * **FTP upload**

        - Download the plugin zip file by clicking the orange download button on this page
        - Unzip the file you just downloaded
        - FTP in to your site
        - Upload the `transactium-wallet-for-woocommerce` folder to the `/wp-content/plugins/` directory

4. Visit the **Plugins** menu in your WordPress dashboard, find `Transactium Wallet for WooCommerce` in your plugin list, and click the _Activate_ link.

5. Visit the **WooCommerce->Settings** from the admin menu, select the Checkout tab and the inner _Transactium Wallet_ menu link respectively. Here input your Transactium Wallet account information. Save your settings.

6. Select the _General_ tab and set your desired currency. This will be the currency used for your product transactions.

7. On checkout, there should now be the Transactium Wallet payment method as an option.

Note: Only the default WooCommerce product types are supported, except for subscriptions.

If you need help, try checking the [screenshots](https://wordpress.org/plugins/transactium-wallet-for-woocommerce/screenshots/)

== Frequently Asked Questions ==

= Do I need to have my own copy of WooCommerce for this plugin to work? =
Yes, you need to install the [WooCommerce plugin](https://www.woocommerce.com/ "visit the WooCommerce website") for this plugin to work.

= Does this version work with the latest version of WooCommerce? =
This plugin was developed to target WooCommerce version 3 and later. It has not been tested on previous versions of WooCommerce.

= Your plugin just does not work =
Please contact [support](http://support.transactium.com/support/tickets/new).

== Screenshots ==

1. Activate Transactium Wallet for WooCommerce

2. Transactium Wallet settings page under **WooCommerce->Settings->Checkout->Transactium Wallet**.

3. Currency setting in **WooCommerce->Settings->General**

4. End Result on Checkout

== Changelog ==

= 1.0 (2017-04-03) =
* Initial release.

= 1.0.1 (2017-04-10) =
* Corrected release.

= 1.1 (2017-04-10) =
* Added support for WooCommerce 3.0 and later.

= 1.2 (2017-04-11) =
* Added backwards compatibility for WooCommerce 2.4 and up

= 1.3 (2018-09-17) =
* Added Return URL notice in admin options
* Fixed API URL field
* Fixed bug in Billing DOB day
* Added AddressLoaded payment state as Pending

== Upgrade Notice ==
= 1.0.1 =
* Fixed API URL handling. Please upgrade.
= 1.1 =
* Now supports WooCommerce 3.
= 1.2 =
* Plug-in was upgraded to support WooCommerce 2.4 and later.
= 1.3 =
* Bug fixes. Please upgrade.