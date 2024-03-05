# Stripe
![Stripe](logo.png)

Accept Payments with Stripe in Thirty Bees.

## Browser support

| [<img src="https://raw.githubusercontent.com/godban/browsers-support-badges/master/src/images/edge.png" alt="IE / Edge" width="16px" height="16px" />](http://godban.github.io/browsers-support-badges/)</br>IE / Edge | [<img src="https://raw.githubusercontent.com/godban/browsers-support-badges/master/src/images/firefox.png" alt="Firefox" width="16px" height="16px" />](http://godban.github.io/browsers-support-badges/)</br>Firefox | [<img src="https://raw.githubusercontent.com/godban/browsers-support-badges/master/src/images/chrome.png" alt="Chrome" width="16px" height="16px" />](http://godban.github.io/browsers-support-badges/)</br>Chrome | [<img src="https://raw.githubusercontent.com/godban/browsers-support-badges/master/src/images/safari.png" alt="Safari" width="16px" height="16px" />](http://godban.github.io/browsers-support-badges/)</br>Safari | [<img src="https://raw.githubusercontent.com/godban/browsers-support-badges/master/src/images/opera.png" alt="Opera" width="16px" height="16px" />](http://godban.github.io/browsers-support-badges/)</br>Opera | [<img src="https://raw.githubusercontent.com/godban/browsers-support-badges/master/src/images/safari-ios.png" alt="iOS Safari" width="16px" height="16px" />](http://godban.github.io/browsers-support-badges/)</br>iOS Safari | [<img src="https://raw.githubusercontent.com/godban/browsers-support-badges/master/src/images/chrome-android.png" alt="Chrome for Android" width="16px" height="16px" />](http://godban.github.io/browsers-support-badges/)</br>Chrome for Android |
| --------- | --------- | --------- | --------- | --------- | --------- | --------- |
| IE9, IE10, IE11, Edge| 30+ | 30+ | 9+ | 36+ | 9+ | 30+ |

Browserlist string: <code>[defaults, ie >= 9, ie_mob >= 10, edge >= 12, chrome >= 30, chromeandroid >= 30, android >= 4.4, ff >= 30, safari >= 9, ios >= 9, opera >= 36](http://browserl.ist/?q=defaults%2C+ie+%3E%3D+9%2C+ie_mob+%3E%3D+10%2C+edge+%3E%3D+12%2C+chrome+%3E%3D+30%2C+chromeandroid+%3E%3D+30%2C+android+%3E%3D+4.4%2C+ff+%3E%3D+30%2C+safari+%3E%3D+9%2C+ios+%3E%3D+9%2C+opera+%3E%3D+36)</code>

## Features
### Mission
The aim of this module is to make accepting payments with Stripe very easy.  
Contributions are more than welcome!

### Current features
- Process the following payment methods with Stripe: 
  - Credit Card
  - Apple Pay (PaymentRequest button)
  - Google Pay (PaymentRequest button)
  - iDEAL
  - Bancontact
  - Giropay
  - Sofort Banking
  - P24
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
- PHP `>= 7.4.0`

### Compatibility
- Thirty Bees `1.0.x`

## License
Academic Free License 3.0
