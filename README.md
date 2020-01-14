TrezarCoin 0-conf Bitcore Demo
=====================================

Demonstration of using an output from an unconfirmed transaction as the input in another transaction with multiple outputs back to the address of the source of the first input in the unconfirmed transaction. The Bitcore indexing is used as part of the demonstration to get the balance and UTXOs of addresses.

trezarcoin.conf  
```
server=1
listen=1
rpcuser=username
rpcpassword=7MNYJKkb7xKF3YZg3u5sA6FA3XxLj9MWYnje5hw52dVH
rpcallowip=127.0.0.1
rpcport=6512
```

Install PHP  
```
sudo apt install -y php
```

Running the script  
```
php -f run.php
```
