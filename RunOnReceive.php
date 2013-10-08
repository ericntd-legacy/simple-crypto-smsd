#!/usr/bin/php
<?php
// START: print out the start time of the script
$start = microtime(true);;
$output = "+++++++++++++++++++++++++++++++ \n";
$output .= date('l jS \of F Y h:i:s A')." SMS received, processing ... \n";

//include("MyKeyUtils.php");
include("/home/gmsntd/crypto/MyKeyUtils.php");
include('/home/gmsntd/crypto/SmsSender.php');

$smsMsg = "";
$keySize = 128;

// get the message parts from the environment variables
// and reconstruct the message
$numParts = getenv('SMS_MESSAGES');
$decodedParts = getenv('DECODED_PARTS');
$senderNo = getenv('SMS_1_NUMBER');
$output .= "number of message parts: $numParts \n";
$output .= "number of decode parts: $decodedParts \n";
$output .= "sender number: $senderNo \n";

for ($i=1; $i<=$numParts; $i++) {
	$output .= "processing message part number $i \n";
        $smsMsg .= getenv("SMS_".$i."_TEXT");
}

$output .= "the complete message received: $smsMsg \n";

// get the measurement from the message

if (!empty($smsMsg)) {
  $parts = array();
  $parts = explode(" ", $smsMsg);
	
  if (count($parts)==2) {
    // check if message is a measurement
    if (strcmp($parts[0], "gmstelehealth")==0) {
      $output .= "health measurement: $parts[1] \n";
			
      // decrypt the measurement
      $decrypted = decrypt(base64_decode($parts[1]), $keySize); // MyKeyUtils.php >> decrypt()
			
			$output .= "decrypted measurement: $decrypted \n";
			
    } else {
      $output .= "unknown message's prefix; known ones are 'keyx' or 'gmstelehealth' \n";
    }
  
  } else if (count($parts==3)) {
      // check if message is a key exchange message
      
    if (strcmp($parts[0], "keyx")==0) {
        $output .= "received a key exchange message, replying with a key exchange message \n";
        // message is a key exchange message
        // reply with a key exchange message
        $pubMod = getPublicModulus($keySize); // base256
        $pubExp = getPublicExponent($keySize);// base256
        $output .= "sending key exchange message to $senderNo, public modulus is $pubMod and exponent is $pubExp \n";
        $output .= sendKeyExchange($senderNo, $pubMod, $pubExp); // SmsSender.php >> sendKeyExchange()
        // TODO to save the public key received from the contact for future use
    } else {  
      $output .= "unknown message's prefix; known ones are 'keyx' or 'gmstelehealth' \n";
    }
  } else {
    $output .= "message is neither a key exchange message or contains encrypted measurement \n";
  }
} else {
  // get the message from the command line argument for testing
  //if ($argv[1]) {
    //$decrypted = decrypt($argv[1]);

    //echo "decrypted: $decrypted \n";
  //}
  $output .= "message is blank \n";
}

/*
 * Execute Kaung's script passing the message and the sender's number
 * Message and sender's no are queried from the database instead of read from the environment variable
 */
$con=mysqli_connect("localhost","root","pa55w0rd","smsd");
// Check connection
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
}
if ($argv&&count($argv)>0) {
  $output .= count($argv)." arguments were passed to RunOnReceive.php, supposedly all IDs of SMS messages in inbox table \n";
  for ($i=1; $i<count($argv); $i++) {
    $output .= "argument number $i : $argv[$i] \n";
    $query = "SELECT * FROM inbox WHERE ID = ".$argv[$i];
    $output .= "mysql query: $query \n";
    $result = mysqli_query($con,$query);
    $row = mysqli_fetch_array($result);
    $message = $row['TextDecoded'];
    $output .= "message: $message \n";
    $message=preg_replace('/\s+/', '', $message);
    $senderNum = $row['SenderNumber'];
    $output .= "sender's number: $senderNum \n";
    $command = "/usr/bin/php /var/www/html/smsrouter/execute_query.php $message $senderNum >> /var/www/html/smsrouter/execute_query.log";
    $output .= "executing Kaung's script: $command \n";
    passthru($command, $log);
    $output .= $log. " \n";
    
    // send an acknowledgement back
    /*$command = "/usr/local/bin/gammu-smsd-inject TEXT $senderNum -text 'Measurement received'";
    $output .= "sending acknowledgement back: $command \n";
    passthru($command, $log);
    $output .= $log. "\n";
     */
  }
} else {
  $output .= "no arguments were passed to this script";
}
mysqli_close($con);


// END print out the end time of the script and the time take to execute it
$output .= date('l jS \of F Y h:i:s A')." Finished processing SMS ... \n";
$time_taken = microtime(true) - $start;
$output .= "RunOnReceive script took $time_taken milliseconds to execute \n";
$output .= "------------------------------------ \n";
echo $output;
?>
