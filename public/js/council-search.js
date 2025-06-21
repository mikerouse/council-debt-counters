(function(){
    'use strict';
    function debounce(fn, delay){
        let timer;
        return function(){
            clearTimeout(timer);
            timer = setTimeout(fn, delay);
        };
    }
    document.addEventListener('DOMContentLoaded', function(){
        const input = document.getElementById('cdc-council-search');
        if(!input) return;
        const list = document.getElementById('cdc-search-results');
        const msg = document.getElementById('cdc-search-message');
        const info = window.cdcSearch && window.cdcSearch.message ? window.cdcSearch.message : '';
        function render(items){
            list.innerHTML = '';
            if(!items || items.length === 0){
                msg.textContent = info;
                return;
            }
            msg.textContent = info;
            items.forEach(function(i){
                const a = document.createElement('a');
                a.className = 'list-group-item list-group-item-action';
                a.href = i.url;
                a.textContent = i.title;
                list.appendChild(a);
            });
        }
        function search(){
            const q = input.value.trim();
            if(q.length < 3){
                list.innerHTML = '';
                msg.textContent = '';
                return;
            }
            const url = `${cdcSearch.ajaxUrl}?action=cdc_search_councils&nonce=${cdcSearch.nonce}&q=${encodeURIComponent(q)}`;
            fetch(url).then(r=>r.json()).then(data=>{
                if(data.success){
                    render(data.data);
                }else if(data.data && data.data.message){
                    msg.textContent = data.data.message;
                }
            }).catch(()=>{});
        }
        input.addEventListener('input', debounce(search, 300));
    });
})();
