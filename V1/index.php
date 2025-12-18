<?php
/*
    EF Digital API V2
    Start Date : 2018-12-04
    Updated: 2025-11-05

    Admin Functions

    /search/{searchText}/{searchType}
    /deliver/{UPC}/{platformID} //PlatformID is optional
    /status/album/{UPC} //returns status, content and delivery errors

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
$app->setBasePath('/V1');
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
                $qResponse = array("result" => "success", "cookie" => "yes", "user" => json_decode($user->toJson()));
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

$app->get('/menus', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/menus.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $menus = new menus();
    if ($userID > 0) {
        $menuResult = $menus->getMenus($userID);
    } else {
        $menuResult = $menus->getMenus(0);
    }
    $menuResponse = checkNull($menus->toJson);
    $response->getBody()->write($menuResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

//Customer Related
/*
/customers/info/{customerID}
/customers/save/{customerID}
/customers/delete/{customerID}
*/

$app->get('/customers/info[/{customerID}]', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/customers.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;

    $user = new users($userID);
    $customerResponse = null;
    if ($args["customerID"] == $customerID || $user->roleID == 1 || $user->roleID == 2) {
        $customers = new customers();
        if (isset($args["customerID"])) {
            $customerResult = $customers->getCustomer($args["customerID"], $userID);
        } else {
            if ($user->roleID == 1) {
                $customerResult = $customers->getCustomer(0, $userID);
            }
        }
        $customerResponse = checkNull($customers->toJson);
    }
    $response->getBody()->write($customerResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/customers/getcustomer[/{customerID}]', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/customers.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $user = new users($userID);
    $customerResponse = null;
    if (($args["customerID"]  ?? 0) == $customerID || $user->roleID == 1 || $user->roleID == 2) {
        $customers = new customers();
        $customerResult = $customers->getcustomer($args["customerID"], $userID);
        $customerResponse = checkNull($customers->toJson);
    }
    $response->getBody()->write($customerResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/customers/save/{customerID}/{userID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/customers.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/customerContracts.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/channels.php";

    $json = $request->getBody();
    $data = json_decode($json, true);
    $data = $data[0];
    $customerResponse = 0;
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $user = new users($userID);
    if ($user->roleID == 1) {
        $user = users::getUserFromUserName($data["username"]);
        if ($args["customerID"] == 0 && $user->ID > 0) {
            $customerResponse = 0;
        } else {
            $customer = new customers($args["customerID"]);
            $customer->customer = $data['customer'];
            $customer->customerType = $data['customerType'];
            $customer->parentCustomerID = $data['parentCustomerID'];
            $customer->countryID = $data['countryID'];
            $customer->paymentCurrency = $data['paymentCurrency'];
            $customer->enableBooklet = $data['enableBooklet'];
            $customer->enableClaims = $data['enableClaims'];
            $customer->enableIndividual = $data['enableIndividual'];
            $customer->channelID = $data['channelID'];
            $customer->phone = $data['phone'];
            $customer->address = $data['address'];
            $customer->bankName = $data['bankName'];
            $customer->bankIban = $data['bankIban'];
            $customer->twitterUrl = $data['twitterUrl'];
            $customer->facebookUrl = $data['facebookUrl'];
            $customer->instagramUrl = $data['instagramUrl'];
            $customerResponse = $customer->save();

            // customerContracts
            if ($customerResponse > 0) {
                $customerContract = new customerContracts($data["customerContractID"]);
                $customerContract->term = $data['term'];
                $customerContract->dealTermID = $data['dealTermID'];
                $customerContract->termDate = $data['termDate'];
                $customerContract->contractID = $data['contractID'];
                $customerContract->customerID = $customerResponse; //$args["customerID"];
                $customerContract->commissionID = $data['commissionID'];
                $customerContract->endBy = userID;
                if ($data["endContract"] == false) {
                    $customerContract->endDate = "NULL";
                    $customerContract->endDescription = "NULL";
                } else {
                    $customerContract->endDate = $data['endDate'];
                    $customerContract->endDescription = $data['endDescription'];
                }
                $customerContract->save();

                if ($args["customerID"] == 0) {
                    $user = users::getUserFromUserName($data["username"]);

                    $password = $user->randomPassword();
                    $user->username = $data['username'];
                    $user->fullname = $data['fullname'];
                    $user->email = $data['email'];
                    $user->password = $password;
                    $user->roleID = 6;
                    $user->clang = ($data['countryID'] == 229) ? "tr" : "en";
                    $user->customerID = $customerResponse;
                    $user->userType = 1;
                    $user->phone = $data['phone'];
                    $user->address = $data['address'];
                    $user->countryID = $data["countryID"];
                    $user->createdBy = userID;
                    $userID = $user->save();
                    if ($userID > 0) {
                        $u = new users($userID);
                        $c = new customers($u->customerID);
                        $ch = new channels($c->channelID);

                        $loc = new localization();
                        $lang = ($data['countryID'] == 229) ? "tr" : "en";
                        $subject = $loc->label("welcome", $lang);
                        $body = str_replace("@@password", $password, str_replace("@@username", $data['username'], $loc->label("welcomeEmail", $lang)));
                        $body = str_replace("@@link", $ch->appUrl, $body);
                        $body = str_replace("@@brand", $ch->channel, $body);
                        $mail = new Mail($data['fullname'], $data['username'], userID, $subject, $body, null, 1, $c->channelID);
                        $mail->sendQueue();
                    }
                }
            }
        }
    }

    $response->getBody()->write($customerResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/customers/setting/save/{customerID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/customers.php";

    $json = $request->getBody();
    $data = json_decode($json, true);
    $data = $data[0];

    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $customerResponse = 0;
    if ($customerID == ($args["customerID"] ?? 0)) {
        $customer = new customers($args["customerID"]);
        $customer->paymentCurrency = $data['paymentCurrency'];
        $customer->phone = $data['phone'];
        $customer->bankIban = $data['bankIban'];
        $customer->twitterUrl = $data['twitterUrl'];
        $customer->facebookUrl = $data['facebookUrl'];
        $customer->instagramUrl = $data['instagramUrl'];
        $customerResponse = $customer->save();
    }

    $response->getBody()->write($customerResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/customers/upgradecontract/{userID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/customers.php";

    $json = $request->getBody();
    $data = json_decode($json, true);
    $data = $data[0];
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $user = new users($userID);
    $customerResponse = 0;
    if ($userID == (int)$args["userID"] || $user->roleID == 1) {
        $user = new users($args["userID"]);

        $customer = new customers($user->customerID);
        $customerResponse = $customer->save();

        if (isset($data["superSead"])) {
            $superSead = $data["superSead"];
            if ($superSead == 1) {
                //kullanicinin kontrati supersead ediliyor
                $customer->customerContractSuperSead($args["userID"], $user->customerID, $data['contractID']);
            } else {  // = 2
                //kullanicinin kontrati imzalaniyor
                $customer->customerContractSign($user->customerID, $data['contractID']);
            }
        } else {
            $customer->customerContractUpgrade($args["userID"], $user->customerID, $data['contractID']);
        }
    }

    $response->getBody()->write($customerResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/customers/delete/{customerID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/customers.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $user = new users($userID);
    $customerResponse = 0;
    if ($user->roleID == 1) {
        $customer = new customers($args["customerID"]);
        $customerResponse = $customer->delete();
    }
    $response->getBody()->write($customerResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/customers/users[/{customerID}]', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/customers.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $customerResponse = null;
    $user = new users($userID);
    if ($customerID == (isset($args["customerID"]) ? $args["customerID"] : 0) || $user->roleID == 1) {
        $customers = new customers();
        if (isset($args["customerID"])) {
            $customerResult = $customers->getCustomerUsers($args["customerID"]);
        } else {
            if ($user->roleID == 1) {
                $customerResult = $customers->getCustomerUsers(0);
            }
        }
        $customerResponse = checkNull($customers->toJson);
    }
    $response->getBody()->write($customerResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

// User Related
// Users are assigned to Customers/Labels,  customers can have subcustomers/sublabels
/*
/users/list
/users/login/{userName}/{password}
/users/forgot/{userName}
/users/info/{userID}
/users/save/{userID}
/users/delete/{userID}
*/

$app->get('/userRoles/{userID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/menuRights.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $usResponse = null;
    $mr = new menuRights();
    $mr->getUserRole($userID);
    $usResponse = checkNull($mr->toJson);

    $response->getBody()->write($usResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/users/list', function ($request, $response, $args) {
    //userID'den customerID alacak ve customer user list getirecek (User List)
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $us = new users();
    $us->getUsers($userID);
    $usResponse = checkNull($us->toJson);
    $response->getBody()->write($usResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

// $app->get('/users/login/{userName}/{password}', function ($request, $response, $args) {
//     require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";
//     $us = users::getAuth($args["userName"], $args["password"]);
//     $usResponse = checkNull($us->toJson());
//     return $response->withStatus(200)
//         ->write($usResponse);
// });

$app->post('/users/login', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";
    $json = $request->getBody();
    $data = json_decode($json, true);

    $us = users::getAuth($data["userName"], $data["password"]);
    $usResponse = checkNull($us->toJson());
    $response->getBody()->write($usResponse);
    return $response->withHeader('Content-Type', 'Application/json')->withStatus(200);
});

$app->get('/users/forgot/{userName}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";
    require_once dirname(dirname(__FILE__)) . "/BL/functions.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;

    $user = users::getUserFromUserName($args["userName"]);
    if ($user->ID > 0 && $userID > 0) {
        $rp = $user->password;

        require_once dirname(dirname(__FILE__)) . "/BL/Tables/customers.php";
        $customer = new customers($user->customerID);
        $body = $rp . " is your password. ";
        $mtResponse = sendEmail($user->ID, $user->fullname, $user->email, "Forgot Password", $body, 1, $customer->channelID);
    } else {
        $mtResponse = 0;
    }
    $response->getBody()->write($mtResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/users/reset/{userName}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";
    require_once dirname(dirname(__FILE__)) . "/BL/functions.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $user = users::getUserFromUserName($args["userName"]);
    if ($user->ID > 0 && $userID > 0) {
        $rp = $user->randomPassword();
        //$user->password = md5($rp);
        $user->password = $rp;
        $mtResponse = checkNull($user->save());

        require_once dirname(dirname(__FILE__)) . "/BL/Tables/customers.php";
        $customer = new customers($user->customerID);
        $body = $rp . " is your new password. ";
        sendEmail($user->ID, $user->fullname, $user->email, "Reset Password", $body, 1, $customer->channelID);
    } else {
        $mtResponse = 0;
    }
    $response->getBody()->write($mtResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/users/info/{userID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $user = new users($userID);
    $user2 = new users($args["userID"]);
    if ($user->roleID == 1 || $user2->customerID == $user->customerID) {
        $us = new users($args["userID"]);
    } else {
        $us = new users($userID);
    }
    $usResponse = checkNull($us->toJson());
    $response->getBody()->write($usResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/creditCardBin/{bin}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/creditCardBins.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/rates.php";

    $us = creditCardBins::pcheckCreditCardBin($args["bin"]);
    if ($us->ID > 0) {
        $rate = rates::getRate(3);
        $usResponse = $rate->exchange;
    } else {
        $usResponse = 1;
    }
    $response->getBody()->write($usResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/users/allinfo/{userID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $user = new users($userID);
    $user2 = new users($args["userID"]);
    if ($user->roleID == 1 || $user2->customerID == $user->customerID) {
        $us = new users($args["userID"]);
        $usresult = $us->getUserByEmail($us->username);
    } else {
        $us = new users($userID);
        $usresult = $us->getUserByEmail($us->username);
    }
    $usResponse = checkNull($us->toJson);
    $response->getBody()->write($usResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/users/info/getbymail/{email}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    if ($userID > 0) {
        $us = new users();
        $usresult = $us->getUserByEmail($args["email"]);
        $usResponse = checkNull($us->toJson);
    } else {
        $usResponse = null;
    }

    $response->getBody()->write($usResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/users/save/{userID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";

    $json = $request->getBody();
    $data = json_decode($json, true);
    $data = $data[0];

    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $user = new users($userID);
    if ($user->ID > 0) {
        $us = new users($args["userID"]);
        $us->username = $data['username'];
        $us->password = $data['password'];
        $us->fullname = $data['fullname'];
        $us->email = $data['email'];
        //$us->customerID = $data['customerID'];
        $us->roleID = $data['roleID'];;
        $us->clang = $data['clang'];
        //$us->contractId = $data['contractId'];
        //$us->channelID = $data['channelID'];
        //$us->countryID = $data['countryID'];
        //$us->paymentCurrency = 1;
        $us->status = $data['status'];
        //$us->address = $data['address'];
        //$us->phone=$data['phone'];
        $us->isDeleted = 0;

        $usResponse = $us->save();

        // Yeni kullanici kaydindan sonra customers'a kayit atiliyor
        if ($args["userID"] == 0 && $usResponse > 0) {
            require_once dirname(dirname(__FILE__)) . "/BL/Tables/userStatusHistory.php";
            $userStatus = new userStatusHistory();
            $userStatus->userID = $usResponse;
            $userStatus->status = 1; // user kaydedildi. Aktivasyon maili bekleniyor.
            $userStatus->ipAdress = $data["clientIP"];
            $userStatus->statusDate = date('Y-m-d H:i:s');
            $userStatus->save();

            require_once dirname(dirname(__FILE__)) . "/BL/Tables/customers.php";
            $customer = new customers();
            $customer->customer = $data['fullname'];
            $customer->customerType = 6; //? 6=admin roles tablosu
            $customer->countryID = $data['countryID'];
            $customer->paymentCurrency = 1; //? 1=TRY currency tablosu
            $customer->enableBooklet = 0; //?
            $customer->channelID = $data['channelID'];
            $customer->isDeleted = 0;
            $newCustomerID = $customer->save();

            $user = new users($usResponse);
            $user->customerID = $newCustomerID;
            $user->save();

            require_once dirname(dirname(__FILE__)) . "/BL/Tables/customerContracts.php";
            $customerContract = new customerContracts();
            $customerContract->customerID = $newCustomerID;
            $customerContract->contractID = $data['contractId'];
            $customerContract->termDate = date('Y-m-d H:i:s');
            $customerContract->term = 1; //?
            $customerContract->commissionID = $data['commissionID'];

            switch ($data['channelID']) {
                case '2':
                    $customerContract->dealTermID = 30;
                    break;
                case '3':
                    $customerContract->dealTermID = 45;
                    break;
                default:
                    # code...
                    break;
            }
            $customerContract->isSent = 1; //?
            $customerContract->isSigned = 0; //? kontrat imzaladaginda 1 olacak
            $customerContract->isDeleted = 0;
            $customerContract->save();
        }
    } else {
        $usResponse = 0;
    }

    $response->getBody()->write($usResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/users/saveuserform/{userID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";

    $json = $request->getBody();
    $data = json_decode($json, true);
    $data = $data[0];

    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $user = new users($userID);
    if ($user->ID > 0) {
        $us = new users($args["userID"]);
        $us->username = $data['username'];
        $us->password = $data['password'];
        $us->fullname = $data['fullname'];
        $us->email = $data['email'];
        $us->customerID = $data['customerID'];
        $us->roleID = $data['roleID'];
        $us->clang = $data['clang'];
        $us->countryID = $data['countryID'];
        $us->status = 3; //kontrat imzalandi. odeme yapilmadi
        $us->address = $data['address'];
        $us->phone = $data['phone'];
        $us->isDeleted = $data['isDeleted'];
        if ($data["roleID"] == 8 || $data["roleID"] == 3) {
            $us->subLabelRate = isset($data['subLabelRate']) ? str_replace(",", ".", $data['subLabelRate']) : 0;
        }
        if ($data["roleID"] == 3) {
            $us->artistID = isset($data['artistID']) ? $data['artistID'] : 0;
        }
        $usResponse = $us->save();

        //kullanici aktif ise bagli oldugu customer da aktif ediliyor
        if ($args["userID"] > 0 && $data['customerID'] > 0) {
            require_once dirname(dirname(__FILE__)) . "/BL/Tables/customers.php";
            $customer = new customers($data['customerID']);
            $customer->isDeleted = 0;
            $customer->save();
        }

        if ($args["userID"] == 0 && $usResponse > 0) {
            require_once dirname(dirname(__FILE__)) . "/BL/Tables/userStatusHistory.php";
            $userStatus = new userStatusHistory();
            $userStatus->userID = $usResponse;
            $userStatus->status = 1; // user kaydedildi. Aktivasyon maili bekleniyor.
            $userStatus->ipAdress = $data["clientIP"];
            $userStatus->statusDate = date('Y-m-d H:i:s');
            $userStatus->save();
            /*
                require_once dirname(dirname(__FILE__)) . "/BL/Tables/customerContracts.php";
                $customerContract = new customerContracts();
                $customerContract->customerID = $newCustomerID;
                $customerContract->contractID = $data['contractId'];
                $customerContract->termDate = date('Y-m-d H:i:s');
                $customerContract->term = 1; //?
                $customerContract->contractID = $data['commissionID'];
                $customerContract->isSent = 1; //?
                $customerContract->isSigned = 0; //? kontrat imzaladaginda 1 olacak
                $customerContract->isDeleted = 0;
                $customerContract->save();
                */
        }
    } else {
        $usResponse = 0;
    }
    $response->getBody()->write($usResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/users/statusupdate/{userID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/userStatusHistory.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/customerContracts.php";

    $json = $request->getBody();
    $data = json_decode($json, true);
    $data = $data[0];
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $user = new users($userID);
    $usResponse = 0;
    if ($userID == (int)$args["userID"] || $user->roleID == 1) {
        $us = new users($args["userID"]);
        $us->status = $data['status'];
        if ($data['status'] == "3") { // kontrat imzalandiginda kullanici turu atanarak aktif ediliyor
            $us->userType = 1;
        } else if ($data['status'] == "4") {
            $us->status = 4;
            $customerContract->setContractPay($us->customerID); //odeme yapildiginda kullanicinin kontrat tarihi odeme yapilan tarih oluyor.
        }
        $usResponse = $us->save();

        if ($data['status'] == "3") { // kontrat imzalandiginda customerContracts Tablosu da guncelleniyor
            $customerContract = new customerContracts();
            $customerContract->setContractSign($us->customerID);
        }
        if ($usResponse > 0) {
            $userStatus = new userStatusHistory();
            $userStatus->userID = $us->ID;
            $userStatus->status = $data["status"];
            $userStatus->ipAdress = $data["clientIP"];
            $userStatus->statusDate = date('Y-m-d H:i:s');
            $userStatus->save();
        }
    }
    $response->getBody()->write($usResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/users/delete/{userID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $user = new users($userID);
    $user2 = new users($args["userID"]);
    $usResponse = 0;
    if ($user->roleID == 1 || $user2->customerID == $user->customerID) {
        $us = new users($args["userID"]);
        $usResponse = $us->delete();
    }
    $response->getBody()->write($usResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/users/setuserconf/{userID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/userConfiguration.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;

    $json = $request->getBody();
    $data = json_decode($json, true);

    $sideBarMini = $data[0]["sideBarMini"];
    $menuColor = $data[0]["menuColor"];
    $us = new userConfiguration;
    $us->setUserConf($userID, $menuColor, $sideBarMini);
    $response->getBody()->write($userID);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));


$app->get('/roles[/{roleType}]', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/roles.php";
    $roles = new roles();
    $roleType = isset($args["roleType"]) ? $args["roleType"] : 0;
    $result = $roles->getRoles($roleType);
    $roleResponse = checkNull($roles->toJson);
    $response->getBody()->write($roleResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));



//Content related
/*
/contents/list/{contentID} - contentID optional
/contents/save/{contentID}
/contents/delete/{contentID}
*/

$app->get('/contents/list/{contentID}', function ($request, $response, $args) {
    //userID'den customerID alacak ve content list getirecek (Catalog List)
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contents.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $album = new contents();
    $album->getContent($customerID, $args["contentID"]);
    $albumResponse = checkNull($album->toJson);
    $response->getBody()->write($albumResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/contents/history/{contentID}', function ($request, $response, $args) {
    //userID'den customerID alacak ve content list getirecek (Catalog List)
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contents.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $contents = new contents();
    $contents->getContentHistory($args["contentID"], $customerID);
    $contentResponse = checkNull($contents->toJson);
    $response->getBody()->write($contentResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/contents/save/{contentID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contents.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";

    $json = $request->getBody();
    $data = json_decode($json, true);
    $data = $data[0];

    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $content = new contents($args["contentID"]);
    $cuser = new users($content->userID);
    $user = new users($userID);
    $contentResponse = 0;
    if ($cuser->customerID == $customerID || $user->roleID == 1) {
        $content->contentStatus = $data['contentStatus'];
        $content->contentType = $data['contentType'];
        $content->userID = $data['userID'];
        if (!$data["ID"] > 0) {
            $content->dateCreated = date('Y-m-d H:i:s');
            $content->createdBy = $data['createdBy'];
        }
        $content->dateModified = date('Y-m-d H:i:s');
        $content->modifiedBy = $data['userID'];
        $content->contentSubTypeID = $data['contentSubTypeID'];;
        $contentResponse = $content->save();
    }
    $response->getBody()->write($contentResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/contents/delete/{contentID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contents.php";
    $content = new contents($args["contentID"]);
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $contentResponse = 0;
    if ($content->userID == $userID) {
        $contentResponse = $content->delete();
    }
    $response->getBody()->write($contentResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

//Catalog related
/*
/catalog/list/{contentType}
*/

$app->get('/catalog/list/{contentType}', function ($request, $response, $args) {
    //userID'den customerID alacak ve album list getirecek (Album List)
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albums.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $album = new albums();
    $album->getCatalog($userID, $customerID, $args["contentType"]);
    $albumResponse = checkNull($album->toJson);
    $response->getBody()->write($albumResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/catalog/list/last/{recordLimit}/{contentType}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albums.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $album = new albums();
    $album->getLastCatalog($userID, $customerID, $args["contentType"], $args["recordLimit"]);
    $albumResponse = checkNull($album->toJson);
    $response->getBody()->write($albumResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

//Album related
/*
/albums/list/UPC/{UPC}
/albums/list/albumID/{albumID}
/albums/save/{albumID}
/albumBooklet/{albumID}
/albumPlatformsContentStatus/{albumID}
/albumDistribution/{albumID}
/platformTakedown
/deliveries
*/

$app->get('/deliveries', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/deliveries.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $user = new users($userID);
    $deliveriesResponse = null;
    if ($user->roleID == 1) {
        $deliveries = new deliveries();
        $deliveries->getDeliveries(5000);
        $deliveriesResponse = checkNull($deliveries->toJson);
    }
    $response->getBody()->write($deliveriesResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/setPrioty/{csID}/{prioty}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contentstatus.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $user = new users($userID);
    $csResponse = 0;
    if ($user->roleID == 1) {
        $cs = new contentStatus($args["csID"]);
        $cs->prioty = $args["prioty"];
        $csResponse = $cs->save();
    }
    $response->getBody()->write($csResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/albums/list/UPC/{UPC}', function ($request, $response, $args) {
    //userID'den customerID alacak ve album list getirecek (Album List)
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albums.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $album = new albums();
    $album->getAlbumsbyUPC($customerID, $args["UPC"]);
    $albumResponse = checkNull($album->toJson);
    $response->getBody()->write($albumResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/albums/list/albumID/{albumID}', function ($request, $response, $args) {
    //userID'den customerID alacak ve album list getirecek (Album List)
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albums.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $album = new albums();
    $album->getAlbumsbyID($userID, $customerID,  $args["albumID"]);
    $albumResponse = checkNull($album->toJson);
    $response->getBody()->write($albumResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/albums/save/{albumID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albums.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contents.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/artists.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/artistAlbums.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contentstatus.php";

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
            if ($args["albumID"] > 0) {
                $content->contentStatus = $data["contentStatus"];
            } else {
                $content->contentStatus = 1;
                $content->isOld = 0;
            }
            $content->contentType = $data['contentType'];
            $content->contentSubTypeID = $data['contentSubTypeID'];
            $content->userID = ($userID != $data["userID"]) ? (($data["userID"] > 0) ? $data["userID"] : $userID) : $userID;
            //$content->userID = $userID;
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
            $album->title = $data['title'];
            $album->description = $data['description'];
            $album->languageID = $data['languageID'];
            $album->genreID = $data['genreID'];
            $album->subgenreID = $data['subgenreID'];
            $album->copyright = $data['copyright'];
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
            $album->titleVersion = $data['titleVersion'];
            if (isset($data['preorderDate'])) {
                $album->preorderDate = $data['preorderDate'];
            }
            $album->labelName = $data['labelName'];
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
                        $getID = artists::getIsArtisID($artistAlbum['name']);
                        $artistID = $getID->ID;
                        if (!$artistID > 0) {
                            $newArtist = new artists();
                            $newArtist->name = $artistAlbum['name'];
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
                //}
            }
            if ($args["albumID"] == 0) {
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

$app->get('/albumBooklet/{albumID}', function ($request, $response, $args) {
    //userID'den customerID alacak ve album list getirecek (Album List)
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albums.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $album = new albums();
    $album->getAlbumBooklet($userID, $customerID, $args["albumID"]);
    $albumResponse = checkNull($album->toJson);
    $response->getBody()->write($albumResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/albumPlatformsContentStatus/{albumID}/{contentType}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albums.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $album = new albums();
    $album->getAlbumPlatformsStatus($args["albumID"], $userID, $customerID, $args["contentType"]);
    $albumResponse = checkNull($album->toJson);
    $response->getBody()->write($albumResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/files/delete/{fileID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/files.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $file = new files($args["fileID"]);
    $file->delete(1);

    $deleteFile = $uploadPath . "files/" . $file->fileName;
    if (file_exists($deleteFile)) {
        unlink($deleteFile);
    }
    $response->getBody()->write((string)$args["fileID"]);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->get('/files/{tableName}/{tableID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/files.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $filesResponse = null;
    if ($userID > 0) {
        $files = new files();
        $files->getFiles($args["tableName"], $args["tableID"]);
        $filesResponse = checkNull($files->toJson);
    }
    $response->getBody()->write($filesResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/files/save/{fileID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/files.php";
    require_once dirname(dirname(__FILE__)) . "/BL/functions.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $fileID = 0;
    if ($userID > 0) {
        $json = $request->getBody();
        $data = json_decode($json, true);

        $uploadsPath = dirname(dirname(__FILE__)) . "/uploads/files/";
        $outputFile = md5($data["fileName"] . rand(1000)) . "." . explode('/', $data["fileType"])[1];
        $file = $uploadsPath . $outputFile;
        //echo $file;
        base64_to_file($data["file"], $file);

        $file = new files($args["fileID"]);
        $file->title = $data["title"];
        $file->tableName = $data["tableName"];
        $file->tableID = $data["tableID"];
        $file->fileName = $outputFile;
        $file->userID = $userID;
        $fileID = $file->save();
    }
    $response->getBody()->write((string)$fileID);
    return $response;
})->add(new JwtMiddleware($secretKey));


$app->post('/albumDistribution/{albumID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contentstatus.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/publishCountries.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contents.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contentErrors.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/customers.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/tracks.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albums.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albumstracks.php";
    require_once dirname(dirname(__FILE__)) . "/BL/functions.php";

    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $json = $request->getBody();
    $data = json_decode($json, true);
    $platforms = $data["platforms"];
    $cnt = new contents($data["contentID"]);
    $user = new users($cnt->userID);
    $customer = new customers($user->customerID);
    $cnt->contentStatus = $data["contentStatus"];
    $cnt->modifiedBy = $userID;
    $cnt->dateModified = date('Y-m-d H:i:s');
    $cnt->save();

    $album = new albums($args["albumID"]);
    $album->salesStartDate = $data["relaseDateModel"];
    $album->save();

    if ($data["contentStatus"] != "4") {
        if ($data["isWorldRelease"] == true) {
            $publishCondition = 1;
            $pCountry = "<WORLD>";
        } else if ($data["isWorldRelease"] == false) {
            if ($data["isCountrySelect"] == true) {
                $countries = $data["selectCountry"];
                $publishCondition = 1;
            } else {
                $countries = $data["exceptCountry"];
                $publishCondition = 2;
            }
            $pCountry = "";
            foreach ($countries as $country) {
                $pCountry = $pCountry . "<" . $country["country"] . ">";
            }
        }
        $publishCountry = new publishCountries($platforms[0]["publishCountryID"]);
        $publishCountry->albumID = $args["albumID"];
        $publishCountry->country = $pCountry;
        $publishCountry->publishCondition = $publishCondition;
        $publishCountry->save();
    } else {
        $ticketClose = new contentErrors();
        $ticketClose->contentTicketsClose($data["contentID"], $userID);

        // update NOUPC
        if (strpos($data["upc"], 'NOUPC') !== false) {
            $getUpc = contents::getUpc();
            $decodeUPC = ean13_check_digit($getUpc->UPC);
            $album = new albums($args["albumID"]);
            $album->upc = $decodeUPC;
            $album->save();
            $setUpc = new contents();
            $setUpc->setUpc($getUpc->UPC + 1);
        }

        // update NOISRC
        $at = new albumsTracks();
        $atResult = $at->getAlbumsTracks($args["albumID"]);
        while (list($trackID) = mysqli_fetch_array($atResult)) {
            $track = new tracks($trackID);
            $orginalTrackFile = $track->assetFile;
            if (strpos($track->isrc, 'NOISRC') !== false) {
                $getIsrc = contents::getIsrc();
                $newIsrcNo = $getIsrc->ISRCno;
                $newIsrc = $getIsrc->ISRC . $newIsrcNo;
                $track->isrc = $newIsrc;
                $track->assetFile = $newIsrc . ".wav";
                $track->save();

                $setIsrc = new contents();
                $setIsrc->setIsrc($newIsrcNo + 1);

                if ($cnt->isOld == 1) {
                    $orginalTrackFile = dirname(dirname(__FILE__)) . "/" . project::uploadPath . $cnt->userID . "/" . $cnt->ID . "/audio/" . $orginalTrackFile;
                    $deliveryTrackFile = dirname(dirname(__FILE__)) . "/" . project::uploadPath . $cnt->userID . "/" . $cnt->ID . "/audio/" . $newIsrc . ".wav";
                } else {
                    $orginalTrackFile = dirname(dirname(__FILE__)) . "/" . project::uploadPath . "customers/" . $user->customerID . "/tracks/" . $orginalTrackFile;
                    $deliveryTrackFile = dirname(dirname(__FILE__)) . "/" . project::uploadPath . "customers/" . $user->customerID . "/tracks/" . $newIsrc . ".wav";
                }
                copy($orginalTrackFile, $deliveryTrackFile);
                unlink($orginalTrackFile);
            }
        }
    }
    if ($data["isAllPlatforms"] == true) {
        foreach ($platforms as $platform) {
            if ((($platform["platformID"] == 10 || $platform["platformID"] == 17) && $customer->enableClaims == 1) || ($platform["platformID"] != 10 && $platform["platformID"] != 17)) {
                $cs = contentStatus::contentStatusWithPlatform($platform["contentID"], $platform["platformID"], 2);
                if (($cs->ID > 0 && $data["contentStatus"] == 4) || $data["contentStatus"] != 4) {
                    $cntStatus = new contentStatus();
                    $cntStatus->contentID = $platform["contentID"];
                    $cntStatus->deliveryID = 0;
                    $cntStatus->status = $data["contentStatus"];
                    $cntStatus->platformID = $platform["platformID"];
                    $cntStatus->dateCreated = date('Y-m-d H:i:s');
                    $cntStatus->userID = $userID;
                    $cntStatus->sessionID = $platform["sessionID"];
                    $cntStatus->salesStartDate = $platform["salesStartDate"];
                    if ($platform["platformID"] == 1) {
                        $cntStatus->albumPrice = $platform["albumPrice"] > 0 ? $platform["albumPrice"] : "21";
                        $cntStatus->trackPrice = $platform["trackPrice"] > 0 ? $platform["trackPrice"] : "1";
                        $cntStatus->appleDigitalMaster = $platform["appleDigitalMaster"];
                        $cntStatus->soundEngineerEmailAddress = $platform["soundEngineerEmailAddress"];
                        $cntStatus->allowPreOrder = $platform["allowPreOrder"];
                        $cntStatus->allowPreOrderPreview = $platform["allowPreOrderPreview"];
                        $cntStatus->preOrderDate = $platform["preOrderDate"] != null ? $platform["preOrderDate"] : $platform["salesStartDate"];
                    }
                    $cntID = $cntStatus->save();
                }
            }
        }
    } else if ($data["isAllPlatforms"] == false) {
        foreach ($platforms as $platform) {
            if ($platform["ID"] == '0') {
                $cntStatus = new contentStatus();
                $cntStatus->contentID = $platform["contentID"];
                $cntStatus->deliveryID = 0;
                $cntStatus->status = $data["contentStatus"];
                $cntStatus->platformID = $platform["platformID"];
                $cntStatus->dateCreated = date('Y-m-d H:i:s');
                $cntStatus->userID = $userID;
                $cntStatus->sessionID = $platform["sessionID"];
                $cntStatus->salesStartDate = $platform["salesStartDate"];
                if ($platform["platformID"] == 1) {
                    $cntStatus->albumPrice = $platform["albumPrice"] > 0 ? $platform["albumPrice"] : "21";
                    $cntStatus->trackPrice = $platform["trackPrice"] > 0 ? $platform["trackPrice"] : "1";
                    $cntStatus->appleDigitalMaster = $platform["appleDigitalMaster"];
                    $cntStatus->soundEngineerEmailAddress = $platform["soundEngineerEmailAddress"];
                    $cntStatus->allowPreOrder = $platform["allowPreOrder"];
                    $cntStatus->allowPreOrderPreview = $platform["allowPreOrderPreview"];
                    $cntStatus->preOrderDate = $platform["preOrderDate"] != null ? $platform["preOrderDate"] : $platform["salesStartDate"];
                }
                $cntID = $cntStatus->save();
            }
        }
    }
    if ($data["contentStatus"] == "6") {
        $ticketClose = new contentErrors();
        $ticketClose->ticketMarkasFixed($data["contentID"]);
    }
    //return $response->withStatus(200)->write($args["albumID"]);
    $response->getBody()->write((string)$args["albumID"]);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->get('/albumControl/{albumID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albums.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contents.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $messages = [];
    $uploadPath = dirname(dirname(__FILE__)) . "/uploads/";

    $album = new albums();
    $album->getAlbumControl($customerID, $args["albumID"], $controlType = 1);
    $albumArtists = json_decode(checkNull($album->toJson), true);
    if ($albumArtists) {
        $albumArtists[0]["isTitle"] > 0 ? "" : array_push($messages, "No album title");
        $albumArtists[0]["isCopyright"] > 0 ? "" : array_push($messages, "No album PLine");
        $albumArtists[0]["isArtFile"] > 0 ? "" : array_push($messages, "No album cover");
        $albumArtists[0]["composerRoleID"] > 0 ? "" : array_push($messages, "No album composer");
        $albumArtists[0]["lyricistRoleID"] > 0 ? "" : array_push($messages, "No album lyricist");
        $albumArtists[0]["mixEngRoleID"] > 0 ? "" : array_push($messages, "No album Mixing Engineer");
        $albumArtists[0]["masEngRoleID"] > 0 ? "" : array_push($messages, "No album Mastering Engineer");
        $albumArtists[0]["insRoleID"] > 0 ? "" : array_push($messages, "No album Instrument");

        if ($albumArtists[0]["isArtFile"] > 0) {

            if (file_exists($uploadPath . $albumArtists[0]["artPath"])) {
                $a = new albums($args["albumID"]);
                $c = new contents($a->contentID);
                //echo $uploadPath.$albumArtists[0]["artPath"];
                $artInfo = getimagesize($uploadPath . $albumArtists[0]["artPath"]);
                //echo var_dump($artInfo);
                if ($c->contentType <> 5) {
                    if ($artInfo[0] == $artInfo[1] && $artInfo[1] == 3000 && $artInfo["mime"] == "image/jpeg") {
                    } else {
                        array_push($messages, "Must be in JPG format and 3000 x 3000 pixels");
                    }
                } else {
                    if ($artInfo[0] == 1080 && $artInfo[1] == 608 && $artInfo["mime"] == "image/jpeg") {
                    } else {
                        array_push($messages, "Must be in JPG format and 1080 x 608 pixels");
                    }
                }
            } else {
                array_push($messages, "The photo could not be uploaded");
            }
        }
        $albumArtists[0]["isGenreID"] > 0 ? "" : array_push($messages, "No album Genres");
        $albumArtists[0]["isLanguageID"] > 0 ? "" : array_push($messages, "No album metadata language");
        $albumArtists[0]["isUpc"] > 0 ? "" : array_push($messages, "No album UPC");
        $albumArtists[0]["performerRoleID"] > 0 ? "" : array_push($messages, "No album Performer");

        foreach ($albumArtists as $albumArtist) {
            $albumArtist["isArtistID"] > 0 ? "" : array_push($messages, "No album artist");
            $albumArtist["isArtistRoleID"] > 0 ? "" : array_push($messages, "No album artist role");
        }
        $album->getAlbumControl($customerID, $args["albumID"], $controlType = 2);
        $albumTracks = json_decode(checkNull($album->toJson), true);
        if ($albumTracks) {
            $trackCount = 0;
            foreach ($albumTracks as $albumTrack) {
                $albumTrack["isTrackISRC"] > 0 ? "" : array_push($messages, "isrcNo:" . $albumTrack["isrc"] . "#No Track ISRC");
                $albumTrack["isLyric"] > 0 ? "" : array_push($messages, "isrcNo:" . $albumTrack["isrc"] . "#No Track lyric");
                if ($albumTrack["isLyric"] > 0) {
                    $albumTrack["isLyricCount"] > 0 ? "" : array_push($messages, "isrcNo:" . $albumTrack["isrc"] . "#Track lyrics must be at least 10 words");
                }
                $albumTrack["isTrackArtistID"] > 0 ? "" : array_push($messages, "isrcNo:" . $albumTrack["isrc"] . "#No Track artist");
                $albumTrack["isComposerRole"] > 0 ? "" : array_push($messages, "isrcNo:" . $albumTrack["isrc"] . "#No Track composer");
                $albumTrack["isLyricistRole"] > 0 ? "" : array_push($messages, "isrcNo:" . $albumTrack["isrc"] . "#No Track Lyricist Role");
                $albumTrack["isExistIsrc"] > 0 ? "" : array_push($messages, "isrcNo:" . $albumTrack["isrc"] . "#Existing ISRC");
                $albumTrack["isPerformerRole"] > 0 ? "" : array_push($messages, "isrcNo:" . $albumTrack["isrc"] . "#No Track performer");
                $albumTrack["mixEngRoleID"] > 0 ? "" : array_push($messages, "isrcNo:" . $albumTrack["isrc"] . "#No Track Mixing Engineer");
                $albumTrack["masEngRoleID"] > 0 ? "" : array_push($messages, "isrcNo:" . $albumTrack["isrc"] . "#No Track Mastering Engineer");
                $albumTrack["insRoleID"] > 0 ? "" : array_push($messages, "isrcNo:" . $albumTrack["isrc"] . "#No Track Instrument");


                if (file_exists($uploadPath . $albumTrack["assetPath"])) {
                    exec(dirname(dirname(__FILE__)) . "/unix/ffmpeg -i " . $uploadPath . $albumTrack["assetPath"] . " 2>&1 | grep Duration | awk '{print $2}' | tr -d , ", $output);
                    list($a, $b, $c, $d) = explode(':', str_replace('.', ':', $output[0]));
                    $duration = 0;
                    $duration = ($a * 60 * 60) + ($b * 60) + ($c);
                    $duration > 0 ? "" : array_push($messages, "isrcNo:" . $albumTrack["isrc"] . "#Track has not audio");
                } else {
                    array_push($messages, "isrcNo:" . $albumTrack["isrc"] . "#Track has not audio");
                }
                $trackCount++;
            }
        } else {
            array_push($messages, "No album track");
        }
    } else {
        array_push($messages, "This album is not yours");
    }
    $messages = json_encode($messages, true);
    $response->getBody()->write($messages);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/albums/delete/{contentID}/{albumID}/{isOld}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albums.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contents.php";
    require_once dirname(dirname(__FILE__)) . "/BL/functions.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $uploadPath = dirname(dirname(__FILE__)) . "/uploads/";
    $albumDelete = new albums();
    $albumDelete->albumDelete($userID, $args["albumID"], $args["contentID"], $args["isOld"]);
    $albumTracks = json_decode(checkNull($albumDelete->toJson), true);
    $alID = 0;
    if ($albumIsrc[0]["assetFile"] == -1) {
        $alID = -1;
    } else {
        if ($args["isOld"] > 0) {
            $count = count($albumTracks);
            foreach ($albumTracks as $val) {
                if ($count == $val["totalTrackCount"]) {
                    $folderPath = $uploadPath . $userID . "/" . $args["contentID"];
                    deleteDirectory($folderPath);
                    $alID = $args["albumID"];
                } else {
                    $assetPath = $uploadPath . $val["assetPath"];
                    if (file_exists($assetPath)) {
                        unlink($assetPath);
                    }
                }
            }
            $coversPath = $uploadPath . $userID . "/" . $args["contentID"] . "/";
            if (file_exists($coversPath . "r_cover.jpg")) {
                unlink($coversPath . "r_cover.jpg");
            }
            if (file_exists($coversPath . "cover.jpg")) {
                unlink($coversPath . "cover.jpg");
            }
            if (file_exists($coversPath . "coverOriginal.jpg")) {
                unlink($coversPath . "coverOriginal.jpg");
            }
        } else {
            foreach ($albumTracks as $val) {
                $assetPath = $uploadPath . $val["assetPath"];
                if (file_exists($assetPath)) {
                    unlink($assetPath);
                }
            }
            $folderPath = dirname(dirname(__FILE__)) . "/uploads/customers/" . $customerID . "/" . $args["contentID"];
            deleteDirectory($folderPath);
        }
        $alID = $args["albumID"];
    }

    $response->getBody()->write((string)$alID);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->post('/republish/{platformID}/{contentID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contentstatus.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contents.php";

    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $content = new contents($args["contentID"]);
    $cuser = new users($content->userID);
    $user = new users($userID);
    if ($cuser->customerID == $customerID || $user->roleID == 1) {
        $cs = contentStatus::contentStatusWithPlatform($args["contentID"], $args["platformID"], 5);
        $cs->delete(1);
    }
    $response->getBody()->write((string)$content->ID);
    return $response;
})->add(new JwtMiddleware($secretKey));


$app->post('/platformTakedowm', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contentstatus.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contents.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $json = $request->getBody();
    $data = json_decode($json, true);
    $content = new contents($data["contentID"]);
    $cuser = new users($content->userID);
    $user = new users($userID);
    $cntStatusID = 0;
    if ($cuser->customerID == $customerID || $user->roleID == 1) {
        $cntStatus = new contentStatus();
        $cntStatus->contentID = $data["contentID"];
        $cntStatus->deliveryID = 0;
        $cntStatus->status = 8;
        $cntStatus->userID = $userID;
        $cntStatus->platformID = $data["platformID"];
        $cntStatus->dateCreated = date('Y-m-d H:i:s');
        $cntStatusID = $cntStatus->save();
    }
    $response->getBody()->write((string)$cntStatusID);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->get('/albumSearch/{searchText}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albums.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $albums = new albums();
    $albums->getAlbumSearch($userID, $customerID, $args["searchText"]);
    $albumResponse = checkNull($albums->toJson);
    $response->getBody()->write($albumResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/albums/copy/{clanguage}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albums.php";
    require_once dirname(dirname(__FILE__)) . "/BL/functions.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $json = $request->getBody();
    $albums = json_decode($json, true);
    $tempArray = '[';
    foreach ($albums as $album) {
        $albumCopy = albums::albumCopy($album["ID"], $album["contentID"], $args["clanguage"], $userID);
        $obj = '{"albumID" :"' . $albumCopy->newAlbumID . '","contentID": "' . $albumCopy->newContentID . '"}';
        $tempArray = $tempArray . $obj . ",";
        albumCopyAsset($album, $albumCopy->newAlbumID, $albumCopy->newContentID, $userID, $customerID);
    }
    $tempArray = rtrim($tempArray, ",");
    $tempArray = $tempArray . "]";
    //echo var_dump($tempArray);
    //return $response->withStatus(200)->write($tempArray);
    $response->getBody()->write((string)$tempArray);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->post('/sales/save/{ID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/sales.php";

    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $json = $request->getBody();
    $data = json_decode($json, true);
    $sales = new sales($args["ID"]);
    $salesID = 0;
    if ($sales->userID == $userID || $args["ID"] == 0) {
        $sales = new sales($args["ID"]);
        $sales->contentID = $data["contentID"];
        $sales->userID = $userID;
        $sales->salesDate = date('Y-m-d H:i:s');
        $sales->packageID = $data["packageID"];
        $sales->price = $data["price"];
        $sales->currency = $data["currency"];
        $sales->completed = 0;
        $sales->payerID = $data["orderID"];
        $sales->customerID = $data["customerID"];
        $sales->channelID = $data["channelID"];
        $sales->upgradeContractID = $data["upgradeContractID"];
        $sales->processType = $data["processType"]; //1=first deploy 2=deploy 3=upgrade package
        $salesID = $sales->save();
    }
    $response->getBody()->write((string)$salesID);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->post('/assetChanges/save/{ID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/assetChanges.php";

    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $json = $request->getBody();
    $data = json_decode($json, true);

    if ($data["changeType"] == 1) {
        $assetChange = new assetChanges($args["ID"]);
        $assetChange->albumID = $data["albumID"];
        $assetChange->changeType = $data["changeType"];
        $assetChange->trackID = 0;
        $assetChange->changeDate = date('Y-m-d H:i:s');
        $assetChange->isDeliver = $data["isDeliver"];
        $assetChange->userID = $userID;
        $assetChangeID = $assetChange->save();
    } else {
        foreach ($data["albumID"] as $albumID) {
            $assetChange = new assetChanges($args["ID"]);
            $assetChange->albumID = $albumID;
            $assetChange->changeType = $data["changeType"];
            $assetChange->trackID = $data["trackID"];
            $assetChange->changeDate = date('Y-m-d H:i:s');
            $assetChange->isDeliver = $data["isDeliver"];
            $assetChange->userID = $userID;
            $assetChangeID = $assetChange->save();
        }
    }
    $response->getBody()->write((string)$assetChangeID);
    return $response;
})->add(new JwtMiddleware($secretKey));

//Tracks related
/*
/tracks/info/{trackID} -trackID optional
/tracks/list/{albumID} -albumID optional        
/tracks/save/{trackID}
/searchTracks/{searchText}[/{albumID}]
/trackCopy/{trackID}/{albumID}
*/

$app->get('/tracks/info[/{trackID}]', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/tracks.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $track = new tracks();
    if (isset($args["trackID"])) {
        $result = $track->getCustomerTracks($userID, $customerID, $args["trackID"]);
    } else {
        $result = $track->getCustomerTracks($userID, $customerID, 0);
    }

    $trackResponse = checkNull($track->toJson);
    $response->getBody()->write($trackResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/tracks/list[/{albumID}]', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/tracks.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $track = new tracks();
    if (isset($args["albumID"])) {
        $result = $track->getTracks($userID, $customerID, $args["albumID"]);
    } else {
        $result = $track->getTracks($userID, $customerID, 0);
    }

    $trackResponse = checkNull($track->toJson);
    $response->getBody()->write($trackResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/getTrackAlbumsInfo/{trackID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/tracks.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $track = new tracks();
    $track->getTrackAlbumsInfo($userID, $customerID, $args["trackID"]);
    $trackResponse = checkNull($track->toJson);
    $response->getBody()->write($trackResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/tracks/save/{trackID}', function ($request, $response, $args) {

    require_once dirname(dirname(__FILE__)) . "/BL/Tables/tracks.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/artists.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/artistTracks.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albumstracks.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/trackISRCs.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albums.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contents.php";

    $json = $request->getBody();
    $data = json_decode($json, true);

    $trackArtists = $data["trackArtists"];
    $data = $data["track"];

    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    //$track = new tracks($args["trackID"]);
    $album = new albums($data["albumID"]);
    $content = new contents($album->contentID);
    $tuser = new users($content->userID);
    $trackID = 0;

    if (($args["trackID"] > 0 && $tuser->customerID == $customerID) || $args["trackID"] == 0) {
        $track = new tracks($args["trackID"]);
        $track->title = $data['title'];
        $track->isrc = $data['isrc'];
        if (isset($data['assetFile'])) {
            $track->assetFile = $data['assetFile'];
        }
        $track->genreID = $data['genreID'];
        $track->explicit = $data['explicit'];
        $track->pricing = $data['pricing'];
        $track->subgenreID = $data['subgenreID'];
        $track->copyright = $data['copyright'];
        if (isset($data['isPDF'])) {
            $track->isPDF = $data['isPDF'];
        }
        $track->trackVersion = $data['trackVersion'];
        $track->trackLabel = $data['trackLabel'];
        $track->lyrics = $data['lyrics'];
        if (isset($data['lrc'])) {
            $track->lrc = $data['lrc'];
        }
        $track->lp = $data['lp'];
        $track->lpCountry = $data['lpCountry'];
        if (isset($data['previewTime'])) {
            $track->previewTime = $data['previewTime'];
        }
        $track->djmixes = isset($data["djmixes"]) ? $data["djmixes"] : 0;
        $track->relatedISRC = isset($data["relatedISRC"]) ? $data["relatedISRC"] : "";
        $track->avRating = isset($data["avRating"]) ? $data["avRating"] : 0;
        $args["trackID"] == 0 ? $track->status = 1 : $track->status = $data['status'];
        $trackID = $track->save();

        foreach ($trackArtists as $trackArtist) {
            //if ($trackArtist["isNeworUpdate"] > 0) {
            $artistID = 0;
            $newArtist = artists::getArtistFromName($trackArtist['name']);
            if ($newArtist->ID > 0) {
                $artistID = $newArtist->ID;
            } else {
                $newArtist->name = $trackArtist['name'];
                $newArtist->dateCreated = date('Y-m-d H:i:s');
                $newArtist->createdBy = $userID;
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
        if ($args["trackID"] == 0) {
            $albumsTracks = new albumsTracks($args["trackID"]);
            $albumsTracks->albumID = $data['albumID'];
            $albumsTracks->trackID = $trackID;
            $albumsTracks->userID = $userID;
            $albumsTracks->trackOrder = $data['trackOrder'];
            $albumsTracks->save();
        }
        if ($trackID > 0) {
            $ti = trackISRCs::getTrackISRC($trackID, 1);
            if (isset($data['dolbyAtmosISRC'])) {
                if ($data['dolbyAtmosISRC'] != "") {
                    $ti->trackID = $trackID;
                    $ti->isrc = ltrim($data['dolbyAtmosISRC']);
                    $ti->isrcType = 1;
                    $ti->save();
                } else {
                    if ($ti->ID > 0) {
                        $ti->delete(1);
                    }
                }
            } else {
                if ($ti->ID > 0) {
                    $ti->delete(1);
                }
            }
        }
    }

    $response->getBody()->write((string)$trackID);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->get('/searchTracks/{searchText}[/{albumID}]', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/tracks.php";

    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;

    isset($args["albumID"]) > 0 ? $albumID = $args["albumID"] : $albumID = 0;
    $searchTrack = new tracks();
    $searchTrack->getSearchTracks($customerID, $args["searchText"], $albumID);
    $trackResponse = checkNull($searchTrack->toJson);
    $response->getBody()->write($trackResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/trackCopy', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albumstracks.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/tracks.php";
    $json = $request->getBody();
    $data = json_decode($json, true);
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $newTrack = tracks::trackCopy($data["trackID"], $userID);
    $trackID = $newTrack->ID;

    $albumsTracks = new albumsTracks();
    $albumsTracks->albumID = $data["albumID"];
    $albumsTracks->trackID = $trackID;
    $albumsTracks->trackOrder = $data["trackOrder"];
    $ID = $albumsTracks->save();
    $response->getBody()->write((string)$ID);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->post('/albumTracksOrder', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albumstracks.php";
    $json = $request->getBody();
    $orderData = json_decode($json, true);
    foreach ($orderData as $data) {
        $albumsTracks = new albumsTracks($data["ID"]);
        $albumsTracks->trackOrder = $data["trackOrder"];
        $ID = $albumsTracks->save();
        if (!$ID > 0)
            return $response->withStatus(200)->write(-1);
    }
    $response->getBody()->write((string)$ID);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->get('/getDeliveredAlbumByTrackID/{trackID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albums.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $albums = new albums();
    $albums->getDeliveredAlbumByTrackID($args["trackID"], $userID, $customerID);
    $albumResponse = checkNull($albums->toJson);
    $response->getBody()->write($albumResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

// $app->get('/tracks/albums/{albumID}', function ($request, $response, $args) {
//     require_once dirname(dirname(__FILE__)) . "/BL/Tables/tracks.php";

//     $tracks = new tracks();
//     $tracks->getTracks(0,$args["albumID"]);
//     $tracksResponse = checkNull($tracks->toJson);
//     return $response->withStatus(200)
//         ->write($tracksResponse);
// });

//AlbumsTracks related
/*
/albumsTracks/delete/{trackID}
*/

$app->post('/albumsTracks/delete/{albumsTrackID}/{trackID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albumstracks.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/tracks.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $albums = new albumsTracks();
    $albums->getTrackAlbums($customerID, $args["trackID"]);
    $albums = json_decode(checkNull($albums->toJson), true);
    $count = count($albums);
    $trID = -1;
    if ($count == 1) {
        $album = $albums[0];
        if ($album["albumsTrackID"] == $args["albumsTrackID"]) {
            $albTrc = new albumsTracks($args["albumsTrackID"]);
            $albumID = $albTrc->albumID;
            $ID = $albTrc->delete(1);
            if ($ID == $args["albumsTrackID"]) {
                if ($album["isOld"] > 0) {
                    $audioPath = dirname(dirname(__FILE__)) . "/uploads/" . $album["userID"] . "/" . $album["contentID"] . "/audio/";
                } else {
                    $audioPath = dirname(dirname(__FILE__)) . "/uploads/customers/" . $customerID . "/tracks/";
                }
                if (file_exists($audioPath . $album["assetFile"])) {
                    unlink($audioPath . $album["assetFile"]);
                }
                $track = new tracks($args["trackID"]);
                $track->delete(1);

                // reorder tracks 
                if ($albumID > 0) {
                    $sql = "select ID from albumsTracks where albumID=" . $albumID . " order by trackOrder";
                    $result = $albTrc->executenonquery($sql);
                    $order = 1;
                    while ($row = mysqli_fetch_array($result)) {
                        $at = new albumsTracks($row["ID"]);
                        $at->trackOrder = $order;
                        $at->save();
                        $order += 1;
                    }
                }

                $trID = $args["trackID"];
            }
        }
    } else {
        $albTrc = new albumsTracks($args["albumsTrackID"]);
        $albumID = $albTrc->albumID;
        $ID = $albTrc->delete(1);

        if ($albumID > 0) {
            $sql = "select ID from albumsTracks where albumID=" . $albumID . " order by trackOrder";
            $result = $albTrc->executenonquery($sql);
            $order = 1;
            while ($row = mysqli_fetch_array($result)) {
                $at = new albumsTracks($row["ID"]);
                $at->trackOrder = $order;
                $at->save();
                $order += 1;
            }
        }

        if ($ID == $args["albumsTrackID"]) {
            $trID = $args["trackID"];
        }
    }
    $response->getBody()->write((string)$trID);
    return $response;
})->add(new JwtMiddleware($secretKey));
//royalty related
/*
/royalty
*/

$app->get('/royalty', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/royalty.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $revenue = new royalty();
    $revenue->getCumulativeRoyalty($userID);
    $revResponse = checkNull($revenue->toJson);
    $response->getBody()->write($revResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

// Earnings - Summary
$app->get('/royalty/info/summary/{userID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/royalty.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $royalty = new royalty();
    $royalty->getRoyaltySummary($userID);
    $royaltyResponse = checkNull($royalty->toJson);
    $response->getBody()->write($royaltyResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

// Earnings - Chart
$app->get('/royalty/info/graph/{userID}[/{currency}]', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/royalty.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $currency = isset($args["currency"]) ? $args["currency"] : 0;
    $royalty = new royalty();
    $royalty->getRoyaltyGraph($userID, $args["currency"]);
    $royaltyResponse = checkNull($royalty->toJson);

    $response->getBody()->write($royaltyResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

// Earnings - Platforms
$app->get('/royalty/info/platforms/[{platform}/{period}/{recordLimit}/{userID}]', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/royalty.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $royalty = new royalty();
    $pltfrm = $args["platform"] == 'null' ? '' : $args["platform"];
    $royalty->getRoyaltyPlatforms($userID, $pltfrm, $args["period"], $args["recordLimit"]);
    $royaltyResponse = checkNull($royalty->toJson);
    $response->getBody()->write($royaltyResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

// Earnings - Invoices
$app->get('/royalty/info/invoices/{period}/{userID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/royalty.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $royalty = new royalty();
    $royalty->getRoyaltyInvoices($userID, $args["period"]);
    $royaltyResponse = checkNull($royalty->toJson);
    $response->getBody()->write($royaltyResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

// Summary - Invoices
$app->get('/royalty/info/summaryinvoices/[{countryID}/{period}/{userID}]', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/royalty.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $royalty = new royalty();
    $royalty->getMainRoyalties($userID, $args["countryID"], $args["period"]);
    $royaltyResponse = checkNull($royalty->toJson);
    $response->getBody()->write($royaltyResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

// Summary - Invoices Details
$app->get('/royalty/info/summaryinvoicesdetails/[{roleID}/{customerID}/{period}]', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/royalty.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $user = new users($userID);
    $royaltyResponse = null;
    if ($args["customerID"] == $customerID || $user->roleID == 1 || $user->roleID == 2) {
        $royalty = new royalty();
        $royalty->pgetRoyaltyInvoicesDetails($args["roleID"], $args["customerID"], $args["period"], $userID);
        $royaltyResponse = checkNull($royalty->toJson);
    }
    $response->getBody()->write($royaltyResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

// Periods
$app->get('/royalty/info/periods', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/royalty.php";
    $royalty = new royalty();
    $royalty->getPeriods();
    $royaltyResponse = checkNull($royalty->toJson);
    $response->getBody()->write($royaltyResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

// Earnings - Download Invoices
$app->get('/royalty/info/downloadinvoices/{scopeDate}/{userID}/{customerName}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/royalty.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $user = new users($userID);
    $royalty = null;
    if ($args["userID"] == $userID || $user->roleID == 1) {
        $royalty = new royalty();
        $royalty->getRoyaltyInvoicesDetails($args["scopeDate"], $args["userID"]);
        $royalty = checkNull($royalty->toJson);
    }
    $response->getBody()->write($royalty);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/royalty/info/downloadinvoicesSublabel/{scopeDate}/{userID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/royalty.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $user = new users($userID);
    $royalty = null;
    if ($args["userID"] == $userID || $user->roleID == 1) {
        $royalty = new royalty();
        $royalty->getRoyaltySublabelInvoicesDetails($args["scopeDate"], $args["userID"]);
        $royalty = checkNull($royalty->toJson);
    }
    $response->getBody()->write($royalty);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/royalty/info/royaltyDetails/{scopeDate}/{userID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/royalty.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $user = new users($userID);
    $royalty = null;
    if ($args["userID"] == $userID || $user->roleID == 1) {
        $royalty = new royalty();
        $royalty->getRoyaltyDetails($args["scopeDate"], $args["userID"]);
        $royalty = checkNull($royalty->toJson);
    }
    $response->getBody()->write($royalty);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/royalty/fx/{scopeDate}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/royalty.php";
    $royalty = new royalty();
    $royalty->getRoyaltyFx($args["scopeDate"]);
    $royalty = checkNull($royalty->toJson);
    $response->getBody()->write($royalty);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

//Dashboard - Top Performing Products
$app->get('/royalty/info/topproducts/{userID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/royalty.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $royalty = new royalty();
    $royalty->getRoyaltyProducts($userID);
    $royaltyResponse = checkNull($royalty->toJson);
    $response->getBody()->write($royaltyResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/royalty/createinvoice/{userID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/royalty.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/invoiceStatuses.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $json = $request->getBody();
    $data = json_decode($json, true);
    $data = $data[0];
    $user = new users($userID);
    $result = 0;
    if ($user->roleID == 1) {
        $invs = invoiceStatuses::getInvoiceStatusFromDate($data["startDate"]);
        if (!$invs->ID > 0) {
            $royalty = new royalty();
            $royalty->createInvoiceCurrency(
                $userID,
                $data["startDate"],
                (isset($data["tryusd"]) ? $data["tryusd"] : 0),
                (isset($data["tryeur"]) ? $data["tryeur"] : 0),
                (isset($data["usdtry"]) ? $data["usdtry"] : 0),
                (isset($data["usdeur"]) ? $data["usdeur"] : 0),
                (isset($data["eurtry"]) ? $data["eurtry"] : 0),
                (isset($data["eurusd"]) ? $data["eurusd"] : 0),
                (isset($data["trygbp"]) ? $data["trygbp"] : 0),
                (isset($data["gbptry"]) ? $data["gbptry"] : 0),
                (isset($data["usdgbp"]) ? $data["usdgbp"] : 0),
                (isset($data["gbpusd"]) ? $data["gbpusd"] : 0),
                (isset($data["eurgbp"]) ? $data["eurgbp"] : 0),
                (isset($data["gbpeur"]) ? $data["gbpeur"] : 0)
            );

            $is = new invoiceStatuses();
            $is->processDate = date("Y-m-d");
            $is->scopeDate = $data["startDate"];
            $is->status = 3;
            $is->statusDate = date("Y-m-d H:i");
            $result = $is->save();
        }
    }
    $response->getBody()->write((string)$result);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->post('/royalty/processInvoice/{userID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/royalty.php";
    //$userID = $args["userID"];
    $json = $request->getBody();
    $data = json_decode($json, true);
    $data = $data[0];
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $user = new users($userID);
    $result = null;
    if ($user->roleID == 1) {
        $royalty = new royalty();
        $result = $royalty->processInvoice($data["status"], $data["statusId"], $data["scopeDate"]);
    }
    $response->getBody()->write($result);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->post('/royalty/processInvoiceStatus/{userID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/royalty.php";
    //$userID = $args["userID"];
    $json = $request->getBody();
    $data = json_decode($json, true);
    $data = $data[0];
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $user = new users($userID);
    $result = null;
    if ($user->roleID == 1) {
        $royalty = new royalty();
        $result = $royalty->processInvoiceStatus($data["chk"], $data["invoiceId"], $data["status"]);
    }
    $response->getBody()->write($result);
    return $response;
})->add(new JwtMiddleware($secretKey));

//Artists related
/*
/artists/info/{artistID}/{artistName} - artistID/artistname are optional
/artists/tracks/{trackID}
/artists/albums/{albumID}
/artists/tracks/save/{artistID}/{trackID}
/artists/tracks/delete/{artistID}/{trackID}
/artists/albums/save/{artistID}/{albumID}
/artists/albums/delete/{artistID}/{albumID}
/artists/save/{artistID}
*/

$app->get('/artists/info/{artistID}[/{artistName}]', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/artists.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $artist = new artists();
    if (isset($args["artistName"])) {
        $artist->getArtist($args["artistID"], $args["artistName"], $userID);
    } else {
        $artist->getArtist($args["artistID"], "", $userID);
    }
    $artistResponse = checkNull($artist->toJson);
    $response->getBody()->write($artistResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/artists/tracks/{trackID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/artistTracks.php";

    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $tracks = new artistTracks();
    $tracks->getTrackArtists($args["trackID"], $userID, $customerID);
    $tracksResponse = checkNull($tracks->toJson);
    $response->getBody()->write($tracksResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/artists/albums/{albumID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/artistAlbums.php";

    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $tracks = new artistAlbums();
    $tracks->getAlbumArtists($args["albumID"], $userID, $customerID);
    $tracksResponse = checkNull($tracks->toJson);
    $response->getBody()->write($tracksResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/artists/tracks/save/{artistID}/{trackID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/artistTracks.php";

    $json = $request->getBody();
    $data = json_decode($json, true);
    $data = $data[0];
    $artistTrackID = artistTracks::getArtistTrackID($args['artistID'], $args['trackID']);
    $artistTrackID->ID > 0 ? $ID = $artistTrackID->ID : $ID = 0;

    $artistTracks = new artistTracks($ID);
    $artistTracks->trackID = $args['trackID'];
    $artistTracks->artistID = $args['artistID'];
    $artistTracks->roleID = $data['roleID'];
    $artistTracks->primary = $data['primary'];
    $artistTracksResponse = $artistTracks->save();
    $response->getBody()->write((string)$artistTracksResponse);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->post('/artists/tracks/delete/{ID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/artistTracks.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $artistTracks = new artistTracks($args['ID']);
    $artistTracksResponse = 0;
    if ($userID == $artistTracks->userID) {
        $artistTracksResponse = $artistTracks->delete(1);
    }
    $response->getBody()->write((string)$artistTracksResponse);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->post('/artists/albums/save/{artistID}/{albumID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/artistAlbums.php";

    $json = $request->getBody();
    $data = json_decode($json, true);
    $data = $data[0];
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $artistAlbumResponse = 0;
    $artistAlbumsID = artistAlbums::getArtistAlbumsID($args['artistID'], $args['albumID']);
    if ($userID == $artistAlbumsID->userID) {
        $artistAlbumsID->ID > 0 ? $ID = $artistAlbumsID->ID : $ID = 0;

        $artistAlbum = new artistAlbums($ID);
        $artistAlbum->albumID = $args['albumID'];
        $artistAlbum->artistID = $args['artistID'];
        $artistAlbum->roleID = $data['roleID'];
        $artistAlbum->primary = $data['primary'];
        $artistAlbum->artistType = $data['artistType'];
        $artistAlbumResponse = $artistAlbum->save();
    }
    $response->getBody()->write((string)$artistAlbumResponse);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->post('/artists/albums/delete/{ID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/artistAlbums.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $artistAlbum = new artistAlbums($args['ID']);
    $artistAlbumResponse = 0;
    if ($userID == $artistAlbum->userID) {
        $artistAlbumResponse = $artistAlbum->delete(1);
    }
    $response->getBody()->write((string)$artistAlbumResponse);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->post('/albumArtistsDelete', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/artistAlbums.php";
    $json = $request->getBody();
    $data = json_decode($json, true);
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $artistAlbumResponse = 0;
    foreach ($data as $val) {
        $artistAlbum = new artistAlbums($val);
        if ($userID == $artistAlbum->userID) {
            $artistAlbumResponse = $artistAlbum->delete(1);
        }
    }
    $response->getBody()->write((string)$artistAlbumResponse);
    return $response;
})->add(new JwtMiddleware($secretKey));


$app->post('/artistSocials/save/{artistID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/artists.php";

    $json = $request->getBody();
    $data = json_decode($json, true);

    $artist = new artists($args["artistID"]);
    $artist->spotifyID = $data['spotifyID'];
    $artist->appleID = $data['appleID'];
    $artist->ISNI = $data['ISNI'];
    $artistResponse = $artist->save();

    $response->getBody()->write((string)$artistResponse);
    return $response;
})->add(new JwtMiddleware($secretKey));



$app->post('/artists/save/{artistID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/artists.php";

    $json = $request->getBody();
    $data = json_decode($json, true);
    $data = $data[0];
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $artist = new artists($args["artistID"]);
    $user = new users($artist->createdBy);
    $artistResponse = 0;
    if ($userID == $user->ID || $args["artistID"] == 0) {
        $artist->name = $data['name'];
        $artist->profilePic = $data['profilePic'];
        $artist->bio = $data['bio'];
        $artist->countryID = $data['countryID'];
        $artist->dateCreated = date("Y-m-d H:i:s");
        $artist->createdBy = $data['createdBy'];
        $artist->email = $data['email'];

        $artistResponse = $artist->save();
    }
    $response->getBody()->write((string)$artistResponse);
    return $response;
})->add(new JwtMiddleware($secretKey));

/* Contract/CustomerContract related
/contracts/list returns customerContracts for customerID user belongs to (CustomerContract List)
/contracts/info/{contractID}
/contracts/save/{contractID} - save contract
/contracts/delete/{contractID} - delete contract
/customer/contracts/save/{customerID}/{contractID} - save customerContract
/customer/contracts/delete/{customerID}/{contractID} - delete customerContract
*/


$app->get('/customer/artists/list[/{artistID}]', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/artists.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $artistID = isset($args["artistID"]) ? $args["artistID"] : 0;
    $artists = new artists();
    $artists->getCustomerArtists($customerID, $artistID, $userID);
    $artistsResponse = checkNull($artists->toJson);
    $response->getBody()->write($artistsResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/customer/contracts/list', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/customerContracts.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $contracts = new customerContracts();
    $contracts->getContracts($customerID);
    $contractsResponse = checkNull($contracts->toJson);
    $response->getBody()->write($contractsResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/customer/contract/{customerContractID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/customerContracts.php";

    $cc = new customerContracts($args["customerContractID"]);
    $contractsResponse = checkNull($cc->toJson());
    $response->getBody()->write($contractsResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/customer/search/{key}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/customers.php";
    $key = isset($args["key"]) ? $args["key"] : "";
    $customers = new customers();
    $customers->searchCustomer($key);
    $customersResponse = checkNull($customers->toJson);
    $response->getBody()->write($customersResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/contracts/list/{userID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contracts.php";
    $userID = isset($args["userID"]) ? $args["userID"] : 0;
    $contracts = new contracts();
    $contracts->getContracts($userID);
    $contractsResponse = checkNull($contracts->toJson);
    $response->getBody()->write($contractsResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/dealTerms/{type}/{channelID}[/{dealTermsID}]', function ($request, $response, $args) {

    // type 1=rates , 2 = commission , 0 = all
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/dealTerms.php";
    $dtID = isset($args["dealTermsID"]) ? $args["dealTermsID"] : 0;
    $channelID = isset($args["channelID"]) ? $args["channelID"] : 0;

    $dt = new dealTerms();
    $dt->getDealTerms($args["type"], $dtID, $channelID);
    $usResponse = checkNull($dt->toJson);
    $response->getBody()->write($usResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/contracts/info/{contractID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contracts.php";
    $contract = new contracts();
    $contract->getContractInfo($args["contractID"]);
    $contractsResponse = checkNull($contract->toJson);
    $response->getBody()->write($contractsResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/contracts/availableForUpgrade/{contractID}/{channelID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contracts.php";
    $contract = new contracts();
    $contract->getContractsForUpgrade($args["contractID"], $args["channelID"]);
    $contractsResponse = checkNull($contract->toJson);
    $response->getBody()->write($contractsResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/contracts/save/{contractID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contracts.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contractPlatforms.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $json = $request->getBody();
    $data = json_decode($json, true);

    $childContractID = isset($data["childContractID"]) ? $data["childContractID"] : 0;
    $contractID = $args['contractID'];

    $contract = new contracts($contractID);
    $contract->contentType = $data['contentType'];
    $contract->version = $data['version'];
    $contract->note = $data['note'];
    $contract->file = $data['file'];
    if (!$contractID > 0) {
        $contract->dateAdded = date('Y-m-d H:i:s');
        $contract->addedBy = $userID;
    }
    $contract->isDefault = $data['isDefault'] == true ? 1 : 0;
    $contract->parentID = $data['parentID'];
    $contract->isDeleted = $data['isDeleted'];
    $contract->countryID = $data['countryID'];
    $contract->color = $data['color'];
    $contract->maxArtist = $data['maxArtist'];
    $contract->contractOrder = $data['contractOrder'];
    $contract->description = $data['description'];
    $contract->currencyID = $data['currencyID'];
    $contract->channelID = $data['channelID'];
    $contract->price = str_replace(",", ".", $data['price']);
    $contract->isSelfInvoice = $data['isSelfInvoice'] == true ? 1 : 0;
    $contract->isRenew = $data['isRenew'] == true ? 1 : 0;

    if ($data['isPdfChange'] > 0) {
        $contract->changeDate = date('Y-m-d H:i:s');
    }
    $contract->modifiedBy = $userID;
    $contractResponse = $contract->save();

    if ($childContractID > 0) {
        if ($contractID > 0) {
            $removeOldParentIDs = $contract->removeParentIds($contractID);
        }
        $contractChild = new contracts($childContractID);
        $contractChild->parentID = $contractResponse;
        $contractChildResponse->modifiedBy = $userID;
        $contractChildResponse->changeDate = date('Y-m-d H:i:s');
        $contractChildResponse = $contractChild->save();
    }

    // platforms

    if (isset($data["platforms"]) &&  $contractResponse > 0) {
        $sql = "delete from contractPlatforms where contractID=" . $contractResponse;
        $contract->executenonquery($sql, null, true);
        //echo var_dump($data["platforms"]);
        foreach ($data["platforms"] as $item) {
            $cp = new contractPlatforms();
            $cp->contractID = $contractResponse;
            $cp->platformID = $item;
            $cp->save();
        }
    }

    $response->getBody()->write((string)$contractResponse);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->post('/contracts/delete/{contractID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contracts.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $user = new users($userID);
    $contractResponse = 0;
    if ($user->roleID == 1) {
        $contract = new contracts($args["contractID"]);
        $contractResponse = $contract->delete();
    }
    $response->getBody()->write((string)$contractResponse);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->post('/customer/contracts/save/{customerID}/{contractID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/customerContracts.php";

    $json = $request->getBody();
    $data = json_decode($json, true);
    $data = $data[0];
    $customerContractID = customerContracts::getCustomerContractID($args['customerID'], $args['contractID']);
    $customerContractID->ID > 0 ? $ID = $customerContractID->ID : $ID = 0;

    $customerContract = new customerContracts($ID);
    $customerContract->customerID = $args['customerID'];
    $customerContract->contractID = $args['contractID'];
    if (!$ID > 0) {
        $customerContract->termDate = date('Y-m-d');
    }
    $customerContract->term = $data['term'];
    $customerContract->isSent = $data['isSent'];
    $customerContract->isSigned = $data['isSigned'];
    $customerContract->parentID = $data['parentID'];
    $customerContract->dealTermID = $data['dealTermID'];
    $customerContract->contractApprovalDate = date('Y-m-d H:i:s');
    $customerContractResponse = $customerContract->save();
    $response->getBody()->write((string)$customerContractResponse);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->post('/customer/contracts/delete/{customerID}/{contractID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/customerContracts.php";

    $json = $request->getBody();
    $data = json_decode($json, true);
    $data = $data[0];
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $user = new users($userID);
    $customerContractResponse = 0;
    if ($user->roleID == 1) {
        $customerContractID = customerContracts::getCustomerContractID($args['customerID'], $args['contractID']);
        $customerContractID->ID > 0 ? $ID = $customerContractID->ID : $ID = 0;

        $customerContract = new customerContracts($ID);
        $customerContractResponse = $customerContract->delete();
    }
    $response->getBody()->write((string)$customerContractResponse);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->get('/contracts/info/getcontractsbychannel/{channelID}/{countryID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contracts.php";
    $contracts = new contracts();
    $contracts->getContractsByChannelID($args["channelID"], $args["countryID"]);
    $contractsResponse = checkNull($contracts->toJson);
    $response->getBody()->write($contractsResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

/* services related
/services/list/{serviceID}
*/

$app->get('/services/list/{userID}/{serviceID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/services.php";
    $userID = $args["userID"];
    $services = new services();
    $services->getServices($userID, $args["serviceID"]);
    $servicesResponse = checkNull($services->toJson);
    $response->getBody()->write($servicesResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

/* channels related
/channels/list
*/

$app->get('/channels/list', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/channels.php";
    $channels = new channels();
    $channels->getChannels();
    $channelsResponse = checkNull($channels->toJson);
    $response->getBody()->write($channelsResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));;

$app->get('/channels/list/{channelID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/channels.php";
    $channels = new channels($args["channelID"]);
    $channelsResponse = checkNull($channels->toJson());
    $response->getBody()->write($channelsResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));


$app->get('/channels/list/channelInfo/{channelID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/channels.php";
    $channels = new channels();
    $channels->getChannelInfo($args["channelID"]);
    $channelsResponse = checkNull($channels->toJson);
    $response->getBody()->write($channelsResponse);
    return $response->withHeader('Content-Type', 'application/json');
});

/* Customer Services related
/customer/services/list
*/

$app->get('/customer/services/list', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/customerServices.php";
    $customerServices = new customerServices();
    $customerServices->getCustomerServices();
    $customerServicesResponse = checkNull($customerServices->toJson);
    $response->getBody()->write($customerServicesResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

/* Definitions related
/customer/services/list
*/

$app->get('/definitions/{tableName}/{fieldName}[/{fieldID}/{filter}]', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/services.php";

    $tableNames = ["ticketErrorNames", "languages", "contentSubTypes", "artistRoles", "pricetiers", "packages"];
    $btResponse = null;
    if (in_array($args["tableName"], $tableNames)) {
        $bt = new services();
        $fieldID = isset($args["fieldID"]) ? $args["fieldID"] : "ID";
        $filter = isset($args["filter"]) ? $args["filter"] : "0=0";
        $filter =  str_replace('@', ' ', $filter);
        $filter =  str_replace('!', '%', $filter);
        $sql = "select " . $fieldID . "," . $args["fieldName"] . " from " . $args["tableName"] . " where " . $filter . " order by " . $args["fieldName"];
        //echo $sql;
        $btResult = $bt->executenonquery($sql, true);
        $btResponse = checkNull($bt->toJson);
    }
    $response->getBody()->write($btResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

/*
Uploads 
*/

$app->post('/uploads/{uploadType}/{contentID}/{albumID}[/{contentType}/{isOld}]', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/albums.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/tracks.php";
    require_once dirname(dirname(__FILE__)) . "/BL/functions.php";
    $json = $request->getBody();
    $data = json_decode($json, true);

    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
   
    isset($args["contentType"]) ? $contentType = $args["contentType"] : $contentType = 0;
    isset($args["isOld"]) ? $isOld = $args["isOld"] : $isOld = 0;
    $uID = 1;
    if ($args["uploadType"] == 'Booklet') {
        uploadBooklet($data, $userID, $args["contentID"], $args["albumID"], $isOld);
    } else if ($args["uploadType"] == 'Image') {
        uploadImage($data, $userID, $args["contentID"], $args["albumID"], $contentType, $isOld);
    } else if ($args["uploadType"] == 'Music') {
        uploadMusic($data, $userID, $args["contentID"], $isOld);
    } else if ($args["uploadType"] == 'Contract') {
        uploadContract($data);
    } else {
        $uID = -1;
    }
    $response->getBody()->write((string)$uID);
    return $response;
})->add(new JwtMiddleware($secretKey));


$app->post('/delete/booklet/{contentID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/tracks.php";
    $json = $request->getBody();
    $data = json_decode($json, true);

    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $userjwt = new users($userID);
    $customerID = $userjwt->customerID;
    $contentIDPath = dirname(dirname(__FILE__)) . "/uploads/" . $customerID . "/" . $args["contentID"] . "/";
    if (file_exists($contentIDPath . $data["upc"] . ".pdf")) {
        unlink($contentIDPath . $data["upc"] . ".pdf");
    }
    $track = new tracks($data["trackID"]);
    $trackID = $track->delete(1);
    $response->getBody()->write((string)$trackID);
    return $response;
})->add(new JwtMiddleware($secretKey));



//emailReports
$app->get('/settings/emailreports/{userID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/emailReports.php";
    $emailreport = new emailReports();
    $emailreport->getEmailReports($args["userID"]);
    $emailreportResponse = checkNull($emailreport->toJson);
    $response->getBody()->write($emailreportResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/settings/emailreports/save/{emailTypeId}/{period}/{userID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/emailReports.php";

    $json = $request->getBody();
    $data = json_decode($json, true);
    $data = $data[0];
    $emailReportResponse = 0;
    $emailReportDel = new emailReports();
    $emailReportDel->deleteEmailReports($args["userID"], $args['emailTypeId']);

    if ($data['period'] > 0 && $data['isChecked'] == true) {
        $emailReport = new emailReports();
        $emailReport->userID = $args["userID"];
        $emailReport->emailTypeID = $args['emailTypeId'];
        $emailReport->period = $args['period'];
        $emailReport->date_ = date('Y-m-d H:i:s');
        $emailReport->isDeleted = 0;
        $emailReportResponse = $emailReport->save();
    }

    $response->getBody()->write((string)$emailReportResponse);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->post('/settings/emailreports/delete/{emailreportID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/emailReports.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $emailReport = new emailReports($args["emailreportID"]);
    $emailReportResponse = 0;
    if ($userID == $emailReport->userID) {
        $emailReportResponse = $emailReport->delete();
    }
    $response->getBody()->write((string)$emailReportResponse);
    return $response;
})->add(new JwtMiddleware($secretKey));


/// Others Gets ///

$app->get('/getGenres/{contentType}/{languageCode}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/genres.php";
    $genres = new genres();
    $genres->getGenres($args["contentType"], $args["languageCode"]);
    $genresRes = checkNull($genres->toJson);
    $response->getBody()->write($genresRes);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/countries', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/country.php";
    $country = new country();
    $country->getCountries();
    $countryRes = checkNull($country->toJson);
    $response->getBody()->write($countryRes);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/getCountry', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/country.php";

    $json = $request->getBody();
    $data = json_decode($json, true);
    $country = new country();
    $country->getCountry($data["text"]);
    $countryRes = checkNull($country->toJson);
    $response->getBody()->write($countryRes);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));


$app->get('/languages', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/languages.php";
    $language = new languages();
    $language->getLanguages();
    $languageRes = checkNull($language->toJson);
    $response->getBody()->write($languageRes);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));


$app->get('/currencies', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/currency.php";
    $currency = new currency();
    $currency->getCurrencies();
    $currencyRes = checkNull($currency->toJson);
    $response->getBody()->write($currencyRes);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

// Others Post
$app->post('/sendmail/{userID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/functions.php";
    $json = $request->getBody();
    $data = json_decode($json, true);
    $data = $data[0];

    $mailres = sendEmail($args["userID"], $data['toFullname'], $data['toEmail'], $data['subject'], $data['body'], $data['templateID'], $data['channelID']);

    $response->getBody()->write($mailres);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->post('/readmail/{userID}', function ($request, $response, $args) {
    $json = $request->getBody();
    $data = json_decode($json, true);
    $data = $data[0];
    $mailID = $data['emailID'];

    require_once dirname(dirname(__FILE__)) . "/BL/Tables/mailQueue.php";
    $mailQueue = new mailQueue($mailID);
    $mailQueue->readDate = date('Y-m-d H:i:s');
    $mailres = $mailQueue->save();

    $response->getBody()->write((string)$mailres);
    return $response;
})->add(new JwtMiddleware($secretKey));

//// Admin Endpoints ////

$app->get('/getReviewsContents', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contents.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $contents = new contents();
    $contents->getReviewsContents($userID);
    $contentsRes = checkNull($contents->toJson);
    $response->getBody()->write($contentsRes);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/albumDistributionError/{contentID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contentstatus.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contents.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contentErrors.php";
    require_once dirname(dirname(__FILE__)) . "/BL/functions.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $json = $request->getBody();
    $data = json_decode($json, true);

    $cnt = new contents($args["contentID"]);
    $cnt->contentStatus = 3;
    $cnt->modifiedBy = $userID;
    $cnt->dateModified = date('Y-m-d H:i:s');
    $cnt->save();

    $cntError = new contentErrors();
    $cntError->contentID = $args["contentID"];
    $cntError->contentErrorID = $data["commentType"];
    $cntError->comment = $data["comment"];
    $cntError->dateCreated = date('Y-m-d H:i:s');
    $getOldSessionID = contentErrors::getOldSessionID($args["contentID"]);
    $sessionID = $getOldSessionID->sessionID != '' ? $getOldSessionID->sessionID : getRandomSessionID();
    $cntError->sessionID = $sessionID;
    $cntError->userID = $userID;
    $cntError->ticketType = 1;
    $cntError->status = 1;
    $cntError->save();

    $cntStatus = new contentStatus();
    $cntStatus->contentID = $args["contentID"];
    $cntStatus->deliveryID = 0;
    $cntStatus->status = 3;
    $cntStatus->platformID = 0;
    $cntStatus->userID = $userID;
    $cntStatus->sessionID = $sessionID;
    $cntStatus->dateCreated = date('Y-m-d H:i:s');
    $cntStatusID = $cntStatus->save();

    $response->getBody()->write((string)$cntStatusID);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->get('/getDeliveryAlbum/{albumID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contents.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $album = new contents();
    $album->getDeliveryAlbum($args["albumID"], $userID);
    $albumResponse = checkNull($album->toJson);
    $response->getBody()->write($albumResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/getLinkFires/{albumID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/linkFires.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    if ($userID > 0) {
        $albumID = isset($args["albumID"]) ? $args["albumID"] : 0;
    } else {
        $albumID = 0;
    }
    $lf = linkFires::getLinkfire($albumID, 1);
    $usResponse = checkNull($lf->toJson());
    $response->getBody()->write($usResponse);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/getTickets[/{albumID}]', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contentErrors.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    if (isset($args["albumID"])) {
        $albumID = $args["albumID"];
    } else {
        $albumID = 0;
    }
    $contents = new contentErrors();
    $contents->getTickets($userID, $albumID);
    $contentsRes = checkNull($contents->toJson);
    $response->getBody()->write($contentsRes);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));


$app->get('/platforms', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/platforms.php";
    $platforms = new platforms();
    $platforms->getPlatforms();
    $platformsRes = checkNull($platforms->toJson);
    $response->getBody()->write($platformsRes);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->get('/getTicketsbySessionID/{sessionID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contentErrors.php";
    $tickets = new contentErrors();
    $tickets->getTicketsbySessionID($args["sessionID"]);
    $ticketsRes = checkNull($tickets->toJson);
    $response->getBody()->write($ticketsRes);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/ticketClose/{sessionID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contentErrors.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $ticketClose = new contentErrors();
    $ID = $ticketClose->ticketClose($args["sessionID"], $userID);
    $response->getBody()->write((string)$ID);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->post('/ticket/save/{ID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contentErrors.php";
    require_once dirname(dirname(__FILE__)) . "/BL/functions.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $json = $request->getBody();
    $data = json_decode($json, true);

    $ticket = new contentErrors($args["ID"]);
    $data["contentID"] > 0 ? $ticket->contentID  = $data["contentID"] : "";
    $ticket->contentErrorID = $data["commentType"];
    $ticket->comment = $data["comment"];
    $ticket->dateCreated = date('Y-m-d H:i:s');
    $ticket->userID = $userID;
    $data["sessionID"] ? $sessionID = $data["sessionID"] : $sessionID = getRandomSessionID();
    $ticket->sessionID = $sessionID;
    $ticket->ticketType = $data["ticketType"];
    $ticket->status = $data["status"];
    $ticketID = $ticket->save();

    $ticketID > 0 ? $result["sessionID"] = $sessionID : $result["sessionID"] = -1;
    $result["ID"] = $ticketID;
    $result = json_encode($result, true);
    $response->getBody()->write($result);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/ticket/delete/{ID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contentErrors.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $ticket = new contentErrors($args["ID"]);
    if ($userID == $ticket->userID) {
        $ID = $ticket->delete(1);
    }
    $response->getBody()->write((string)$ID);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->post('/contentStatus/delete/{csID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/contentstatus.php";
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/users.php";

    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $user = new users($userID);
    $ID=0;
    if ($user->roleID==1 || $user->roleID==2) {
         $cs = new contentStatus($args["csID"]);
        $ID = $cs->delete(1);
    }
    $response->getBody()->write((string)$ID);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->get('/marketingContacts', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/marketingContacts.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $user = new users($userID);
    $mcRes = null;
    if ($user->roleID == 1) {
        $mc = new marketingContacts();
        $mc->getMarketingContacts();
        $mcRes = checkNull($mc->toJson);
    }
    $response->getBody()->write($mcRes);
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware($secretKey));

$app->post('/marketingContacts/delete/{ID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/marketingContacts.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $user = new users($userID);
    $ID = 0;
    if ($user->roleID == 1) {
        $mc = new marketingContacts($args["ID"]);
        $ID = $mc->delete(1);
    }
    $response->getBody()->write((string)$ID);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->post('/appleImports', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/appleImports.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $aiID = 0;
    $json = $request->getBody();
    $data = json_decode($json, true);
    $user = new users($userID);
    if ($user->email != "") {
        $ai = appleImports::getAppleImportsFromUpc($data["upc"], $userID);
        $ai->upc = $data["upc"];
        $ai->storeFront = $data["storeFront"];
        $ai->userID = $userID;
        $ai->status = 1;
        $aiID = $ai->save();
    }
    $response->getBody()->write((string)$aiID);
    return $response;
})->add(new JwtMiddleware($secretKey));

$app->post('/marketingContacts/save/{ID}', function ($request, $response, $args) {
    require_once dirname(dirname(__FILE__)) . "/BL/Tables/marketingContacts.php";
    $jwt = $request->getAttribute('jwt');
    $userID = $jwt->user_id;
    $json = $request->getBody();
    $data = json_decode($json, true);
    $user = new users($userID);
    $mcID = 0;
    if ($user->roleID == 1) {
        $mc = new marketingContacts($args["ID"]);
        $mc->company = $data["company"];
        $mc->customer = $data["customer"];
        $mc->email = $data["email"];
        $mc->phone = $data["phone"];
        $mc->contactType = $data["contactType"];
        $mcID = $mc->save();
    }
    $response->getBody()->write((string)$mcID);
    return $response;
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
