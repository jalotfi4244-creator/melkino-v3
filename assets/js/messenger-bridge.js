/* UI lifecycle only. No identity or signed data is ever sent to the parent/native bridge. */
(function(){
    'use strict';
    if(window.MelkinoMessengerBridge)return;
    const origins={telegram:['https://web.telegram.org'],bale:['https://web.bale.ai','https://beta.bale.ai','https://zo.bale.ai','https://internal.bale.ai'],eitaa:['https://web.eitaa.com']};
    function native(p){return p==='bale'?window.BaleWebApp:p==='eitaa'?window.EitaaWebviewProxy:window.TelegramWebviewProxy;}
    function send(p,event){
        if(!origins[p]||!['web_app_ready','web_app_close'].includes(event))return false;
        try{const bridge=native(p);if(bridge&&typeof bridge.postEvent==='function'){bridge.postEvent(event,'{}');return true;}}catch(e){}
        try{if(window.external&&typeof window.external.notify==='function'){window.external.notify(JSON.stringify({eventType:event,eventData:{}}));return true;}}catch(e){}
        try{if(window.parent&&window.parent!==window){origins[p].forEach(origin=>window.parent.postMessage(JSON.stringify({eventType:event,eventData:{}}),origin));return true;}}catch(e){}
        return false;
    }
    window.MelkinoMessengerBridge={ready:p=>send(p,'web_app_ready'),close:p=>send(p,'web_app_close'),hasNative:p=>{try{return !!native(p);}catch(e){return false;}}};
})();
