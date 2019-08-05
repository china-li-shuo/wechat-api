<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/8/1
 * Time: 9:20
 */

Route::get(':version/article/today', 'article/:version.Article/getDailyPush');  //日推文章
Route::post(':version/collect', 'article/:version.Article/addCollect');         //收藏
Route::post(':version/collect/cancel', 'article/:version.Article/cancelCollect');//取消收藏
Route::get(':version/article/:id/detail', 'article/:version.Article/getArticleChild');//获取某篇文章详情
Route::post(':version/add/record', 'article/:version.Record/addUserRecord');//添加用户学习记录