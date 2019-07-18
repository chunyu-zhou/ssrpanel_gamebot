<?php
include 'config.php';
include 'Medoo.php';
use Medoo\Medoo;
include 'Telegram.php';
$database = new medoo($config);
$telegram = new Telegram($bot_token);
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
$result = $telegram->getData();
$text = $result['message'] ['text'];
$chat_id = $result['message']['chat']['id'];
$message_id = $result['message']['message_id'];
$username = $result['message']['from']['username'];
$username = strtolower($username);
if ($text) {
  $options = [['注册', '账户查询', '下注'], ['充值', '开奖查询', '取消']];
  if ($text == '/start' OR $text == '取消' OR $text == '/newgame' OR $text == '/newgame'.$bot_name) {
    $redis->del($username);
    $keyb = $telegram->buildKeyBoard($options, $onetime = true, $resize = true, $selective = true, $selective = true);
    $content = ['chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => '欢迎使用GameBot。本游戏每天根据-体彩排列三-第一位数-开奖，数据公平、公正、公开。', 'reply_to_message_id' => $message_id ];
    $log = $telegram->sendMessage($content);
  } elseif ($text == '注册') {

    $name = $database->get("user","username", [
      "wechat" => $username
    ]);
    if ($name) {
      $texts = '绑定成功。用户名：'.$name;
    } else {
      $texts = '您的Telegram未绑定账号。';
    }

    $content = ['chat_id' => $chat_id, 'text' => $texts, 'reply_to_message_id' => $message_id ];
    $log = $telegram->sendMessage($content);
  } elseif ($text == '账户查询') {
    $scoretext='';
    if ($score_bet) {
      $res = $database->get("user", [
        "transfer_enable",
        "u",
        "d",
        "score",
        "balance"
      ] , [
        "wechat" => $username
      ]);
      $scoretext='积分剩余：'.$res['score'].' 分';
    } else {
      $res = $database->get("user", [
        "transfer_enable",
        "u",
        "d",
        "balance"
      ] , [
        "wechat" => $username
      ]);
    } 
    $data = $database->select("game", [
      "expect",
      "project",
      "type",
      "data",
      "result"
    ], [
      "user" => $username,
      "ORDER" => ["id"=>"DESC"],
      "LIMIT" => 10
    ]);
    foreach ($data as $datas) {
      $textss = $textss.'

第'.$datas['expect'].'期：
投注：['.$datas['type'].']['.$datas['project'].']['.$datas['data'].']
结果：'.$datas['result'];
    }
    if ($res) {
      $texts = '账户信息：
流量剩余：'.intval(($res['transfer_enable']-$res['u']-$res['d'])/1024/1024).' MB
余额剩余：'.round($res['balance']/ 100, 2).' 元
'.$scoretext.'

下注记录：'.$textss;
    } else {
      $texts = '您的Telegram未绑定账号。';
    }

    $content = ['chat_id' => $chat_id, 'text' => $texts, 'reply_to_message_id' => $message_id ];
    $log = $telegram->sendMessage($content);
  } elseif ($text == '充值') {
    $content = ['chat_id' => $chat_id, 'text' => '请前往官网'.$url.' 充值余额或购买流量。', 'reply_to_message_id' => $message_id ];
    $log = $telegram->sendMessage($content);
  } elseif ($text == '开奖查询') {
    $lot = $database->get("lottery", [
      "expect"
    ], [
      "id" => 1
    ]);
    //流量奖池
    $data1 = $database->sum("game","data", [
      "expect" => $lot['expect'],
      "type" => '流量',
      "result" => '未开奖'
    ]);
    //余额奖池
    $data2 = $database->sum("game","data", [
      "expect" => $lot['expect'],
      "type" => '余额',
      "result" => '未开奖'
    ]);
    //积分奖池
    $data3 = $database->sum("game","data", [
      "expect" => $lot['expect'],
      "type" => '积分',
      "result" => '未开奖'
    ]);
    $t_text='';
    $b_text='';
    $s_text='';
    if ($transfer_bet) {
      $t_text =  '流量投注共 '.round($data1, 2).' M
';
    } 
    if ($balance_bet) {
      $b_text = '余额投注共 '.round($data2, 2).' 元
';
    } 
    if ($score_bet) {
      $s_text =  '积分投注共 '.round($data3, 2).' 分';
    } 
    $text1 ='第'.$lot['expect']. '期投注情况
'.
      $t_text.
      $b_text.
      $s_text.'

往期开奖号码
';
    $data = $database->select("lottery", [
      "expect",
      "opencode"
    ], [
      "id[!]" => 1,
      "ORDER" => ["lottery.id" => "DESC"],
      "LIMIT" => 5
    ]);
    foreach ($data as $datas) {
      $texts = $texts.'第'.$datas['expect'].'期：'.$datas['opencode'].'
';
    }
    $content = ['chat_id' => $chat_id, 'text' => $text1.$texts, 'reply_to_message_id' => $message_id ];
    $log = $telegram->sendMessage($content);
  } elseif ($text == '下注') {
    $bet = [];
    if ($balance_bet) {
      $bet[] = '余额';
    } 
    if ($transfer_bet) {
      $bet[] =  '流量';
    } 
    if ($score_bet) {
      $bet[] = '积分';
    } 
    $option = [$bet, ['取消']];
    $keyb = $telegram->buildKeyBoard($option, $onetime = true, $resize = true, $selective = true);
    $content = ['chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => '请选择货币类型', 'reply_to_message_id' => $message_id ];
    $log = $telegram->sendMessage($content);
  } elseif (($text == '余额' AND $balance_bet)  OR( $text == '流量' AND $transfer_bet)  OR ($text == '积分' AND $score_bet) ) {
    $redis->hSet($username, '货币类型', $text);
    $option = [['大', '小', '单', '双'], ['取消']];
    $keyb = $telegram->buildKeyBoard($option, $onetime = true, $resize = true, $selective = true);
    $content = ['chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => '请选择投注的项目', 'reply_to_message_id' => $message_id ];
    $log = $telegram->sendMessage($content);
  } elseif (($text == '大' OR $text == '小' OR $text == '单' OR $text == '双') AND $redis->hExists($username, '货币类型') == 'true') {
    $redis->hSet($username, '投注项目', $text);
    $option = [['1', '10', '1024', '10240'], ['取消']];
    $keyb = $telegram->buildKeyBoard($option, $onetime = true, $resize = true, $selective = true);
    $content = ['chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => '请输入投注数额', 'reply_to_message_id' => $message_id ];
    $log = $telegram->sendMessage($content);
  } elseif (is_numeric($text) AND $redis->hExists($username, '投注项目') == 'true' AND $text > 0 AND $username != NULL) {
    if ($score_bet) {
      $res = $database->get("user", [
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
        "transfer_enable",
        "u",
        "d",
        "balance"
      ] , [
        "wechat" => $username
      ]);
    }
    $data = intval(($res['transfer_enable']-$res['u']-$res['d'])/1024/1024);
    if (($redis->hGet($username, '货币类型') == '流量' AND $data >= $text AND $transfer_bet) 
        OR ($redis->hGet($username, '货币类型') == '余额' AND round($res['balance']/ 100, 2) >= $text AND $balance_bet) 
        OR ($redis->hGet($username, '货币类型') == '积分' AND $res['score'] >= $text AND $score_bet == true)) {

      if ($redis->hGet($username, '货币类型') == '流量') {
        $rrr = $text*1024*1024;
        $database->update("user", [
          "transfer_enable[-]" => $rrr
        ], [
          "wechat" => $username
        ]);

        $database->insert("user_traffic_modify_log", [
          "user_id" => $res['id'],
          "order_id" => 0,
          "before" => $res['transfer_enable'],
          "after" => $res['transfer_enable'] - $rrr,
          "desc" => '购买彩票',
          "created_at" => date("Y-m-d H:i:s"),
          "updated_at" => date("Y-m-d H:i:s")
        ]);

      } elseif ($redis->hGet($username, '货币类型') == '余额') {
        $money = $text*100;
        $database->update("user", [
          "balance[-]" => $money
        ], [
          "wechat" => $username
        ]);

        $database->insert("user_balance_log", [
          "user_id" => $res['id'],
          "order_id" => 0,
          "before" => $res['balance'],
          "after" => $res['balance'] - $money,
          "amount" => -$money,
          "desc" => '购买彩票',
          "created_at" => date("Y-m-d H:i:s")
        ]);

      } elseif ($redis->hGet($username, '货币类型') == '积分') {
        $database->update("user", [
          "score[-]" => $text
        ], [
          "wechat" => $username
        ]);

        $database->insert("user_score_log", [
          "user_id" => $res['id'],
          "before" => $res['score'],
          "after" => $res['score'] - $text,
          "score" => -$text,
          "desc" => '购买彩票',
          "created_at" => date("Y-m-d H:i:s")
        ]);

      }
      $expect = $database->get("lottery", "expect", [
        "id" => 1
      ]);
      $database->insert("game", [
        "user" => $username,
        "project" => $redis->hGet($username, '投注项目'),
        "type" => $redis->hGet($username, '货币类型'),
        "data" => $text,
        "result" => '未开奖',
        "expect" => $expect
      ]);
      $redis->del($username);
      $texts = '恭喜您投注成功 体彩排列三 第'.$expect.'期。';
    } else {
      $texts = '投注失败，'.$redis->hGet($username, '货币类型').'不足。';
    }
    $keyb = $telegram->buildKeyBoard($options, $onetime = true, $resize = true, $selective = true);
    $content = ['chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => $texts, 'reply_to_message_id' => $message_id ];
    $log = $telegram->sendMessage($content);
  }
}

//记录机器人发送的消息和用户命令消息的ID
if ($log AND $telegram->messageFromGroup()) {
  $mid = $log['result']['message_id'];
  $m = array('id' => $mid, 'time' => time(), 'chat_id' => $chat_id);
  $redis->lpush('gamebotmessage', serialize($m));
  $mm = array('id' => $message_id, 'time' => time(), 'chat_id' => $chat_id);
  $redis->lpush('gamebotmessage', serialize($mm));
}

//自动删除消息
$me = $redis->lrange('gamebotmessage', 0, -1);
foreach($me as $mee){
  $rsss = unserialize($mee);
  if (time() - $rsss['time'] > 20) {
    $content = array('chat_id' => $rsss['chat_id'], 'message_id' => $rsss['id']);
    $telegram->deleteMessage($content);
    $redis->lrem('gamebotmessage', $mee, 0);
  }
}

?>