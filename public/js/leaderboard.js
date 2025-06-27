(function(){
    'use strict';
    function init(){
        document.querySelectorAll('.cdc-leaderboard').forEach(function(el){
            var select = el.querySelector('.cdc-year-select');
            if(!select){ return; }
            select.addEventListener('change', function(){
                var overlay = document.createElement('div');
                overlay.id = 'cdc-year-overlay';
                overlay.innerHTML = '<span class="spinner-border" role="status"></span>';
                document.body.appendChild(overlay);
                var data = new FormData();
                data.append('action','cdc_render_leaderboard');
                data.append('nonce', el.dataset.nonce);
                data.append('type', el.dataset.type);
                data.append('limit', el.dataset.limit);
                data.append('format', el.dataset.format);
                data.append('year', select.value);
                fetch(el.dataset.ajaxUrl || (window.cdcLeaderboard && window.cdcLeaderboard.ajaxUrl), {
                    method:'POST',
                    credentials:'same-origin',
                    body:data
                }).then(function(r){ return r.json(); })
                  .then(function(res){
                    var container = el.querySelector('.cdc-leaderboard-container');
                    if(res.success && res.data && container){
                        container.classList.remove('cdc-show');
                        container.innerHTML = res.data.html;
                        void container.offsetWidth;
                        container.classList.add('cdc-show');
                    }
                    overlay.remove();
                  }).catch(function(){ overlay.remove(); });
            });
        });
    }
    document.addEventListener('DOMContentLoaded', init);
})();
