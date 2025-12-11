
<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '-1');

class garantiPay
{
    private $amount;
    private $customerEmail;

    function __construct($amount,$customerEmail)
    {
        $this->amount=$amount;
        $this->customerEmail=$customerEmail;

    }

    function pay() {
        $strMode = "PROD";
        $strVersion = "v0.01";
        $strTerminalID = "10215529";
        //$strTerminalID_ = "030690133"; //'TerminalID ba��na 0 ile 9 digit yap�lmal�
        $strProvUserID = "PROVAUT";
        $strProvisionPassword = "Nub.6KsaLK"; //'SanalPos �ifreniz ( PROVAUT kullan�c�s�n�n �ifresi )
        $strUserID = "ETICARET";
        $strMerchantID = "1298777"; // 'MerchantID (Uye isyeri no) 
        $strIPAddress = "52.48.17.189";
        $strEmailAddress = $this->customerEmail;
        
        $strOrderID = "5944_12717"; // Bunu otomatik vermeliyiz
        
        $strInstallmentCnt = ""; //'Taksit Say�s�. Bo� g�nderilirse taksit yap�lmaz
        $strAmount = $this->amount; //'��lem Tutar� Son 2 hane kuru� 
        $strType = "gpdatarequest"; // 'gpdatarequest
		$strSubType = "sales"; 
        $strCurrencyCode = "949";
        $strCardholderPresentCode = "0";
		$strReturnServerUrl = "https://localhost/EFDigitalAPI/ok.php";
        $strMotoInd = "Y";
        $strHostAddress = "https://sanalposprovtest.garanti.com.tr/VPServlet";
        $strNumber="1";
        $SecurityData = sha1($strProvisionPassword . $strTerminalID);
        $HashData = sha1($strOrderID . $strTerminalID . $strNumber . $strAmount . $SecurityData);

       

        $strXML = "<?xml version='1.0' encoding='UTF-8'?>".
                    "<GVPSRequest>".
                    "<Mode>".$strMode."</Mode>".
                    "<Version>".$strVersion."</Version>".
                    "<ChannelCode></ChannelCode>".
                    "<Terminal><ProvUserID>".$strProvUserID."</ProvUserID><HashData>".$HashData."</HashData><UserID>".$strUserID."</UserID>".
                    "<ID>".$strTerminalID."</ID><MerchantID>".$strMerchantID."</MerchantID></Terminal>".
                    "<Customer><IPAddress>".$strIPAddress."</IPAddress><EmailAddress>".$strEmailAddress."</EmailAddress></Customer>".
                    "<Order><OrderID>".$strOrderID."</OrderID><GroupID></GroupID></Order>".
                    "<Transaction><Type>".$strType."</Type><SubType>".$strSubType."</SubType><InstallmentCnt>".$strInstallmentCnt."</InstallmentCnt><Amount>".$strAmount."</Amount>".
                    "<CurrencyCode>".$strCurrencyCode."</CurrencyCode><CardholderPresentCode>".$strCardholderPresentCode."</CardholderPresentCode>".
                    "<ReturnServerUrl>".$strReturnServerUrl."</ReturnServerUrl><MotoInd>".$strMotoInd."</MotoInd>".
                    "<GarantiPaY><bnsuseflag>N</bnsuseflag><fbbuseflag>N</fbbuseflag ><chequeuseflag>N</chequeuseflag><mileuseflag>N</mileuseflag >".
                    "<CompanyName>GARANTI TEST</CompanyName><OrderInfo></OrderInfo><TxnTimeOutPeriod>300</TxnTimeOutPeriod><NotifSendInd>Y</NotifSendInd>".
                    "<ReturnUrl></ReturnUrl><TCKN>15224860132</TCKN><GSMnumber>5309313756</GSMnumber><InstallmentOnlyForCommercialCard>Y</InstallmentOnlyForCommercialCard>".
                    "<TotalInstamenlCount>2</TotalInstamenlCount><GPInstallments></Installment><Installmentnumber>2</Installmentnumber>".
                    "<Installmentamount>150</Installmentamount></Installment><Installment><Installmentnumber>5</Installmentnumber><Installmentamount>150</Installmentamount></Installment></GPInstallments></GarantiPaY>".
                    "</Transaction>".
                    "</GVPSRequest>";

                    echo $strXML;
                    $headers=array("Content-Type: application/x-www-form-urlencode");
                    //Jeson için örnek bir tanımlama
                    
                    $ch = curl_init($strHostAddress);
                    curl_setopt($ch, CURLOPT_HEADER, $headers);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_POST, $strXML);
                    curl_setopt($ch, CURLOPT_VERBOSE, 0);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    $output = curl_exec($ch);
                    curl_close($ch);
                
                    echo var_dump($output);
                    //$xml = json_decode(json_encode(simplexml_load_string($output)),true);
                    //echo var_dump($xml);  
                    
    }

}

?>
