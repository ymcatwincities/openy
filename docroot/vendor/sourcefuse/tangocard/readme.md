### Tango Card SDK 


TangoCard RAAS PHP SDK

This repository contains the open source PHP SDK that allows you to access TangoCard RAAS from your PHP app. Except as otherwise noted,
The SDK is licensed under the MIT License (MIT).
Refer to tango card Raas API for actual response and requests. https://github.com/tangocarddev/RaaS

Usage
-----

Create a new instance of tango card

    $tangocard = new TangoCard('PLATFORM_ID','PLATFORM_KEY');
    'PLATFORM_ID' & 'PLATFORM_KEY' will be provided by TangoCard
    $tangocard->setAppMode("sandbox"); //By default app mode is production. Change it to sandbox if needed. Skip      //this line in production

Valid Values : "production", "sandbox"

Raas API Calls:
All Raas api calls return a stdobject
1) Create an Account 

    $tangoCard->createAccount($customer, $accountIdentifier, $email);
Return values:

        {
          "success": true,
          "account": {
            "identifier": "$accountIdentifier",
            "email": "$email",
            "customer": "$customer",
            "available_balance": 0
          }
        }

2) Get Account Information 

    $tangoCard->getAccountInfo($customer, $accountId);
Response: Customer details (same as create account)

3) Register Credit Card

    $tangoCard->registerCreditCard($customer, $accountIdentifier, $ccNumber, $securityCode, $expiration, $fName, $lName, $address, $city, $state, $zip, $country, $email, $clientIp);
              
Response:

    {
    "success": true,
    "cc_token": "27739887",
    "active_date": 1418380099
    }

Store the cc_token. If lost the credit card cannot be billed
4) Fund an Account 

    $tangoCard->fundAccount($customer, $accountIdentifier, $amount, $ccToken, $securityCode, $clientIp);

5) Delete a Credit Card

    $tangoCard->deleteCreditCard($customer, $accountIdentifier, $ccToken);

5) Get a List of Rewards 

    $tangoCard->listRewards();

5) Place an Order 

    $tangoCard->placeOrder($customer, $accountIdentifier, $campaign, $rewardFrom, $rewardSubject, $rewardMessage,  $sku, $amount, $recipientName, $recipientEmail, $sendReward);

6) Get Order Information 

    $tangoCard->getOrderInfo($orderId);

7) Get Order History 

    $tangoCard->getOrderHistory($customer, $accountId, $offset, $limit, $startDate, $endDate);
