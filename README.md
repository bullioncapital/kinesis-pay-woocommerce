# Kinesis Pay - WooCommerce Plugin

## Installation

1. Download `kinesis-pay-woocommerce.zip` file from https://github.com/bullioncapital/kinesis-pay-woocommerce/releases
2. Login to Wordpress admin and go to Plugins page
3. Click `Add new` button
4. Click `Upload plugin` button
5. Choose the `kinesis-pay-woocommerce.zip` file
6. Click `Install Now` button, and choose `Replace current with uploaded` if the option shows up
7. Done.

## Change log

### Ver. 1.0.0

1. First version released

### Ver. 1.0.1

1. Fixed bug with floating point number moving to accepting strings on client and server
2. Removed Test Mode from Settings

### Ver. 1.0.2

1. Fixed shipping address issue
2. Updated Kinesis Pay UI

### Ver. 1.0.3

1. Removed thousands separator from amounts
2. Updated fetching rates api call as per api changes
3. Changed to use bid price instead of ask

### Ver. 1.0.4

1. Replace QR code generator

### Ver. 1.1.0

1. Removed `Kinesis-Pay Payment Status` column from order list view in admin
2. Added new fields to Kinesis Pay table
3. Added `Payment Details - Kinesis Pay` card with payment details to order details view in admin
4. Added `Show Payment Log` to settings, and `Payment log` to order details view
5. Changed `Payment status` to indicate payment confirmation
6. Removed `Close` button from QR code popup
7. Changed to support Wordpress multisite
8. Fixed compatibility issues with HPOS
