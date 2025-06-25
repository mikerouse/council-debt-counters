(function(){
    'use strict';
    // Build the year drop-down and handle changes
    function init(){
        if(!window.cdcYear) return; // script only runs on council pages
        var select=document.getElementById('cdc-year-select');
        if(!select) return;
        window.cdcYear.years.forEach(function(y){
            var opt=document.createElement('option');
            opt.value=y;
            opt.textContent=y;
            if(y===window.cdcYear.current){ opt.selected=true; }
            select.appendChild(opt);
        });

        // When a year is selected fetch new HTML for the page
        select.addEventListener('change', function(){
            // Basic loading overlay so the user knows we're fetching data
            var overlay=document.createElement('div');
            overlay.id='cdc-year-overlay';
            overlay.innerHTML='<span class="spinner-border" role="status"></span>';
            document.body.appendChild(overlay);
            var data=new FormData();
            data.append('action','cdc_select_year');
            data.append('nonce',window.cdcYear.nonce);
            data.append('post_id',window.cdcYear.postId);
            data.append('year',select.value);
            fetch(window.cdcYear.ajaxUrl,{method:'POST',credentials:'same-origin',body:data})
                .then(function(r){return r.json();})
                .then(function(res){
                    if(res.success && res.data){
                        var container=document.getElementById('cdc-year-container');
                        if(container){
                            container.innerHTML=res.data.html;
                        }
                    }
                    overlay.remove();
                })
                .catch(function(){ overlay.remove(); });
        });
    }
    document.addEventListener('DOMContentLoaded',init);
})();
