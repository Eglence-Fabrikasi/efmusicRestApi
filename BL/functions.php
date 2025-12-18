<?php

function uploadBooklet($data, $userID, $contentID, $albumID, $isOld)
{
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albums.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/tracks.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albumstracks.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";

    $createdBy = $userID;
    $user = new users($userID);
    $customerID = $user->customerID;
    $uploadsPath = dirname(dirname(__FILE__)) . "/uploads/";
    if ($isOld > 0) {
        if (!is_dir($uploadsPath . $userID)) {
            mkdir($uploadsPath . $userID);
        }
        if (!is_dir($uploadsPath . $userID . "/" . $contentID)) {
            mkdir($uploadsPath  . $userID . "/" . $contentID);
        }
        if (!is_dir($uploadsPath . $userID . "/" . $contentID . "/audio")) {
            mkdir($uploadsPath  . $userID . "/" . $contentID . "/audio");
        }
        $contentIDPath = $uploadsPath . $userID . "/" . $contentID . "/audio/";
    } else {
        if (!is_dir($uploadsPath . "customers")) {
            mkdir($uploadsPath . "customers");
        }
        if (!is_dir($uploadsPath . "customers/" . $customerID)) {
            mkdir($uploadsPath . "customers/" . $customerID);
        }
        if (!is_dir($uploadsPath . "customers/" . $customerID . "/" . $contentID)) {
            mkdir($uploadsPath  . "customers/" . $customerID . "/" . $contentID);
        }
        $contentIDPath = $uploadsPath . "customers/" . $customerID . "/" . $contentID . "/";
    }

    $outputFile = $contentIDPath . $data["fileName"];
    $oldFilePath = $contentIDPath . $data["oldFileName"];
    base64_to_file($data["file"], $outputFile);
    if ($data["oldFileName"] != '' && $data["oldFileName"] != $data["fileName"]) {
        unlink($oldFilePath);
    }

    $alb = albums::getAlbumByPdfID($createdBy, $contentID);

    $alb->isPDF > 0 ? $trackID = $alb->trackID : $trackID = 0;
    $pdf = new tracks($trackID);
    $pdf->title = "Digital Booklet";
    $pdf->isrc = "UNDEFINED";
    $pdf->assetFile = $data["fileName"];
    $pdf->filesize = filesize($outputFile);
    $pdf->MD5 = md5_file($outputFile);
    $pdf->duration = 0;
    $pdf->genreID = $alb->genreID;
    $pdf->explicit = 3;
    $pdf->pricing = 1;
    $pdf->copyright = $alb->copyright;
    $pdf->status = 1;
    $pdf->createdBy = $createdBy;
    $pdf->isPDF = 1;
    $pdf->trackVersion = " ";
    $pdf->trackLabel = " ";
    $pdf->preorderType = 1;
    $trackID = $pdf->save();

    if (!$alb->isPDF > 0) {
        $albtrack = new albumsTracks(0);
        $albtrack->albumID = $alb->ID;
        $albtrack->trackID = $trackID;
        $albtrack->trackOrder = $alb->trackOrder + 1;
        $albtrack->date_ = date('Y-m-d H:i:s');
        $albtrack->save();
    }
}
function uploadImage($data, $userID, $contentID, $albumID, $contentType, $isOld)
{
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albums.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";

    $user = new users($userID);
    $customerID = $user->customerID;
    $uploadsPath = dirname(dirname(__FILE__)) . "/uploads/";
    if ($isOld > 0) {
        if (!is_dir($uploadsPath . $userID)) {
            mkdir($uploadsPath . $userID);
        }
        if (!is_dir($uploadsPath . $userID . "/" . $contentID)) {
            mkdir($uploadsPath  . $userID . "/" . $contentID);
        }
        $albumPath = $uploadsPath  . $userID . "/" . $contentID . "/";
        $outputFile = $albumPath . $data["fileName"];
    } else {
        if (!is_dir($uploadsPath . "customers")) {
            mkdir($uploadsPath . "customers");
        }
        if (!is_dir($uploadsPath . "customers/" . $customerID)) {
            mkdir($uploadsPath . "customers/" . $customerID);
        }
        if (!is_dir($uploadsPath . "customers/" . $customerID . "/" . $contentID)) {
            mkdir($uploadsPath  . "customers/" . $customerID . "/" . $contentID);
        }

        $albumPath = $uploadsPath  . "customers/" . $customerID . "/" . $contentID . "/";
        $outputFile = $albumPath . $data["fileName"];
    }
    base64_to_file($data["file"], $outputFile);
    list($coverMD5, $coverSize) = handleAlbumArt($userID, $contentID, $outputFile, $contentType, $isOld);
    $albcover = new albums($albumID);
    $albcover->artFile = 'cover.jpg';
    $albcover->artFileSize = $coverSize;
    $albcover->artFileMD5 = $coverMD5;
    $albcover->save();
}
function uploadMusic($data, $userID, $contentID, $isOld)
{
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";

    $user = new users($userID);
    $customerID = $user->customerID;
    $uploadsPath = dirname(dirname(__FILE__)) . "/uploads/";

    if ($isOld > 0) {
        if (!is_dir($uploadsPath . $userID)) {
            mkdir($uploadsPath . $userID);
        }
        if (!is_dir($uploadsPath . $userID . "/" . $contentID)) {
            mkdir($uploadsPath  . $userID . "/" . $contentID);
        }
        if (!is_dir($uploadsPath . $userID . "/" . $contentID . "/audio")) {
            mkdir($uploadsPath  . $userID . "/" . $contentID . "/audio");
        }
        $audioPath = $uploadsPath . $userID . "/" . $contentID . "/audio/";
    } else {
        if (!is_dir($uploadsPath . "customers")) {
            mkdir($uploadsPath . "customers");
        }
        if (!is_dir($uploadsPath . "customers/" . $customerID)) {
            mkdir($uploadsPath . "customers/" . $customerID);
        }
        if (!is_dir($uploadsPath . "customers/" . $customerID . "/tracks")) {
            mkdir($uploadsPath  . "customers/" . $customerID . "/tracks");
        }
        $audioPath = $uploadsPath . "customers/" . $customerID . "/tracks/";
    }

    $outputFile = $audioPath . $data["fileName"];
    $oldFilePath = $audioPath . $data["oldFileName"];
    base64_to_file($data["file"], $outputFile);
    if ($data["oldFileName"] != '' && $data["oldFileName"] != $data["fileName"]) {
        unlink($oldFilePath);
    }
    list($duration, $fileMD5, $fileSize) = getTrackInfo($outputFile);
    $track = new tracks($data["trackID"]);
    $track->assetFile = $data["fileName"];
    $track->filesize = $fileSize;
    $track->MD5 = $fileMD5;
    $track->duration = $duration;
    $track->save();
}
function uploadContract($data)
{
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/customerContracts.php";

    $uploadsPath = dirname(dirname(__FILE__)) . "/uploads/";
    if (!is_dir($contractsPath = $uploadsPath . "contracts")) {
        mkdir($contractsPath = $uploadsPath . "contracts");
    }
    $outputFile = $contractsPath . "/" . $data["fileName"];
    $oldFilePath = $contractsPath . "/" . $data["oldFileName"];
    base64_to_file($data["file"], $outputFile);
    if ($data["oldFileName"] != '' && $data["oldFileName"] != $data["fileName"]) {
        unlink($oldFilePath);
    }

    $st = new customerContracts();
    $st->setUsersStatus($data["contratID"]);
}

function base64_to_file($data, $output_file)
{
    $decoded = base64_decode($data, true); // strict mode

    if ($decoded === false) {
        throw new RuntimeException('Invalid base64 data');
    }
    $ifp = fopen($output_file, 'wb');
    if ($ifp === false) {
        throw new RuntimeException('File could not be opened');
    }

    $result = fwrite($ifp, $decoded);
    fclose($ifp);

    return $result;

    // $ifp = fopen($output_file, "wb");
    // $result = fwrite($ifp, base64_decode($data));
    // fclose($ifp);
    // return $result;
}
function handleAlbumArt($userID, $contentId, $uploadedFile, $contentType, $isOld)
{
    require_once dirname(dirname(__FILE__)) . '/BL/resize_class.php';
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";

    $user = new users($userID);
    $customerID = $user->customerID;
    if ($isOld > 0) {
        $newAlbumCoverFolder = dirname(dirname(__FILE__)) . "/uploads/" . $userID . "/" . $contentId;
    } else {
        $newAlbumCoverFolder = dirname(dirname(__FILE__)) . "/uploads/customers/" . $customerID . "/" . $contentId;
    }

    $newFile = $newAlbumCoverFolder . "/coverOriginal.jpg";
    $artFilePath = $newAlbumCoverFolder . "/cover.jpg";
    $artFileMiniPath = $newAlbumCoverFolder . "/r_cover.jpg";

    //mkdir($newAlbumCoverFolder);    
    copy($uploadedFile, $newFile);
    if (file_exists($uploadedFile)) {
        unlink($uploadedFile);
    }

    $getMime = explode('.', $newFile);
    $extension = strtolower(end($getMime));
    $get = getimagesize($newFile);

    //Check if the image is square, if larger then 1500px, resize and create Thumbnail
    if ($contentType == 1 || $contentType == 6) {
        if (($get[0] == $get[1]) && ($get[0] >= 3000)) {

            //resize the art 1500x1500
            $resizeObj = new resize($newFile);
            $resizeObj->resizeImage(3000, 3000, 'crop');
            $resizeObj->saveImage($artFilePath, 100);

            //resize the art to 150x150
            $resizeObj = new resize($newFile);
            $resizeObj->resizeImage(150, 150, 'crop');
            $resizeObj->saveImage($artFileMiniPath, 100);
        } else {
            $resizeObj = new resize($newFile);
            $resizeObj->resizeImage(1500, 1500, 'crop');
            $resizeObj->saveImage($artFilePath, 100);

            //resize the art to 150x150
            $resizeObj = new resize($newFile);
            $resizeObj->resizeImage(150, 150, 'crop');
            $resizeObj->saveImage($artFileMiniPath, 100);
        }
    } elseif ($contentType == 3) {
        if (($get[0] <= $get[1]) and ($get[1] >= 600)) {
            /*
            $resizeObj = new resize($newFile);
            $resizeObj -> resizeImage(1400, 1873, 'crop');
            $resizeObj -> saveImage($artFilePath, 100);
            // echo $newFile;
            $resizeObj = new resize($newFile);
            $resizeObj -> resizeImage(140, 187, 'crop');
            $resizeObj -> saveImage($artFileMiniPath, 100);
            */
            $height = $get[1];
            $width = $get[0];
            $heightA = round(1500 * $height / $width);
            $resizeObj = new resize($newFile);
            $resizeObj->resizeImage(1500, $heightA, 'crop');
            $resizeObj->saveImage($artFilePath, 100);

            // resize the art to 150x150
            $heightB = round(150 * $height / $width);
            $resizeObj = new resize($newFile);
            $resizeObj->resizeImage(150, $heightB, 'crop');
            $resizeObj->saveImage($artFileMiniPath, 100);
        }
    } elseif ($contentType == 2) {
        //echo "geliyor mu <br>";
        if (($get[0] * 3 == $get[1] * 2) and ($get[0] >= 2000)) {
            //echo "geldi <br>";
            //resize the art 1500x1500
            $resizeObj = new resize($newFile);
            $resizeObj->resizeImage(2000, 3000, 'crop');
            $resizeObj->saveImage($artFilePath, 100);

            //resize the art to 150x150
            $resizeObj = new resize($newFile);
            $resizeObj->resizeImage(200, 300, 'crop');
            $resizeObj->saveImage($artFileMiniPath, 100);
        } else {
            //echo "geldi <br>";
            //resize the art 1500x1500
            $resizeObj = new resize($newFile);
            $resizeObj->resizeImage(1400, 2100, 'crop');
            $resizeObj->saveImage($artFilePath, 100);

            //resize the art to 150x150
            $resizeObj = new resize($newFile);
            $resizeObj->resizeImage(140, 210, 'crop');
            $resizeObj->saveImage($artFileMiniPath, 100);
        }
    } elseif ($contentType == 5) {
        if (($get[0] >= 640)) {
            //echo "geliyor mu <br>".$get[0]."-".$get[1];
            //resize the art 1500x1500
            $resizeObj = new resize($newFile);
            $resizeObj->resizeImage(1280, 720, 'crop');
            $resizeObj->saveImage($artFilePath, 100);

            //resize the art to 150x150
            $resizeObj = new resize($newFile);
            $resizeObj->resizeImage(170, $get[1] / 5, 'crop');
            $resizeObj->saveImage($artFileMiniPath, 100);
        }
    }
    $md5 = hash_file('md5', $artFilePath);
    $images_size = filesize($artFilePath);
    // echo $md5.":".$images_size;
    return array($md5, $images_size);
}

function getTrackInfo($filePath)
{
    $md5 = hash_file('md5', $filePath);
    $filesize = filesize($filePath);
    exec(dirname(dirname(__FILE__)) . "/unix/ffmpeg -i " . $filePath . " 2>&1 | grep Duration | awk '{print $2}' | tr -d , ", $output);
    // exec("/usr/bin/ffmpeg -i ".$filePath." 2>&1 | grep Duration | awk '{print $2}' | tr -d , ", $output);
    list($a, $b, $c, $d) = explode(':', str_replace('.', ':', $output[0]));
    $duration = 0;
    $duration = ((int)$a * 60 * 60) + ((int)$b * 60) + ((int)$c);
    return array($duration, $md5, $filesize);
}
function deleteDirectory($dir)
{
    if (!file_exists($dir)) {
        return true;
    }
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    return rmdir($dir);
}

function sendEmail($senderUserID, $toFullName, $toEmail, $subject, $body, $templateID, $channelID)
{
    try {
        require_once dirname(dirname(__FILE__)) . "/BL/Tables/mailQueue.php";
        $mailQueue = new mailQueue();
        $mailQueue->sender = $senderUserID; //gonderen user ID
        $mailQueue->recipient = $toFullName; //alici adi
        $mailQueue->recipientEmail = $toEmail; //alici email
        $mailQueue->subject = $subject; //mail konu
        $mailQueue->body = $body; //mail icerik
        $mailQueue->createTime = date('Y-m-d H:i:s');
        $mailQueue->templateID = $templateID; //mail template
        $mailQueue->channelID = $channelID; //mail ChannelID
        $mailQueue->save();
        return true;
    } catch (Exception $error) {
        //echo $error->getMessage();
        return false;
    }
}

function getRandomSessionID()
{
    $alphabet = 'abcdefghijklmnopqrstuvwxyz1234567890';
    $pass = array();
    $alphaLength = strlen($alphabet) - 1;
    for ($i = 0; $i < 10; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass);
}
function ean13_check_digit($digits)
{

    //echo $digits;
    //first change digits to a string so that we can access individual numbers
    $digits = (string)$digits;
    // 1. Add the values of the digits in the even-numbered positions: 2, 4, 6, etc.
    $even_sum = $digits[1] + $digits[3] + $digits[5] + $digits[7] + $digits[9] + $digits[11];
    // 2. Multiply this result by 3.
    $even_sum_three = $even_sum * 3;
    // 3. Add the values of the digits in the odd-numbered positions: 1, 3, 5, etc.
    $odd_sum = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8] + $digits[10];
    // 4. Sum the results of steps 2 and 3.
    $total_sum = $even_sum_three + $odd_sum;
    // 5. The check character is the smallest number which, when added to the result in step 4,  produces a multiple of 10.
    $next_ten = (ceil($total_sum / 10)) * 10;
    $check_digit = $next_ten - $total_sum;
    //echo $digits . $check_digit;
    return $digits . $check_digit;
}
function albumCopyAsset($data, $newAlbumID, $newContentID, $userID, $customerID)
{
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contents.php";
    $oldContent = new contents($data["contentID"]);

    $uploadsPath = dirname(dirname(__FILE__)) . "/uploads/";

    if ($data["isOld"] == 1) {
        $oldAlbumPath = $uploadsPath . $oldContent->userID . "/" . $data["contentID"];
        $newAlbumPath = $uploadsPath . "customers/" . $customerID . "/" . $newContentID;
    } else {
        $oldAlbumPath = $uploadsPath . "customers/" . $customerID . "/" . $data["contentID"];
        $newAlbumPath = $uploadsPath . "customers/" . $customerID . "/" . $newContentID;
    }
    /*
    if (!is_dir($newAlbumPath)) {        
        mkdir($newAlbumPath);        
    } 
    */
    //echo $oldAlbumPath. " - ". $newAlbumPath;        
    //recurse_copy($oldAlbumPath,$newAlbumPath);
    shell_exec("cp -r $oldAlbumPath $newAlbumPath");
}
/*
function recurse_copy($src,$dst) {  
    $dir = opendir($src);
    @mkdir($dst);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                recurse_copy($src . '/' . $file,$dst . '/' . $file);
            }
            else {
                copy($src . '/' . $file,$dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}
*/
