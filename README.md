# weibo-spider -- 微博爬虫PHP脚本 
weibo-spider是微博项目爬虫脚本的整理，以PHP语言开发，MySQL作为存储数据库。

# 快速上手
配置MySQL数据库 /config/app.ini
```
[APP]
name=weibo-spider

;MySQL数据库配置文件
[MySQL]
dbtype=mysql
hostname=localhost
username=root
password=123456
database=weibo
hostport=3306
charset=utf8mb4
```
项目脚本目录 /app
* Bootstrap.php 初始引入脚本
* Comment.php 评论采集脚本, 需提供微博唯一标识
* Repost.php 转发采集脚本, 需提供微博唯一标识
* Weibo.php 微博采集脚本，需提供博主微博UID
```
$ ll
-rw-r--r-- 1 Administrator 197121  632 八月  9 22:53 Bootstrap.php  
-rw-r--r-- 1 Administrator 197121 5917 九月 14 14:04 Comment.php
-rw-r--r-- 1 Administrator 197121 4727 九月 14 15:04 Repost.php
-rw-r--r-- 1 Administrator 197121 8324 八月 31 11:29 Weibo.php
```

#### 运行界面如下:      
```
$ php weibo.php
[2018-09-14 15:46:20] ID.1 | 1-0 -> 罗永浩 is Ad
[2018-09-14 15:46:20] ID.2 | 1-0 -> 罗永浩 is Ad
[2018-09-14 15:46:20] ID.3 | 1-0 -> 罗永浩 is Ad
[2018-09-14 15:46:21] ID.4 | 1-0 -> 罗永浩 is Ad
[2018-09-14 15:46:21] ID.5 | 1-0 -> 罗永浩 is Ad
[2018-09-14 15:46:21] ID.6 | 1-0 -> 罗永浩 is Ad
[2018-09-14 15:46:21] ID.7 | 1-0 -> 罗永浩 is Ad
[2018-09-14 15:46:21] ID.8 | 1-0 -> 罗永浩 is Ad
[2018-09-14 15:46:21] ID.9 | 1-0 -> 罗永浩 is Ad
[2018-09-14 15:46:21] ID.10 | 1-0 -> 罗永浩 is Ad
[2018-09-14 15:46:21] ID.11 | 1-0 -> 罗永浩 is Ad
[2018-09-14 15:46:21] ID.12 | 1-0 -> 罗永浩 is Ad
[2018-09-14 15:46:21] ID.13 | 1-0 -> 罗永浩 is Ad
[2018-09-14 15:46:21] ID.14 | 1-0 -> 罗永浩 is Ad
[2018-09-14 15:46:21] ID.15 | 1-0 -> 罗永浩 is Ad
[2018-09-14 15:46:21] ID.16 | 1-0 -> 罗永浩 is Ad
```
