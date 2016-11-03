Introduction
===============

Creditfield is a small proof of concept module that provides 3 form elements to be used in the Drupal FormAPI for custom forms:

- Credit Card Number
- Credit Card Expiration Date
- Credit Card Code

These fields provide basic validation and form errors on their own (including Luhn check against card number). 
You could easily add more within your own form validation callbacks as well.

Why would I use this module?
============================

If you're a developer who implements custom payment forms such as a billpay or invoice payment, you may find this useful. With the predefined credit card form 
field types, all the basic validation is written, plus a expiration date field, so you don't have to duplicate code in multiple payment forms.

If you're not a developer or looking for a more robust solution, you may want to investigate Pay (http://drupal.org/project/pay).

How to Use
===============

Within your custom form code, you can call the form field types like so:

$form['credit_card_number'] = array(
  '#type' => 'creditfield_cardnumber',
  '#title' => 'Credit Card Number',
  '#maxlength' => 16,
);
    
$form['expiration_date'] = array(
  '#type' => 'creditfield_expiration',
  '#title' => 'Expiration Date',
);

$form['credit_card_cvv'] = array(
  '#type' => 'creditfield_cardcode',
  '#title' => 'CVV Code',
  '#maxlength' => 4,
  '#description' => 'Your 3 or 4 digit security code on the back of your card.',
);

This will display simple form fields that contain validation code checks for payment information.

Currently, the following validation is performed:

- Valid card number (using Luhn algorithm to check card length)
- Valid Expiration Date

What this module doesn't do
===========================

This module does -not- handle submitting payment data to payment processors. It also does not validate against specific card types (Visa, MC, etc). That 
part is entirely up to you.

Warnings
===============

You should never ever store credit card data in a database, cache or watchdog. This will invalidate any PCI compliance you might have.

If you feel like you need the ability to store data, you may want to look into something such as Authorize.net CIM integration, which will store it on
the payment gateway side, instead of Drupal.