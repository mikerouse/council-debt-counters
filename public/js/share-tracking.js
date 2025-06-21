(function(){
    'use strict';
    function logShare(el){
        if(!window.cdcShare) return;
        try{
            var data=new FormData();
            data.append('action','cdc_log_share');
            data.append('nonce',cdcShare.nonce);
            data.append('id',el.dataset.councilId);
            navigator.sendBeacon(cdcShare.ajaxUrl,data);
        }catch(e){}
    }
    function track(el){
        var type=el.dataset.shareType||'';
        if(window.gtag){
            window.gtag('event','share',{event_category:'CDC Share',event_label:type});
        }else if(window.ga){
            window.ga('send','event','CDC Share',type);
        }
        logShare(el);
    }
    document.addEventListener('DOMContentLoaded',function(){
        document.querySelectorAll('.cdc-share-link').forEach(function(a){
            a.addEventListener('click',function(){track(a);});
        });
    });
})();

