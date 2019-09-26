<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/9/26
 * Time: 16:05
 */

Route::group('', function () {
    Route::group('v1', function () {
        // 查询鸡汤
        Route::get('soul', 'api_dream/v1.Soul/getSouls');
        // 新建鸡汤
        Route::post('soul', 'api_dream/v1.Soul/create');
        // 查询指定sid的鸡汤
        Route::get('soul/:sid', 'api_dream/v1.Soul/getSoul');
        // 更新鸡汤
        Route::put('soul/:sid', 'api_dream/v1.Soul/update');
        // 删除图鸡汤
        Route::delete('soul/:sid', 'api_dream/v1.Soul/delete');
    });
});