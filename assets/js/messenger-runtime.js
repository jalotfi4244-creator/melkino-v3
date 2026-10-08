/* Protected pages: ready/back only, never hidden re-authentication or competing SDKs. */
(function(){
    'use strict';
    if(window.__mkMessengerRuntime)return;window.__mkMessengerRuntime=true;
    function boot(){
        const p=document.documentElement.getAttribute('data-melkino-messenger');
        if(window.MelkinoMessengerBridge)window.MelkinoMessengerBridge.ready(p);
        let bound=null;
        function bind(){
            const w=p==='telegram'?(window.Telegram&&window.Telegram.WebApp):p==='bale'?(window.Bale&&window.Bale.WebApp):(window.Eitaa&&window.Eitaa.WebApp);
            if(!w||bound===w)return;bound=w;
            try{if(w.ready)w.ready();}catch(e){}
            try{if(w.expand)w.expand();}catch(e){}
            const page=(location.pathname.split('/').pop()||'index.php').toLowerCase();
            const home=['home.php','index.php'].includes(page);
            function back(){
                let ref=null;try{ref=new URL(document.referrer);}catch(e){}
                if(ref&&ref.origin===location.origin&&!/\/(?:login|telegram-app|bale-app|eitaa-app)\.php$/.test(ref.pathname)&&history.length>1)history.back();
                else if(!home)location.assign('home.php');else{try{if(w.close)w.close();}catch(e){}}
            }
            try{if(w.BackButton){w.BackButton.onClick(back);if(home)w.BackButton.hide();else w.BackButton.show();}}catch(e){}
        }
        document.querySelectorAll('script[data-mk-sdk]').forEach(s=>s.addEventListener('load',bind));
        bind();window.addEventListener('load',bind,{once:true});
        // Some native clients complete their bridge asynchronously after the script load.
        let checks=0;const timer=setInterval(()=>{bind();if(bound||++checks>=30)clearInterval(timer);},200);
        document.addEventListener('click',function(e){
            const a=e.target&&e.target.closest?e.target.closest('a[href]'):null;if(!a)return;
            try{const u=new URL(a.href,location.href);if(u.origin===location.origin&&/\/logout\.php$/.test(u.pathname)){
                ['__telegram__initParams','__bale__initParams','__eitaa__initParams','melkino_tg_hash'].forEach(k=>{try{sessionStorage.removeItem(k);}catch(e){}});
            }}catch(e){}
        });
    }
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
})();
