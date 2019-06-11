<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公号：空城旧梦狂啸狂啸当歌
 * Date: 2019/6/3
 * Time: 11:57
 */
namespace app\api\controller\v5;

use app\api\dao\User;
use app\api\validate\MobileRule;
use app\lib\exception\MissException;
use app\lib\exception\ParameterException;
use app\lib\exception\SmsException;
use app\lib\exception\TokenException;
use think\Db;
use think\facade\Cache;
use think\facade\Request;
use think\src\sendWithParam;
use think\src\SmsSingleSender;

class Sms
{
    /**
     * 發送短信驗證碼
     * @param string $mobie
     * @return string
     * @throws ParameterException
     */
    public function sendSms($mobile = '')
    {
        if (!$mobile) {
            throw new MissException([
                'msg'       => 'mobie不允许为空',
                'errorCode' => 60000
            ]);
        }

        $validate = new MobileRule();
        $validate->goCheck();

        $res = Db::name('user')->field('mobile,mobile_bind')->where('mobile', $mobile)->find();

        if (empty($res) || $res['mobile_bind'] == 2) {

            $vcode = rand(1000, 9999);
            $this->setRegSmsCache(['mobile' => $mobile, 'vcode' => $vcode, 'times' => time()]);

            $new_sms = new SmsSingleSender(config('sms.app_id'), config('sms.app_key'));
            $params  = [
                "$vcode",
                "10"
            ];

            $sign = "社科赛斯";
            $res  = $new_sms->sendWithParam(86, $mobile, config('sms.tpl_id'), $params, $sign, $extend = "", $ext = "", $vcode);
            echo $res;
        } else {
            return json_encode(array('sta' => 1001, 'message' => "该手机号已验证过"), JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * 綁定手機號
     * @param string $mobile
     * @param string $code
     * @throws ParameterException
     */
    public function bindMobile($mobile = '', $code = '')
    {
        if (!$mobile || !$code) {
            throw new MissException([
                'msg'       => '你输入的参数缺失',
                'errorCode' => 60000
            ]);
        }
        $res = $this->checkRegSms($mobile, $code);

        if (!$res) {
            throw new SmsException();
        }
        //获取token令牌信息，与手机号进行绑定
        $token = Request::instance()
            ->header('token');

        if (!$token) {
            throw new MissException([
                'msg'       => '你输入的token参数缺失',
                'errorCode' => 60000
            ]);
        }

        $identities = Cache::get($token);
        if (!$identities) {
            throw new TokenException();
        };
        $identities = json_decode($identities, true);

        $res = User::bindMobile($identities, $mobile);

        if (!$res) {
            return json_encode(['msg' => '绑定失败', 'error_code' => 6001], JSON_UNESCAPED_UNICODE);
        }
        return json_encode(['msg' => '绑定成功', 'code' => 200], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 检测手机短信验证码
     * @param $mobile
     * @param bool|false $code
     * @return bool
     */
    protected function checkRegSms($mobile, $code = false)
    {

        if (!$mobile) return false;
        if ($code === false) {   //判断60秒以内是否重复发送
            if (!Cache::has('sms_' . $mobile)) return true;
            if (Cache::get('sms_' . $mobile)['times'] > time()) {
                return false;
            } else {
                return true;
            }
        } else {  //判断验证码是否输入正确
            if (!Cache::has('sms_' . $mobile)) return false;
            if (Cache::get('sms_' . $mobile)['vcode'] == $code) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 设置手机短息验证码缓存
     */
    protected function setRegSmsCache($data_cache)
    {
        Cache::set('sms_' . $data_cache['mobile'], $data_cache, 600);
    }
}