(function(){
    'use strict';

    const LOG_LEVELS = { quiet: 0, standard: 1, verbose: 2 };
    const easeOutCubic = (t, b, c, d) => {
        t /= d;
        t--;
        return c * (t * t * t + 1) + b;
    };

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

    /**
     * Format leaderboard values using shortened units when appropriate.
     *
     * @param {number} val  The numeric value to format.
     * @param {string} type The leaderboard type (used to skip ratios).
     * @return {{value:number, decimals:number, suffix:string}}
     */
    function formatLeaderboardValue(val, type){
        // Ratios and percentages should not be shortened and retain 2 decimals.
        if(type === 'reserves_to_debt_ratio'){
            return { value: val, decimals: 2, suffix: '' };
        }

        const abs = Math.abs(val);
        if(abs >= 1e9){
            return { value: val / 1e9, decimals: 3, suffix: 'bn' };
        }
        if(abs >= 1e6){
            return { value: val / 1e6, decimals: 3, suffix: 'm' };
        }
        if(abs >= 1e5){
            return { value: val / 1e3, decimals: 0, suffix: 'k' };
        }
        return { value: val, decimals: 0, suffix: '' };
    }

    function initInfoElement(el){
        el.dataset.cdcInfoInitialised = '1';
        let items = [];
        try { items = JSON.parse(el.dataset.items || '[]'); } catch(e){}
        if(!items.length) return;
        let idx = 0;
        el.textContent = items[0];
        el.style.opacity = '1';
        if(items.length > 1){
            setInterval(()=>{
                idx = (idx + 1) % items.length;
                el.style.opacity = '0';
                setTimeout(()=>{
                    el.textContent = items[idx];
                    el.style.opacity = '1';
                },400);
            },3500);
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
        // Allow each counter to control how long the initial animation lasts.
        const duration = parseFloat(el.dataset.duration) || 15;
        let decimals = 2;
        let suffix = '';
        let displayTarget = target;
        if(el.dataset.lbType){
            const fmt = formatLeaderboardValue(target, el.dataset.lbType);
            displayTarget = fmt.value;
            decimals = fmt.decimals;
            suffix = fmt.suffix;
            start = formatLeaderboardValue(start, el.dataset.lbType).value;
        }

        debugLog('Initialising counter', {target, start, growth, prefix, displayTarget, decimals, suffix});

        const counter = new CountUpClass(el, displayTarget, {
            startVal: start,
            decimalPlaces: decimals,
            prefix: prefix,
            suffix: suffix,
            duration: duration,
            easingFn: easeOutCubic
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
        } else if(window.cdcLeaderboard && el.dataset.lbType && el.dataset.cid && el.dataset.year && el.dataset.nonce){
            const data = new FormData();
            data.append('action','cdc_get_leaderboard_value');
            data.append('nonce', el.dataset.nonce);
            data.append('id', el.dataset.cid);
            data.append('lb_type', el.dataset.lbType);
            data.append('year', el.dataset.year);
            fetch(window.cdcLeaderboard.ajaxUrl,{method:'POST',credentials:'same-origin',body:data})
                .then(r=>r.json())
                .then(res=>{
                    if(res.success && res.data){
                        const val = parseFloat(res.data.value);
                        if(!isNaN(val) && Math.abs(val - target) > 0.01){
                            const fmt = formatLeaderboardValue(val, el.dataset.lbType);
                            counter.update(fmt.value);
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
        context.querySelectorAll('.cdc-counter-info').forEach(el => {
            if(!el.dataset.cdcInfoInitialised){
                initInfoElement(el);
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
                    if (node.classList && node.classList.contains('cdc-counter-info')){
                        initInfoElement(node);
                    }
                    const nestedCounters = node.querySelectorAll ? node.querySelectorAll('.cdc-counter') : [];
                    nestedCounters.forEach(el => observeCounters(el.parentNode || document));
                    const nestedInfo = node.querySelectorAll ? node.querySelectorAll('.cdc-counter-info') : [];
                    nestedInfo.forEach(el => initInfoElement(el));
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
