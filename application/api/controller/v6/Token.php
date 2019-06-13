<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公号：空城旧梦狂啸当歌
 * Date: 2019/6/3
 * Time: 11:57
 */

namespace app\api\controller\v6;

use app\api\model\User;
use app\api\service\AppToken;
use app\api\service\Token as TokenService;
use app\api\service\UserToken;
use app\api\validate\AppTokenGet;
use app\api\validate\TokenGet;
use app\lib\exception\MissException;
use think\src\sendWithParam;

/**
 * 获取令牌，相当于登录
 */
class Token
{

    /**
     * 用户获取令牌（登陆）
     * @url /token
     * @POST code
     * @note 虽然查询应该使用get，但为了稍微增强安全性，所以使用POST
     */
    public function getToken()
    {
        $validate = new TokenGet();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        $data['nick_name'] = urlencode($data['nick_name']);
        $wx = new UserToken($data['code']);
        $token = $wx->get();
        $mobile_bind = User::updateUserInfo($data, $token);
        return json([
            'token'       => $token,
            'mobile_bind' => $mobile_bind
        ]);
    }

    /**
     * 第三方应用获取令牌
     * @url /app_token?
     * @POST ac=:ac se=:secret
     */
    public function getAppToken($ac = '', $se = '')
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header('Access-Control-Allow-Methods: GET');
        (new AppTokenGet())->goCheck();
        $app   = new AppToken();
        $token = $app->get($ac, $se);
        return json([
            'token' => $token
        ]);
    }

    public function verifyToken($token = '')
    {
        if (!$token) {
            throw new MissException([
                'msg'       => 'token不允许为空',
                'errorCode' => 60000
            ]);
        }
        $valid = TokenService::verifyToken($token);

        return json([
            'isValid' => $valid
        ]);
    }
}