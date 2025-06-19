(function(){
    function getCountUpClass(){
        // Always prefer the namespaced CountUp class bundled with this plugin.
        // Some themes ship an old global `CountUp` which is incompatible with
        // the v2 API we rely on. Using that version results in counters dropping
        // the currency prefix and decimal places.
        if(window.countUp && window.countUp.CountUp){
            return window.countUp.CountUp;
        }
        if(window.CountUp && window.CountUp.version){
            return window.CountUp;
        }
        console.error('CountUp library not loaded or incompatible');
        logToServer('CountUp library missing or bad version');
        return null;
    }

    function logToServer(message){
        if(!window.CDC_LOGGER) return;
        try{
            var data=new FormData();
            data.append('action','cdc_log_js');
            data.append('nonce',window.CDC_LOGGER.nonce);
            data.append('message',message);
            navigator.sendBeacon(window.CDC_LOGGER.ajaxUrl,data);
        }catch(e){
            console.warn('Logging failed',e);
        }
    }

    function init(el){
        var CountUpClass=getCountUpClass();
        if(!CountUpClass) return;
        var target=parseFloat(el.dataset.target)||0;
        var start=parseFloat(el.dataset.start)||0;
        var growth=parseFloat(el.dataset.growth)||0;
        var prefix=el.dataset.prefix||'';
        var decimals=2;

        console.log('CDC counter init', {target:target,start:start,growth:growth});

        var counter=new CountUpClass(el,target,{startVal:start,decimalPlaces:decimals,prefix:prefix});
        if(counter.error){
            console.error(counter.error);
            logToServer(counter.error);
            return;
        }
        counter.start(function(){
            if(growth!==0){
                setInterval(function(){
                    start+=growth;
                    counter.update(start);
                },1000);
            }
        });
    }

    document.addEventListener('DOMContentLoaded',function(){
        document.querySelectorAll('.cdc-counter').forEach(init);
    });
})();
