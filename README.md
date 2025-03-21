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

### Ver. 1.1.1

1. Added from_address, to_address and transaction_hash
2. Removed `paymentKauAmount` and `paymentKagAmount` from createPayment request

### Ver. 2.0.0

1. Changed to support block-based checkout page
2. Changed to create a Pending status order before payment is processed
3. Removed unused cron job
4. Removed custom error page
5. Changed to cancel/process a pending order as per payment status in admin and inventory cron job
6. Changed to use the existing valid payment on placing order if there's one
7. Appended KPay payment ID to payment method on order received page, customer account order details page and admin order details page
8. Added replace_title_with_icon and hide_description settings
9. Changed to disable the gateway if selected currency is not supported
10. Some UI changes in frontend and admin
11. Enhanced error handling
12. Enhanced form data security check

### Ver. 2.1.0

1. Added `Test API Connection` feature to payment settings page
2. Renamed `Live Publishable Key` to `Access Token`, `Live Private Key` to `Secret Token`
3. Changed to disable the gateway if it fails to connect to API when saving payment settings

### Ver. 2.1.1

1. On the payment page, added an initial modal that connects to the KMS and evaluates if the Payment ID is `created` (showing payment instructions) or `accepted` (redirecting to the order-received page)
2. On the order-received page, added informative text when the Payment ID status is `accepted`
3. Added a setting to delete the Kinesis Payments database table upon plugin uninstall
4. Added a new WP-Cron job to sync payment statuses from the KMS, complementary to the existing on-demand sync when a customer accesses my-account/orders page
5. Deactivating or unistalling clears the scheduled WP-Cron jobs
6. UI changes in the frontend to use the KMS typography and color palette
7. Added hooks to prevent the 'pay' ability on orders with an `accepted` Payment Id
8. Added hooks to prevent the 'cancel' ability on orders with an `accepted` Payment Id

### Ver. 2.1.2

1. Fixed issue where orders not using Kinesis Pay were cancelled when the payment record was not created.
