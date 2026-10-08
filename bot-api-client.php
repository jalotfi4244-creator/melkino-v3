<?php
/** Fixed-origin bot API transport. Tokens stay server-side for Eitaa channel operations. */
declare(strict_types=1);
require_once __DIR__ . '/bot-settings-write.php';

/** Optional callable is dependency injection for CLI contract tests, NOT an HTTP/app mode. */
function melkinoBotApiRequest(string $provider, string $method, array $params, string $token, ?callable $transport = null): array {
    $origins=['telegram'=>'https://api.telegram.org/bot','bale'=>'https://tapi.bale.ai/bot','eitaa_channel'=>'https://eitaayar.ir/api/'];
    $methods=['getMe','getChat','sendMessage','sendFile'];
    if (!isset($origins[$provider]) || !in_array($method,$methods,true)) throw new InvalidArgumentException('Unsupported bot operation');
    $key=$provider==='eitaa_channel'?'eitaa_channel_token':$provider.'_token';
    $token=melkinoBotValidateValue($key,$token);
    if ($token==='' || $token==='-') throw new MelkinoBotSettingsError('توکن این بخش تنظیم نشده است.',[$key=>'توکن این بخش را وارد کنید.']);
    $url=$origins[$provider].rawurlencode($token).'/'.$method;
    $multipart=$method==='sendFile';
    $body=$multipart ? $params : http_build_query($params,'','&',PHP_QUERY_RFC3986);
    if ($transport !== null) {
        $raw=$transport($url,$body);
    } else {
        $raw=['http'=>0,'errno'=>0,'body'=>''];
        if (!function_exists('curl_init')) $raw['errno']=2;
        else {
            $ch=curl_init($url);
            curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body,
                CURLOPT_HTTPHEADER=>$multipart ? ['Accept: application/json'] : ['Content-Type: application/x-www-form-urlencoded','Accept: application/json'],
                CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>20,
                CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_FOLLOWLOCATION=>false,
                CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS]);
            $proxy=function_exists('melkinoProxy')?melkinoProxy():'';
            if ($proxy!=='') curl_setopt($ch,CURLOPT_PROXY,$proxy);
            $result=curl_exec($ch);
            $raw=['http'=>(int)curl_getinfo($ch,CURLINFO_HTTP_CODE),'errno'=>curl_errno($ch),'body'=>is_string($result)?$result:''];
            curl_close($ch);
        }
    }
    $http=(int)($raw['http']??0);$errno=(int)($raw['errno']??0);
    $json=json_decode((string)($raw['body']??''),true,64,JSON_BIGINT_AS_STRING);
    if ($errno) {
        return ['ok'=>false,'state'=>in_array($errno,[2,5,6,7,35,60],true)?'failed':'unknown',
            'code'=>'network_'.$errno,'message'=>'ارتباط امن با سرویس برقرار نشد (کد شبکه '.$errno.'). تنظیمات شبکه/پروکسی هاست را بررسی کنید.','result'=>null,'http'=>$http];
    }
    if (is_array($json) && ($json['ok']??null)===true && $http>=200 && $http<300) {
        return ['ok'=>true,'state'=>'accepted','result'=>$json['result']??null,'message'=>'سرویس درخواست را پذیرفت.','http'=>$http,'code'=>'ok'];
    }
    $explicit=is_array($json) && array_key_exists('ok',$json) && $json['ok']===false;
    $description=$explicit && is_scalar($json['description']??$json['message']??null)
        ? (string)($json['description']??$json['message']) : 'پاسخ سرویس قابل تأیید نبود؛ کد HTTP '.$http;
    return ['ok'=>false,'state'=>$explicit?'failed':'unknown','code'=>$explicit?'rejected':'unconfirmed',
        'message'=>melkinoBotRedact($description,[$token]),'result'=>null,'http'=>$http];
}
function melkinoTestBotSection(PDO $pdo, string $scope, string $kind, array $data, ?callable $transport = null): array {
    $label=melkinoBotScopeLabel($scope);
    $source='server'; $state='failed'; $message='آزمون شناخته نشد.'; $result=[]; $secrets=[];
    if ($scope==='methods' || $scope==='proxy') {
        $state='checked'; $source='configuration';
        $message=$scope==='methods'?'روش‌های ورود از دیتابیس خوانده شدند؛ این بررسی، ورود واقعی پیام‌رسان نیست.':'پروکسی در آزمون اتصال هر ربات استفاده می‌شود؛ مقصد جداگانه‌ای برای تست حدسی فراخوانی نمی‌شود.';
    } elseif ($scope==='eitaa') {
        $token=is_string($data['token']??null) && melkinoBotTrim($data['token'])!==''?melkinoBotTrim($data['token']):melkinoEitaaToken();
        $r=melkinoTestEitaaConnection($token);
        $state=$r['success']?'checked':'failed';$source='configuration';$message=$r['message'];$result=$r;
    } elseif ($scope==='sms') {
        $phone=is_string($data['phone']??null)?strtr($data['phone'],['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']):'';
        $phone=preg_replace('/\D/','',$phone);
        if (!preg_match('/^09[0-9]{9}$/D',$phone)) throw new MelkinoBotSettingsError('شمارهٔ تست پیامک معتبر نیست.',['phone'=>'شمارهٔ تست را کامل وارد کنید.']);
        if (empty($data['confirmed'])) throw new MelkinoBotSettingsError('ارسال پیامک آزمایشی باید تأیید شود.');
        require_once __DIR__ . '/sms.php';
        $r=smsSendText($phone,'تست پنل پیامک ملکینو');
        $state=!empty($r['success'])?'success':'failed';$message=!empty($r['success'])?'درخواست پیامک آزمایشی پذیرفته شد.':(string)($r['message']??'ارسال پیامک ناموفق بود.');
    } elseif (in_array($scope,['telegram','bale','eitaa_channel'],true)) {
        $key=$scope==='eitaa_channel'?'eitaa_channel_token':$scope.'_token';
        $token=is_string($data['token']??null) && melkinoBotTrim($data['token'])!==''?melkinoBotTrim($data['token']):melkinoBotSetting($key);
        if ($token==='' && $scope==='telegram') $token=melkinoTelegramToken();
        if ($token==='' && $scope==='bale') $token=melkinoBaleToken();
        $secrets[]=$token;
        $channel=is_scalar($data['channel']??null)?trim((string)$data['channel']):melkinoBotSetting($scope==='eitaa_channel'?'eitaa_channel':$scope.'_channel');
        $method='getMe';$params=[];
        if ($kind==='channel' || $kind==='message') {
            $channel=melkinoBotValidateValue($scope==='eitaa_channel'?'eitaa_channel':$scope.'_channel',$channel);
            if ($channel==='') throw new MelkinoBotSettingsError('شناسهٔ کانال وارد نشده است.',['channel'=>'کانال مقصد را وارد کنید.']);
            $method='getChat';$params=['chat_id'=>$channel];
            if ($scope==='eitaa_channel') {
                // No undocumented getChat endpoint. Sending a test requires explicit consent.
                if ($kind!=='message' || empty($data['confirmed'])) throw new MelkinoBotSettingsError('برای آزمون دسترسی ارسال، دکمهٔ «ارسال پیام آزمایشی به کانال» را تأیید کنید.');
                $method='sendMessage';$params=['chat_id'=>$channel,'text'=>'پیام آزمایشی اتصال کانال ملکینو','title'=>'تست اتصال ملکینو'];
            }
        }
        $r=melkinoBotApiRequest($scope,$method,$params,$token,$transport);
        $state=$r['ok']?'success':$r['state'];
        if ($r['ok']) {
            if ($method==='getMe' && (!is_array($r['result']) || !isset($r['result']['id']))) {
                $state='unknown';$message='پاسخ سرویس فاقد مشخصات معتبر API بود؛ موفقیت فرض نمی‌شود.';
            } elseif ($method==='getMe') {
                $who=is_scalar($r['result']['username']??null)?mb_substr((string)$r['result']['username'],0,100):'';
                $message='اتصال API '.$label.' تأیید شد'.($who!==''?'؛ حساب: @'.$who:'').'؛ این آزمون به‌تنهایی مجوز ارسال به کانال را تأیید نمی‌کند.';
            }
            elseif ($method==='getChat') $message='مشخصات کانال '.$label.' خوانده شد؛ انتشار واقعی مرحلهٔ جدا دارد.';
            else {
                $mid=is_array($r['result'])?($r['result']['message_id']??null):null;
                if (!(is_string($mid) || is_int($mid)) || (string)$mid==='' || strlen((string)$mid)>80) {$state='unknown';$message='پاسخ سرویس بدون شناسهٔ پیام بود؛ پیش از تکرار، کانال را بررسی کنید.';}
                else {$message='پیام آزمایشی به کانال ایتا ارسال شد؛ شناسهٔ پیام: '.(string)$mid; $result['message_id']=(string)$mid;}
            }
        } else $message=$label.': '.$r['message'];
    }
    $log=melkinoBotLog($pdo,$scope,'test_'.$kind,$state,$message,$source,$secrets);
    return array_merge($result,['success'=>in_array($state,['success','checked'],true),'scope'=>$scope,'state'=>$state,'source'=>$source,
        'message'=>melkinoBotRedact($message,$secrets),'log'=>$log,'log_warning'=>$log?null:'ثبت سابقه در دسترس نیست؛ فایل SQL نسخهٔ جدید را وارد کنید.']);
}
