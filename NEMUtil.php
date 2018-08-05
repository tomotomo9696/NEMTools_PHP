<?php
function calcFeeTransfer($amount) { //単位はmicroXEM
    $x = floor(max(1, ($amount / 1000000) / 10000));
    return 0.05 * $x * 1000000;
}
function calcFeeMessage($message) { //HEXではなくbinary
    if(strlen($message) === 0){
        return 0;
    }else{
        return 0.05 * (floor(strlen($message) / 32) + 1) * 1000000;
    }
}
function int2binary($int) {
    return pack("V", $int);
}
function long2binary($longlong) {
    $highMap = 0xffffffff00000000;
    $lowMap = 0x00000000ffffffff;
    $higher = ($longlong & $highMap) >> 32;
    $lower = $longlong & $lowMap;
    $packed = pack('VV', $lower, $higher);
    return $packed;
}
function timestamp2NEMTime($timestamp) {
    return $timestamp - 1427587585;
}
function getVersion($network = "TESTNET") {
    if (strtoupper($network) === "MAINNET") {
        return 0x68000001;
    } else {
        return 0x98000001;
    }
}
