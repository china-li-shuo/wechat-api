<?php
//Token
Route::post('api/:version/token/user', 'api/:version.Token/getToken');
Route::post('api/:version/token/app', 'api/:version.Token/getAppToken');
Route::post('api/:version/token/verify', 'api/:version.Token/verifyToken');

//手機短信
Route::post('api/:version/sms/mobile', 'api/:version.Sms/sendSms');
Route::post('api/:version/sms/bind', 'api/:version.Sms/bindMobile');

//学习阶段
Route::post('api/:version/stage/all', 'api/:version.Stage/getAllStage');
Route::post('api/:version/stage/detail', 'api/:version.Stage/getDetail');
Route::post('api/:version/stage/record', 'api/:version.Stage/recordStage');

//分享打卡
Route::post('api/:version/share/punch', 'api/:version.Share/punchCard');

//开始学习
Route::post('api/:version/learned/list', 'api/:version.Learned/getList');
Route::post('api/:version/learned/sentence', 'api/:version.Learned/clickSentence');
Route::post('api/:version/learned/click', 'api/:version.Learned/clickNext');
Route::post('api/:version/learned/collection', 'api/:version.Learned/collection');
Route::post('api/:version/learned/sc', 'api/:version.Learned/sentenceCollection');
Route::post('api/:version/learned/search', 'api/:version.Learned/wordSearch');


//单词本
Route::post('api/:version/activity/already', 'api/:version.Activity/alreadyStudied');
Route::post('api/:version/activity/detail', 'api/:version.Activity/alreadyDetail');
Route::post('api/:version/activity/error', 'api/:version.Activity/errorBook');
Route::post('api/:version/activity/list', 'api/:version.Activity/errorDetail');
Route::post('api/:version/activity/remove', 'api/:version.Activity/errorRemove');
Route::post('api/:version/activity/collection', 'api/:version.Activity/collection');
Route::post('api/:version/activity/info', 'api/:version.Activity/collectionDetail');
Route::post('api/:version/activity/asen', 'api/:version.Activity/alreadySentence');
Route::post('api/:version/activity/csen', 'api/:version.Activity/collectionSentence');

//首页信息
Route::post('api/:version/index/info', 'api/:version.Index/getUserInfo');
Route::post('api/:version/home/school', 'api/:version.Home/getBranchSchool');
Route::post('api/:version/home/unitclass', 'api/:version.Home/getUnitClass');
Route::post('api/:version/home/today_punch', 'api/:version.Home/getPunchCardToday');
Route::post('api/:version/home/ranking', 'api/:version.Home/getRankingList');
Route::post('api/:version/home/comment', 'api/:version.Home/respondComment');
Route::post('api/:version/home/zan', 'api/:version.Home/clickZan');

//背单词结算
Route::post('api/:version/settlement/info', 'api/:version.Settlement/getSettlementInfo');
Route::post('api/:version/settlement/again', 'api/:version.Settlement/getAgainInfo');
Route::post('api/:version/settlement/next', 'api/:version.Settlement/nextGroupInfo');
Route::post('api/:version/settlement/post', 'api/:version.Settlement/sendPost');

//今日榜单
Route::post('api/:version/top/today', 'api/:version.Top/getTodayList');
Route::post('api/:version/top/rank', 'api/:version.Top/getTodayRanking');
Route::post('api/:version/top/history', 'api/:version.Top/getHistoryList');

//老师页面
Route::post('api/:version/teacher/status', 'api/:version.Teacher/getClassStatus');
Route::post('api/:version/teacher/screen', 'api/:version.Teacher/getScreenInfo');

//圈子
Route::post('api/:version/circle/verify', 'api/:version.Circle/verifyClass');
Route::post('api/:version/circle/module', 'api/:version.Circle/getModule');
Route::post('api/:version/circle/info', 'api/:version.Circle/getCircleInfo');
Route::post('api/:version/circle/today_punch', 'api/:version.Circle/getPunchCardToday');
Route::post('api/:version/circle/ranking', 'api/:version.Circle/getRankingList');
Route::post('api/:version/circle/intention', 'api/:version.Circle/addUserIntention');

//我的
Route::post('api/:version/personal/punch', 'api/:version.Personal/getMyPunchCard');
Route::post('api/:version/personal/info', 'api/:version.Personal/getPersonalInfo');
Route::post('api/:version/personal/class', 'api/:version.Personal/getMyClass');


