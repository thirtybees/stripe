# Stripe
![](https://travis-ci.org/thirtybees/stripe.svg?branch=master)  
![Stripe](views/img/stripebtnlogo.png)

Accept Payments with Stripe in Thirty Bees.

## Features
### Mission
The aim of this module is to make accepting payments with Stripe very easy.  
Contributions are more than welcome!

### Current features
- Process the following payment methods with Stripe: 
  - Credit Card
  - Credit Card (3D Secure)
  - Apple Pay
  - Alipay
  - Bitcoin (USD only)
  - iDEAL
  - Bancontact
  - Giropay
  - Sofort Banking
- Process refunds received by webhooks:
    - Partial refund
    - Full refund
    - Generate credit slip
- Refund from Back Office Order page
    - Partial refund
    - Full refund
- View transactions on Back Office Order Page
- View all transactions on the module configuration page
- Uses Stripe's Checkout form to stay up to date with the latest version
- Supports the Advanced checkout page of the Advanced EU Compliance module

### Roadmap
The issues page will give you a good overview of the current roadmap and priorities:
https://github.com/thirtybees/stripe/issues

## Installation
### Module installation
- Upload the module via your Thirty Bees Back Office
- Install the module
- Check if there are any errors and correct them if necessary
- Profit!

## Documentation
The wiki can be found here: https://github.com/thirtybees/stripe/wiki

## Minimum requirements
- Thirty Bees `>= 1.0.0`
- PHP `>= 5.5.0`
- `TLSv1.2` connectivity (`cURL` or `fsockopen`)

### Compatibility
- Thirty Bees `1.0.x`

## License
Academic Free License 3.0
