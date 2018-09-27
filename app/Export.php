<?php 
/**
 * Sina-Weibo Export
 * @extend   Bootstap
 */
require_once __DIR__ . '/Bootstrap.php';
$db = (new Bootstrap())->db();

$list = [
    'weibo_3306575061', 
    'weibo_5112892863', 
    'weibo_5315629003', 
    'weibo_5413246912', 
    'weibo_5994871534', 
    'weibo_6073660670',
    'weibo_6115636158', 
    'weibo_6330544304',
    'weibo_6338505167'
];

foreach ($list as $val) {
    $result = $db->find($val);
    if (empty($result)) {
       continue;
    }
    $data = [];
    foreach ($result as $res) {
        $data[] = [
            $res['uid'], $res['name'], $res['post_time'], $res['from_device'], $res['is_forward'],
            $res['forward_num'], $res['comment_num'], $res['like_num'], $res['link_url'], $res['content'], $res['html']
        ];
    }
    $menu = [
        'UID', '微博昵称', '发文时间', '发文来源', '原创/转发',
        '转发数', '评论数', '点赞数', '微博链接', '微博内容', '微博HTML内容'
    ];
    $name = $data[0][1];
    $Excel = new Excel();
    $Excel->addSheetName(array($name));
    $Excel->cellCaptionRows = 1;
    $Excel->setUnilineCellcaption($menu);
    $Excel->setAllCellvalue($data);
    $Excel->writeExcel("D:/data/weibo/" . $name);
    echo '[' . date('Y-m-d H:i:s', time()) . "] {$name} is OK.\n";
}