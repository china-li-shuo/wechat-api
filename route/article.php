<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/8/1
 * Time: 9:20
 */

Route::get(':version/nav', 'article/:version.Nav/getNavTempL');  //获取首页导航模板信息
Route::get(':version/article/today', 'article/:version.Article/getDailyPush');  //日推文章
Route::post(':version/collect', 'article/:version.Article/addCollect');         //收藏
Route::post(':version/collect/cancel', 'article/:version.Article/cancelCollect');//取消收藏
Route::get(':version/article/:id/detail', 'article/:version.Article/getArticleChild');//获取某篇文章详情
Route::post(':version/article/leader_board', 'article/:version.Record/getArticleLeaderBoard');//查看排名接口
Route::post(':version/token', 'article/:version.Token/getToken');//用户授权
Route::get(':version/personal/record', 'article/:version.Personal/getLearningRecords');//用户授权
