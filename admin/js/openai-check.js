(function(){
    document.addEventListener('DOMContentLoaded', function(){
        var btn = document.getElementById('cdc-check-openai');
        if(!btn) return;
        var input = document.getElementById('cdc_openai_api_key');
        var result = document.getElementById('cdc-openai-result');
        btn.addEventListener('click', function(e){
            e.preventDefault();
            if(!input) return;
            result.textContent = CDC_OPENAI_CHECK.checkingText;
            fetch(ajaxurl, {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
                body: new URLSearchParams({
                    action: 'cdc_check_openai_key',
                    nonce: CDC_OPENAI_CHECK.nonce,
                    key: input.value
                })
            }).then(function(resp){
                return resp.json();
            }).then(function(data){
                if(data.success){
                    result.textContent = data.data.message;
                } else {
                    result.textContent = data.data && data.data.message ? data.data.message : CDC_OPENAI_CHECK.errorText;
                }
            }).catch(function(){
                result.textContent = CDC_OPENAI_CHECK.errorText;
            });
        });
    });
})();

