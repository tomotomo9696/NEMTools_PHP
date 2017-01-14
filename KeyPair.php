<?php
require_once ("./Sha3.php");
require_once ("./salt/autoload.php");
class Base32 {
   private static $map = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',  'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '2', '3', '4', '5', '6', '7', '=');

    public static function encode($input, $padding = true) {
        if(empty($input)) return "";
        $input = str_split($input);
        $binaryString = "";
        for($i = 0; $i < count($input); $i++) {
            $binaryString .= str_pad(base_convert(ord($input[$i]), 10, 2), 8, '0', STR_PAD_LEFT);
        }
        $fiveBitBinaryArray = str_split($binaryString, 5);
        $base32 = "";
        $i=0;
        while($i < count($fiveBitBinaryArray)) {
            $base32 .= self::$map[base_convert(str_pad($fiveBitBinaryArray[$i], 5,'0'), 2, 10)];
            $i++;
        }
        if($padding && ($x = strlen($binaryString) % 40) != 0) {
            if($x == 8) $base32 .= str_repeat(self::$map[32], 6);
            else if($x == 16) $base32 .= str_repeat(self::$map[32], 4);
            else if($x == 24) $base32 .= str_repeat(self::$map[32], 3);
            else if($x == 32) $base32 .= self::$map[32];
        }
        return $base32;
    }
}
class KeyPair {
    private $binaryPrivate = '';
    private $binaryPublic = '';
    private $Failed = false;
    public function __construct($private) {
        if (is_string($private)) {
            if (strlen($private) === 32) { // Binary private key
                $this->binaryPrivate = $private;
            } elseif (strlen($this->fixPrivateKey($private)) === 64) { //Hex private key
                $this->binaryPrivate = pack("H*", $this->HexReverse($this->fixPrivateKey($private)));
            } else {
                $this->Failed = true;
            }
        } elseif (is_array($private) && count($private) === 32) { //Array private key
            $this->binaryPrivate = pack("C*", ...array_merge($private));
        } else {
            $this->Failed = true;
        }
        if (!$this->Failed) {
            $this->calcPublic();
        }
    }
    private function calcPublic() {
        $hashed = Sha3::keccakhash($this->binaryPrivate, 512, true);
        $sliced = array_chunk(array_merge(unpack("C*", $hashed)), 32);
        $a = $sliced[0];
        $a[31]&= 127;
        $a[31]|= 64;
        $a[0]&= 248;
        $dataElement = Salt::decodeInput($a);
        $ed = Ed25519::instance();
        $R = new GeExtended();
        $ed->geScalarmultBase($R, $dataElement);
        $result = new FieldElement(32);
        $ed->GeExtendedtoBytes($result, $R);
        $this->binaryPublic = pack("C*", ...$result->toArray());
    }
    private function calcAddress() {
        $sha3Hash = Sha3::keccakhash($this->binaryPublic, 256, true);
        $ripemd160Hash = hash("ripemd160", $sha3Hash, true);
        $withNetworkByte = hex2bin("68") . $ripemd160Hash;
        $sha3HashWithNetworkByte = Sha3::keccakhash($withNetworkByte, 256, true);
        $checkSum = substr($sha3HashWithNetworkByte, 0, 4);
        $withCheckSum = $withNetworkByte . $checkSum;
        return Base32::encode($withCheckSum);
    }
    public function fixPrivateKey($privkey) {
        $privkey = "0000000000000000000000000000000000000000000000000000000000000000" . preg_replace("/^00/i", "", $privkey);
        return substr($privkey, -64);
    }
    public function HexReverse($hex) {
        $len = strlen($hex);
        $output = "";
        $i = $len - 2;
        while ($i >= 0) {
            $output.= substr($hex, $i, 2);
            $i-= 2;
        }
        return $output;
    }
    public function getHexPrivate() {
        if ($this->Failed) {
            return false;
        }
        return $this->HexReverse(unpack("H*", $this->binaryPrivate) [1]);
    }
    public function getHexPublic() {
        if ($this->Failed) {
            return false;
        }
        return bin2hex($this->binaryPublic);
    }
    public function getBinaryPrivate() {
        if ($this->Failed) {
            return false;
        }
        return $this->binaryPrivate;
    }
    public function getBinaryPublic() {
        if ($this->Failed) {
            return false;
        }
        return $this->binaryPublic;
    }
    public function getAddress() {
        if ($this->Failed) {
            return false;
        }
        return $this->calcAddress();
    }
    public function sign($data) {
        if ($this->Failed) {
            return false;
        }
        $hashed = Sha3::keccakhash($this->binaryPrivate, 512, true);
        $sliced = array_chunk(array_merge(unpack("C*", $hashed)), 32);
        $a = $sliced[0];
        $a[31]&= 127;
        $a[31]|= 64;
        $a[0]&= 248;
        $sm = new FieldElement(64);
        $hashed = Sha3::keccakhash(pack("C*", ...$sliced[1]) . $data, 512, true);
        $ed = Ed25519::instance();
        $r = Salt::decodeInput($hashed);
        $ed->scReduce($r);
        for ($i = 32;$i < 64;$i++) {
            $r[$i] = 0;
        }
        $R = new GeExtended();
        $ed->geScalarmultBase($R, $r);
        $ed->GeExtendedtoBytes($sm, $R);
        $slicedSm = new FieldElement(32);
        $slicedSm->copy($sm, 32);
        $hashed = Sha3::keccakhash($slicedSm->toString() . $this->binaryPublic . $data, 512, true);
        $h = Salt::decodeInput($hashed);
        $ed->scReduce($h);
        for ($i = 32;$i < 64;$i++) {
            $h[$i] = 0;
        }
        $x = new SplFixedArray(64);
        for ($i = 0;$i < 64;$i++) {
            $x[$i] = 0;
        }
        for ($i = 0;$i < 32;$i++) {
            $x[$i] = $r[$i];
        }
        for ($i = 0;$i < 32;$i++) {
            for ($j = 0;$j < 32;$j++) {
                $x[$i + $j]+= $h[$i] * $a[$j];
            }
        }
        $re = new SplFixedArray(32);
        $this->modL($re, $x);
        $sm->copy($re->toArray(), 32, 32);
        return unpack("H*", pack("C*", ...$sm)) [1];
    }
    private function modL($r, $x) {
        $L = array(0xed, 0xd3, 0xf5, 0x5c, 0x1a, 0x63, 0x12, 0x58, 0xd6, 0x9c, 0xf7, 0xa2, 0xde, 0xf9, 0xde, 0x14, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0x10);
        $carry;
        $i;
        $j;
        $k;
        for ($i = 63;$i >= 32;--$i) {
            $carry = 0;
            for ($j = $i - 32, $k = $i - 12;$j < $k;++$j) {
                $x[$j]+= $carry - 16 * $x[$i] * $L[$j - ($i - 32) ];
                $carry = ($x[$j] + 128) >> 8;
                $x[$j]-= $carry * 256;
            }
            $x[$j]+= $carry;
            $x[$i] = 0;
        }
        $carry = 0;
        for ($j = 0;$j < 32;$j++) {
            $x[$j]+= $carry - ($x[31] >> 4) * $L[$j];
            $carry = $x[$j] >> 8;
            $x[$j]&= 255;
        }
        for ($j = 0;$j < 32;$j++) $x[$j]-= $carry * $L[$j];
        for ($i = 0;$i < 32;$i++) {
            $x[$i + 1]+= $x[$i] >> 8;
            $r[$i] = $x[$i] & 255;
        }
    }
}
