<?php

$user = $_REQUEST['user'];
$token = $_REQUEST['token'];



$pret = new stdClass();
$pret->status = 1;
$pret->msg = "Something is not loaded properly. Please check and try again.";
$pret->res = "";
$proceed = false;


if (!array_key_exists($user, $r_api_users)) {
  $pret->msg = "User $user not known. Please check and try again.";
  die(json_encode($pret));
}


$uinfo = $r_api_users[$user];
$ip = $_SERVER['REMOTE_ADDR'];
if ((is_scalar($uinfo['ipallow']) && $uinfo['ipallow'] != '*') || (is_array($uinfo['ipallow']) && !in_array($ip, $uinfo['ipallow']))) {
  $pret->msg = "Host $ip is NOT allowed to invoke this API. Please check and try again.";
  die(json_encode($pret));
}

if ($token != $uinfo['token']) {
  $pret->msg = "Invalid Token specified. Please check and try again.";
  die(json_encode($pret));
}


if (isset($uinfo['rollpattern']) && $uinfo['rollpattern'] != '') {
  if (preg_match($uinfo['rollpattern'], $_REQUEST['roll']) !== 1) {
    $pret->msg = "Not permitted. Please check and try again. Contact your service provider if this problem persists.";
    die(json_encode($pret));
  }
}



#die();


# Interactive (from Web) part (CAPTCHA) required


if (!$proceed) {
  die(json_encode($pret)); 
}

define('API_DETECTED', true);
$API_DETECTED = true;
define('CAN_VIEW_RESULT', true);
$CAN_VIEW_RESULT = true;
define('DISPLAY_REGNO', true);
$DISPLAY_REGNO = true;
define('RESOLVE_EIIN', true);
$RESOLVE_EIIN = true;
define('RESOLVE_SUBJECT', true);
$RESOLVE_SUBJECT = true;

$env['DISPLAY_REGNO'] = true;
$env['DISPLAY_DOB'] = true;
$env['DISPLAY_SESSION'] = true;


require_once("getres_impl.php");

?>
