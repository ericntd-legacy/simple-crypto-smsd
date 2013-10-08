<?php
include('/home/gmsntd/crypto/MyKeyUtils.php');
include('/home/gmsntd/crypto/SmsSender.php');

$phoneNo = "+6596147928";

if (isset($argv)&&isset($argv[1])) {
	$phoneNo = $argv[1];
}

// the modulus
$modBase256 = getPublicModulus(128);
$modBase64Encoded = base64_encode($modBase256); // this is what we will send out

echo "length: base256: ".strlen($modBase256)." - and base64: ".strlen($modBase64Encoded)." \n";

// the exponent of the public key
$expBase256 = getPublicExponent(128);
$expBase64Encoded = base64_encode($expBase256);

// public key string: "modulus exponent"
$keyXMsg = "keyx $modBase64Encoded $expBase64Encoded";
$msgLen = strlen($keyXMsg);
echo "key exchange message length: $msgLen and message is: $keyXMsg \n";

//echo sendSMS($phoneNo, $keyXMsg);
//echo sendKeyExchange($phoneNo, $modBase256, $expBase256);

/*$encrypted = encrypt("etst message", 128);

$decrypted = decrypt($encrypted, 128);
echo "decrypted: $decrypted \n";

// send the key exchange message through SMS, leveraging gammu-smsd-inject command
$command = "gammu-smsd-inject TEXT $phoneNo -len $msgLen -text '$keyXMsg' ";
echo "executing command: $command \n";
$output = "";
passthru($command, $output);
echo $output;
 */
?>
