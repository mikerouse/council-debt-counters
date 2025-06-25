(function(){
    'use strict';
    function init(){
        document.querySelectorAll('.cdc-council-counters').forEach(function(el){
            var select = el.querySelector('.cdc-year-select');
            if(!select) return;
            select.addEventListener('change', function(){
                var overlay = document.createElement('div');
                overlay.id = 'cdc-year-overlay';
                overlay.innerHTML = '<span class="spinner-border" role="status"></span>';
                document.body.appendChild(overlay);
                var data = new FormData();
                data.append('action','cdc_render_counters');
                data.append('nonce', el.dataset.nonce);
                data.append('post_id', el.dataset.councilId);
                data.append('year', select.value);
                fetch(el.dataset.ajaxUrl || (window.cdcCounters && window.cdcCounters.ajaxUrl),{method:'POST',credentials:'same-origin',body:data})
                    .then(function(r){return r.json();})
                    .then(function(res){
                        if(res.success && res.data){
                            var container = el.querySelector('.cdc-counters-container');
                            if(container){ container.innerHTML = res.data.html; }
                        }
                        overlay.remove();
                    })
                    .catch(function(){ overlay.remove(); });
            });
        });
    }
    document.addEventListener('DOMContentLoaded',init);
})();
