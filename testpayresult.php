<?php
global $woocommerce;

//$customer_country = WC()->customer->get_country();

//echo "Country Code: $customer_country";

$resourcePath  = $_GET["resourcePath"];
echo "<br /> Resource Path: " . $resourcePath;
$orderId  = $_GET["orderId"];
echo "<br /> Order Id: " . $orderId;

echo "<br />";
function check_transaction_status($presourcePath, $pentityId)
{
    $url = "https://test.oppwa.com" . $presourcePath;
    $url .= "?entityId=(8a829418533cf31d01533d06f2ee06fa";
    
    echo "<br />URL: " . $url;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization:Bearer OGE4Mjk0MTg1MzNjZjMxZDAxNTMzZDA2ZmQwNDA3NDh8WHQ3RjIyUUVOWA=='
    ));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    // this should be set to true in production
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $responseData = curl_exec($ch);

    if (curl_errno($ch)) {
        return curl_error($ch);
    }

    curl_close($ch);
    return $responseData;
}

$response = check_transaction_status($resourcePath, $entityId);

var_dump($response);

//wp_redirect('http://localhost/wp-content/plugins/woo-payment-gateway-for-taurus-datafast/test.html');