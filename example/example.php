<?php
require_once("../NEMToolsLoadAll.php");

/*
キーペアを作成します。パブリックキーの生成や署名ができます。
HEXの他に、バイナリ文字列、バイト配列に対応しています。

Create keypair.
*/
$kp = new KeyPair("2b01f02cfb32d2909cb3b9eca1420f92e02473426ea93b368f117e8514f564a6");
echo $kp->getHexPrivate() . PHP_EOL;
echo $kp->getHexPublic() . PHP_EOL;
echo $kp->getBinaryPrivate() . PHP_EOL;
echo $kp->getBinaryPrivate() . PHP_EOL;
echo $kp->getAddress() . PHP_EOL;

$message = "Test message.";
$hexmessage = bin2hex($message);
$data = array(
  'type' => 0x101,  //必須 トランザクションタイプです。普通の送金なら0x101です。
  'version' => getVersion("TESTNET"), //必須 getVersion("TESTNET" 又は "MAINNET") で取得できます。
  'signer' => $kp->getHexPublic(), //必須
  'recipient' => "TBEWB64H6WORLGN7RBWERJKL56FVBKWAKQAY3NFD", //必須 宛先です。スペースやハイフンを除いてください。
  'amount' => 10 * 1000000, //必須 金額です。単位はmicroXEM, 通常の金額を1000000倍してください。
  'fee' => calcFeeTransfer(10 * 1000000) + calcFeeMessage($message), //任意 手数料です。省略された場合自動で計算されます。それより多く設定したい場合などは明示してください。
  'message' => array( 'payload' => '', 'type' => 1), //任意 添付するメッセージです。現在 type 1 の非暗号化メッセージにしか対応していません。
  'timestamp' => timestamp2NEMTime(time()),  //任意 このトランザクションを作成した日時です。省略した場合 new した時の時間が設定されます。
  'deadline' => timestamp2NEMTime(time() + 7200) //任意 このトランザクションが承認されない場合キャンセルされる時間です。省略した場合 new した時の時間に2時間を足した時間が設定されます。
);
/*
$data = array(
  'type' => 0x101, Required
  'version' => getVersion("TESTNET"), //[Required] You can get this using getVersion("TESTNET" or "MAINNET")
  'signer' => $kp->getHexPublic(), //[Required]
  'recipient' => "TBEWB64H6WORLGN7RBWERJKL56FVBKWAKQAY3NFD", //[Required] Please remove space and hyphen.
  'amount' => 10 * 1000000, //[Required] microXEM.
  'fee' => calcFeeTransfer(10 * 1000000) + calcFeeMessage($message), //[optional] If omitted, it will be calculated automatically. microXEM.
  'message' => array( 'payload' => '', 'type' => 1), //[optional] Only supports the type 1(plain text).
  'timestamp' => timestamp2NEMTime(time()),  //[optional] If omitted, the current time is set.
  'deadline' => timestamp2NEMTime(time() + 7200) //[optional] If omitted, it will be set after 2 hours.
);
*/

/*
トランザクションを作成します。

Create transaction object.
*/
$tx = new TransactionBuilder($data);
echo $tx->getHex() . PHP_EOL;
echo $tx->getBinary() . PHP_EOL;

/*
トランザクションを署名します。

Sign the transaction.
*/
$signature = $kp->sign($tx->getBinary());

/*
NISアドレスを指定してNIS APIのリクエストを送ることができるオブジェクトを作成します。

Create NIS API Request object.
(https://github.com/NemProject/php2nem)
*/
$conf = array('nis_address' => '192.3.61.243');
$nem = new NEM($conf);

/*
GETリクエストを送信します。
Send get request.
*/
$res = $nem->nis_get('/account/get', array("address" => "TCSQGO6ZEW7353YYYSQPA2IQPPVDTLM5JLRUAP74"));
$json = json_decode($res, true);
var_dump($json);

/*
トランザクションをブロードキャストします。(POSTリクエスト)
Broadcast transaction.
*/
$res = $nem->nis_post('/transaction/announce', array("data" => $tx->getHex(), "signature" => $signature));
$json = json_decode($res, true);
var_dump($json);
