<?php
/** Unified browser contract. Identification is read-only; only existing verified auth handlers grant identity. */
declare(strict_types=1);
require_once __DIR__.'/messenger-login-lib.php';
melkinoMessengerSessionStart();
require_once __DIR__.'/config.php';
require_once __DIR__.'/bot-settings.php';
require_once __DIR__.'/db_helpers.php';
require_once __DIR__.'/security-lib.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');
melkinoMessengerSessionCookie();
$reply=static function(array $d,int $status=200):never{http_response_code($status);echo json_encode($d,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;};
try {
    if(!isset($pdo)||!($pdo instanceof PDO))$reply(['success'=>false,'code'=>'storage','message'=>'اتصال پایگاه داده در دسترس نیست.'],503);
    if(empty($_SESSION['mk_login_probe']))$_SESSION['mk_login_probe']=bin2hex(random_bytes(16));
    $method=$_SERVER['REQUEST_METHOD']??'GET';
    $action=$_GET['action']??'';
    if($method==='GET'&&in_array($action,['bootstrap','status'],true)){
        $u=melkinoMessengerCurrentUser($pdo);
        $done=$_SESSION['mk_login_completed']??[];
        $reply(['success'=>true,'authenticated'=>$u!==null,'user_id'=>$u?(int)$u['id']:null,
            'provider'=>melkinoMessengerContext(),'attempt'=>$u?(string)($done['attempt']??''):'',
            'csrf_token'=>melkinoCsrfToken(),'session_probe'=>$_SESSION['mk_login_probe']]);
    }
    if($method!=='POST'){header('Allow: GET, POST');$reply(['success'=>false,'code'=>'method','message'=>'روش درخواست مجاز نیست.'],405);}
    if((int)($_SERVER['CONTENT_LENGTH']??0)>65536)$reply(['success'=>false,'code'=>'too_large','message'=>'حجم دادهٔ ورود نامعتبر است.'],413);
    melkinoCsrfCheck();
    if(function_exists('melkinoRateLimitHit')&&!melkinoRateLimitHit('messenger-start:'.(string)($_SERVER['REMOTE_ADDR']??''),120,60,true)){
        header('Retry-After: 60');$reply(['success'=>false,'code'=>'rate_limit','message'=>'درخواست زیاد است؛ یک دقیقه بعد دوباره تلاش کنید.'],429);
    }
    $body=json_decode((string)file_get_contents('php://input'),true);
    if(!is_array($body))$body=[];
    $action=is_string($body['action']??null)?$body['action']:'';
    $raw=is_string($body['init_data']??null)?$body['init_data']:'';
    if($raw===''||strlen($raw)>32768)$reply(['success'=>false,'code'=>'missing_data','message'=>'اطلاعات امضاشدهٔ شروع برنامه دریافت نشد.'],422);
    if($action==='identify'){
        $valid=[];$stale=[];$disabled=[];
        foreach(array_keys(melkinoMessengerNames())as$p){
            $proof=melkinoMessengerProof($p,$raw);
            if(!empty($proof['user'])){
                if(melkinoLoginMethodEnabled($p))$valid[]=$p;else $disabled[]=$p;
            }elseif(in_array($proof['error']??'',['expired','future'],true))$stale[]=$p;
        }
        if(count($valid)===1)$reply(['success'=>true,'provider'=>$valid[0]]);
        if(count($valid)>1)$reply(['success'=>false,'code'=>'ambiguous','message'=>'پیام‌رسان به‌صورت یکتا تشخیص داده نشد؛ برنامهٔ درست را انتخاب کنید.'],409);
        if($disabled)$reply(['success'=>false,'code'=>'disabled','provider'=>$disabled[0],'message'=>'ورود با این پیام‌رسان توسط مدیر غیرفعال شده است.'],403);
        if($stale)$reply(['success'=>false,'code'=>'expired','provider'=>$stale[0],'message'=>'اطلاعات ورود قدیمی است؛ برنامک را ببندید و از دکمهٔ اجرای آن دوباره باز کنید.'],401);
        $reply(['success'=>false,'code'=>'unrecognized','message'=>'اطلاعات ورود با تنظیمات پیام‌رسان‌ها مطابقت ندارد؛ برنامهٔ درست را انتخاب کنید.'],401);
    }
    if($action!=='authenticate')$reply(['success'=>false,'code'=>'action','message'=>'درخواست شناخته نشد.'],422);
    $p=is_string($body['provider']??null)?$body['provider']:'';
    if(!isset(melkinoMessengerNames()[$p]))$reply(['success'=>false,'code'=>'provider','message'=>'پیام‌رسان را انتخاب کنید.'],422);
    if(!melkinoLoginMethodEnabled($p))$reply(['success'=>false,'code'=>'disabled','message'=>'این روش ورود توسط مدیر غیرفعال شده است.'],403);
    $attempt=is_string($body['attempt']??null)?$body['attempt']:'';
    if(!preg_match('/^[A-Za-z0-9_-]{16,80}$/D',$attempt))$reply(['success'=>false,'code'=>'attempt','message'=>'شناسهٔ درخواست معتبر نیست؛ دوباره تلاش کنید.'],422);
    $proof=melkinoMessengerProof($p,$raw);
    if(empty($proof['user'])){
        $code=(string)($proof['error']??'bad_hash');
        $expired=in_array($code,['expired','future','bad_date'],true);
        $reply(['success'=>false,'code'=>$code,'message'=>$expired?'اطلاعات ورود منقضی شده یا ساعت سرور نادرست است؛ برنامک را ببندید و دوباره باز کنید.':($code==='not_configured'?'توکن این پیام‌رسان در تنظیمات سایت کامل نیست.':'امضای ورود معتبر نیست؛ پیام‌رسان انتخاب‌شده و توکن همان برنامه باید مطابقت داشته باشند.')],$code==='not_configured'?503:401);
    }
    $done=$_SESSION['mk_login_completed']??[];
    $current=melkinoMessengerCurrentUser($pdo);
    if($current&&($done['provider']??'')===$p&&(int)($done['uid']??0)===(int)$current['id']
        &&is_string($done['fingerprint']??null)&&hash_equals($done['fingerprint'],$proof['fingerprint'])){
        $_SESSION['mk_login_completed']['attempt']=$attempt;
        $reply(['success'=>true,'provider'=>$p,'user_id'=>(int)$current['id'],'attempt'=>$attempt,'reused'=>true]);
    }
    // A disabled account must not be updated by a legacy upsert before rejection.
    $column=['telegram'=>'telegram_id','bale'=>'bale_id','eitaa'=>'eitaa_id'][$p];
    $st=$pdo->prepare("SELECT is_active FROM users WHERE `$column`=? LIMIT 1");$st->execute([$proof['user']['id']]);
    $active=$st->fetchColumn();
    if($active!==false&&(int)$active!==1)$reply(['success'=>false,'code'=>'account_disabled','message'=>'این حساب غیرفعال است؛ با پشتیبانی تماس بگیرید.'],403);
    $_SESSION['mk_login_pending']=['provider'=>$p,'attempt'=>$attempt,'fingerprint'=>$proof['fingerprint']];
    define('MELKINO_UNIFIED_LOGIN',true); // Server-owned route context, never read from HTTP input.
    require __DIR__.'/auth-'.$p.'.php';
    exit;
}catch(Throwable $e){
    unset($_SESSION['mk_login_pending']);
    error_log('[melkino][messenger-start] code='.$e->getCode());
    $reply(['success'=>false,'code'=>'storage','message'=>'تکمیل ورود در سرور انجام نشد؛ دوباره تلاش کنید یا با پشتیبانی تماس بگیرید.'],503);
}
