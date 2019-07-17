<?php
include 'config.php';
include 'Medoo.php';
use Medoo\Medoo;
include 'Telegram.php';
$database = new medoo($config);
$telegram = new Telegram($bot_token);

$data = file_get_contents('http://f.apiplus.net/pl3-1.json');
$data = json_decode($data,true);
$opencode = explode(",",$data['data'][0]['opencode']);
$expect = $data['data'][0]['expect'];
$expect1 = $expect + 1;
$lastopen = $database->get("lottery", [
    "expect",
    "opencode"
], [
    "id" => 1
]);
$opendates = $lastopen["expect"] - 1;
if($opendates == $expect and $lastopen["opencode"] == 0){
$data = $database->select("game", [
    "id",
    "user",
    "project",
    "type",
    "result",
    "data"
], [
    "expect" => $expect
]);
$database->insert("lottery", [
    "expect" => $expect,
    "opencode" => $opencode[0]
]);
if ($opencode[0]%2 == 0) {
    $result1 = '双';
} else {
    $result1 = '单';
}
if ($opencode[0] >= 5) {
    $result2 = '大';
} else {
    $result2 = '小';
}
//流量输掉的奖池
$data1 = $database->sum("game","data", [
    "expect" => $expect,
    "project[!]" => [$result1,$result2],
    "type" => '流量',
    "result" => '未开奖'
]);
$data2 = $database->sum("game","data", [
    "expect" => $expect,
    "project" => [$result1,$result2],
    "type" => '流量',
    "result" => '未开奖'
]);
//余额输掉的奖池
$data3 = $database->sum("game","data", [
    "expect" => $expect,
    "project[!]" => [$result1,$result2],
    "type" => '余额',
    "result" => '未开奖'
]);
$data4 = $database->sum("game","data", [
    "expect" => $expect,
    "project" => [$result1,$result2],
    "type" => '余额',
    "result" => '未开奖'
]);
//积分输掉的奖池
$data5 = $database->sum("game","data", [
    "expect" => $expect,
    "project[!]" => [$result1,$result2],
    "type" => '积分',
    "result" => '未开奖'
]);
$data6 = $database->sum("game","data", [
    "expect" => $expect,
    "project" => [$result1,$result2],
    "type" => '积分',
    "result" => '未开奖'
]);
$text = '本次开奖数字：'.$opencode[0].'
流量奖池共 '.$data1.' M 倍率：'.round($data1/$data2,2).'
余额奖池共 '.$data3.' 元倍率：'.round($data3/$data4,2).'
积分奖池共 '.$data5.' 分倍率：'.round($data5/$data6,2).'
';
foreach ($data as $datas) {
  if ($score_bet) {
    $res = $database->get("user", [
      "id",
      "transfer_enable",
      "u",
      "d",
      "score",
      "balance"
    ] , [
      "wechat" => $username
    ]);
  } else {
    $res = $database->get("user", [
      "id",
      "transfer_enable",
      "u",
      "d",
      "balance"
    ] , [
      "wechat" => $username
    ]);
  }
  if ($datas['result'] == '未开奖') {
    if ($datas['type'] == '流量') {
            $rrr1 = ceil(($datas['data']/$data2)*$data1);
            $rrr = ($datas['data'] + $rrr1)*1024*1024;
            $database->update("user", [
                "transfer_enable[+]" => $rrr
            ], [
                "wechat" => $datas['user']
            ]);
              
            $database->insert("user_traffic_modify_log", [
                "user_id" => $res['id'],
                "order_id" => 0,
                "before" => $res['transfer_enable'],
                "after" => $res['transfer_enable'] + $rrr,
                "desc" => '彩票中奖',
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            ]);
          
        } elseif ($datas['project'] == $result1 OR $datas['project'] == $result2) {
        $result = '已中奖';
        if ($datas['type'] == '余额') {
            $rrr1 = round(($datas['data']/$data4)*$data3,2); 
            $rrr2 = $datas['data'] + $rrr1;
            $rrr = round(($rrr2*100,2); 
            $database->update("user", [
                "balance[+]" => $rrr
            ], [
                "wechat" => $datas['user']
            ]);
              
            $database->insert("user_balance_log", [
                "user_id" => $res['id'],
                "order_id" => 0,
                "before" => $res['balance'],
                "after" => $res['balance'] + $rrr,
                "amount" => $rrr,
                "desc" => '彩票中奖',
                "created_at" => date("Y-m-d H:i:s")
            ]);
          
        } elseif ($datas['type'] == '积分') {
            $rrr1 = round(($datas['data']/$data6)*$data5,2); 
            $rrr = $datas['data'] + $rrr1;
            $database->update("user", [
                "score[+]" => $rrr
            ], [
                "wechat" => $datas['user']
            ]);
              
            $database->insert("user_score_log", [
                "user_id" => $res['id'],
                "before" => $res['score'],
                "after" => $res['score'] + $rrr,
                "score" => $rrr,
                "desc" => '彩票中奖',
                "created_at" => date("Y-m-d H:i:s")
            ]);
          
        }
        $text = $text.'
@'.$datas['user'].' 第'.$expect.'期彩票已中奖，获得'.$datas['type'].' '.$rrr1;

    } else {
        $text = $text.'

@'.$datas['user'].' 第'.$expect.'期彩票未中奖';
        $result = '未中奖';
    }
    $database->update("game", [
        "result" => $result
    ], [
        "id" => $datas['id']
    ]);
}
  }
  
  if ($group_id != null) {
    $content = array('chat_id' => $group_id, 'text' => $text);
    $telegram->sendMessage($content);
  }
  if ($channel_id != null) {
    $content = array('chat_id' => $channel_id, 'text' => $text);
    $telegram->sendMessage($content);
  }
    $database->update("lottery", [
                "opencode" => 1
            ], [
                "id" => 1
            ]);
}

if($lastopen["expect"] == 1){
    $database->update("lottery", [
                "expect" => $expect1
            ], [
                "id" => 1
            ]);
}
echo 'success';
?>