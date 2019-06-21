<?php
use app\api\model\Stage;
/**
 * @param string $url post请求地址
 * @param array $params
 * @return mixed
 */
function curl_post($url, array $params = array())
{
    $data_string = json_encode($params);
    $ch          = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt(
        $ch, CURLOPT_HTTPHEADER,
        array(
            'Content-Type: application/json'
        )
    );
    $data = curl_exec($ch);
    curl_close($ch);
    return ($data);
}

function curl_post_raw($url, $rawData)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $rawData);
    curl_setopt(
        $ch, CURLOPT_HTTPHEADER,
        array(
            'Content-Type: text'
        )
    );
    $data = curl_exec($ch);
    curl_close($ch);
    return ($data);
}

/**
 * @param string $url get请求地址
 * @param int $httpCode 返回状态码
 * @return mixed
 */
function curl_get($url, &$httpCode = 0)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    //不做证书校验,部署在linux环境下请改为true
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    $file_contents = curl_exec($ch);
    $httpCode      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $file_contents;
}

function getRandChar($length)
{
    $str    = null;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max    = strlen($strPol) - 1;

    for ($i = 0;
         $i < $length;
         $i++) {
        $str .= $strPol[rand(0, $max)];
    }

    return $str;
}


function fromArrayToModel($m, $array)
{
    foreach ($array as $key => $value) {
        $m[$key] = $value;
    }
    return $m;
}


/**
 * 无限极分类排序
 * @param  [type]  $menuData  [description]
 * @param  integer $parent_id [description]
 * @param  integer $level [description]
 * @return [type]             [description]
 */
function createTree($data, $parent_id = 0, $level = 0)
{
    static $new_arr = []; //定义空数组
    //循环
    foreach ($data as $key => $value) {
        //判断
        if ($value['parent_id'] == $parent_id) {

            //把级别放入value
            $value['level'] = $level;
            //找到之后
            $new_arr[] = $value;
            //找儿子
            createTree($data, $value['stage_id'], $level + 1);
        }
    }

    //返回数据
    return $new_arr;
}


/**
 * 递归排序
 * @param  [type]  $data      [description]
 * @param  integer $parent_id [description]
 * @return [type]             [description]
 */
function createTreeBySon($data, $parent_id = 0)
{
    $new_arr = array();
    foreach ($data as $key => $val) {
        if ($val['parent_id'] == $parent_id) {
            $new_arr[$key]        = $val;
            $new_arr[$key]['son'] = createTreeBySon($data, $val['id']);
        }
    }
    return $new_arr;
}

function returnJson($result, $code)
{
    echo json_encode($result, $code, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function errorUrl()
{
    $request = \think\facade\Request::instance();
    return $request->url();
}

function isTeacher($uid)
{
    $data = [
        0 => [
            'msg'       => '公共阶段已经学完，请选择学员通道开始学习',
            'errorCode' => 0
        ],
        1 => [
            'msg'       => '公共阶段已经学完，如需继续学习，请进行购买',
            'errorCode' => 50000
        ]
    ];
    //如果公共词汇没有了下一组了，判断用户是不是学员或者是不是会员，如果不是此阶段会员也不是学员则提示购买
    $isTeacher = \think\Db::name('user')
        ->field('is_teacher')
        ->where('id', $uid)
        ->find();
    switch ($isTeacher['is_teacher']) {
        case 0:
            $userMember = \think\Db::name('user_member')->where('user_id', $uid)->select();
            if (empty($userMember)) {
                return $data[1];
            }
            return $data[0];
        case 1:
            return $data[0];
        case 2:
            return $data[0];
        default:
            return $data[1];
    }
}

function dailyQuotations($key)
{
    $arr = [
        0  => '在人生的舞台上，从不给落伍者颁发奖牌。',
        1  => '欲望以提升热枕，毅力以磨平高山。',
        2  => '时间，就像海绵里的水，只要愿挤，总是有的。',
        3  => '形成天才的决定因素应该是勤奋。',
        4  => '爱学出勤奋，勤奋出天才。',
        5  => '人的大脑和肢体一样，多用则灵，不用则废。',
        6  => '勿将今日之事拖到明日。',
        7  => '没有艰辛，便无所获。',
        8  => '考研的路上，最重要的是不要被其他事情分心。',
        9  => '你想成为幸福的人吗？但愿你首先学会吃得起苦。',
        10 => '倘能生存，我当然仍要学习。',
        11 => '人生能有机会搏，今日不搏何时搏！',
        12 => '淫奢让人懈怠，安逸让人丧志。',
        13 => '考研不是无间道，而是开往春天的地铁！',
        14 => '考研的路上，我们都不是孤单的。',
        15 => '只要有一丝希望就永不放弃，一切皆有可能！',
    ];
    return $arr[$key];
}


/**
 * 返回urldecode解码后的昵称
 */
function urlDecodeNickName($str)
{
    return !empty(urldecode($str)) ? urldecode($str) : $str;
}

function todayWhere()
{
    $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
    $endToday   = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
    $where[]    = ['p.create_time', 'between time', [$beginToday, $endToday]];
    return $where;
}

function whereTime()
{
    $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
    $endToday   = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
    $where[]    = ['create_time', 'between time', [$beginToday, $endToday]];
    return $where;
}

function cpStages($userInfo)
{
    //获取与班级相关联
    $stages =  Stage::getCpStage($userInfo['class_id'], $userInfo['p_stage_id']);
    $stages = $stages->toArray();
    foreach ($stages as $key=>&$val){
        if(empty($val['cp']))
        {
            unset($stages[$key]);
        }
        unset($val['cp']);
    }
    $stages = array_values($stages);
    return $stages;
}