(function(){
    'use strict';

    const LOG_LEVELS = { quiet: 0, standard: 1, verbose: 2 };

    debugLog('counter-animations script loaded', null, 'verbose');

    function getCurrentLogLevel() {
        if (window.CDC_LOGGER && window.CDC_LOGGER.logLevel) {
            return window.CDC_LOGGER.logLevel;
        }
        return 'standard';
    }

    function debugLog(message, data, level = 'standard'){
        const current = getCurrentLogLevel();
        if (LOG_LEVELS[current] >= LOG_LEVELS[level]) {
            if(data !== undefined){
                console.log('[CDC]', message, data);
            } else {
                console.log('[CDC]', message);
            }
        }
    }

    function getCountUpClass(){
        // Prefer the CountUp instance bundled with this plugin. Some themes
        // include an old global `CountUp` (v1) which does not support prefixes
        // or decimal places.
        if (window.countUp && window.countUp.CountUp) {
            debugLog('Using bundled CountUp', {version: window.countUp.CountUp.version});
            return window.countUp.CountUp;
        }
        if (window.CountUp && window.CountUp.version) {
            debugLog('Using global CountUp', {version: window.CountUp.version});
            return window.CountUp;
        }
        console.error('CountUp library not loaded or incompatible');
        logToServer('CountUp library missing or bad version');
        return null;
    }

    function logToServer(message){
        if (!window.CDC_LOGGER) return;
        try {
            const data = new FormData();
            data.append('action', 'cdc_log_js');
            data.append('nonce', window.CDC_LOGGER.nonce);
            data.append('message', message);
            navigator.sendBeacon(window.CDC_LOGGER.ajaxUrl, data);
        } catch (e) {
            console.warn('Logging failed', e);
        }
    }

    function init(el){
        if (el.dataset.cdcCountupInitialised) {
            debugLog('Skipping already-initialised counter', {id: el.id}, 'verbose');
            return;
        }
        el.dataset.cdcCountupInitialised = '1';
        el.style.visibility = 'hidden';
        el.style.opacity = '0';
        el.style.transform = 'translateY(10px)';

        const CountUpClass = getCountUpClass();
        if (!CountUpClass) {
            debugLog('CountUp not available', null, 'standard');
            return;
        }

        const target  = parseFloat(el.dataset.target) || 0;
        let start     = parseFloat(el.dataset.start)  || 0;
        const growth  = parseFloat(el.dataset.growth) || 0;
        const prefix  = el.dataset.prefix || '';
        const decimals = 2;

        debugLog('Initialising counter', {target, start, growth, prefix});

        const counter = new CountUpClass(el, target, {
            startVal: start,
            decimalPlaces: decimals,
            prefix: prefix,
            duration: 2
        });

        if (counter.error) {
            logToServer(counter.error);
            return;
        }

        el.style.visibility = 'visible';
        requestAnimationFrame(() => {
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        });

        counter.start(() => {
            debugLog('Counter animation complete', {target}, 'verbose');
        });

        if (window.cdcCounters && el.dataset.cid && el.dataset.field && el.dataset.year){
            const data = new FormData();
            data.append('action','cdc_get_counter_value');
            data.append('id', el.dataset.cid);
            data.append('field', el.dataset.field);
            data.append('year', el.dataset.year);
            fetch(window.cdcCounters.ajaxUrl,{method:'POST',credentials:'same-origin',body:data})
                .then(r=>r.json())
                .then(res=>{
                    if(res.success && res.data){
                        const val = parseFloat(res.data.value);
                        if(!isNaN(val) && Math.abs(val - target) > 0.01){
                            counter.update(val);
                        }
                    }
                })
                .catch(()=>{});
        }

        if (growth !== 0) {
            setInterval(() => {
                start += growth;
                counter.update(start);
                debugLog('Counter tick', {value: start}, 'verbose');
            }, 1000);
        }
    }

    function observeCounters(context){
        context.querySelectorAll('.cdc-counter').forEach(el => {
            if (!el.dataset.cdcCountupInitialised){
                intersection.observe(el);
            }
        });
    }

    const intersection = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting){
                debugLog('Counter visible', {id: entry.target.id}, 'verbose');
                init(entry.target);
                intersection.unobserve(entry.target);
            }
        });
    });

    const mutation = new MutationObserver(mutations => {
        mutations.forEach(m => {
            m.addedNodes.forEach(node => {
                if (node.nodeType === 1){
                    if (node.classList && node.classList.contains('cdc-counter')){
                        observeCounters(node.parentNode || document);
                    }
                    const nested = node.querySelectorAll ? node.querySelectorAll('.cdc-counter') : [];
                    nested.forEach(el => observeCounters(el.parentNode || document));
                }
            });
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        debugLog('DOM loaded - setting up observers');
        observeCounters(document);
        mutation.observe(document.body, {childList: true, subtree: true});
    });
})();
