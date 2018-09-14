<?php
/**
 * Sina-Weibo comment crawler
 * @extend   Bootstap
 */
require_once __DIR__ . '/Bootstrap.php';

/* 这个ID是微博-博文的唯一ID
 * https://www.weibo.com/2803301701/FnJdHF3FO?filter=hot&root_comment_id=0&type=comment
 */
$ids = [
	'GkiFNcZJC',
	'GlFKwBxc3',	
	'GqCyZ37ar',
	'GqCZnagLF',		
	'GqDlGm0kj',	
	'GqPSZ5Y9l'
];

foreach ($ids as $id) {
	// 收集采集需要的条件
	$statuses_url = 'https://m.weibo.cn/statuses/show?id='. $id;
	$statuses_response = request($statuses_url);
	if (is_null($statuses = json_decode($statuses_response))) {
	    exit('Weibo bid is error.');
	}
	createTable($id);
	$mid = $statuses->data->mid;
	$query = [
	    'id' => $mid,
	    'mid' => $mid,
	    'max_id_type' => 0
	];
	$url = 'https://m.weibo.cn/comments/hotflow?' . http_build_query($query);
	main($url, $id, $mid);
}

function main($url, $id, $mid) {
	$comment_response = request($url);
	$comment = json_decode($comment_response, true);
	$result = isset($comment['data']['data']) ? $comment['data']['data'] : [];
	foreach ($result as $res) {
		save($res, $id);
		if ($res['total_number']) {
			child($id, $res['id'], 0);
		}
	}
	$max_id = isset($comment['data']['max_id']) ? $comment['data']['max_id']: 0;
	if ($max_id) {
		$max_id_type = isset($comment['data']['max_id_type']) ? $comment['data']['max_id_type']: 0;
		$next_url = 'https://m.weibo.cn/comments/hotflow?' . http_build_query([
		    'id' => $mid,
		    'mid' => $mid,
		    'max_id' => $max_id,
		    'max_id_type' => $max_id_type
		]);
		main($next_url, $id, $mid);	
	} else {
		echo "采集完毕\n";
	}
}

function child($id, $cid, $max_id, $max_id_type = 0) {
	$query = [
	    'cid' => $cid,
	    'max_id' => $max_id,
	    'max_id_type' => $max_id_type
	];
	$child_url = 'https://m.weibo.cn/comments/hotFlowChild?' . http_build_query($query);
	$child_response = request($child_url);
	$child = json_decode($child_response, true);
	$result = isset($child['data']) ? $child['data'] : [];
	foreach ($result as $com) {
		save($com, $id);
	}
	$max_id = isset($child['max_id']) ? $child['max_id']: 0;
	if ($max_id){
		$max_id_type = isset($child['max_id_type']) ? $child['max_id_type']: 0;
		child($id, $cid, $max_id, $max_id_type);
	}
}

function save($comment, $id) {
	if ($comment['user']['gender'] == 'm') {
		$gender = '男';
	} else if ($comment['user']['gender'] == 'f') {
		$gender = '女';
	} else {
		$gender = '未知';
	}
	$created_at = strtotime($comment['created_at']);
	$data = [
		'id' => $comment['id'],
		'rootid' => $comment['rootid'],
		'created_at' => date('Y-m-d H:i:s', $created_at),
		'user_id' => $comment['user']['id'],
		'screen_name' => addslashes($comment['user']['screen_name']),
		'gender' => $gender,
		'urank' => $comment['user']['urank'],
		'description' => addslashes($comment['user']['description']),
		'profile_image_url' => $comment['user']['profile_image_url'],
		'statuses_count' => $comment['user']['statuses_count'],
		'followers_count' => $comment['user']['followers_count'],
		'follow_count' => $comment['user']['follow_count'],
		'text' => addslashes($comment['text'])
	];
	$db = (new Bootstrap())->db();
   	if (!$db->findOne("comment_{$id}", 'id', "id={$data['id']}")) {
		$commentid = $db->add($data, "comment_{$id}");
		$date = date('Y-m-d H:i:s', time());
   		echo "[{$date}] [{$data['created_at']}] - {$data['id']} is success\n";
	} else {
		echo "[{$date}] [{$data['created_at']}] - {$data['id']} is Ex\n";
	}
}

//创建评论表
function createTable($id) {
	$sql = "CREATE TABLE IF NOT EXISTS `comment_{$id}` (
  	`id` bigint(30) NOT NULL DEFAULT '0' COMMENT '评论ID',
	`rootid` bigint(30) NOT NULL DEFAULT '0' COMMENT '评论父级ID',
	`created_at` varchar(50) DEFAULT NULL COMMENT '创建时间',
	`user_id` bigint(30) NOT NULL DEFAULT '0' COMMENT '评论者UID',
	`screen_name` varchar(255) DEFAULT NULL COMMENT '评论者昵称',
	`gender` enum('男','女','未知') DEFAULT '未知',
	`urank` int(15) DEFAULT '0' COMMENT '博主等级',	
	`description` varchar(255) DEFAULT NULL COMMENT '博主简介',
	`profile_image_url` varchar(255) DEFAULT NULL COMMENT '评论者头像',
	`statuses_count` int(15) DEFAULT '0' COMMENT '评论者微博数',
	`followers_count` int(15) DEFAULT '0' COMMENT '评论者粉丝数',
	`follow_count` int(15) DEFAULT '0' COMMENT '评论者关注数',
	`text` text COMMENT '评论内容',
	KEY `id` (`id`),
	KEY `rootid` (`rootid`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微博【{$id}】的评论'";
    (new Bootstrap())->db()->exec($sql);
}

//cURL模拟采集
function request($url) {
    //实例化curl资源
    $ch = curl_init();
    $http_header = [
        'Host: m.weibo.cn',
        "Pragma: no-cache",
        "Connection: keep-alive",
        "Upgrade-Insecure-Requests: 1",
        "Accept-Language: zh-CN,zh;q=0.8,en;q=0.6",
        "Content-Type: application/json; charset=utf-8",
        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
        "User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.181 Safari/537.36"
    ];
    //设置请求header头
    curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    //允许302跳转
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    //设置超时间
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
   	$info = curl_getinfo($ch);
    $code = $info['http_code'];
 	if ($code !== 200) {
        $result = request($url);
    }
    return $result;
}
