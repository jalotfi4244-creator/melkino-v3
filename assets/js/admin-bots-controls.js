/* One scoped settings controller shared by BOTH admin shells. No global reload after saves. */
(function () {
    'use strict';
    if (window.MelkinoBots) return;
    const fields={
        login_telegram_enabled:'loginTelegramEnabled',login_bale_enabled:'loginBaleEnabled',login_eitaa_enabled:'loginEitaaEnabled',login_sms_enabled:'loginSmsEnabled',
        telegram_token:'botTelegramToken',telegram_channel:'botTelegramChannel',telegram_bot_username:'botTelegramUsername',telegram_miniapp_url:'botTelegramMiniappUrl',
        bale_token:'botBaleToken',bale_channel:'botBaleChannel',bale_bot_username:'botBaleUsername',bale_miniapp_url:'botBaleMiniappUrl',
        eitaa_token:'botEitaaToken',eitaa_bot_username:'botEitaaUsername',eitaa_miniapp_url:'botEitaaMiniappUrl',
        eitaa_channel_token:'botEitaaChannelToken',eitaa_channel:'botEitaaChannel',http_proxy:'botProxy',
        sms_enabled:'smsEnabled',sms_api_key:'smsApiKey',sms_api_url:'smsApiUrl',sms_sender_line:'smsSenderLine',sms_provider:'smsProvider',sms_password:'smsApiPassword',
        sms_otp_line:'smsOtpLine',sms_promo_line:'smsPromoLine',sms_otp_body_id:'smsOtpBodyId',sms_otp_template:'smsOtpTemplate'
    };
    const scopes={
        methods:['login_telegram_enabled','login_bale_enabled','login_eitaa_enabled','login_sms_enabled'],
        telegram:['telegram_token','telegram_channel','telegram_bot_username','telegram_miniapp_url','login_telegram_enabled'],
        bale:['bale_token','bale_channel','bale_bot_username','bale_miniapp_url','login_bale_enabled'],
        eitaa:['eitaa_token','eitaa_bot_username','eitaa_miniapp_url','login_eitaa_enabled'],
        eitaa_channel:['eitaa_channel_token','eitaa_channel'],proxy:['http_proxy'],
        sms:['sms_enabled','sms_api_key','sms_api_url','sms_sender_line','sms_provider','sms_password','sms_otp_line','sms_promo_line','sms_otp_body_id','sms_otp_template','login_sms_enabled']
    };
    const secrets=['telegram_token','bale_token','eitaa_token','eitaa_channel_token','sms_api_key','sms_password'];
    const state={loaded:false,loading:null,revisions:{},baseline:{},busy:{},logs:{}};
    const el=k=>document.getElementById(fields[k]||k);
    const keys=scope=>scope==='all'?Object.keys(fields):(scopes[scope]||[]);
    function value(k){const e=el(k);return !e?'':e.type==='checkbox'?(e.checked?'1':'0'):e.value;}
    function dirty(k){return secrets.includes(k)?value(k)!=='':value(k)!==state.baseline[k];}
    function notice(scope,text,result){
        const n=document.querySelector('[data-bot-status="'+scope+'"]');
        if(n){n.textContent=text||'';n.dataset.state=result===true?'success':result===false?'failed':(result||'working');}
    }
    function lock(scope,on){
        const card=document.querySelector('[data-bot-section="'+scope+'"]');
        if(card){card.setAttribute('aria-busy',on?'true':'false');card.querySelectorAll('input,select,textarea,button:not([data-bot-refresh])').forEach(b=>b.disabled=on);}
        keys(scope).forEach(k=>{const e=el(k);if(e)e.disabled=on;});
    }
    function drawLogs(scope){
        const list=document.querySelector('[data-bot-log="'+scope+'"]');if(!list)return;
        list.replaceChildren();
        (state.logs[scope]||[]).slice(0,6).forEach(log=>{
            const li=document.createElement('li');li.dataset.outcome=log.outcome||'';
            const tm=document.createElement('time');
            tm.textContent=new Date(Number(log.created_epoch||0)*1000).toLocaleString('fa-IR')+' · '+(log.actor||'مدیر')+' · '+(log.source==='browser_report'?'گزارش مرورگر':log.source==='configuration'?'بررسی محلی':'سرور');
            li.appendChild(tm);li.appendChild(document.createTextNode(log.message||''));list.appendChild(li);
        });
        if(!list.children.length){const li=document.createElement('li');li.textContent='هنوز رویدادی ثبت نشده است.';list.appendChild(li);}
    }
    function addLog(scope,log){if(!log)return;state.logs[scope]=[log].concat((state.logs[scope]||[]).filter(x=>String(x.id)!==String(log.id)));drawLogs(scope);}
    async function api(action,payload){
        const meta=document.querySelector('meta[name="csrf-token"]');
        const token=window.MELKINO_CSRF||(meta?meta.content:'');
        const opts={cache:'no-store',credentials:'same-origin',headers:{Accept:'application/json'}};
        if(payload!==undefined){opts.method='POST';opts.headers['Content-Type']='application/json';opts.headers['X-CSRF-Token']=token;opts.body=JSON.stringify(payload);}
        const r=await fetch('admin-bots.php?action='+action,opts);const text=await r.text();let data;
        try{data=JSON.parse(text);}catch(e){throw new Error('پاسخ تنظیمات JSON نبود (HTTP '+r.status+'). نشست یا محدودیت هاست را بررسی کنید؛ فرم بازخوانی نشده است.');}
        data.http_status=r.status;return data;
    }
    function apply(data,ks){
        const s=data.settings||{};
        ks.forEach(k=>{
            const e=el(k);if(!e)return;
            if(secrets.includes(k)){e.value='';e.placeholder=s[k+'_masked']||'تنظیم نشده';state.baseline[k]='';}
            else {
                const v=k==='sms_otp_template'?(s[k]??s.sms_otp_template_masked??''):(s[k]??'');
                if(e.type==='checkbox')e.checked=String(v)==='1';else e.value=String(v);
                state.baseline[k]=value(k);
            }
            e.removeAttribute('aria-invalid');
        });
        for(const [name,path] of [['Telegram','telegram-app.php'],['Bale','bale-app.php'],['Eitaa','eitaa-app.php']]){const e=document.getElementById('bot'+name+'EntryUrl');if(e)e.value=new URL(path,location.href).href;}
    }
    function errors(data){
        Object.keys(data.field_errors||{}).forEach(k=>{const e=el(k);if(e){e.setAttribute('aria-invalid','true');e.title=data.field_errors[k];}});
    }
    async function load(){
        if(state.loaded)return true;
        if(state.loading)return state.loading;
        Object.keys(scopes).forEach(s=>{lock(s,true);notice(s,'در حال دریافت تنظیمات…','working');});
        state.loading=(async()=>{
            try{
                const d=await api('get');if(!d.success)throw new Error(d.message||'دریافت تنظیمات انجام نشد.');
                apply(d,Object.keys(fields));state.revisions=d.revisions||{};state.logs=d.logs||{};state.loaded=true;
                Object.keys(scopes).forEach(s=>{notice(s,'تنظیمات دریافت شد.','checked');drawLogs(s);lock(s,false);});return true;
            }catch(e){Object.keys(scopes).forEach(s=>notice(s,e.message,false));return false;}
            finally{state.loading=null;}
        })();return state.loading;
    }
    async function refresh(scope){
        if(state.busy[scope])return;
        if(!state.loaded)return load();
        if(keys(scope).some(dirty)&&!confirm('تغییرات ذخیره‌نشدهٔ همین بخش کنار گذاشته شود؟'))return;
        state.busy[scope]=true;lock(scope,true);
        try{
            const d=await api('get');if(!d.success)throw new Error(d.message||'بازخوانی ناموفق بود.');
            // Do not overwrite a dirty login switch from the separate methods card.
            const ks=keys(scope).filter(k=>scope==='methods'||!k.startsWith('login_')||!dirty(k));
            apply(d,ks);state.revisions[scope]=(d.revisions||{})[scope]||0;
            if(scope==='methods')state.revisions.methods=(d.revisions||{}).methods||0;
            state.logs[scope]=(d.logs||{})[scope]||[];drawLogs(scope);notice(scope,'همین بخش بازخوانی شد.','checked');
        }catch(e){notice(scope,e.message,false);}finally{state.busy[scope]=false;lock(scope,false);}
    }
    async function save(scope){
        if(!state.loaded&&!(await load()))return false;
        if(scope==='all'){
            // Compatibility for an old cached "save all" button: independent sections.
            if(keys('methods').some(dirty)&&!(await save('methods')))return false;
            let ok=true;
            for(const s of Object.keys(scopes).filter(x=>x!=='methods')){if(keys(s).some(dirty)&&!(await save(s)))ok=false;}
            return ok;
        }
        if(!scopes[scope]||state.busy[scope])return false;
        const payload={scope,expected_revision:state.revisions[scope]||0,expected_methods_revision:state.revisions.methods||0};
        const snapshot={};
        keys(scope).forEach(k=>{
            if(!el(k))return;
            if(k.startsWith('login_')&&scope!=='methods'&&!dirty(k))return;
            snapshot[k]=value(k);payload[k]=value(k);
        });
        state.busy[scope]=true;lock(scope,true);notice(scope,'در حال ذخیرهٔ همین بخش…','working');
        try{
            const d=await api('save',payload);addLog(scope,d.log);
            if(!d.success){errors(d);notice(scope,d.message||'ذخیره انجام نشد.',false);return false;}
            // No whole-form GET here: only acknowledged values from this request are applied.
            const ks=(d.applied_keys||[]).filter(k=>value(k)===snapshot[k]);
            apply(d,ks);state.revisions[scope]=(d.revisions||{})[scope]||0;
            if(Object.keys(snapshot).some(k=>k.startsWith('login_')))state.revisions.methods=(d.revisions||{}).methods||0;
            if((d.changed_keys||[]).some(k=>secrets.includes(k))&&typeof window.melkinoClearRelayCache==='function')window.melkinoClearRelayCache();
            notice(scope,d.message+(d.log_warning?'\n'+d.log_warning:''),d.log_warning?'checked':'success');return true;
        }catch(e){notice(scope,e.message||'ذخیره انجام نشد؛ تغییرات فرم حفظ شده است.',false);return false;}
        finally{state.busy[scope]=false;lock(scope,false);}
    }
    async function test(scope,kind){
        kind=kind||'connection';if(!state.loaded&&!(await load()))return;
        if(state.busy[scope])return;
        const tokenKey=scope==='eitaa_channel'?'eitaa_channel_token':scope+'_token';
        const channelKey=scope==='eitaa_channel'?'eitaa_channel':scope+'_channel';
        const body={scope,kind,token:fields[tokenKey]?value(tokenKey):'',channel:fields[channelKey]?value(channelKey):''};
        if(scope==='sms'){const phone=document.getElementById('smsTestPhone');body.phone=phone?phone.value:'';}
        if(kind==='message'){
            const recipient=scope==='sms'?body.phone:body.channel;
            if(!confirm('یک پیام آزمایشی واقعی به «'+recipient+'» ارسال شود؟'))return;
            body.confirmed=true;
        }
        state.busy[scope]=true;lock(scope,true);notice(scope,'در حال آزمون…'+(body.token?' (توکن واردشده؛ هنوز الزاماً ذخیره نشده)':''),'working');
        try{
            // Keep the existing browser route for hosts blocking Telegram/Bale egress.
            // This is a diagnostic report, never proof of Eitaa publication.
            if(['telegram','bale'].includes(scope)&&kind==='connection'&&typeof window.melkinoApiCall==='function'){
                const c=await window.melkinoApiCall(scope,'getMe',{}, {token:body.token,clientOnly:true});
                if(c&&c.ok===true&&!c.assumed){
                    const report=await api('record_client_test',{scope,ok:true});addLog(scope,report.log);
                    notice(scope,report.message||'مرورگر پاسخ موفق سرویس را دریافت کرد.','success');return;
                }
            }
            const d=await api('test_section',body);addLog(scope,d.log);errors(d);
            notice(scope,(d.message||'پاسخی دریافت نشد.')+(d.log_warning?'\n'+d.log_warning:''),d.state||(d.success?'success':'failed'));
        }catch(e){notice(scope,e.message||'آزمون انجام نشد.',false);}
        finally{state.busy[scope]=false;lock(scope,false);}
    }
    window.MelkinoBots={load,save,test,refresh,notice};
    document.addEventListener('click',function(e){
        const b=e.target&&e.target.closest?e.target.closest('[data-bot-save],[data-bot-test],[data-bot-refresh]'):null;
        if(!b||b.disabled)return;e.preventDefault();
        if(b.hasAttribute('data-bot-save'))save(b.dataset.botSave);
        else if(b.hasAttribute('data-bot-test'))test(b.dataset.botTest,b.dataset.testKind||'connection');
        else refresh(b.dataset.botRefresh);
    });
})();
