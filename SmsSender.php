<?php
/*
 *  Key exchnage message format: "keyx base64EncodedPublicModulus base64encodedPublicExponent"
 *  expect $pubMod and $pubExp to be in base 256
 */
function sendKeyExchange($recipient, $pubMod, $pubExp) {
  // encode the pulic modulus and exponent
  $pubMod = base64_encode($pubMod);
  $pubExp = base64_encode($pubExp);

  $msg = "keyx $pubMod $pubExp";

  return sendSMS($recipient, $msg);
}

function sendSMS($recipient, $msg) {
  $msgLen = strlen($msg);
  $result = "";
  $command = "/usr/local/bin/gammu-smsd-inject TEXT $recipient -len $msgLen -text '$msg'";
  $result .= "executing command: $command \n";
  passthru($command, $output);
  $result .= $output." \n";
  return "SmsSender >> sendSMS(): $result \n";
}

?>
