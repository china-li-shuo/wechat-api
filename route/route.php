<?php
//Token
Route::post('api/:version/token/user', 'api/:version.Token/getToken');
Route::post('api/:version/token/app', 'api/:version.Token/getAppToken');
Route::post('api/:version/token/verify', 'api/:version.Token/verifyToken');

//手機短信
Route::post('api/:version/sms/mobile', 'api/:version.Sms/sendSms');
Route::post('api/:version/sms/bind', 'api/:version.Sms/bindMobile');

//学习阶段
Route::post('api/:version/stage/info', 'api/:version.Stage/getStages');
Route::post('api/:version/stage/all', 'api/:version.Stage/getAllStage');
Route::post('api/:version/stage/detail', 'api/:version.Stage/getDetail');
Route::post('api/:version/stage/record', 'api/:version.Stage/recordStage');

//分享打卡
Route::post('api/:version/share/punch', 'api/:version.Share/punchCard');

//开始学习
Route::post('api/:version/learned/list', 'api/:version.Learned/getList');
Route::post('api/:version/learned/common', 'api/:version.Learned/commonList');
Route::post('api/:version/learned/click', 'api/:version.Learned/clickNext');
Route::post('api/:version/learned/collection', 'api/:version.Learned/collection');


//单词本
Route::post('api/:version/activity/already', 'api/:version.Activity/alreadyStudied');
Route::post('api/:version/activity/detail', 'api/:version.Activity/alreadyDetail');
Route::post('api/:version/activity/error', 'api/:version.Activity/errorBook');
Route::post('api/:version/activity/list', 'api/:version.Activity/errorDetail');
Route::post('api/:version/activity/remove', 'api/:version.Activity/errorRemove');
Route::post('api/:version/activity/collection', 'api/:version.Activity/collection');
Route::post('api/:version/activity/info', 'api/:version.Activity/collectionDetail');

//首页信息
Route::post('api/:version/index/info', 'api/:version.Index/getUserInfo');

//背单词结算
Route::post('api/:version/settlement/info', 'api/:version.Settlement/getSettlementInfo');
Route::post('api/:version/settlement/again', 'api/:version.Settlement/getAgainInfo');
Route::post('api/:version/settlement/next', 'api/:version.Settlement/nextGroupInfo');

//今日榜单
Route::post('api/:version/top/today', 'api/:version.Top/getTodayList');
Route::post('api/:version/top/rank', 'api/:version.Top/getTodayRanking');
Route::post('api/:version/top/history', 'api/:version.Top/getHistoryList');


//老师页面
Route::post('api/:version/teacher/status', 'api/:version.Teacher/getClassStatus');
Route::post('api/:version/teacher/screen', 'api/:version.Teacher/getScreenInfo');





