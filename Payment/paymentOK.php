<?php 
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Europe/Istanbul');
setlocale(LC_ALL, "tr_TR");
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
define("userID",'657');  // EFMusic User

require_once dirname(dirname(__FILE__)) . '/BL/Tables/sales.php';
require_once dirname(dirname(__FILE__)) . '/BL/Tables/albums.php';
require_once dirname(dirname(__FILE__)) . '/BL/Tables/customerContracts.php';
require_once dirname(dirname(__FILE__)) . '/BL/communication.php';

$payerID = isset($_POST["orderid"]) ? $_POST["orderid"] : "";
$xid = isset($_POST["xid"]) ? $_POST["xid"] : "";
$response = isset($_POST["response"]) ? $_POST["response"] : "";
$success=0;
$albumID=0;
$channelID = 0;
$upgradeContractID =0;

if ($payerID!="" && strlen($payerID)>5 && $response=="Approved") {
    $success=1;
    $sale = sales::getSalesFromPayerID($payerID);
    $contentID=$sale->contentID;
    $upgradeContractID=$sale->upgradeContractID;
    $sale->xid=$xid;
    $sale->completed=1;
    $sale->save();

    if ($contentID>0) {
        $album = albums::getAlbumByContentID($contentID);
        $albumID=$album->ID;
    }
    if ($upgradeContractID>0) {
        $customerC = customerContracts::getCustomerContract($sale->customerID);
        $customerC->endDate=date("Y-m-d");
        $customerC->endBy=userID;
        $customerC->endDescription="Upgrade";
        $customerC->save();

        $customerC->delete();

        $cc = new customerContracts();
        $cc->customerID=$sale->customerID;
        $cc->contractID=$sale->upgradeContractID;
        $cc->termDate=date("Y-m-d");
        $cc->term=1;
        $cc->isSent=1;
        $cc->userID=userID;
        $cc->contractApprovalDate=date("Y-m-d");
        $cc->dealTermID=46;
        $cc->save();
    }

    $channelID=$sale->channelID;
    $mailBody = "#".$albumID." Album icin odeme gelmistir.";
    $m = new Mail("info", "info@distronaut.com", 5, "Distronaut Odeme",$mailBody);
    $m->sendQueue();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Ok</title>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"
        integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/all.min.css"
        integrity="sha512-HK5fgLBL+xu6dm/Ii3z4xhlSUyZgTT9tuc/hSrtw6uzJOvgRr2a9jyxxT1ely+B+xFAmJKVSTbpM/CuL7qxO8w=="
        crossorigin="anonymous" />

    <style>
        body {
            text-align: center;
            background-color: #F5F5F5;
        }

        .container {
            height: 100vh;
            background-color: #FFFFFF;
        }

        .row {
            -webkit-box-align: center;
            -webkit-align-items: center;
            -ms-flex-align: center;
            align-items: center;
            height: 100%;
            -webkit-box-shadow: 0 0px 10px rgb(85 85 85 / 20%) !important;
            box-shadow: 0 0px 10px rgb(85 85 85 / 20%) !important;
        }

        #success-icon {
            font-size: 5em;
            color: #218838;
        }

        #success-text {
            font-size: 2em;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col">
                <div id="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div id="success-text">
                    <p>Ödemeniz başarıyla gerçekleşti!</p>
                    <p>Yönlendiriliyorsunuz...</p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
<?php } else { 
echo "Hata olustu!";   
}
?>
<script>
setTimeout(function(){
    var success = <?php echo $success;?>;
    var channelID = <?php echo $channelID;?>;
        if (success==1) {
            if ($contentID>0) {
                var albumID=<?php echo $albumID;?>;
                if (channelID==1) {
                    document.location.href="https://app.eglencefabrikasi.com/content/album/"+albumID;
                } else if (channelID==2) {
                    document.location.href="https://app.distronaut.com/content/album/"+albumID;
                }  else if (channelID==3) {
                    document.location.href="https://app.distronaut.com/content/album/"+albumID;
                } 
            } else {
                if (channelID==1) {
                    document.location.href="https://app.eglencefabrikasi.com/settings";
                } else if (channelID==2) {
                    document.location.href="https://app.distronaut.com/content/settings";
                }  else if (channelID==3) {
                    document.location.href="https://app.distronaut.com/content/settings";
                } 
            }
        } else {
            document.location.href="https://app.eglencefabrikasi.com/content/albums";
        }
   // 5 sn
}, 3000);

</script>