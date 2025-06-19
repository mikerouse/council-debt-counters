(function(){
    'use strict';

    function getCountUpClass(){
        // Prefer the CountUp instance bundled with this plugin. Some themes
        // include an old global `CountUp` (v1) which does not support prefixes
        // or decimal places.
        if (window.countUp && window.countUp.CountUp) {
            return window.countUp.CountUp;
        }
        if (window.CountUp && window.CountUp.version) {
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
        const CountUpClass = getCountUpClass();
        if (!CountUpClass) return;

        const target  = parseFloat(el.dataset.target) || 0;
        let start     = parseFloat(el.dataset.start)  || 0;
        const growth  = parseFloat(el.dataset.growth) || 0;
        const prefix  = el.dataset.prefix || '';
        const decimals = 2;

        const counter = new CountUpClass(el, target, {
            startVal: start,
            decimalPlaces: decimals,
            prefix: prefix
        });

        if (counter.error) {
            logToServer(counter.error);
            return;
        }

        counter.start(() => {
            if (growth !== 0) {
                setInterval(() => {
                    start += growth;
                    counter.update(start);
                }, 1000);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.cdc-counter').forEach(init);
    });
})();
