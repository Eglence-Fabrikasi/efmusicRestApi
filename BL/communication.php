<?php

//header("Content-type: text/html; charset=utf-8");
date_default_timezone_set ( 'Europe/Istanbul' );
use PHPMailer\PHPMailer\PHPMailer;

require_once dirname(dirname(__FILE__)).'/Library/PHPMailer-master/src/PHPMailer.php';
require_once dirname(dirname(__FILE__)).'/Library/PHPMailer-master/src/Exception.php';
require_once dirname(dirname(__FILE__)).'/Library/PHPMailer-master/src/SMTP.php';

require_once dirname(dirname(__FILE__)) . "/BL/Tables/communicationLogs.php";
require_once dirname(dirname(__FILE__)) . "/BL/Tables/mailQueue.php";
require_once dirname(dirname(__FILE__)) . "/BL/Tables/emailTemplates.php";
require_once dirname(dirname(__FILE__)) . "/BL/Tables/channels.php";

class Mail
{
    private $recipient = '';
    private $recipientMail = '';
    private $senderID = 0;
    private $subject = '';
    private $body = '';
    private $recipients;
    private $templateID = 1;
    private $channelID;

    function __construct($recipient, $recipientMail, $senderID, $subject, $body, $recipients = NULL,$templateID=1,$channelID=1)
    {
        if (! is_array($recipients)) {
            $this->recipient = $recipient;
            $this->recipientMail = $recipientMail;
            $this->senderID = $senderID;
            $this->subject = $subject;
            $this->body = $body;
            $this->templateID=$templateID;
            $this->channelID=$channelID;
        }
    }

    function sendQueue()
    {
        $mq = new mailQueue();
        $mq->body = $this->body;
        $mq->recipient = $this->recipient;
        $mq->recipientEmail = $this->recipientMail;
        $mq->sender = $this->senderID;
        $mq->subject = $this->subject;
        $mq->createTime = date("Y-m-d H:i:s");
        $mq->templateID=$this->templateID;
        $mq->channelID=$this->channelID;
        $mq->save();
    }
}

class worker
{
    private $qID;
    function __construct($qID)
    {
      $this->qID=$qID;
    }
    
    function sendMailFromQ()
    {
        $mq = new mailQueue($this->qID);
        $template = new emailTemplates($mq->templateID);
        $channel = new channels($mq->channelID);
        
        $mail = new PHPMailer(true); // the true param means it will throw
        $mail->isSMTP(); // telling the class to use SendMail transport
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = "tls";
        
        try {

            /*
            echo "id ".$this->qID."<br>";
            echo "recipient ".$mq->recipient."<br>";
            echo "recem ".$mq->recipientEmail."<br>";
            echo "ema ".$mq->email."<br>";
            echo "temp ".$template->content."<br>";
            echo "body ".$mq->body."<br>";
            echo "tempID". $mq->templateID;
            echo "channelID". $mq->channelID
            */

            $sbjt = $mq->subject;
            if ($sbjt == ""){
                $sbjt = $channel->channel;
            }
           
            $mail->AddAddress ( str_replace ( 'I', 'i', $mq->recipientEmail ), $mq->recipient );
            $mail->SetFrom($channel->mailUser, $channel->mailFromName);
            $mail->addReplyTo($channel->channelEmail, $channel->channel);
            $mail->Subject = $mq->subject;

            $body = $template->content;
            $body = str_replace("@message", $mq->body, $body);
            $body = str_replace("@header", $mq->subject, $body);
            $body = str_replace("@subject", $mq->subject, $body);
            $body = str_replace("@mailadress", $channel->channelEmail, $body);
            $body = str_replace("@usermail", $mq->recipientEmail, $body);
            $body = str_replace("@userid", $mq->sender, $body);
            $body = str_replace("@username", $mq->recipient, $body);
            $body = str_replace("@channelLogo", $channel->channelLogo, $body);
            $body = str_replace("@channelName", $channel->channel, $body);  
            $body = str_replace("@channelURL", $channel->channelURL, $body);
            $body = str_replace("@channelColor", $channel->channelColor, $body);
            $body = str_replace("@emailID", $mq->ID, $body);
            
            $mail->MsgHTML($body);
           
            $mail->SMTPAuth = true;
            $mail->Port = $channel->mailPort; //mailserver::port;
            $mail->Host = $channel->mailServer; //mailServer::host;
            $mail->Username = $channel->mailUser; //mailServer::username;
            //$mail->From=$channel->channelEmail;
            $mail->Password = $channel->mailPass; //mailServer::password;
            $mail->CharSet = "UTF-8";
            $mail->Encoding = "base64";
            //$mail->SMTPDebug=2;
            $mail->isHTML(true);

            if (! $mail->Send()) {
                
               //echo $mail->ErrorInfo;
               $cl = new communicationLogs();
               $cl->comType = 2;
               $cl->comID = $mq->ID;
               $cl->toInfo = $mq->recipientEmail;
               $cl->result = $mail->ErrorInfo;
               $cl->save();
                exit();
            };

            $mq->sendTime=date("Y-m-d H:i:s");
            $mq->save();
            
            $cl = new communicationLogs();
            $cl->comType = 2;
            $cl->comID = $mq->ID;
            $cl->toInfo = $mq->recipientEmail;
            $cl->result = "sended";
            $cl->save();
            
            return true;
            
        } catch (Exception $e) {
            echo $e->errorMessage ();
            return false;
        } catch (Exception $e) {
            echo $e->errorMessage ();
            return false;
        }
      
    }
    
    
    
}

?>
