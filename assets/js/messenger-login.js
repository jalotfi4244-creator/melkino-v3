/* Unified cold-start controller. One SDK/document, bounded waiting, signed POST only. */
(function () {
    'use strict';
    const cfg=JSON.parse(document.getElementById('mkLoginConfig').textContent);
    const $=id=>document.getElementById(id);
    const state={provider:cfg.hint||'',sdkProvider:'',sdk:'none',raw:'',source:'none',carrier:'',busy:false,done:false,mode:'messenger',generation:0,timer:null,attempt:'',expectedUid:null,csrf:cfg.csrf,probe:cfg.sessionProbe};
    function diag(code){$('mkLoginDiagnostic').textContent='login-v3 | app='+(state.provider||'unknown')+' | sdk='+state.sdk+' | data='+state.source+' | code='+String(code||'WAIT').toUpperCase().replace(/[^A-Z0-9_-]/g,'').slice(0,36);}
    function say(text,code,error){$('mkLoginStatus').textContent=text;$('mkLoginStatus').dataset.error=error?'1':'0';diag(code);}
    function phase(name){document.querySelectorAll('[data-stage]').forEach(e=>e.classList.toggle('active',e.dataset.stage===name));}
    function spinner(on){$('mkLoginSpinner').hidden=!on;}
    function choices(on){$('mkProviderChoice').hidden=!on;}
    function enableChoices(on){document.querySelectorAll('[data-provider]').forEach(b=>b.disabled=!on);}
    function instance(p){return p==='telegram'?(window.Telegram&&window.Telegram.WebApp):p==='bale'?(window.Bale&&window.Bale.WebApp):(window.Eitaa&&window.Eitaa.WebApp);}
    function rawFrom(input){
        try{
            let value=String(input||'').replace(/^[#?]/,'');
            if(value.includes('?'))value=value.slice(value.indexOf('?')+1);
            const params=new URLSearchParams(value);
            for(const [k,v] of params)if(k.toLowerCase()==='tgwebappdata'&&v.length<=32768)return v;
        }catch(e){}
        return '';
    }
    function freshCapture(){
        const h=location.hash||'';const q=location.search||'';
        const raw=rawFrom(h)||rawFrom(q);
        if(raw){state.raw=raw;state.source=rawFrom(h)?'hash':'query';state.carrier=h;}
        return raw;
    }
    const early=window.__mkLaunch||{};
    state.raw=rawFrom(early.hash)||rawFrom(early.search);
    if(state.raw){state.source=rawFrom(early.hash)?'hash':'query';state.carrier=early.hash||'';}
    // Deliberately do not restore the old cross-messenger login hash cache.
    try{sessionStorage.removeItem('melkino_tg_hash');}catch(e){}
    function nativeHint(){
        if(window.BaleWebApp)return 'bale';
        if(window.EitaaWebviewProxy||window.EitaaGameProxy_receiveEvent)return 'eitaa';
        if(window.TelegramWebviewProxy||window.TelegramGameProxy_receiveEvent)return 'telegram';
        const ua=navigator.userAgent||'';
        if(/eitaa/i.test(ua))return 'eitaa';if(/bale|ble\.ir/i.test(ua))return 'bale';if(/telegram/i.test(ua))return 'telegram';
        try{const h=new URL(document.referrer).hostname;if(['web.bale.ai','beta.bale.ai'].includes(h))return 'bale';if(h==='web.telegram.org')return 'telegram';if(h==='web.eitaa.com')return 'eitaa';}catch(e){}
        return '';
    }
    if(!state.provider)state.provider=nativeHint();
    function getData(){
        freshCapture();if(state.raw)return state.raw;
        try{const w=state.provider&&instance(state.provider);if(w&&typeof w.initData==='string'&&w.initData.length&&w.initData.length<=32768){state.raw=w.initData;state.source='sdk';}}
        catch(e){}
        return state.raw;
    }
    function updateProvider(){
        const p=cfg.providers[state.provider];
        $('mkProviderLabel').textContent=p?p.label:'تشخیص پیام‌رسان';
        document.querySelectorAll('[data-provider]').forEach(b=>b.setAttribute('aria-pressed',b.dataset.provider===state.provider?'true':'false'));
        for(const [id,key,label]of[['mkOpenApp','launch','باز کردن برنامک در '],['mkOpenBot','bot','باز کردن صفحهٔ ربات در ']]){
            const a=$(id);a.hidden=!(p&&p.enabled&&p[key]);if(!a.hidden){a.href=p[key];a.textContent=label+p.label;}
        }
        const w=state.provider&&instance(state.provider);$('mkCloseApp').hidden=!((w&&typeof w.close==='function')||(window.MelkinoMessengerBridge&&window.MelkinoMessengerBridge.hasNative(state.provider)));
    }
    function announce(){
        let ready=false;try{const w=instance(state.provider);if(w&&typeof w.ready==='function'){w.ready();ready=true;}}catch(e){}
        if(!ready&&window.MelkinoMessengerBridge)window.MelkinoMessengerBridge.ready(state.provider);
        updateProvider();
    }
    function clearVendorAuth(p){
        try{
            const key='__'+p+'__initParams';const old=JSON.parse(sessionStorage.getItem(key)||'null');
            if(old&&typeof old==='object'){
                Object.keys(old).forEach(k=>{if(k.toLowerCase()==='tgwebappdata')delete old[k];});
                sessionStorage.setItem(key,JSON.stringify(old));
            }
        }catch(e){}
    }
    function loadSdk(p){
        if(!p||!cfg.providers[p]||state.sdkProvider)return;
        state.sdkProvider=p;
        if(instance(p)){state.sdk='ready';announce();return;}
        clearVendorAuth(p); // Old cache is not a new launch identity.
        state.sdk='loading';diag('SDK_LOADING');announce();
        const script=document.createElement('script');script.src=cfg.providers[p].sdk;script.async=true;script.dataset.messengerSdk=p;
        script.onload=function(){state.sdk=instance(p)?'ready':'error';if(state.provider===p){announce();if(!state.busy&&!state.done&&state.mode==='messenger'&&getData())begin(false);}};
        script.onerror=function(){state.sdk='error';diag('SDK_NETWORK');if(!state.raw&&!state.busy)fallback('کیت پیام‌رسان بارگیری نشد؛ اتصال اینترنت را بررسی کنید یا برنامک را دوباره از ربات باز کنید.','SDK_NETWORK');};
        document.head.appendChild(script);
    }
    function fallback(text,code){
        clearInterval(state.timer);state.timer=null;spinner(false);choices(true);enableChoices(true);$('mkLoginRetry').hidden=false;
        say(text||'اطلاعات حساب هنوز دریافت نشده است. پیام‌رسان را انتخاب کنید؛ در صورت ادامهٔ مشکل، برنامک را ببندید و دوباره باز کنید.',code||'NO_DATA',true);announce();
    }
    function newAttempt(){
        if(window.crypto&&crypto.randomUUID)return crypto.randomUUID().replace(/-/g,'');
        return 'm'+Date.now()+Math.random().toString(36).slice(2)+Math.random().toString(36).slice(2);
    }
    async function api(action,payload){
        const ctrl=typeof AbortController!=='undefined'?new AbortController():null;
        const t=ctrl?setTimeout(()=>ctrl.abort(),15000):null;
        const opts={credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}};
        let url=cfg.endpoint+'?action='+action;
        if(payload){opts.method='POST';opts.headers['Content-Type']='application/json';opts.headers['X-CSRF-Token']=state.csrf;opts.body=JSON.stringify(Object.assign({action},payload));}
        if(ctrl)opts.signal=ctrl.signal;
        try{
            const r=await fetch(url,opts);const text=await r.text();let d;
            try{d=JSON.parse(text);}catch(e){throw {code:'HOST_RESPONSE',message:'سرور به‌جای پاسخ ورود، صفحهٔ دیگری فرستاد. ممکن است نشست یا صفحهٔ امنیتی هاست مانع باشد؛ اطلاعات ورود در همین صفحه نگه داشته شده است.'};}
            return {http:r.status,data:d,ok:r.ok&&d.success===true};
        }catch(e){if(e&&e.code)throw e;throw {code:'NETWORK',message:'ارتباط ورود کامل نشد؛ اتصال را بررسی کنید و «تلاش دوباره» را بزنید.'};}
        finally{if(t)clearTimeout(t);}
    }
    let sessionTask=null;
    async function session(){
        if(sessionTask)return sessionTask;
        sessionTask=bootstrapSession();
        try{return await sessionTask;}finally{sessionTask=null;}
    }
    async function bootstrapSession(){
        const a=await api('bootstrap');if(!a.ok)throw {code:a.data.code||'SERVER',message:a.data.message||'نشست امن آماده نشد.'};
        state.csrf=a.data.csrf_token;
        if(state.probe&&a.data.session_probe!==state.probe){
            const b=await api('status');
            if(!b.ok||b.data.session_probe!==a.data.session_probe)throw {code:'COOKIES_BLOCKED',message:'نشست در مرورگر حفظ نمی‌شود. کوکی‌ها یا محدودیت وب‌ویو را بررسی کنید و برنامک را از برنامهٔ اصلی باز کنید.'};
            state.csrf=b.data.csrf_token;
        }
        state.probe=a.data.session_probe;return a.data;
    }
    function keepUiLaunchParams(){
        // Runtime navigation needs SDK version/platform even if the SDK was slow.
        // Only UI hints are persisted; auth data is deliberately excluded.
        try{
            const p=state.provider;if(!p)return;
            const params=new URLSearchParams((state.carrier || early.hash || '').replace(/^#/,''));
            const query=new URLSearchParams(early.search||'');
            const key='__'+p+'__initParams';let safe={};
            try{safe=JSON.parse(sessionStorage.getItem(key)||'{}')||{};}catch(e){}
            delete safe.tgWebAppData;
            ['tgWebAppVersion','tgWebAppPlatform','tgWebAppThemeParams'].forEach(k=>{
                let v=params.get(k)||query.get(k)||'';
                if(k==='tgWebAppVersion'&&!/^[0-9]+(?:\.[0-9]+){0,2}$/.test(v))return;
                if(k==='tgWebAppPlatform'&&!/^[a-z0-9_-]{1,24}$/i.test(v))return;
                if(k==='tgWebAppThemeParams'){
                    try{const raw=JSON.parse(v);const clean={};Object.keys(raw).forEach(n=>{if(/^[a-z_]+$/.test(n)&&/^#[0-9a-f]{3,8}$/i.test(String(raw[n])))clean[n]=raw[n];});v=JSON.stringify(clean);}catch(e){return;}
                }
                if(v)safe[k]=v;
            });
            sessionStorage.setItem(key,JSON.stringify(safe));
        }catch(e){}
    }
    function scrub(){
        // Only after the server session is confirmed; never before a failed attempt.
        try{const u=new URL(location.href);Array.from(u.searchParams.keys()).forEach(k=>{if(/^tgwebapp/i.test(k))u.searchParams.delete(k);});u.hash='';history.replaceState(history.state,'',u.pathname+u.search);}catch(e){}
        keepUiLaunchParams();
        ['telegram','bale','eitaa'].forEach(clearVendorAuth);
        ['melkino_login_token','melkino_telegram_id','melkino_bale_id','melkino_eitaa_id','melkino_user_id','melkino_user_phone','melkino_user_name'].forEach(k=>{try{localStorage.removeItem(k);}catch(e){}});
        ['reg_telegram_id','reg_bale_id','reg_eitaa_id','reg_phone','reg_last_name','melkino_user_id','melkino_tg_hash','melkino_profile_synced','melkino_identified_ok'].forEach(k=>{try{sessionStorage.removeItem(k);}catch(e){}});
        state.raw='';state.carrier='';window.__mkLaunch=null;
    }
    function enter(){
        state.done=true;state.busy=false;clearInterval(state.timer);choices(false);$('mkLoginRetry').hidden=true;phase('session');spinner(false);
        say('ورود تأیید شد؛ در حال باز کردن ملکینو…','DONE',false);announce();scrub();
        const next=new URL(cfg.next||'home.php',location.href);
        location.replace(next.origin===location.origin?next.href:new URL('home.php',location.href).href);
    }
    function restartFor(p){
        // If a late payload proves the initial routing hint wrong, change document once,
        // carrying data only in the fragment; never load competing SDKs in one page.
        const u=new URL(cfg.providers[p].entry,location.href);u.searchParams.set('redirect',cfg.next||'home.php');
        const q=new URLSearchParams();q.set('tgWebAppData',state.raw);
        try{
            const source=new URLSearchParams((state.carrier||'').replace(/^#/,''));
            ['tgWebAppVersion','tgWebAppPlatform','tgWebAppThemeParams'].forEach(k=>{if(source.has(k))q.set(k,source.get(k));});
        }catch(e){}
        u.hash=q.toString();location.replace(u.href);
    }
    async function confirmSession(){
        const r=await api('status');
        return r.ok&&r.data.authenticated===true&&Number(r.data.user_id)===Number(state.expectedUid)&&r.data.attempt===state.attempt;
    }
    async function begin(manual){
        if(state.busy||state.done||state.mode!=='messenger')return;
        const raw=getData();if(!raw){wait();return;}
        state.busy=true;const generation=++state.generation;clearInterval(state.timer);spinner(true);enableChoices(false);$('mkLoginRetry').hidden=true;$('mkContinueSession').hidden=true;
        try{
            phase('detect');say('در حال تشخیص امن پیام‌رسان…','IDENTIFY',false);await session();
            let p=state.provider;
            if(!manual||!p){
                const found=await api('identify',{init_data:raw});
                if(!found.ok){if(found.data.provider&&cfg.providers[found.data.provider])state.provider=found.data.provider;throw {code:found.data.code||'UNRECOGNIZED',message:found.data.message};}
                p=found.data.provider;
            }
            if(generation!==state.generation)return;
            if(!cfg.providers[p]||!cfg.providers[p].enabled)throw {code:'DISABLED',message:'ورود با این پیام‌رسان غیرفعال است؛ روش دیگری انتخاب کنید.'};
            state.provider=p;updateProvider();
            if(!manual&&state.sdkProvider&&state.sdkProvider!==p){restartFor(p);return;}
            loadSdk(p);phase('verify');say('در حال تأیید هویت از '+cfg.providers[p].label+'…','VERIFY',false);
            if(!state.attempt)state.attempt=newAttempt();
            let result=await api('authenticate',{provider:p,init_data:raw,attempt:state.attempt});
            // One recoverable CSRF refresh, not an infinite page-reload/authentication loop.
            if(result.http===419){await session();result=await api('authenticate',{provider:p,init_data:raw,attempt:state.attempt});}
            if(!result.ok)throw {code:result.http===419?'COOKIES_BLOCKED':(result.data.code||'AUTH_FAILED'),message:result.http===419?'نشست امن حفظ نشد. کوکی‌ها را بررسی کنید یا برنامک را ببندید و دوباره باز کنید.':(result.data.message||'ورود انجام نشد.')};
            state.expectedUid=result.data.user_id;phase('session');say('هویت تأیید شد؛ در حال بررسی نشست…','SESSION_CHECK',false);
            if(!(await confirmSession()))throw {code:'SESSION_LOST',message:'هویت تأیید شد اما نشست حفظ نشد. کوکی یا تنظیمات هاست را بررسی کنید؛ انتقال بی‌پایان تکرار نمی‌شود.'};
            if(generation===state.generation)enter();
        }catch(e){
            if(generation!==state.generation)return;
            // Lost response may have arrived after the server completed the attempt.
            if(state.attempt){
                try{const r=await api('status');if(r.ok&&r.data.authenticated&&r.data.attempt===state.attempt&&r.data.provider===state.provider){state.expectedUid=r.data.user_id;enter();return;}}catch(ignored){}
            }
            fallback((e&&e.message)||'ورود تکمیل نشد. دوباره تلاش کنید.',(e&&e.code)||'ERROR');
        }finally{if(generation===state.generation&&!state.done){state.busy=false;enableChoices(true);}}
    }
    function wait(){
        if(state.busy||state.done||state.mode!=='messenger')return;
        clearInterval(state.timer);phase('detect');updateProvider();spinner(true);
        if(state.provider&&cfg.providers[state.provider]&&cfg.providers[state.provider].enabled)loadSdk(state.provider);
        const start=Date.now();
        say('منتظر اطلاعات شروع برنامه هستیم…','WAIT_DATA',false);
        if(!state.provider){choices(true);spinner(false);say('پیام‌رسان را انتخاب کنید. ورود فقط با اطلاعات امضاشدهٔ همان برنامه انجام می‌شود.','CHOOSE_APP',false);}
        state.timer=setInterval(function(){
            if(state.done||state.busy||state.mode!=='messenger'){clearInterval(state.timer);return;}
            if(getData()){begin(false);return;}
            if(Date.now()-start>3500)choices(true);
            if(Date.now()-start>=12000)fallback(null,'NO_DATA');
        },100);
    }
    async function choose(p){
        if(state.busy||state.done)return;
        state.mode='messenger';state.provider=p;state.attempt='';updateProvider();
        if(state.sdkProvider&&state.sdkProvider!==p){
            fallback('برای ورود از '+cfg.providers[p].label+'، دکمهٔ باز کردن برنامک را بزنید. اگر انتخاب قبلی اشتباه بود، می‌توانید اطلاعات همین صفحه را با «تلاش دوباره» بررسی کنید.','OPEN_SELECTED_APP');
            return;
        }
        if(getData())begin(true);else wait();
    }
    function open(p,url){
        if(!url)return;state.mode='opening';++state.generation;clearInterval(state.timer);
        try{
            const w=instance(p);
            if(p==='telegram'&&w&&typeof w.openTelegramLink==='function'){w.openTelegramLink(url);return;}
            if(p==='eitaa'&&w&&typeof w.openEitaaLink==='function'){w.openEitaaLink(url);return;}
            if(p==='bale'&&w&&typeof w.openLink==='function'){w.openLink(url);return;}
        }catch(e){}
        location.assign(url);
    }
    document.querySelectorAll('[data-provider]').forEach(b=>b.addEventListener('click',()=>choose(b.dataset.provider)));
    $('mkLoginRetry').addEventListener('click',()=>{
        if(state.busy)return;state.mode='messenger';
        if(state.sdk==='error'&&!instance(state.provider)){
            document.querySelectorAll('[data-messenger-sdk]').forEach(e=>e.remove());state.sdkProvider='';state.sdk='none';
        }
        if(getData())begin(!!state.provider);else wait();
    });
    $('mkContinueSession').addEventListener('click',async()=>{
        if(state.busy)return;
        try{const r=await api('status');if(r.ok&&r.data.authenticated){state.expectedUid=r.data.user_id;enter();}else fallback('نشست فعلی معتبر نیست؛ پیام‌رسان را انتخاب کنید.','SESSION_EMPTY');}
        catch(e){fallback(e.message,e.code);}
    });
    ['mkOpenApp','mkOpenBot'].forEach(id=>$(id).addEventListener('click',e=>{e.preventDefault();open(state.provider,$(id).href);}));
    $('mkCloseApp').addEventListener('click',()=>{try{const w=instance(state.provider);if(w&&w.close){w.close();return;}}catch(e){}if(window.MelkinoMessengerBridge)window.MelkinoMessengerBridge.close(state.provider);});
    window.addEventListener('hashchange',()=>{if(freshCapture()&&!state.busy&&!state.done&&state.mode==='messenger')begin(false);});
    // OTP retains the same endpoints and has no dependency on a messenger SDK.
    const sms=$('mkSmsPanel');
    function normalize(v){return String(v||'').replace(/[۰-۹]/g,d=>'۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g,d=>'٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/[^0-9+]/g,'').replace(/^(?:\+98|0098)/,'0');}
    async function smsCall(url,body){const r=await fetch(url,{method:'POST',credentials:'same-origin',cache:'no-store',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify(body)});return r.json();}
    if(sms){
        sms.addEventListener('toggle',()=>{if(sms.open&&!state.busy){state.mode='sms';clearInterval(state.timer);spinner(false);}});
        $('smsSendBtn').addEventListener('click',async()=>{
            const phone=normalize($('smsPhone').value);if(!/^09[0-9]{9}$/.test(phone)){$('smsMsg').textContent='شماره موبایل معتبر وارد کنید.';return;}
            $('smsSendBtn').disabled=true;
            try{const r=await smsCall('request-otp.php',{phone});$('smsMsg').textContent=r.message||'درخواست بررسی شد.';if(r.success)$('mkSmsCodeRow').hidden=false;}
            catch(e){$('smsMsg').textContent='پاسخ پیامک دریافت نشد؛ اتصال یا محدودیت هاست را بررسی کنید.';}finally{$('smsSendBtn').disabled=false;}
        });
        $('smsVerifyBtn').addEventListener('click',async()=>{
            $('smsVerifyBtn').disabled=true;
            try{const r=await smsCall('verify-otp.php',{phone:normalize($('smsPhone').value),code:normalize($('smsCode').value)});if(r.success){const check=await api('status');if(check.ok&&check.data.authenticated&&Number(check.data.user_id)===Number(r.user_id)){enter();return;}throw new Error('session');}$('smsMsg').textContent=r.message||'کد صحیح نیست.';}
            catch(e){$('smsMsg').textContent='تأیید یا حفظ نشست انجام نشد؛ دوباره تلاش کنید.';}finally{$('smsVerifyBtn').disabled=false;}
        });
    }
    updateProvider();
    if(getData())begin(false);
    else {
        if(state.provider&&cfg.providers[state.provider]&&!cfg.providers[state.provider].enabled)fallback('ورود با این پیام‌رسان غیرفعال است؛ روش دیگری انتخاب کنید.','DISABLED');
        else wait();
        session().then(d=>{if(d.authenticated&&!state.busy&&!state.done&&!getData())$('mkContinueSession').hidden=false;}).catch(e=>{if(!state.busy&&!state.done)fallback(e.message,e.code);});
    }
})();
