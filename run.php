<?php
include "jsonRPCClient.php";

// Daemon
$servername = "127.0.0.1";
$daemonuser = "username";
$daemonpass = "7MNYJKkb7xKF3YZg3u5sA6FA3XxLj9MWYnje5hw52dVH";
$port = "6512";

$wallet = new jsonRPCClient('http://' . $daemonuser . ':' . $daemonpass . '@' . $servername . ':' . $port . '/');

// Test connection with call to getblockchaininfo
try {
    $info = $wallet->getblockchaininfo();
} catch (Exception $e) {
    echo "Failed to connect to daemon, check settings in conf and in run.php\n\n";
    exit;
}

echo "Client connected, reported block height " . $info['blocks'] . "\n\n";

// Get new address to send a coin to later
try {
    $address = $wallet->getnewaddress();
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n\n";
    exit;
}

// Test that Bitcore indexing available
try {
    $wallet->getaddressbalance($address);
} catch (Exception $e) {
    echo "Either Bitcore commands not available or addressindex=1 and txindex=1 not set in conf\n\n";
    exit;
}

// Make sure we have a coin to test with
try {
    $balance = $wallet->getbalance();
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
    exit;
}

if ($balance < 1) {
	echo "Balance less than 1 TZC, exiting demo. Balance " . $balance . "\n\n";
    exit;
}

// Send a coin to the new address created above
try {
    $txid = $wallet->sendtoaddress($address, 1);
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
    exit;
}

// Grab first source address from above TX to be destination for next TXs
try {
	// Get raw TXID for above TX
    $rawtxid = $wallet->getrawtransaction($txid, 1);

    // Get raw TXID for first input in above TX
    $input_rawtxid = $wallet->getrawtransaction($rawtxid['vin'][0]['txid'], 1);
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
    exit;
}

// Finally get the source address we've been looking for
$source_address = $input_rawtxid['vout'][$rawtxid['vin'][0]['vout']]['scriptPubKey']['addresses'][0];

echo "1 TZC sent from " . $source_address . " to " . $address . "\n\n";

// Look for confirmed UTXOs, we expect none
$confirmed_utxos = $wallet->getaddressutxos($address);

if (empty($confirmed_utxos)) {
	echo "Could not get any confirmed UTXOs from " . $address . " which is expected as we only just sent the TX to a newly generated address.\n\n";
} else {
	echo "We found confirmed UTXOs on the new address which is weird, either it's an address that's already been generated from the keypool before or it genuinely got added to a new block somewhere, either way lets just quit this demo.\n\n";
	exit;
}

// Now get the unconfirmed UTXOs
$unconfirmed_utxos = $wallet->getaddressmempool($address);

if (!empty($unconfirmed_utxos)) {
	echo "Found the UTXOs for " . $address . " in the mempool, this means that the TX they belong to are yet to be confirmed in a block, this does not prevent us from using them in new TXs\n\n";
} else {
	echo "No UTXOs found in the mempool, weird, lets just leave\n\n";
	exit;
}

// Let's build a TX to send 0.99 TZC back to the source address
try {
    $new_rawtxid = $wallet->createrawtransaction(
    	array( // Inputs
    		array(
    			"txid"=>$unconfirmed_utxos[0]['txid'],
    			"vout"=>$unconfirmed_utxos[0]['index']
    		)
    	),
    	array( // Outputs
    		$source_address=>0.99
    	)
    );
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
    exit;
}

echo "Created a new TX to send back most of the coin, TXID below if you want to decode it\n\n" . $new_rawtxid . "\n\n";

// Sign newly built TX
try {
    $signed = $wallet->signrawtransaction($new_rawtxid);
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
    exit;
}

echo "Signed the new TX, TXID below if you want to decode it\n\n" . $signed['hex'] . "\n\n";

// Send newly built TX
try {
    $new_txid = $wallet->sendrawtransaction($signed['hex']);
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
    exit;
}

echo "TX now sent back to the source address with the following TXID\n\n" . $new_txid . "\n\n";

echo "The TX was sent using a UTXO that was from the mempool and is yet to be confirmed.\n\n";
