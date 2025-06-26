(function(){
    document.addEventListener('DOMContentLoaded', function(){
        var select = document.getElementById('cdc_council');
        var container = document.getElementById('cdc-calc-results');
        if(!select || !container) return;

        function load(){
            if(!select.value){ container.innerHTML=''; return; }
            container.innerHTML = '<span class="spinner is-active"></span>';
            var data = new FormData();
            data.append('action','cdc_calc_table');
            data.append('nonce', cdcCalc.nonce);
            data.append('cid', select.value);
            fetch(cdcCalc.ajaxUrl,{method:'POST',credentials:'same-origin',body:data})
                .then(function(r){return r.json();})
                .then(function(res){
                    if(res.success && res.data && res.data.html){
                        container.innerHTML = res.data.html;
                    }else{
                        container.innerHTML = '<p>Error loading data</p>';
                    }
                })
                .catch(function(){ container.innerHTML='<p>Error loading data</p>'; });
        }

        select.addEventListener('change', load);
        load();
    });
})();
