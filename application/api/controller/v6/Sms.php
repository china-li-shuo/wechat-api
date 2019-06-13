<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公号：空城旧梦狂啸狂啸当歌
 * Date: 2019/6/3
 * Time: 11:57
 */
namespace app\api\controller\v6;

use app\api\model\User;
use app\api\service\Token;
use app\api\validate\MobileRule;
use app\lib\exception\MissException;
use app\lib\exception\ParameterException;
use app\lib\exception\SmsException;
use think\facade\Cache;
use think\src\sendWithParam;
use think\src\SmsSingleSender;

class Sms
{
    /**
     * 發送短信驗證碼
     * @param string $mobile
     * @return string|\think\response\Json
     * @throws ParameterException
     */
    public function sendSms($mobile = '')
    {
        (new MobileRule())->goCheck();
         $user = User::where('mobile','=',$mobile)
            ->field('mobile,mobile_bind')
            ->find();
        if (!$user || $user->mobile_bind == 2) {
            $vcode = rand(1000, 9999);
            $this->setRegSmsCache(['mobile' => $mobile, 'vcode' => $vcode, 'times' => time()]);

            $new_sms = new SmsSingleSender(config('sms.app_id'), config('sms.app_key'));
            $params  = [
                "$vcode",
                "10"
            ];

            $sign = "社科赛斯";
            $res  = $new_sms->sendWithParam(86, $mobile, config('sms.tpl_id'), $params, $sign, $extend = "", $ext = "", $vcode);
            return $res;
        } else {
            return json([
                'sta' => 1001,
                'message' => "该手机号已验证过"
            ]);
        }
    }

    /**
     * 綁定手機號
     * @param string $mobile
     * @param string $code
     * @return \think\response\Json
     * @throws MissException
     * @throws ParameterException
     * @throws SmsException
     */
    public function bindMobile($mobile = '', $code = '')
    {
        $uid = Token::getCurrentUid();
        (new MobileRule())->goCheck();
        if (!$code) {
            throw new MissException([
                'msg' => 'code不能为空',
                'errorCode' => 60000
            ]);
        }
        $res = $this->checkRegSms($mobile, $code);
        if (!$res) {
            throw new SmsException();
        }
        //进行绑定手机号
        $res = User::bindMobileByUid($uid, $mobile);

        if (!$res) {
            return json(['msg' => '绑定失败', 'code' => 201]);
        }
        return json(['msg' => '绑定成功', 'code' => 200]);
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