<?php
/*
 * phpseclib is required for this script to work
 * phpseclib/ directory should be in the same directory as this script
 */
include('./phpseclib/Math/BigInteger.php');
include('./phpseclib/Crypt/RSA.php');

define("PRIVATE_KEY_FILE", "/home/gmsntd/keys/private.pem");

define("PRIVATE", "privatekey");// IMPORTANT: the constant must have value 'privatekey' to be consistent with phpseclib's convention
define("PUBLIC", "publickey");// IMPORTANT: the constant must have value 'publickey' to be consistent with phpseclib's convention

//define("PUBLIC_KEY_FILE", "public.pem");

//$rsa = new Crypt_RSA();

//echo PRIVATE;
//echo constant("PRIVATE");

/*
* get the modulus
* does not include base64 encoding, return base256
*/
function getPublicModulus($size = 2048) {
	$modulus = "";
	
	// get the public key
	$rsa = new Crypt_RSA();

  $keyArr = array();
  $keyArr = getKeys($size);

  $privKey = $keyArr[constant("PRIVATE")];
  
  $rsa->loadKey($privKey);
	$rsa->loadKey($rsa->getPublicKey());// if it does not work, I'll have to remove this line
	
	$modulus = $rsa->modulus->toBytes();// this returns base256 while toString() returns base10
	echo "modulus in base 256: ".$modulus." and in base 10: ".$rsa->modulus->toString()." \n";
	return $modulus;
}

/*
* get the exponent of the public key
* does not include base64 encoding, returns base256
*/
function getPublicExponent($size = 2048) {
	$exponent = "";
	
	// get the public key
	$rsa = new Crypt_RSA();

  $keyArr = array();
  $keyArr = getKeys($size);

  $privKey = $keyArr[constant("PRIVATE")];

  $rsa->loadKey($privKey);
	$rsa->loadKey($rsa->getPublicKey());
	
	$exponent = $rsa->exponent->toBytes();// this returns base256 while toString() returns base10
	
	echo "exponent in base 256: ".$exponent." and in base 10: ".$rsa->exponent->toString()." \n";
	return $exponent;
}

/*
* Encrypt a message using RSA algorithm
* IMPORTANT: setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1) is required for client end decryption to be successful
* phpseclib is used
* this does not base64 encoding, return base256
*/
function encrypt($message, $size = 2048) {
  $result = "";
	
  // getthe keys ready for decryption
  $rsa = new Crypt_RSA();

  $keyArr = array();
  $keyArr = getKeys($size);

  $privKey = $keyArr[constant("PRIVATE")];
  $result .= "private key found: $privKey \n";
  $rsa->loadKey($privKey);
  $rsa->loadKey($rsa->getPublicKey());
	// IMPORTANT: setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1) is required as client end decryption use PKCS1 padding scheme
  $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
  
  $encrypted = $rsa->encrypt($message);
	// not sure how I can debug like I do with OpenSSL
	
  if ($encrypted) {
    return $encrypted;
  }

  $result .= $encrypted." \n";

  return $result;
}

/*
* Decrypt a message which is encrypted using RSA algorithm
* IMPORTANT: setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1) is required as client end decryption use PKCS1 padding scheme
* phpseclib is used
* this does not incldue base64 decoding, input message is assumed to be in base256
*/
function decrypt($message, $size = 2048) {
  $result = "start decrypting $message \n";
  
  // getthe keys ready for decryption
  $rsa = new Crypt_RSA();

  $keyArr = array();
  $keyArr = getKeys($size);

  $privKey = $keyArr[constant("PRIVATE")];
  $result .= "going to use the following key for decryption: $privKey \n";
  $rsa->loadKey($privKey);
	
	// IMPORTANT: setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1) is required as client end decryption use PKCS1 padding scheme
  $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
  
  $decrypted = $rsa->decrypt($message);
	// not sure how I can debug like I do with OpenSSL
	
  if ($decrypted) {
    return $decrypted;
  }

  $result .= "done decrypting $decrypted \n";

  return $result;
}

// an alternative to decrypt() method
// not tested
function decryptOpenSSL($message, $size = 2048) {
	$result = "";
	
	// Base64 decode the message
	$decoded = base64_decode($message);
	$result .= "decoded $decoded \n";
	
	// decrypt the message
	if ($decoded) {
	} else {
		$result .= "failed to decode";
		return $result; // point of failure #1
	}
	
	// get the keys ready for decryption
	$keyArr = array();
	$keyArr = getKeysOpenSSL($size);
		
	if ($keyArr) {
		$privateKey = $keyArr[constant("PRIVATE")];
		$tmp = openssl_private_decrypt(base64_decode($message), $decrypted, $privateKey, OPENSSL_PKCS1_PADDING);
		
	  while ($errorMsg = openssl_error_string()) {
                $result .=  "error: $errorMsg \n";
                //echo "$msg\n";
    }
       	
 	  if ($tmp) {
 			$result = $decrypted;
 			return $result; // point of success
 	  } else {
 			$result .= "failed to decrypt";
 			return $result; // point of failure #2
 	  }
	} else {
		$result .= "could not retrieve key";
	}
	
	return $result;
}

/*
* read the existings RSA keys
* or trigger the generation of new keys
* phpseclib is used
*/
function getKeys($size = 2048) {
  $rsa = new Crypt_RSA();
  //$privKey = readKeyFromFile('private-phpseclib.pem');
  $privKey = file_get_contents(constant("PRIVATE_KEY_FILE"));
  $rsa->loadKey($privKey);
  $pubKey = $rsa->getPublicKey();
  //$pubKey = readKeyFromFile('public-phpseclib.pem');
  if (empty($privKey)||empty($pubKey)) {
  	echo "couldnt find keys, trying to regenerate them \n";
    return generateKeys($size);
  } else {
    echo "keys found, not generating \n";
    $keyArr = array();
    $keyArr[constant("PRIVATE")] = $privKey;
    $keyArr[constant("PUBLIC")] = $pubKey;
    //var_dump($keyArr);

    return $keyArr;
  }
}

// an alternative to getKeys()
// not tested
function getKeysOpenSSL($size = 2048) {
	$keyArr = array();

	$pubKey = "";
	$privKey = "";
	
	// attempt to retrive existing private key
	$privKey = openssl_pkey_get_private(file_get_contents(constant("PRIVATE_KEY_FILE")));
	
	$pubKey = openssl_pkey_get_public(file_get_contents(constant("PRIVATE_KEY_FILE")));
	/*if(file_exists(PRIVATE_KEY_FILE)) {
		$privFH = fopen($privateKeyFile, "r");
		if (!$privFH) {
			echo "can't open $privateKeyFile \n";
			//resetKeys();
		} else {
			if (filesize($privateKeyFile)>0) {
				$privKey = fread($privFH, filesize($privateKeyFile));
				fclose($privFH);
	
				if (!$privKey) {
					echo "Private key is null how is that possible? \n";
					//resetKeys();
		
				} else {
					echo "private key found \n";
				}
			} else {
				echo "somehow $privateKeyFile is empty";
				//resetKeys();
			}
		}

	} else {
		echo "$privateKeyFile does not exist";
	}*/
	
	// attempt to retrive existing public key
	
	if ($pubKey&&$privKey&&!empty($pubKey)&&!empty($privKey))  {
		$keyArr[constant("PRIVATE")] = $privKey;
   	$keyArr[constant("PUBLIC")] = $pubKey;
             
		return $keyArr;	
	} else {
		echo "couldnt find keys, trying to regenerate them \n";
		return resetKeys($size);
	}
}

/*
* generate RSA private and public keys
* phpseclib is used
*/
function generateKeys($size = 2048) {
  $rsa = new Crypt_RSA();
  // PKCS1 padding scheme is used by default, included here just for consistency and clarity
  $rsa->setPrivateKeyFormat(CRYPT_RSA_PRIVATE_FORMAT_PKCS1);
  $rsa->setPublicKeyFormat(CRYPT_RSA_PUBLIC_FORMAT_PKCS1);
  $keyArr = array();
  $keyArr = $rsa->createKey($size);
  
  echo "generated public key: ".$keyArr[constant("PUBLIC")]." \n";

  $keyStr = $keyArr[constant("PRIVATE")];
  echo "generated private key $keyStr \n";
  $keyFileName = constant("PRIVATE_KEY_FILE");

  saveKeyToFile($keyStr, $keyFileName);
  //saveKeyToFile($keyArr[PUBLIC], "public-phpseclib.pem");

  //var_dump($keyArr);
  return $keyArr;
}

/*
* Helper functions
*/
function saveKeyToFile($keyStr, $keyFileName) {
  if (!$handle = fopen($keyFileName, 'w+')) {
    echo "Cannot open file ($keyFileName)";
    exit;
  }

  // Write $somecontent to our opened file.
  if (fwrite($handle, $keyStr) === FALSE) {
    echo "Cannot write to file ($keyFileName)";
    exit;
  }
  
  echo "Success, wrote ($keyStr) to file ($keyFileName) \n";
  
  fclose($handle);
}

function readKeyFromFile($keyFileName) {
    $result = "";
      if (!$handle = fopen($keyFileName, 'r')) {
            echo "Cannot open file ($keyFileName)";
                return $result;
                exit;
                  }

      $result = fread($handle, filesize($keyFileName));
      fclose($handle);
        return $result;
}

?>
