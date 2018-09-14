<?php
/**
 * Sina-Weibo repost crawler
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
	for ($page = 1;; $page++) { 
		$query = [
		    'id' => $mid,
		    'page' => $page
		];
		$url = 'https://m.weibo.cn/api/statuses/repostTimeline?' . http_build_query($query);
		$repost_response = request($url);
		$repost = json_decode($repost_response, true);
		if (empty($repost['ok'])) {
			break;
		}
		$result = isset($repost['data']['data']) ? $repost['data']['data'] : [];
		foreach ($result as $res) {
			save($res, $id);
		}		
	}
	echo "采集完毕\n";
}

function save($repost, $id) {
	if ($repost['user']['gender'] == 'm') {
		$gender = '男';
	} else if ($repost['user']['gender'] == 'f') {
		$gender = '女';
	} else {
		$gender = '未知';
	}
	$data = [
		'id' => $repost['id'],
		'created_at' => $repost['created_at'],
		'user_id' => $repost['user']['id'],
		'screen_name' => addslashes($repost['user']['screen_name']),
		'gender' => $gender,
		'urank' => $repost['user']['urank'],
		'description' => addslashes($repost['user']['description']),
		'profile_image_url' => $repost['user']['profile_image_url'],
		'statuses_count' => $repost['user']['statuses_count'],
		'followers_count' => $repost['user']['followers_count'],
		'follow_count' => $repost['user']['follow_count'],
		'source' => addslashes($repost['source']),
		'text' => addslashes($repost['text'])
	];
	$db = (new Bootstrap())->db();
   	if (!$db->findOne("repost_{$id}", 'id', "id={$data['id']}")) {
		$commentid = $db->add($data, "repost_{$id}");
		$date = date('Y-m-d H:i:s', time());
   		echo "[{$date}] [{$data['created_at']}] - {$data['id']} is success\n";
	} else {
		echo "[{$date}] [{$data['created_at']}] - {$data['id']} is Ex\n";
	}
}

//创建评论表
function createTable($id) {
	$sql = "CREATE TABLE IF NOT EXISTS `repost_{$id}` (
  	`id` bigint(30) NOT NULL DEFAULT '0' COMMENT '转发ID',
	`created_at` varchar(50) DEFAULT NULL COMMENT '创建时间',
	`user_id` bigint(30) NOT NULL DEFAULT '0' COMMENT '转发者UID',
	`screen_name` varchar(255) DEFAULT NULL COMMENT '转发者昵称',
	`gender` enum('男','女','未知') DEFAULT '未知',
	`urank` int(15) DEFAULT '0' COMMENT '博主等级',	
	`description` varchar(255) DEFAULT NULL COMMENT '博主简介',
	`profile_image_url` varchar(255) DEFAULT NULL COMMENT '转发者头像',
	`statuses_count` int(15) DEFAULT '0' COMMENT '转发者微博数',
	`followers_count` int(15) DEFAULT '0' COMMENT '转发者粉丝数',
	`follow_count` int(15) DEFAULT '0' COMMENT '转发者关注数',
	`source` varchar(255) DEFAULT NULL COMMENT '发布来源设备',
	`text` text COMMENT '转发内容',
	KEY `id` (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微博【{$id}】的转发'";
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
