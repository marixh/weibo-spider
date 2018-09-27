<?php
/**
 * 微博采集
 * @author   Ma.Shixin<mashixin@inmyshow.com>
 * @date     2018/07/17 12:03
 */
require_once __DIR__ . '/Bootstrap.php';

/**
 * 转 https://m.weibo.cn/api/statuses/repostTimeline?id=4275228743400463&page=1
 * 评 https://m.weibo.cn/comments/hotflow?id=4275228743400463&mid=4275228743400463&max_id=139241857933326
 * 赞 https://m.weibo.cn/api/attitudes/show?id=4275228743400463&page=1
 */

$uids = [
	'6338505167',
	'6330544304'
];

foreach ($uids as $key => $uid) {
	weibo($uid);
}

function weibo($uid) {
	$url = 'https://m.weibo.cn/api/container/getIndex?type=uid&value=' . $uid;
	//微博主页URL
	$getIndex = request($url, true);
	$getIndex = json_decode($getIndex, true);
	$userInfo = $getIndex['data']['userInfo'];
	$profile_url = $userInfo['profile_url'];
	$lfid = explode('lfid=', $profile_url)[1];
	$domain = substr($lfid, 0, 6);
	$name = $userInfo['screen_name'];

	//生成表结构
	$tableDes = '[' . $name . '] 发文数量：' . $userInfo['statuses_count'];
	$sql = "CREATE TABLE IF NOT EXISTS `weibo_" . $uid . "` (
	  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	  `uid` bigint(20) NOT NULL COMMENT '微博用户ID',
	  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '微博昵称',
	  `post_time` varchar(30) NOT NULL DEFAULT '' COMMENT '发布时间',
	  `from_device` varchar(255) NOT NULL DEFAULT '' COMMENT '发布设备',
	  `is_forward` enum('转发','原创') NOT NULL DEFAULT '转发' COMMENT '是否转发',
	  `forward_num` int(11) NOT NULL DEFAULT '0' COMMENT '转发数',
	  `comment_num` int(11) NOT NULL DEFAULT '0' COMMENT '评论数',
	  `like_num` int(11) NOT NULL DEFAULT '0' COMMENT '点赞数',
	  `link_url` varchar(255) NOT NULL DEFAULT '' COMMENT '微博链接',
	  `content` text NOT NULL COMMENT '微博内容',
	  `html` text NOT NULL COMMENT '微博HTML内容',
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='" . $tableDes . "'";
	$db = (new Bootstrap())->db();
	$db->exec($sql);

	//爬取微博内容
	$page_total = ceil($userInfo['statuses_count'] / 44);
	$page_id = $domain . $uid;
	for ($page = 1; $page <= $page_total; $page++) {
	    for ($p = 1; $p <= 3; $p++) {
	        switch ($p) {
	            case 1:
	                $pagebar = 0;
	                $pre_page = $page - 1;
	                break;
	            case 2:
	                $pagebar = 0;
	                $pre_page = $page;
	                break;
	            case 3:
	                $pagebar = 1;
	                $pre_page = $page;
	                break;
	            default:
	                $pagebar = 0;
	                $pre_page = $page - 1;
	        }
	        $params = [
	            'ajwvr' => 6,
	            'domain' => $domain,
	            'domain_op' => $domain,
	            'profile_ftype' => 1,
	            'is_all' => 1,
	            'is_search' => 0,
	            'visible' => 0,
	            'pagebar' => $pagebar,
	            'id' => $page_id,
	            'script_uri' => '/p/' . $page_id . '/home',
	            'feed_type' => 0,
	            'page' => $page,
	            'pre_page' => $pre_page,
	        ];
	        $param_uri = http_build_query($params);
	        $url = 'https://weibo.com/p/aj/v6/mblog/mbloglist?' . $param_uri;
	        $result = json_decode(request($url), true);

	        //加载网页资源到phpQuery
	        phpQuery::newDocument($result['data']);
	        $cardwraps = pq("div.[action-type='feed_list_item']");
	        foreach ($cardwraps as $key => $cardwrap) {
	            $cardwrap = pq($cardwrap);
	            $href = $cardwrap->find(".WB_detail .WB_from a:first")->attr('href');
	            $link = $href ? explode('?', $href) : [];
	            $data = [];
	            $data['uid'] = $uid;
	            $data['name'] = $name;
	            //微博链接
	            $data['link_url'] = 'http://weibo.com' . $link[0];
	            //发送日期
	            $data['post_time'] = $cardwrap->find(".WB_detail .WB_from a:first")->attr('title');
	            //发送设备
	            if ($cardwrap->find(".WB_detail .WB_from a")->length > 1) {
	                $from_device = $cardwrap->find(".WB_detail .WB_from a:last")->text();
	                $data['from_device'] = addslashes($from_device);
	            } else {
	                $data['from_device'] = '微博 weibo.com';
	            }
	            //是否转发
	            $isforward = $cardwrap->find(".WB_detail div")->hasClass("WB_feed_expand");
	            $data['is_forward'] = $isforward ? '转发' : '原创';
	            //转发数
	            $forward_num = $cardwrap->find(".WB_feed_handle [node-type='forward_btn_text'] em:last")->text();
	            $data['forward_num'] = is_numeric($forward_num) ? $forward_num : 0;
	            //评论数
	            $comment_num = $cardwrap->find(".WB_feed_handle [node-type='comment_btn_text'] em:last")->text();
	            $data['comment_num'] = is_numeric($comment_num) ? $comment_num : 0;
	            //点赞数
	            $like_num = $cardwrap->find(".WB_feed_handle [node-type='like_status'] em:last")->text();
	            $data['like_num'] = is_numeric($like_num) ? $like_num : 0;
	            //微博内容
	            $detail_html = $cardwrap->find(".WB_detail")->html();
	            $is_more = stripos($detail_html, '展开全文<i class=');
	            if ($is_more) {
	                $mid = $cardwrap->find(".WB_detail .WB_from a")->attr("name");
	                $longtext_url = 'https://weibo.com/p/aj/mblog/getlongtext?ajwvr=6&mid=' . $mid;
	                $longtext = json_decode(request($longtext_url), true);
	                $feed_html = $longtext['data']['html'];
	            } else {
	                $feed_html = $cardwrap->find(".WB_detail [node-type='feed_list_content']")->html();
	            }
	            $data['html'] = addslashes(trim($feed_html));
	            $data['content'] = addslashes(strip_tags(trim($feed_html)));
	            $db->add($data, 'weibo_'. $uid);
	            $id = $db->getLastId();
	            $date = date('Y-m-d H:i:s', time());
	            echo '['. $date .'] ID.'. $id . ' | ' . $page . '-' . $pre_page . ' -> ' .$name . " is Ad\n";
	        }
	    }
	}
}

//cURL模拟采集
function request($url, $is_m = false) {
    //实例化curl资源
    $ch = curl_init();
    $http_header = [
        "Pragma: no-cache",
        "Connection: keep-alive",
        "Upgrade-Insecure-Requests: 1",
        "Accept-Language: zh-CN,zh;q=0.8,en;q=0.6",
        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
        "User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.181 Safari/537.36"
    ];
    if ($is_m) {
        $http_header[] = 'Host: m.weibo.cn';
    } else {
        $http_header[] = 'Host: weibo.com';
        $http_header[] = 'Cookie: SUB=_2AkMugWVsdcPxrABTm_0XyWzqao1H-jydVAyaAn7tJhMyOBgv7l80qSWDtONeMK5Rp7AtQ5d5GrBDf_cscA..';
    }
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
        $result = request($url, $is_m);
    }
    return $result;
}