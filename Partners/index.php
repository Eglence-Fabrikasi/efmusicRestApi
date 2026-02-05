<?php
/*
    EF Digital API Partners Module
    Start Date : 2026-02-03
    Updated: 2026-02-03
*/

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
ini_set('memory_limit', '-1');
//error_reporting(E_ALL);

date_default_timezone_set('Europe/Istanbul');
setlocale(LC_ALL, "tr_TR");

use Slim\Factory\AppFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Monolog\Logger;
use \Dotenv\Dotenv;


// standard libraries
require_once dirname(dirname(__FILE__)) . "/vendor/autoload.php";
//require_once dirname(dirname(__FILE__)) . "/Library/tuupolaBasic/autoload.php";
require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";
require_once dirname(dirname(__FILE__)) . "/BL/communication.php";
require_once dirname(dirname(__FILE__)) . "/BL/Tables/localization.php";
require_once dirname(dirname(__FILE__)) . "/BL/token.php";
require_once dirname(dirname(__FILE__)) . "/BL/jwtMiddle.php";

$app = AppFactory::create();
$app->setBasePath('/Partners');
$app->addRoutingMiddleware();

$app->map(['OPTIONS'], '/{routes:.+}', function ($request, $response) {
    $response = $response
        ->withHeader('Access-Control-Allow-Origin', 'https://core.eglencefabrikasi.com')
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');

    return $response;
});

// error handler
$customErrorHandler = function (
    Request $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails,
    ?Logger $logger = null
) use ($app) {
    if ($logger) {
        $logger->error($exception->getMessage());
    }
    $payload = ['error' => $exception->getMessage()];

    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write(
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );

    return $response;
};

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setDefaultErrorHandler($customErrorHandler);

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$secretKey = $_ENV["SECRET_KEY"];

$app->post('/login', function ($request, $response, array $args) {
    $json = $request->getBody();
    $data = json_decode($json, true);
    if (ltrim($data["email"]) != "" && ltrim($data["password"]) != "" && ltrim($data["hash"]) != "") {
        $user = users::getAuth($data["email"], $data["password"]);
        if ($user->ID > 0) {
            $signature = hash_hmac('sha256', (isset($user->password) ? $user->password : ""), $_ENV["SECRET_KEY"], true);
            $signatureHex = bin2hex($signature);
            if ($signatureHex == $data["hash"]) {
                $token = new token();
                // Cookie set etme
                //setcookie('auth_token', $token->generateToken($user->ID), (time() + 3600), "/", "", true, true);
                $tokenValue = $token->generateToken($user->ID);

                $cookie = "auth_token={$tokenValue}"
                    . "; Expires=" . gmdate('D, d-M-Y H:i:s T', time() + 3600)
                    . "; Path=/"
                    . "; Secure"
                    . "; HttpOnly"
                    . "; SameSite=None";

                header("Set-Cookie: $cookie", false);
                $qResponse = array("result" => "success", "cookie" => "yes", "user" => array("userID" => $user->ID, "customerID" => $user->customerID, "channelID" => $user->channelID));
            } else {
                $qResponse = array("result" => "error", "error" => "Check Hash Value!");
            }
        } else {
            $qResponse = array("result" => "error", "error" => "Check User and password!");
        }
    } else {
        $qResponse = array("result" => "error", "error" => "Check variables!");
    }

    $qResponse = json_encode($qResponse);
    if ($qResponse === false) {
        error_log("JSON encode error: " . json_last_error_msg());
        $qResponse = json_encode(["error" => "JSON encode failed"]);
    }

    $response->getBody()->write($qResponse);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/albums/save/{albumID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albums.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contents.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/artists.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/artistAlbums.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contentstatus.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/tracks.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/artistTracks.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albumstracks.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/trackISRCs.php";

    $json = $request->getBody();
    $data = json_decode($json, true);
    if (isset($data[0])) {
        $artistAlbums = $data[0]["artistAlbums"];
        $data = $data[0]["album"];
        $jwt = $request->getAttribute('jwt');
        $userID = $jwt->user_id;
        $userjwt = new users($userID);
        $customerID = $userjwt->customerID;
        $album = new albums($args["albumID"]);
        $content = new contents($album->contentID);
        $user = new users($content->userID);
        if (($args["albumID"] > 0 && $customerID == $user->customerID) || $args["albumID"] == 0) {
            $content = contents::getContentFromAlbum($args["albumID"]);
            $content->contentStatus = 1;
            $content->isOld = 0;
            $content->contentType = $data['contentType'];
            $content->contentSubTypeID = $data['contentSubTypeID'];
            $content->userID =  $userID;
            if (!$content->ID > 0) {
                $content->dateCreated = date("Y-m-d H:i:s");
                $content->createdBy = $userID;
            }
            $content->dateModified = date("Y-m-d H:i:s");
            $content->modifiedBy = $userID;
            $contentID = $content->save();

            $album = new albums($args["albumID"]);
            $album->contentID = $contentID;
            $album->upc = rtrim(ltrim($data['upc']));
            if (isset($data['artFile'])) {
                $album->artFile = $data['artFile'];
            }
            $album->artFileMD5 = '';
            $album->artFileSize = 0;
            $album->title = trim($data['title']);
            $album->description = trim($data['description']);
            $album->languageID = $data['languageID'];
            $album->genreID = $data['genreID'];
            $album->subgenreID = $data['subgenreID'];
            $album->copyright = trim($data['copyright']);
            $album->releaseDate = $data['releaseDate'];
            if (isset($data['prevReleased'])) {
                $album->prevReleased = $data['prevReleased'];
            }
            if (isset($data['pricing'])) {
                $album->pricing = $data['pricing'];
            }
            $album->salesStartDate = $data['salesStartDate'];
            $album->status = 0;
            if (isset($data['preorder'])) {
                $album->preorder = $data['preorder'];
            }
            if (isset($data['countryID'])) {
                $album->countryID = $data['countryID'];
            }
            $album->titleVersion = trim($data['titleVersion']);
            if (isset($data['preorderDate'])) {
                $album->preorderDate = $data['preorderDate'];
            }
            $album->labelName = trim($data['labelName']);
            $album->vendorID = $data['vendorID'];
            if (isset($data['mfit'])) {
                $album->mfit = $data['mfit'];
            }
            if (isset($data['allowPreorderPreview'])) {
                $album->allowPreorderPreview = $data['allowPreorderPreview'];
            }
            if (isset($data['tags'])) {
                $album->tags = $data['tags'];
            }
            if (isset($data['imprint'])) {
                $album->imprint = $data['imprint'];
            }
            if (isset($data['printLength'])) {
                $album->printLength = $data['printLength'];
            }
            if (isset($data['explicit'])) {
                $album->explicit = $data['explicit'];
            }
            $album->isCompilation = $data['isCompilation'];
            $album->numberOfVolumes = $data['numberOfVolumes'];
            $album->isReleaseLabel = $data['isReleaseLabel'];
            $albumID = $album->save();

            if ($albumID > 0) {
                $sql = "delete from artistAlbums where albumID=" . $albumID;
                $album->executenonquery($sql, null, true);

                foreach ($artistAlbums as $artistAlbum) {
                    // if ($artistAlbum["isNeworUpdate"] > 0) {
                    $artistID = 0;
                    if (!$artistAlbum["artistID"] > 0) {
                        $getID = artists::getIsArtisID(trim($artistAlbum['name']));
                        $artistID = $getID->ID;
                        if (!$artistID > 0) {
                            $newArtist = new artists();
                            $newArtist->name = trim($artistAlbum['name']);
                            $newArtist->dateCreated = date('Y-m-d H:i:s');
                            $newArtist->createdBy = $userID;
                            $artistID = $newArtist->save();
                        }
                    } else {
                        $artistID = $artistAlbum["artistID"];
                    }
                    if ($artistID > 0) {
                        $albumArtist = new artistAlbums(0);
                        $albumArtist->albumID = $albumID;
                        $albumArtist->artistID = $artistID;
                        $albumArtist->roleID = $artistAlbum['roleID'];
                        $albumArtist->primary = $artistAlbum['primary'];;
                        $albumArtist->artistType = 0;
                        $albumArtist->userID = $userID;
                        $albumArtist->save();
                    }
                }
                if (isset($data["imageData"])) {
                    uploadImage($data["imageData"], $userID, $contentID, $albumID, $contentType, 0);
                }

                // insert tracks
                foreach ($data['tracks'] as $trackData) {
                    $track = new tracks();
                    $track->title = trim($trackData['title']);
                    $track->isrc = trim($trackData['isrc']);
                    if (isset($trackData['assetFile'])) {
                        $track->assetFile = $data['assetFile'];
                    }
                    $track->genreID = $trackData['genreID'];
                    $track->explicit = $trackData['explicit'];
                    $track->pricing = $trackData['pricing'];
                    $track->subgenreID = $trackData['subgenreID'];
                    $track->copyright = trim($trackData['copyright']);
                    if (isset($trackData['isPDF'])) {
                        $track->isPDF = $trackData['isPDF'];
                    }
                    $track->trackVersion = trim($trackData['trackVersion']);
                    $track->trackLabel = trim($trackData['trackLabel']);
                    $track->lyrics = trim($trackData['lyrics']);
                    if (isset($trackData['lrc'])) {
                        $track->lrc = $trackData['lrc'];
                    }
                    $track->lp = $trackData['lp'];
                    $track->lpCountry = $trackData['lpCountry'];
                    if (isset($trackData['previewTime'])) {
                        $track->previewTime = $trackData['previewTime'];
                    }
                    $track->djmixes = isset($trackData["djmixes"]) ? $trackData["djmixes"] : 0;
                    $track->avRating = isset($trackData["avRating"]) ? $trackData["avRating"] : 0;
                    $track->status = 1;
                    $trackID = $track->save();

                    foreach ($trackData["trackArtists"] as $trackArtist) {
                        $artistID = 0;
                        $newArtist = artists::getArtistFromName(trim($trackArtist['name']));
                        if ($newArtist->ID > 0) {
                            $artistID = $newArtist->ID;
                        } else {
                            $newArtist->name = trim($trackArtist['name']);
                            $newArtist->dateCreated = date('Y-m-d H:i:s');
                            $newArtist->createdBy = $userID;
                            $newArtist->appleID = trim($trackArtist['appleID']);
                            $newArtist->spotifyID = trim($trackArtist['spotifyID']);
                            $artistID = $newArtist->save();
                        }

                        $artistTrack = new artistTracks($trackArtist["ID"]);
                        $artistTrack->trackID = $trackID;
                        $artistTrack->artistID = $artistID;
                        $artistTrack->userID = $userID;
                        $artistTrack->roleID = $trackArtist["roleID"];
                        $artistTrack->primary = $trackArtist["primary"];
                        $artistTrackID = $artistTrack->save();
                        //}
                    }
                    if ($trackID > 0) {
                        $sql = "delete from albumsTracks where albumID=" . $albumID;
                        $album->executenonquery($sql, null, true);

                        $albumsTracks = new albumsTracks();
                        $albumsTracks->albumID =  $albumID;
                        $albumsTracks->trackID = $trackID;
                        $albumsTracks->userID = $userID;
                        $albumsTracks->trackOrder = $data['trackOrder'];
                        $albumsTracks->save();

                        isset($data['contentType']) ? $contentType = $data['contentType'] : $contentType = 0;
                        isset($data['isOld']) ? $isOld = $data['isOld'] : $isOld = 0;
                        $uID = 1;
                        if ($args["uploadType"] == 'Booklet') {
                            uploadBooklet($trackArtist["assetData"], $userID, $contentID, $albumID, $isOld);
                        } else if ($args["uploadType"] == 'Image') {
                            uploadImage($trackArtist["assetData"], $userID, $contentID, $albumID, $contentType, $isOld);
                        } else if ($args["uploadType"] == 'Music') {
                            uploadMusic($trackArtist["assetData"], $userID, $contentID, $isOld);
                        } else {
                            $uID = -1;
                        }
                    }
                }
            }
            if ($albumID > 0) {
                $cntStatus = new contentStatus();
                $cntStatus->contentID = $contentID;
                $cntStatus->deliveryID = 0;
                $cntStatus->status = 1;
                $cntStatus->platformID = 0;
                $cntStatus->dateCreated = date('Y-m-d H:i:s');
                $cntStatus->userID = $userID;
                $cntStatus->sessionID = '';
                $cntStatus->save();
            }
            $data = '{"contentID":' . $contentID . ', "albumID" : ' . $albumID . '}';
        } else {
            $data = null;
        }
    } else {
        $data = null;
    }
    $response->getBody()->write($data);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/definitions/{tableName}/{fieldName}[/{fieldID}/{filter}]', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/services.php";

    $tableNames = ["genres", "country", "languages", "artistRoles"];
    $btResponse = null;
    if (in_array($args["tableName"], $tableNames)) {
        $bt = new services();
        $fieldID = isset($args["fieldID"]) ? $args["fieldID"] : "ID";
        $filter = isset($args["filter"]) ? $args["filter"] : "0=0";
        $filter =  str_replace('@', ' ', $filter);
        $filter =  str_replace('!', '%', $filter);
        $sql = "select " . $fieldID . "," . $bt->checkInjection($args["fieldName"]) . " from " . $bt->checkInjection($args["tableName"]) . " where " . $bt->checkInjection($filter) . " order by " . $bt->checkInjection($args["fieldName"]);
        //echo $sql;
        $btResult = $bt->executenonquery($sql, true);
        $btResponse = checkNull($bt->toJson);
    }
    $response->getBody()->write($btResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->run();

function checkNull($value)
{
    if ($value == "null") {
        return "{'No record'}";
    } else {
        return $value;
    }
}
