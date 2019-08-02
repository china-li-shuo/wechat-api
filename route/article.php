<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/8/1
 * Time: 9:20
 */

Route::get(':version/article/today', 'article/:version.Article/getDailyPush');
Route::post(':version/collect', 'article/:version.Article/addCollect');
Route::post(':version/collect/cancel', 'article/:version.Article/cancelCollect');