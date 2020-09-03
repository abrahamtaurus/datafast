# datafast gateway
PaymentGateway
Creating a plugin for Datafast Payment Gateway

The wooshop-taurus-datafast.php file extends the WooCommerce payment gateway
The testpayresult.php file is called by the payment gateway provider after accepting the card details and the payment.

The function receipt_page($orderId)  calls the generate_taurusdatafast_form($order).
The generate_taurusdatafast_form($order) sends the request to the payment gateway and redirect the control to the testpayresult.php file 
