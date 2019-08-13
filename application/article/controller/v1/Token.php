<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/8/6
 * Time: 19:14
 */


namespace app\article\controller\v1;
use app\article\model\User;
use app\article\service\AppToken;
use app\article\service\Token as TokenService;
use app\article\service\UserToken;
use app\article\validate\AppTokenGet;
use app\article\validate\TokenGet;
use app\lib\exception\MissException;
use think\facade\Request;


/**
 * 获取令牌，相当于登录
 */
class Token
{
    /**
     * * 用户获取令牌（登陆）
     * @note 虽然查询应该使用get，但为了稍微增强安全性，所以使用POST
     * @throws \app\lib\exception\ParameterException
     * @throws \think\Exception
     */
    public function getToken()
    {
        $validate = new TokenGet();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        $data['nick_name'] = $this->filterEmoji($data['nick_name']);
        $wx = new UserToken($data['code']);
        list($uid, $token) = $wx->get();
        $user = User::get($uid);
        $user->save($data);
        return json([
            'token'=> $token
        ]);
    }

    /**
     * 微信昵称过滤特殊表情
     * @param $nick_name
     * @return string
     */
    private function filterEmoji($str)
    {
        $new_str = preg_replace_callback( '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);
        if(strlen($new_str) != 0){
            return $new_str;
        }
        return $str;
    }
    /**
     * 第三方应用获取令牌
     * @throws \app\lib\exception\ParameterException
     * @throws \app\lib\exception\TokenException
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

    /**
     * 验证token时效性
     * @throws MissException
     */
    public function verifyToken()
    {
        $token = Request::instance()
            ->header('token');
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