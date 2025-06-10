(function(){
    document.addEventListener('DOMContentLoaded', function(){
        var btn = document.getElementById('cdc-check-license');
        if(!btn) return;
        var input = document.getElementById('cdc_license_key');
        var result = document.getElementById('cdc-license-result');
        btn.addEventListener('click', function(e){
            e.preventDefault();
            if(!input) return;
            result.textContent = 'Checking...';
            fetch(ajaxurl, {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
                body: new URLSearchParams({
                    action: 'cdc_check_license',
                    nonce: CDC_LICENSE_CHECK.nonce,
                    key: input.value
                })
            }).then(function(resp){
                return resp.json();
            }).then(function(data){
                if(data.success){
                    result.textContent = data.data.message;
                } else {
                    result.textContent = data.data && data.data.message ? data.data.message : 'Error validating license.';
                }
            }).catch(function(){
                result.textContent = 'Error validating license.';
            });
        });
    });
})();
