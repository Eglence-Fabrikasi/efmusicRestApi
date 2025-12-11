<?php

    function createXML($albumId,$platformId,$batchFolder = '',$upc = '',$contentStatus=4,$uploadUrl){
		$a = new albums($albumId);
		if ($contentStatus!=8) {
		$ct = 1;
		$query = "select 
						case when contents.isOld=1 then 
							concat('".$uploadUrl."',contents.userID,'/',contents.ID,'/audio/',tracks.assetFile) 
						else
							concat('".$uploadUrl."customers/',users.customerID,'/tracks/',tracks.assetFile) 
						end
						as pt,ifnull(tracks.duration,0) as duration,albumsTracks.trackID,
						ifnull(tracks.fileSize,'') as fileSize,ifnull(tracks.MD5,'') as MD5,
						albums.title,users.username, contents.contentType,
						concat('".$uploadUrl."customers/',users.customerID,'/tracks/',trackISRCs.assetFile) as daFilePath
					from albumsTracks
					inner join tracks on tracks.ID=albumsTracks.trackID
					inner join albums on albumsTracks.albumID = albums.ID
					inner join contents on contents.ID = albums.contentID
					inner join users on contents.userID=users.ID
					left outer join trackISRCs on trackISRCs.trackID=albumsTracks.trackID and trackISRCs.isrcType=1
					where ifnull(tracks.isPDF,0) in (0,2) and albums.ID=".$albumId;
	
		$result = $a->executenonquery($query);
		while(list($path,$dur,$qtrackId,$fileSize,$md,$title,$username,$contentType,$daFilePath)=mysqli_fetch_array($result))
		{
					$ct=$contentType;
					$fullPath =  dirname(dirname(dirname(__FILE__))).$path;
					
					
					if (file_exists($fullPath)) {
						if ($dur==0) {
				
						// check duration
							$t = new tracks();
							$duration = $t->getTrackInfo($fullPath)[0];
							 if ((int)$duration>0) {
								 $tt = new tracks($qtrackId);
								 $tt->duration=$duration;
								 $tt->save();
								
							}			
						}
						// check md5
						if ($md=='') {
							$md5 = hash_file('md5', $fullPath);
							$tt = new tracks($qtrackId);
							$tt->md5=$md5;
							$tt->save();
						}
						// check File size
						if ($fileSize!=filesize($fullPath) || $fileSize=='') {
							$tt = new tracks($qtrackId);
							$tt->filesize=filesize($fullPath);
							$tt->save();		
						}

						// check dolby atmos File size
						$da = trackISRCs::getTrackISRC($qtrackId,1);
						if ($da->isrc!="" && !$da->filesize>0) {
							$fullPathda =  dirname(dirname(dirname(__FILE__))).$daFilePath;
							$da->filesize=filesize($fullPathda);
							$da->MD5=hash_file('md5', $fullPathda);
							$da->save();
						}
					}
				
				
		}
		$query = "select 
						case when contents.isOld=1 then 
							concat('/uploads/',contents.userID,'/',contents.ID,'/',artFile) 
						else
							concat('uploads/customers/',users.customerID,'/',contents.ID,'/',artFile) 
						end
						 as pt,ifnull(artFileMD5,'') as artFileMD5,
						ifnull(artFileSize,0) as artFileSize,albums.title from albums
						inner join contents on contents.ID=albums.contentID
						inner join users on users.ID=contents.userID
						where albums.ID=".$albumId;
		$result = $a->executenonquery($query);
			
		while(list($path,$md,$size,$title)=mysqli_fetch_array($result))
		{
			$fullPath =  dirname(dirname(dirname(__FILE__))).$path;
		
			if (file_exists($fullPath)) {
				//check art size
				if ($md=='' || filesize($fullPath)==0) {
					$md5 = hash_file('md5', $fullPath);
					$aa = new albums($albumId);
					$aa->artFileMD5=$md5;
					$aa->artFileSize=filesize($fullPath);
					$aa->save();
					
				}
		
			}
		}
		
		} else {
		   $query = "select contents.contentType
					from albums 
					inner join contents on contents.ID = albums.contentID
					where albums.ID=".$albumId;
		   $result = $a->executenonquery($query);
		   list($contentType)=mysqli_fetch_array($result);
		   $ct = $contentType;
		}
		$query = "delete from deliveries where deliveries.albumID=".$albumId." and platformID=".$platformId;
		$a->executenonquery($query,null,true);
		$contentStatusText = "Insert";
		switch ($contentStatus)
		{
			case 4:
				$contentStatusText = "Insert";
				break;
			case 8:
				$contentStatusText = "Delete";
				break;
			case 9:
				$contentStatusText = "Update";
				break;
				
		}
		
		
		
		switch ($platformId) {
			case 1:
				$query = sprintf("call pXMLOutputiTunesV2($albumId,'$contentStatusText',0)");
				$counts = $a->executenonquery($query);
				list($XMLId) = mysqli_fetch_array($counts);
				break;
			case 2:
				if ($contentStatusText=="Insert") {
					$sql = "call psetrackRowNumber($albumId)";
					$a->executenonquery($query,null,true);
				}
				if ($ct==5) {
					$query = sprintf("call pXMLOutputSpotifyDDEX43MV($albumId,'$contentStatusText');");
				} else {
					$query = sprintf("call pXMLOutputSpotifyDDEX43($albumId,'$contentStatusText');");
				}
				//$query = sprintf("call pXMLOutputSpotifyDDEX($albumId,'$contentStatusText');");
				$counts = $a->executenonquery($query);
	
				list($XMLId) = mysqli_fetch_array($counts);
					if ($contentStatus!=8) {
						$query = "select XMLvalue from deliveries where id=".$XMLId. " limit 1";
						$xmlResult = $a->executenonquery($query);
						list($xml)= mysqli_fetch_array($xmlResult);
						
						$query = "select concat('/deliveries/2".$batchFolder."/".$upc."/".$upc."_01_',LPAD(trackOrder,3,'0'),'.flac') as pt,MD5
								from albumsTracks 
									inner join tracks on tracks.ID=albumsTracks.trackID
						where ifnull(tracks.isPDF,0)=0 and albumsTracks.albumID=".$albumId." order by albumsTracks.trackOrder";
						
						$result = $a->executenonquery($query);
						while (list($flacFile,$md5master)=mysqli_fetch_array($result)) {
							$gfile= ltrim(dirname(dirname(__file__)).$flacFile); 
							if (file_exists($gfile)) {
								$md5g = hash_file('md5', $gfile);
								$query = "update deliveries set XMLvalue=REPLACE(XMLvalue,'".$md5master."','".$md5g."') where id=$XMLId";
								$a->executenonquery($query,null,true);
							}
						}
						
					}
	
				break;
			/*
				case 2:
				$query = sprintf("call pXMLOutputSpotify($albumId,'$contentStatusText',0);");
				$counts = $a->executenonquery($query);
				list($XMLId) = mysqli_fetch_array($counts);
				break;
			*/
			case 3:
				//$query = sprintf("call pXMLOutputDeezer($albumId,'$contentStatusText',0);");
				$query = sprintf("call pXMLOutputDeezerDDEX41($albumId,'$contentStatusText');");
				$counts = $a->executenonquery($query);
				
				list($XMLId) = mysqli_fetch_array($counts);
				break;
			case 4:
				if ($contentStatusText=="Insert") {
					$sql = "call psetrackRowNumber($albumId)";
					$a->executenonquery($query,null,true);
				}
				$query = sprintf("call pXMLOutputFuga($albumId,'$contentStatusText');");
				$counts = $a->executenonquery($query);
				list($XMLId) = mysqli_fetch_array($counts);
				break;
			  case 5:
				  $query = sprintf("call pXMLOutputGoogle($albumId,'$contentStatusText');");
				  $counts = $a->executenonquery($query);
				  
				  list($XMLId) = mysqli_fetch_array($counts);
				  if ($contentStatus!=8) {
					  $query = "select XMLvalue from deliveries where id=".$XMLId. " limit 1";
					  $xmlResult = $a->executenonquery($query);
					  list($xml)= mysqli_fetch_array($xmlResult);
					  
					  $query = "select concat('/deliveries/5".$batchFolder."/".$upc."/resources/',SUBSTRING_INDEX(assetFile,'.',1),'.flac') as pt,MD5
								  from albumsTracks 
								 			inner join tracks on tracks.ID=albumsTracks.trackID
								  where ifnull(tracks.isPDF,0)=0 and albumsTracks.albumID=".$albumId." order by albumsTracks.trackOrder";
		
					  $result = $a->executenonquery($query);
					  while (list($flacFile,$md5master)=mysqli_fetch_array($result)) {
						$gfile= ltrim(dirname(dirname( __FILE__ )).$flacFile); 
						if (file_exists($gfile)) {
							$md5g = hash_file('md5', $gfile);
							$query = "update deliveries set XMLvalue=REPLACE(XMLvalue,'".$md5master."','".$md5g."') where ID=$XMLId";
							$a->executenonquery($query,null,true);
						}
					}
					  
				  }
				  break;
			case 21:
				$query = sprintf("call pXMLOutputTikTok($albumId,'$contentStatusText');");
				$counts = $a->executenonquery($query);
				
				list($XMLId) = mysqli_fetch_array($counts);
				if ($contentStatus!=8) {
					$query = "select XMLvalue from deliveries where ID=".$XMLId. " limit 1";
					$xmlResult = $a->executenonquery($query);
					list($xml)= mysqli_fetch_array($xmlResult);
					
					$query = "select concat('/deliveries/21".$batchFolder."/".$upc."/resources/',SUBSTRING_INDEX(assetFile,'.',1),'.flac') as pt,MD5
								from albumsTracks 
								inner join tracks on tracks.ID=albumsTracks.trackID
								where ifnull(tracks.isPDF,0)=0 and albumsTracks.albumID=".$albumId." order by albumsTracks.trackOrder";
	
					$result = $a->executenonquery($query);
					while (list($flacFile,$md5master)=mysqli_fetch_array($result)) {
						$gfile= ltrim(dirname(dirname(__FILE__)).$flacFile); 
						if (file_exists($gfile)) {
							$md5g = hash_file('md5', $gfile);
							$query = "update deliveries set XMLvalue=REPLACE(XMLvalue,'".$md5master."','".$md5g."') where ID=$XMLId";
							$a->executenonquery($query,null,true);
						}
					}
					
				}
				break;
				case 22:
					$query = sprintf("call pXMLOutputAM($albumId,'$contentStatusText');");
					$counts =  $a->executenonquery($query);
					
					list($XMLId) = mysqli_fetch_array($counts);
					if ($contentStatus!=8) {
						$query = "select XMLvalue from deliveries where ID=".$XMLId. " limit 1";
						$xmlResult =  $a->executenonquery($query);
						list($xml)= mysqli_fetch_array($xmlResult);
						
						$query = "select concat('/deliveries/22".$batchFolder."/".$upc."/resources/',SUBSTRING_INDEX(assetFile,'.',1),'.mp3') as pt,MD5
								from albumsTracks 
								inner join tracks on tracks.ID=albumsTracks.trackID
								where ifnull(tracks.isPDF,0)=0 and albumsTracks.albumID=".$albumId." order by albumsTracks.trackOrder";
			
						$result =  $a->executenonquery($query);
						while (list($flacFile,$md5master)=mysqli_fetch_array($result)) {
							$gfile= ltrim(dirname(dirname(__file__)).$flacFile); 
							if (file_exists($gfile)) {
								$md5g = hash_file('md5', $gfile);
								$query = "update deliveries set XMLvalue=REPLACE(XMLvalue,'".$md5master."','".$md5g."') where ID=$XMLId";
								$a->executenonquery($query);
							}
						}
						
					}
					break;
			  case 7:
				  //$query = sprintf("call pXMLOutputTTNet($albumId,'$contentStatusText');");
				  if ($ct==5) {
					$query = sprintf("call pXMLOutputTTNetDDEXMV($albumId,'$contentStatusText',0);");
				  } else {
					$query = sprintf("call pXMLOutputTTNetDDEX($albumId,'$contentStatusText',0);");
				  }
				  
				  $counts = $a->executenonquery($query);
				  
				  list($XMLId) = mysqli_fetch_array($counts);
				  if  ($ct==5) {
					$query = "update deliveries set XMLValue=replace(XMLValue,'.mp3','.mp4') where ID=".$XMLId;
					$a->executenonquery($query,null,true);
				  }
				  break;
			case 8:
				$query = "select contents.contentType
					from albums
					inner join contents on contents.ID = albums.contentID
					where albums.ID=".$albumId;
				$result = $a->executenonquery($query);
				list($tcontentType)=mysqli_fetch_array($result);
				if ($tcontentType==5) {
					$query = sprintf("call pXMLOutputTurkcellMV($albumId,'$contentStatusText',0);");
				} else {
					$query = sprintf("call pXMLOutputTurkcell($albumId,'$contentStatusText',0);");
				}
				$counts = $a->executenonquery($query);
			
				list($XMLId) = mysqli_fetch_array($counts);
				if ($contentStatus!=8) {
					$query = "select XMLvalue from deliveries where ID=".$XMLId. " limit 1";
					$xmlResult = $a->executenonquery($query);
					list($xml)= mysqli_fetch_array($xmlResult);
					 if ($ct!=5) {
						$query = "select concat('/deliveries/8".$batchFolder."/".$upc."/',SUBSTRING_INDEX(assetFile,'.',1),'.mp3') as pt,MD5
								  from albumsTracks 
								  inner join tracks on tracks.ID=albumsTracks.trackID
								  where tracks.isPDF=0 and albumsTracks.albumID=".$albumId;
					} else {
						$query = "select concat('/deliveries/8".$batchFolder."/".$upc."/',SUBSTRING_INDEX(assetFile,'.',1),'.mp4') as pt,MD5
								  from albumsTracks 
								  inner join tracks on tracks.ID=albumsTracks.trackID
								  where tracks.isPDF=0 and albumsTracks.albumID=".$albumId;
					}
					$result = $a->executenonquery($query);
					while (list($newmp3File,$md5master)=mysqli_fetch_array($result)) {
						//echo dirname(dirname(__file__)).$flacFile,"<br>";
						if (file_exists(dirname(dirname(__FILE__)).$newmp3File)) {
							$md5g = hash_file('md5', dirname(dirname(__FILE__)).$newmp3File);
							$query = "update deliveries set XMLvalue=REPLACE(XMLvalue,'".$md5master."','".$md5g."') where ID=$XMLId";
							$a->executenonquery($query,null,true);
						}
					}
				}
				break;
			case 9:
				$query = sprintf("call pXMLOutputTarget($albumId,'$contentStatusText');");
				$counts = $a->executenonquery($query);
				
				
				list($XMLId) = mysqli_fetch_array($counts);
				
				$query = "select XMLvalue from deliveries where ID=".$XMLId. " limit 1";
				$xmlResult = $a->executenonquery($query);
				list($xml)= mysqli_fetch_array($xmlResult);
				
				$query = "select concat('/deliveries/9".$batchFolder."/".$upc."/".$upc."',replace(artFile,SUBSTRING_INDEX(artFile,'.',1),'')) as coverFile,artFileMD5
								  from albums where albums.ID=".$albumId;
				$result = $a->executenonquery($query);
				list($artFile,$artFileMD5)=mysqli_fetch_array($result);
				$gafile= ltrim(dirname(dirname(__FILE__)).$artFile);
				if (file_exists($gafile)) {
					$acrc32 = hash_file('crc32', $gafile);
					$query = "update deliveries set XMLvalue=REPLACE(XMLvalue,'".$artFileMD5."','".$acrc32."') where ID=$XMLId";
					$a->executenonquery($query,null,true);
				}
				 
				$query = "select concat('/deliveries/9".$batchFolder."/".$upc."/".$upc."_1_',case when length(albumsTracks.position)=1 then concat('0',albumsTracks.trackOrder) else albumsTracks.trackOrder end,'.wav') as mp3file,MD5
								  from albumsTracks 
								  inner join tracks on tracks.ID=albumsTracks.trackID
								  where ifnull(tracks.isPDF,0)=0 and albumsTracks.albumID=".$albumId." order by albumsTracks.trackOrder";
				$result = $a->executenonquery($query);
				while (list($mp3File,$md5master)=mysqli_fetch_array($result)) {
					$gfile= ltrim(dirname(dirname(__FILE__)).$mp3File);
					if (file_exists($gfile)) {
						$crc32 = hash_file('crc32', $gfile);
						$query = "update deliveries set XMLvalue=REPLACE(XMLvalue,'".$md5master."','".$crc32."') where id=$XMLId";
						$a->executenonquery($query,null,true);
					}
				}
				
				break;
			case 10:
				if ($contentStatus!=8) {
					switch ($ct) {
						case 1:
								$query = sprintf("call pXMLOutputYoutubeAlbumSongs($albumId,'$contentStatusText',0);");
								break;
						case 5:
								$query = sprintf("call pXMLOutputYoutubeMusicVideo($albumId,'$contentStatusText',0);");
								break;
					}
					
					$counts = $a->executenonquery($query);
					
					
					list($XMLId) = mysqli_fetch_array($counts);
				}
				break;
			case 17:
					$query = sprintf("call pXMLOutputYoutubeMusicKey($albumId,'$contentStatusText');");
					$counts = $a->executenonquery($query);
					list($XMLId) = mysqli_fetch_array($counts);
					break;
	}
		
			$contentId=$a->contentID;
			$sql = "select deliveries.ID from deliveries where deliveries.albumID=".$albumId . " and deliveries.platformID=".$platformId;
			$delResult = $a->executenonquery($sql);
			list($deliveryId)=mysqli_fetch_array($delResult);
		if ($contentStatus!=8) {	
			updateContentStatusNew($contentId, 5,$XMLId,$platformId,0);
		} else {
			updateContentStatusNew($contentId, 10,$XMLId,$platformId,0);
		}
		return $XMLId;
		
	}

    function copyLocalizedFiles ($albumId,$productFolder,$platformId,$upc='',$contentStatus=4,$uploadUrl){
        $a = new albums($albumId);
        $contentId = $a->contentID;
        
        $q = sprintf("SELECT a.userID,a.isOld FROM contents a WHERE a.ID = %s;", ($contentId));
        $result2 = $a->executenonquery($q);
        list ($userid,$isOld) = mysqli_fetch_array($result2);
        mysqli_free_result($result2);
        
		$user = new users ($userid);
		
        if ($contentStatus==4) {
            $query = sprintf("call pgetLocalizedFiles(%s)", ($contentId));
        } elseif ($contentStatus==9) {
            // change for update
            $query = sprintf("SELECT tracks.assetFile,a.trackOrder FROM albumsTracks a
					inner join tracks on tracks.ID=a.trackID
					inner join assetChanges b on a.trackID=b.trackID and b.changeType=2 and b.isDeliver=0
					WHERE a.albumID = %s order by a.trackOrder asc;", ($albumId));
        } else {
            exit();
        }
        $resultFiles = $a->executenonquery($q);;
        while(list ($selectorType,$position,$fileName,$md5,$size) = mysqli_fetch_array($resultFiles)){
			
			if ($isOld==1) {
				$orginalTrackFile = $uploadUrl.$userid."/".$contentId."/".$fileName;
			} else {

				$orginalTrackFile = $uploadUrl."customers/".$user->customerID."/tracks/".$fileName;
			}
			
			if ($platformId==5 || $platformId==21) {
                $deliveryTrackFile = $productFolder."/resources/".$fileName;
                copy($orginalTrackFile,$deliveryTrackFile);
            } elseif ($platformId==1) {
                $deliveryTrackFile = $productFolder."/".$fileName;
                
                copy($orginalTrackFile,$deliveryTrackFile);
                
                
            } elseif ($platformId==14) {
                $dFile = explode(".", $fileName);
                $deliveryTrackFile = $productFolder."/".$upc.".".$dFile[1];
                copy($orginalTrackFile,$deliveryTrackFile);
            }
            
            $md5 = hash_file('md5', $deliveryTrackFile);
            $fileSize = filesize ($deliveryTrackFile);
                
            $sql = "update albumsLocalized set value='$md5' where selectorType=11 and selectorValue=1 and contentId=$contentId and keyName='md5'";
            $a->executenonquery($sql,null,true);
                
            $sql = "update albumsLocalized set value='$fileSize' where selectorType=11 and selectorValue=1 and contentId=$contentId and keyName='size'";
            $a->executenonquery($sql,null,true);
            
            
        }
        mysqli_free_result($resultFiles);
        
    }

    function convertTomp3H($filewithpath,$deliveryTrackFile){
        $getMime = explode('.', $filewithpath);
        $extension = strtolower(end($getMime));
        $outputFilewithPath = $getMime[0]."mp3";
        //echo $outputFilewithPath;
        exec(dirname(dirname(__FILE__))."/unix/ffmpeg -i ".$filewithpath." -ar 44100 -ab 320k -y ".$deliveryTrackFile, $output);
        return $output;
    }
    function convertTomp3($filewithpath,$deliveryTrackFile){
        $getMime = explode('.', $filewithpath);
        $extension = strtolower(end($getMime));
        $outputFilewithPath = $getMime[0]."mp3";
        //echo $outputFilewithPath;
        exec(dirname(dirname(__FILE__))."/unix/ffmpeg -i ".$filewithpath." -ab 192k -y ".$deliveryTrackFile, $output);
        return $output;
    }
        
    function convertTomp4($filewithpath,$deliveryTrackFile,$uploadUrl){
            
        $getMime = explode('.', $deliveryTrackFile);
        $outputFilewithPath = $getMime[0].".mp4";
        //$getSource = explode('/uploads',$filewithpath);
        //$filewithpath = dirname(dirname(dirname(__FILE__))).$uploadUrl.$getSource[1];
        //echo dirname(dirname(__FILE__))."/unix/ffmpeg  -y -i ".$filewithpath." -ab 192000 -b:a 4000000 -bf 3 -ar 44100 -b_strategy 2 -coder 1 -qmin 10 -qmax 51 -sc_threshold 40 -crf 25 -flags +loop -cmp +chroma -me_range 16  -me_method hex -subq 8 -i_qfactor 0.71 -qcomp 0.6 -qdiff 5 -dts_delta_threshold 1 -acodec libfaac -s 1920X1080 -vcodec libx264 -threads 0 -r 25 -pix_fmt yuv420p ".$outputFilewithPath;
        exec(dirname(dirname(__FILE__))."/unix/ffmpeg  -y -i ".$filewithpath." -ab 192000 -b:a 4000000 -bf 3 -ar 44100 -b_strategy 2 -coder 1 -qmin 10 -qmax 51 -sc_threshold 40 -crf 25 -flags +loop -cmp +chroma -me_range 16  -me_method hex -subq 8 -i_qfactor 0.71 -qcomp 0.6 -qdiff 5 -dts_delta_threshold 1 -acodec aac -strict -2 -s 1920X1080 -vcodec libx264 -threads 0 -r 25 -pix_fmt yuv420p ".$deliveryTrackFile,$output);
        //exec("ffmpeg -i ".$filewithpath." ".$outputFilewithPath, $output);
        return $output;
    }
    
	function createPrelistenmp4($filewithpath,$deliveryTrackFile){
            
        $getMime = explode('.', $deliveryTrackFile);
        $outputFilewithPath = $getMime[0].".mp4";
        //$getSource = explode('/uploads',$filewithpath);
        //$filewithpath = dirname(dirname(dirname(__FILE__))).$uploadUrl.$getSource[1];
        //echo dirname(dirname(__FILE__))."/unix/ffmpeg  -y -i ".$filewithpath." -ab 192000 -b:a 4000000 -bf 3 -ar 44100 -b_strategy 2 -coder 1 -qmin 10 -qmax 51 -sc_threshold 40 -crf 25 -flags +loop -cmp +chroma -me_range 16  -me_method hex -subq 8 -i_qfactor 0.71 -qcomp 0.6 -qdiff 5 -dts_delta_threshold 1 -acodec libfaac -s 1920X1080 -vcodec libx264 -threads 0 -r 25 -pix_fmt yuv420p ".$outputFilewithPath;
        exec(dirname(dirname(__FILE__))."/unix/ffmpeg  -ss 20 -t 30 -y -i ".$filewithpath." -ab 192000 -b:a 4000000 -bf 3 -ar 44100 -b_strategy 2 -coder 1 -qmin 10 -qmax 51 -sc_threshold 40 -crf 25 -flags +loop -cmp +chroma -me_range 16  -me_method hex -subq 8 -i_qfactor 0.71 -qcomp 0.6 -qdiff 5 -dts_delta_threshold 1 -acodec aac -strict -2 -s 1920X1080 -vcodec libx264 -threads 0 -r 25 -pix_fmt yuv420p ".$deliveryTrackFile,$output);
        //exec("ffmpeg -i ".$filewithpath." ".$outputFilewithPath, $output);
        return $output;
    }

    function createPrelisten($filewithpath,$deliveryTrackFile){
        $getMime = explode('.', $filewithpath);
        $extension = strtolower(end($getMime));
        $outputFilewithPath = $getMime[0]."mp3";
		//echo dirname(dirname(__FILE__))."/unix/ffmpeg -ss 20 -t 30 -i -y ".$filewithpath." ".$deliveryTrackFile;
        exec(dirname(dirname(__FILE__))."/unix/ffmpeg -ss 20 -t 30 -i ".$filewithpath." -y ".$deliveryTrackFile, $output);
        return $output;
    }

    function convertToFlac($filewithpath,$deliveryTrackFile){
        $getMime = explode('.', $filewithpath);
        $extension = strtolower(end($getMime));
        $outputFilewithPath = $getMime[0]."flac";
        exec(dirname(dirname(__FILE__))."/unix/ffmpeg -i ".$filewithpath." -ab 192k -y ".$deliveryTrackFile, $output);
        return $output;
    }

function copyAlbumTracks($albumId,$productFolder,$platformId,$upc='',$contentStatus=4,$uploadUrl){
	$a = new albums($albumId);
	$contentId = $a->contentID;
	
	$q = sprintf("SELECT a.userID,a.contentType,a.isOld FROM contents a WHERE a.ID = %s;", ($contentId));
	$result2 = $a->executenonquery($q);
	list ($userid,$ct,$isOld) = mysqli_fetch_array($result2);
	$user = new users($userid); 

	if ($contentStatus==4) {
			$query = sprintf("select * from (SELECT tracks.assetFile,a.trackOrder,tracks.isPDF FROM albumsTracks a 
											inner join tracks on tracks.ID=a.trackID
								WHERE a.albumID = %s and (%s=1 or %s=8 or %s=10 or (%s <>1 and ifnull(isPDF,0)=0))  
							union all
							SELECT trackISRCs.assetFile,albumsTracks.trackOrder,0 FROM albumsTracks
							inner join trackISRCs on trackISRCs.trackID=albumsTracks.trackID and trackISRCs.isrcType=1
							where 
							albumsTracks.albumID=%s and %s=1) der
								order by der.trackOrder asc;", ($albumId),$platformId,$platformId,$platformId,$platformId,$albumId,$platformId);
		} elseif ($contentStatus==9) {
			$query = sprintf("select * from (SELECT tracks.assetFile,a.trackOrder,tracks.isPDF FROM albumsTracks a 
														inner join tracks on tracks.ID=a.trackID
															inner join assetChanges b on a.trackID=b.trackID and b.changeType=2 and b.isDeliver=0
															WHERE a.albumID = %s 
							union all
							SELECT trackISRCs.assetFile,albumsTracks.trackOrder,0 FROM albumsTracks
							inner join trackISRCs on trackISRCs.trackID=albumsTracks.trackID and trackISRCs.isrcType=1
							inner join assetChanges on albumsTracks.trackID=assetChanges.trackID and assetChanges.changeType=3 and assetChanges.isDeliver=0
															WHERE albumsTracks.albumID = %s and %s=1) der 							
							order by der.trackOrder asc;", $albumId,$albumId,$platformId);
	} else {
		exit();
	}
	$result = $a->executenonquery($query,null,true);

	while(list ($mp3File,$position,$isPDF) = mysqli_fetch_array($result)){
		// mehmet ile gorus
		$uploadUrl="/assets/uploads/";
		
		if ($isOld==1) {
			$orginalTrackFile = $uploadUrl.$userid."/".$contentId."/audio/".$mp3File;
		} else {
			if ($isPDF==1) {
				$orginalTrackFile = $uploadUrl."customers/".$user->customerID."/".$contentId."/".$mp3File;
			} else {
				$orginalTrackFile = $uploadUrl."customers/".$user->customerID."/tracks/".$mp3File;
			}
		}

		//$orginalTrackFile = $uploadUrl.$userid."/".$contentId."/audio/".$mp3File;
		
		if ($platformId==5 || $platformId==21) {
			$deliveryTrackFile = $productFolder."/resources/".str_replace(".wav", ".flac", $mp3File);
			convertToFlac($orginalTrackFile,$deliveryTrackFile);
		} elseif ($platformId==17) {
			$deliveryTrackFile = $productFolder."/".str_replace(".wav", ".flac", $mp3File);
			convertToFlac($orginalTrackFile,$deliveryTrackFile);
		} elseif ($platformId==4 || $platformId==9) {
			$dFile = explode(".", $mp3File);
			$deliveryTrackFile = $productFolder."/".$upc.'_1_'.str_pad($position, 2, "0", STR_PAD_LEFT).'.'.$dFile[1];
			copy($orginalTrackFile,$deliveryTrackFile);
		/*} elseif ($platformId==2) {
			$dFile = explode(".", $mp3File);
			$deliveryTrackFile = $productFolder."/".$upc.'_01_'.str_pad($position, 2, "0", STR_PAD_LEFT).'.'.$dFile[1];
			copy($orginalTrackFile,$deliveryTrackFile);*/
		} elseif ($platformId==2) {
			//$dFile = explode(".", $mp3File);
			if ($ct==5) {
				$deliveryTrackFile = $productFolder."/resources/".$upc.'_01_'.str_pad($position, 3, "0", STR_PAD_LEFT).'.mov';
				copy($orginalTrackFile,$deliveryTrackFile);
			} else {
				$deliveryTrackFile = $productFolder."/resources/".$upc.'_01_'.str_pad($position, 3, "0", STR_PAD_LEFT).'.flac';
				convertToFlac($orginalTrackFile,$deliveryTrackFile);
			}
		} elseif ($platformId==7) {
			if ($ct!=5) {
				$deliveryTrackFile = $productFolder."/mp3/".$upc.'_'.str_pad($position, 2, "0", STR_PAD_LEFT).'.mp3';
				$deliveryTrackFilePre = $productFolder."/prelisten/".$upc.'_'.str_pad($position, 2, "0", STR_PAD_LEFT).'.mp3';
				convertTomp3H($orginalTrackFile,$deliveryTrackFile);
				createPrelisten($orginalTrackFile,$deliveryTrackFilePre);
			} else {
				$deliveryTrackFile = $productFolder."/mp3/".$upc.'_'.str_pad($position, 2, "0", STR_PAD_LEFT).'.mp4';
				$deliveryTrackFilePre = $productFolder."/prelisten/".$upc.'_'.str_pad($position, 2, "0", STR_PAD_LEFT).'.mp4';
				//$deliveryTrackFile = $productFolder."/".str_replace(".mov", ".mp4", $mp3File);
				convertTomp4($orginalTrackFile,$deliveryTrackFile,$uploadUrl);
				createPrelistenmp4($orginalTrackFile,$deliveryTrackFilePre);
			}
		} elseif ($platformId==22) {
			$deliveryTrackFile = $productFolder."/resources/".str_replace(".wav", ".mp3", $mp3File);
			convertTomp3($orginalTrackFile,$deliveryTrackFile);
		} elseif ($platformId==8) {
			if ($ct!=5) {
				$deliveryTrackFile = $productFolder."/".str_replace(".wav", ".mp3", $mp3File);
				convertTomp3($orginalTrackFile,$deliveryTrackFile);
			} else {
				$deliveryTrackFile = $productFolder."/".str_replace(".mov", ".mp4", $mp3File);
				convertTomp4($orginalTrackFile,$deliveryTrackFile,$uploadUrl);
				//$deliveryTrackFile = $productFolder."/".$mp3File;
				//copy($orginalTrackFile,$deliveryTrackFile);
			}
		} else
		{
			$deliveryTrackFile = $productFolder."/".$mp3File;
			copy($orginalTrackFile,$deliveryTrackFile);
		}
	}	    
}

    function copyAlbumArt($albumId,$productFolder,$platformId,$contentStatus=4,$uploadUrl){
        $c = new contents();
        if ($contentStatus ==4 ) {
            $query = sprintf("SELECT a.artFile, a.contentID, b.userID,a.upc,ifnull(a.artFileMD5,''),ifnull(a.artFileSize,''),b.contentType,b.isOld
                FROM albums a,contents b WHERE a.contentID = b.ID AND a.ID = %s;", $albumId);
        } elseif ($contentStatus==9) {
            $query = sprintf("SELECT a.artFile, a.contentID, b.userID,a.upc,ifnull(a.artFileMD5,''),ifnull(a.artFileSize,''),b.contentType,b.isOld 
                                                                    FROM albums a 
                                                                    inner join contents b on a.contentID = b.ID 
                                                                    inner join assetChanges c on a.ID = c.albumID and c.changeType=1 and c.isDeliver=0
                                                                    WHERE a.ID = %s;", ($albumId));
        } else {
            exit();
        }
    
        $counts = $c->executenonquery($query);
        list($artFile, $contentId, $userid,$upc,$aMD5,$aSize,$contentType,$isOld) = mysqli_fetch_array($counts);
        $user = new users($userid);

		$uploadUrl="/assets/uploads/";
		if ($isOld==1) {
			$orginalArtFile = $uploadUrl.$userid."/".$contentId."/".$artFile;
		} else {

			$orginalArtFile = $uploadUrl."customers/".$user->customerID."/".$contentId."/".$artFile;
		}
		
        //$orginalArtFile = $uploadUrl.$userid."/".$contentId."/".$artFile;
    
        if ($platformId!=5 && $platformId!=21 && $platformId!=22) {
            if ($platformId==4 || $platformId==8 || $platformId==7 || $platformId==9 || $platformId==14 || $platformId==2) {
                if ($platformId==8 && $contentType==5) {
                    $dFile = explode(".", $artFile);
                    $deliveryArtFile = $productFolder."/".$upc.'C.'.$dFile[1];
				} elseif ($platformId==2) {
					$dFile = explode(".", $artFile);
					$deliveryArtFile = $productFolder."/resources/".$upc.'.'.$dFile[1];
                } else {
                    $dFile = explode(".", $artFile);
                    $deliveryArtFile = $productFolder."/".$upc.'.'.$dFile[1];
                }
            } else {
                $deliveryArtFile = $productFolder."/".$artFile;
            }
        } else {
            $deliveryArtFile = $productFolder."/resources/".$artFile;
		}
		//echo "*****".dirname(__FILE__).$orginalArtFile." - ".$deliveryArtFile."****";
		//$orginalArtFile=dirname(__FILE__).$orginalArtFile;
		
		copy($orginalArtFile,$deliveryArtFile);
		
		$md5 = hash_file('md5', $deliveryArtFile);
		$fileSize = filesize ($deliveryArtFile);
		if ($fileSize>0) {
			$sql = "update albums set artFileMD5='".$md5."',artFileSize=".$fileSize." where albums.ID=".$albumId;
			$c->executenonquery($sql,null,true);
		}	
        
    }

    function makeDir($path,$rights=0775){
        return is_dir($path) || mkdir($path, $rights, false);
    }

    function rrmdir($dir) {
        if (is_dir ( $dir )) {
            $objects = scandir ( $dir );
            foreach ( $objects as $object ) {
                if ($object != "." && $object != "..") {
                    if (filetype ( $dir . "/" . $object ) == "dir")
                        rrmdir ( $dir . "/" . $object );
                    else
                        unlink ( $dir . "/" . $object );
                }
            }
            reset ( $objects );
            rmdir ( $dir );
        }
    }

    function copyAlbumXML($XMLId,$productFolder,$platformId,$upc){
        $c = new contents();
        $query = sprintf("SELECT d.XMLvalue,contents.contentType,d.batchLocation FROM deliveries d 
                                                inner join albums on albums.ID=d.albumID
                                                inner join contents on contents.ID=albums.contentID
                                    WHERE d.ID = %s;", $XMLId);	
    
        $result = $c->executenonquery($query,null,true);
        list($content,$ct,$bl) = mysqli_fetch_array($result);
    
        
        if ($platformId==5 || $platformId==21 || $platformId==22 || $platformId==2) {
            $fileName = $productFolder."/".$upc.".xml";
        } elseif ($platformId==4 || $platformId==8 || $platformId==7 || $platformId==9 || $platformId==14) {
            $fileName = $productFolder."/".$upc.".xml";
        } else {
            $fileName = $productFolder."/metadata.xml";
        }
        if(file_exists($fileName)){
            unlink($fileName);
        }
        $f = fopen($fileName, 'a');
        fwrite($f, $content);
        fclose($f);
    
    }

    function updateContentStatusNew ( $contentId,$status,$deliveryId,$platformId,$userId,$sessionId = NULL) {
        $cs = new contentStatus();
        $cs->platformID=$platformId;
        $cs->contentID=$contentId;
        $cs->deliveryID=$deliveryId;
        $cs->status = $status;
		$cs->userID=0;
		$cs->dateCreated=date("Y-m-d H:i:s");
        $cs->save();
    }

    function updateContentStatus($contentId,$status){
        if ($contentId>0) {
            $cn = new contents($contentId);
            $cn->dateModified=date("Y-m-d H:i");
            $cn->contentStatus=$status;
            $cn->save();
        }
    }

    function updateDeliveriesBatchLoc($XMLId, $shortBatchFolder){
        $c = new contents();
        $query = sprintf("UPDATE deliveries SET batchLocation = '%s' WHERE ID = %s;", $shortBatchFolder, $XMLId);
        $result = $c->executenonquery($query,null,true);
    }

    function packIt($contentId,$platformId,$contentStatus=4,$contentType=1,$uploadUrl){
	
        //this one is called when content status = 4
        $content = new contents();
        $album = albums::getAlbumByContentID($contentId);
        $albumId = $album->ID;
        $upc =  $album->upc;
        
        if (!is_dir(DIRNAME(DIRNAME(__FILE__))."/deliveries/".$platformId)) {
            makeDir(DIRNAME(DIRNAME(__FILE__))."/deliveries/".$platformId);
        }
        
		/*
        if ($platformId==2) {
                $sql = "select (count(*)+1) from platformDeliveryCounts where platformID=2 and date(date_)=date(now())";
                $result = $content->executenonquery($sql);
                list($dc)=mysqli_fetch_array($result);
                
                $shortBatchFolder = "/".date("Ymd")."_".str_pad($dc, 2, "0", STR_PAD_LEFT);
        } else {
		*/
            $shortBatchFolder = "/".date("YmdHis").rand(100,999);
         /*   }*/
        
        $batchFolder = DIRNAME(DIRNAME(__FILE__))."/deliveries/".$platformId.$shortBatchFolder;
        if ($platformId==10 || $platformId==17) {
            $productFolder = $batchFolder;
        } else {
            $productFolder = $batchFolder."/".$upc;
        }
        
            echo "Lets create folders: $batchFolder";
            makeDir($batchFolder);
            makeDir($productFolder);
            if ($platformId==5 || $platformId==21 || $platformId==22 || $platformId==2) {
                $resourceFolder = $productFolder."/resources";
                makeDir($resourceFolder);
            }
            if ($platformId==7) {
                $resourceFolder = $productFolder."/mp3";
                makeDir($resourceFolder);
                
                $resourceFolder = $productFolder."/prelisten";
                makeDir($resourceFolder);
            }
        
    
            
            if ($contentStatus!=8) {
                //copy albumArt
                
                if ($platformId!=10) {
                    copyAlbumArt($albumId,$productFolder,$platformId,$contentStatus,$uploadUrl);
                }
                //copy audio files
                if ($contentType!=3) {
                  copyAlbumTracks($albumId,$productFolder,$platformId,$upc,$contentStatus,$uploadUrl);
                } else {
                  copyLocalizedFiles($albumId,$productFolder,$platformId,$upc,$contentStatus,$uploadUrl);  
                }
            }
            
            $XMLId = createXML($albumId,$platformId,$shortBatchFolder,$upc,$contentStatus,$uploadUrl);
            /*
            if ($contentType!=3) {
                $XMLId = createXML($albumId,$platformId,$shortBatchFolder,$upc,$contentStatus);
            } else {
               $XMLId = createBookXML($albumId,$platformId,$shortBatchFolder,$upc,$contentStatus);
            }
            */
            if ($contentStatus!=8) {
                $query = sprintf("UPDATE contentStatus SET contentStatus.deliveryID = $XMLId WHERE contentStatus.contentID = $contentId and contentStatus.platformID=$platformId and contentStatus.status=5;");
                //echo $query;
                $result = $content->executenonquery($query,NULL,true);
            }
            copyAlbumXML($XMLId, $productFolder,$platformId,$upc);
            updateDeliveriesBatchLoc($XMLId, $shortBatchFolder);
    
        //create delivery.complete in batch Folder if platform is Spotify, Deezer (NOT iTunes)
        if (($platformId!=1) && ($platformId!=7) && ($platformId!=14) ){
            $deliveryCompleteTemplateLocation = dirname(dirname(__FILE__))."/deliveries/delivery.complete";
            if($platformId == 4 || $platformId == 9){//if the platform is Fuga, the file will be called zzz.complete
                $deliveryCompleteLocation = $batchFolder."/".$upc."/".$upc.".complete";
            } elseif ($platformId==5 || $platformId==21 || $platformId==22 || $platformId==8) {
                    $deliveryCompleteLocation = $batchFolder."/BatchComplete_".str_replace("/", "", $shortBatchFolder).".xml";
            } else {
                $deliveryCompleteLocation = $batchFolder."/delivery.complete";
            }
            //echo $deliveryCompleteTemplateLocation;
            //echo $deliveryCompleteLocation;
            copy($deliveryCompleteTemplateLocation,$deliveryCompleteLocation);
        }
    
    }

	function copyBatchCompletedXML($batchFolder,$platformId,$upc){
	
		echo $batchFolder."-".$upc;
		$md5 = hash_file('md5', $batchFolder."/".$upc."/".$upc.".xml");
	
		$content = '<?xml version="1.0" encoding="UTF-8"?>';
		$content = $content .'<echo:ManifestMessage MessageVersionId="1.2" xs:schemaLocation="http://ddex.net/xml/2011/echo/12 http://ddex.net/xml/2011/echo/12/echo.xsd">';
		$content = $content .'<MessageHeader>';
		$content = $content .'<MessageSender>';
		$content = $content .'<PartyId>PADPIDA2014051405T</PartyId>';
		$content = $content .'<PartyName>';
		$content = $content .'<FullName>Eglence Fabrikasi</FullName>';
		$content = $content .'</PartyName>';
		$content = $content .'</MessageSender>';
		$content = $content .'<MessageRecipient>';
		$content = $content .'<PartyId>PADPIDA2011072101T</PartyId>';
		$content = $content .'<PartyName>';
		$content = $content .'<FullName>Spotify</FullName>';
		$content = $content .'</PartyName>';
		$content = $content .'</MessageRecipient>';
		$content = $content .'<MessageCreatedDateTime>'.date("Y-m-d")."T".date("H:i:s").date("T").':00</MessageCreatedDateTime>';
		$content = $content .'</MessageHeader>';
		$content = $content .'<IsTestFlag>false</IsTestFlag>';
		$content = $content .'<RootDirectory>/'.substr($batchFolder,-17).'</RootDirectory>';
		$content = $content .'<NumberOfMessages>1</NumberOfMessages>';
		$content = $content .'<MessageInBatch>';
		$content = $content .'<MessageType>NewReleaseMessage</MessageType>';
		$content = $content .'<MessageId>'.($upc.rand(1000,9999)).'</MessageId>';
		$content = $content .'<URL>/'.substr($batchFolder,-17)."/".$upc."/".$upc.'.xml</URL>';
		$content = $content .'<IncludedReleaseId>';
		$content = $content .'<GRid>'.$upc.'</GRid>';
		$content = $content .'</IncludedReleaseId>';
		$content = $content .'<DeliveryType>NewReleaseDelivery</DeliveryType>';
		$content = $content .'<ProductType>AudioProduct</ProductType>';
		$content = $content .'<HashSum>';
		$content = $content .'<HashSum>'.$md5.'</HashSum>';
		$content = $content .'<HashSumAlgorithmType>MD5</HashSumAlgorithmType>';
		$content = $content .'</HashSum>';
		$content = $content .'</MessageInBatch>';
		$content = $content .'</echo:ManifestMessage>';
	
	
		$fileName = $batchFolder."/BatchComplete_".substr($batchFolder,-17).".xml";
		if ($platformId==2) {
			$fileName = $batchFolder.'/BatchComplete_'.substr($batchFolder,-17).".xml";
		} 
		echo $fileName;
		if(file_exists($fileName)){
			unlink($fileName);
		}
		$f = fopen($fileName, 'a');
		fwrite($f, $content);
		fclose($f);
	
	}
?>
