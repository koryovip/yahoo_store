<?php
require_once('./setup.php');
session_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>无标题文档</title>
</head>

<body>
<?php
// session_start ();

require_once 'YConnect/Util/HttpClient.php';
require_once 'YConnect/Util/JWT.php';
require_once 'YConnect/Util/Logger.php';
require_once 'YConnect/Endpoint/TokenClient.php';
require_once 'YConnect/Endpoint/AuthorizationCodeClient.php';
require_once 'YConnect/YConnectClient.php';
require_once 'YConnect/Credential/ClientCredential.php';

require_once 'YConnect/Endpoint/ApiClient.php';
require_once 'YConnect/Endpoint/AuthorizationClient.php';

require_once 'YConnect/Endpoint/RefreshTokenClient.php';
require_once 'YConnect/Constant/GrantType.php';
require_once 'YConnect/Constant/OIDConnectDisplay.php';
require_once 'YConnect/Constant/OIDConnectPrompt.php';
require_once 'YConnect/Constant/OIDConnectScope.php';
require_once 'YConnect/Constant/ResponseType.php';
require_once 'YConnect/Credential/BearerToken.php';
require_once 'YConnect/Credential/ClientCredentialsClient.php';
require_once 'YConnect/Credential/IdToken.php';
require_once 'YConnect/Credential/RefreshToken.php';

require_once 'YConnect/Exception/ApiException.php';
require_once 'YConnect/Exception/AuthorizationException.php';
require_once 'YConnect/Exception/IdTokenException.php';
require_once 'YConnect/Exception/TokenException.php';
require_once 'YConnect/WebAPI/BillingAddressClient.php';
require_once 'YConnect/WebAPI/UserInfoClient.php';

use YConnect\Credential\ClientCredential;
use YConnect\YConnectClient;

if (true) {

    if (isset ($_SESSION ["access_token"])) {
        $access_token = $_SESSION ["access_token"];
    } else {
        $cred = new ClientCredential ($client_id, $client_secret);
        $client = new YConnectClient ($cred);

        try {
            // Authorization Codeを取得
            $code_result = $client->getAuthorizationCode($state);

            // print_r ($code_result);

            // Tokenエンドポイントにリクエスト
            $client->requestAccessToken($redirect_uri, $code_result);

            // アクセストークン, リフレッシュトークンを取得
            $access_token = $client->getAccessToken();

            $refresh_token = $client->getRefreshToken();

            // UserInfo APIにリクエスト
            $client->requestUserInfo($access_token);
            // 属性情報を取得。必要に応じて登録情報にプリセットしてください
            print_r($client->getUserInfo(), true);

            $_SESSION ["access_token"] = $access_token;
            $_SESSION ["refresh_token"] = $refresh_token;
        } catch (TokenException $e) {
            // 再度ログインして認可コードを発行してください
            print_r($e);
        } catch (Exception $e) {
            print_r($e);
        }
    }

    echo "<pre>";
    var_dump($_SESSION);
    echo "</pre>";
} else {
    $access_token = 'xxxxxxxxxxxxxxxxx';
}
$header = array(
    "Host: circus.shopping.yahooapis.jp",
    "Authorization: Bearer " . $access_token, /*アクセストークン*/
    'Content-Type: application/x-www-form-urlencoded'
);

// //////////////////////////////////////////////////////////////////////////////////
$url = "https://circus.shopping.yahooapis.jp/ShoppingWebService/V1/orderCount";
/*
$data = http_build_query ( array (
		'seller_Id' => "$G_SELLER_ID"
) );
*/
while ($msg = openssl_error_string()) {
    echo $msg;
}
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://circus.shopping.yahooapis.jp/ShoppingWebService/V1/orderCount?sellerId=$G_SELLER_ID");
// curl_setopt($ch, CURLOPT_PORT, 443);
// curl_setopt($ch, CURLOPT_SSLVERSION, 3);
curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_VERBOSE, 1);
curl_setopt($ch, CURLOPT_SSLCERT, './' . $G_SELLER_ID . '.crt');
curl_setopt($ch, CURLOPT_SSLKEY, './' . $G_SELLER_ID . '.key');
// curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
$result = curl_exec($ch);
var_dump(curl_error($ch));
echo "--------------------------------<br/>";
$ResultSet = new SimpleXMLElement ($result);
var_dump($ResultSet);
echo "<br/>--------------------------------<br/>";
echo '新規注文件数（注文詳細未読件数）：<a href="https://pro.store.yahoo.co.jp/pro.' . $G_SELLER_ID . '/order/manage/new_order" target="_blank">' . $ResultSet->Result->Count->NewOrder . '</a><br/>';
echo "入金待ち件数：" . $ResultSet->Result->Count->WaitPayment . "<br/>";
echo "出荷待ち件数：" . $ResultSet->Result->Count->WaitShipping . "<br/>";
echo "出荷処理中件数：" . $ResultSet->Result->Count->Shipping . "<br/>";
echo "注文完了待ち件数：" . $ResultSet->Result->Count->WaitDone . "<br/>";
curl_close($ch);
echo '<hr/>';


//　注文検索　/////////////////////////////////////////////////////////////////////////////////////////////////
$OrderTimeFrom = '20160501000000';
$OrderTimeTo = '20161008000000';
$aaaaa = <<<EOF
<?xml version="1.0" encoding="UTF-8" ?>
<Req>
	<SellerId>$G_SELLER_ID</SellerId>
	<Search>
		<Field>OrderId,OrderTime,LastUpdateTime,OrderStatus,IsSeen,ShipStatus,ShipPrefecture,ShipFirstName,ShipLastName</Field>
		<Result>20</Result>
		<Sort>-order_time</Sort>
		<Condition>
			<ShipStatus>0,1,2,3,4</ShipStatus>
			<OrderTimeFrom>$OrderTimeFrom</OrderTimeFrom>
			<OrderTimeTo>$OrderTimeTo</OrderTimeTo>
		</Condition>
	</Search>
</Req>
EOF;
$api = 'https://circus.shopping.yahooapis.jp/ShoppingWebService/V1/orderList';
/*
$data = http_build_query ( array (
    'param' => $aaaaa,
    'access_token' => $access_token
));
*/

$ch = curl_init($api);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_VERBOSE, 1);
curl_setopt($ch, CURLOPT_SSLCERT, './' . $G_SELLER_ID . '.crt');
curl_setopt($ch, CURLOPT_SSLKEY, './' . $G_SELLER_ID . '.key');
curl_setopt($ch, CURLOPT_POSTFIELDS, $aaaaa);
curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

$result = curl_exec($ch);
curl_close($ch);

$movies = new SimpleXMLElement ($result);
//echo "<pre>";var_dump($movies);echo "</pre>";
echo '<table border="1" cellspacing="0" cellpadding="0"><tr>';
echo '<th>ID</th>';
echo '<th>注文日時</th>';
echo '<th>注文ステータス</th>';
echo '<th>最終更新日時</th>';
echo '<th>都道府県</th>';
echo '<th>姓</th>';
echo '<th>名</th>';
echo '<tr>';
foreach ($movies->Search->OrderInfo as $result) {
    echo '<tr>';
    echo '<td><a href="https://pro.store.yahoo.co.jp/pro.' . $G_SELLER_ID . '/order/manage/detail/' . $result->OrderId . '" target=_blank>' . $result->OrderId . '</a></td>';
    echo '<td>' . $result->OrderTime . '</td>';
    echo '<td>' . getOrderStatusText($result->OrderStatus, $result->IsSeen, $result->ShipStatus) . '</td>';
    echo '<td>' . $result->LastUpdateTime . '</td>';
    echo '<td>' . $result->ShipPrefecture . '</td>';
    echo '<td>' . $result->ShipLastName . '</td>';
    echo '<td>' . $result->ShipFirstName . '</td>';
    echo '</tr>';
}
echo '<hr/>';

function getOrderStatusText($orderStatus, $IsSeen, $ShipStatus)
{
    if ('0' == $orderStatus) {
        return '未入力';
    }
    if ('1' == $orderStatus) {
        if ('false' == $IsSeen) {
            return '新規予約注文';
        }
        return '予約中';
    }
    if ('2' == $orderStatus) {
        if ('false' == $IsSeen) {
            return '新規注文';
        }
        if ('3' == $ShipStatus) {
            return '出荷完了';
        }
        return '処理中';
    }
    if ('3' == $orderStatus) {
        return '保留';
    }
    if ('4' == $orderStatus) {
        return 'キャンセル';
    }
    if ('5' == $orderStatus) {
        return '完了';
    }
    if ('8' == $orderStatus) {
        return '繰上げ同意待ち';
    }
    return '不明(' . $orderStatus . ')';
}

///////////////////////////////////////////////////////////////////////////////////////////////////
//　在庫　/////////////////////////////////////////////////////////////////////////////////////////////////

$api = 'https://circus.shopping.yahooapis.jp/ShoppingWebService/V1/getStock';

$data = http_build_query ( array (
    'seller_id' => $G_SELLER_ID,
    'item_code' => '160627-01:160627-01RH,160627-01:160627-01PH,160627-01:160627-01SM,160627-01:160627-01PL,160627-01:160627-01ST'
));

$ch = curl_init($api);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_VERBOSE, 1);
curl_setopt($ch, CURLOPT_SSLCERT, './' . $G_SELLER_ID . '.crt');
curl_setopt($ch, CURLOPT_SSLKEY, './' . $G_SELLER_ID . '.key');
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

$result = curl_exec($ch);
curl_close($ch);

$movies = new SimpleXMLElement ($result);
// echo "<pre>";var_dump($movies);echo "</pre>";
echo '<table border="1" cellspacing="0" cellpadding="0"><tr>';
echo '<th>商品コード</th>';
echo '<th>個別商品コード</th>';
echo '<th>在庫取得ステータス</th>';
echo '<th>在庫数</th>';
echo '<th>超過購入設定</th>';
echo '<th>商品反映フラグ</th>';
echo '<th>最終更新日時</th>';
echo '<tr>';
foreach ($movies->Result as $result) {
    echo '<tr>';
    echo '<td>' . $result->ItemCode . '</td>';
    echo '<td>' . $result->SubCode . '</td>';
    echo '<td>' . $result->Status . '</td>';
    echo '<td>' . $result->Quantity . '</td>';
    echo '<td>' . $result->AllowOverdraft . '</td>';
    echo '<td>' . $result->IsPublished . '</td>';
    echo '<td>' . $result->UpdateTime . '</td>';
    echo '</tr>';
}
echo '<hr/>';
///////////////////////////////////////////////////////////////////////////////////////////////////

$data = http_build_query(array(
    'sellerId' => "$G_SELLER_ID"
));
$options = array(
    'http' => array(
        'ignore_errors' => true,
        'method' => 'GET',
        'header' => implode("\r\n", $header)
    )
);
//$result = file_get_contents($url.'?'.$data, false, stream_context_create($options));
//$movies = new SimpleXMLElement($result);
//print_r($movies);
// //////////////////////////////////////////////////////////////////////////////////
$url = "https://circus.shopping.yahooapis.jp/ShoppingWebService/V1/libDirectoryList";
$data = http_build_query(array(
    'seller_id' => "$G_SELLER_ID"
));
$options = array(
    'http' => array(
        'ignore_errors' => true,
        'method' => 'GET',
        'header' => implode("\r\n", $header)
    )
);
$result = file_get_contents($url . '?' . $data, false, stream_context_create($options));
$movies = new SimpleXMLElement ($result);
// print_r($movies);
foreach ($movies->Result->Directory as $result) {
    echo $result . '<br/>';
}
echo '<hr/>';
// //////////////////////////////////////////////////////////////////////////////////
$url = "https://circus.shopping.yahooapis.jp/ShoppingWebService/V1/stCategoryList";
$data = http_build_query(array(
    'seller_id' => "$G_SELLER_ID",
    /*'page_key' => 'a5d8a5a2a5'*/
));
$options = array(
    'http' => array(
        'ignore_errors' => true,
        'method' => 'GET',
        'header' => implode("\r\n", $header)
    )
);
$result = file_get_contents($url . '?' . $data, false, stream_context_create($options));
$movies = new SimpleXMLElement ($result);
// print_r($movies);
foreach ($movies->Result as $result) {
    echo $result->Name . '(' . $result->PageKey . ')<br>';
}
echo '<hr/>';
// //////////////////////////////////////////////////////////////////////////////////
$url = "https://circus.shopping.yahooapis.jp/ShoppingWebService/V1/myItemList";
$data = http_build_query(array(
    'seller_id' => "$G_SELLER_ID", /*ストアアカウント*/
    'stcat_key' => 'bcfdc7bca5', /*kategori*/
    'stock' => 'true'
));
$options = array(
    'http' => array(
        'ignore_errors' => true,
        'method' => 'GET',
        'header' => implode("\r\n", $header)
    )
);
$result = file_get_contents($url . '?' . $data, false, stream_context_create($options));
$movies = new SimpleXMLElement ($result);
// print_r($movies);

echo '<table border="1" cellspacing="0" cellpadding="0"><tr>';
echo '<th>ID</th>';
echo '<th>Name</th>';
echo '<th>定価</th>';
echo '<th>通常販売価格</th>';
echo '<th>特価</th>';
echo '<th>在庫</th>';
echo '<th>商品画像</th>';
echo '<tr>';
foreach ($movies->Result as $result) {
    echo '<tr>';
    echo '<td><a href="http://store.shopping.yahoo.co.jp/' . $G_SELLER_ID . '/' . $result->ItemCode . '.html" target=_blank>' . $result->ItemCode . '</a></td>';
    echo '<td>' . $result->Name . '</td>';
    echo '<td>' . $result->OriginalPrice . '</td>';
    echo '<td>' . $result->Price . '</td>';
    echo '<td>' . $result->SalePrice . '</td>';
    echo '<td>' . $result->Quantity . '</td>';
    echo '<td><img height="100" src="http://item.shopping.c.yimg.jp/i/l/morizora_' . $result->ItemCode . '"></td>';
    echo '</tr>';
}
echo '</table><br/><hr/>';
// /////////////////////////////////////////////////////////////
$url = 'https://circus.shopping.yahooapis.jp/ShoppingWebService/V1/libImageList';
$data = http_build_query(array(
    // 'seller_id' => 'ogurakomu', /*ストアアカウント*/
    'seller_id' => "$G_SELLER_ID", /*ストアアカウント*/
    'start' => 1,
    'results' => 100
));

$options = array(
    'http' => array(
        'ignore_errors' => true,
        'method' => 'GET',
        'header' => implode("\r\n", $header)
    )
);
$result = file_get_contents($url . '?' . $data, false, stream_context_create($options));
// print_r($result);
$movies = new SimpleXMLElement ($result);
//var_dump($movies);
foreach ($movies->Result as $result) {
    //echo '<img src="' . $result->Url . '" /><br/><hr/>';
    echo $result->Url . '<br/>';
}
?>
</body>
</html>