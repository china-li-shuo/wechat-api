<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/8/1
 * Time: 9:20
 */

Route::get(':version/nav', 'article/:version.Nav/getNavTempL');  //获取首页导航模板信息
Route::get(':version/article/info', 'article/:version.Article/getArticleInfo');  //获取文章信息,指定文章或者日推送文章
Route::post(':version/collect/add', 'article/:version.Article/addCollect');         //收藏
Route::post(':version/collect/cancel', 'article/:version.Article/cancelCollect');//取消收藏
Route::get(':version/article/:id/detail', 'article/:version.Article/getArticleChild');//获取某篇文章详情
Route::post(':version/article/leader_board', 'article/:version.Record/getArticleLeaderBoard');//查看排名接口
Route::post(':version/token', 'article/:version.Token/getToken');//用户授权
Route::get(':version/personal/record', 'article/:version.Personal/getLearningRecords');//我的学习记录
Route::get(':version/personal/collect', 'article/:version.Personal/getCollectedArticles');//我收藏的文章

//Pay
Route::post(':version/pay/pre_order', 'article/:version.Pay/getPreOrder');
Route::post(':version/pay/notify', 'article/:version.Pay/receiveNotify');
Route::post(':version/pay/re_notify', 'article/:version.Pay/redirectNotify');
Route::post(':version/pay/concurrency', 'article/:version.Pay/notifyConcurrency');