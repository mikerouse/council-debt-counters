(function() {
    function ready(fn){
        if(document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    }
    function formatCurrency(val) {
        if (isNaN(val)) return '';
        return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP', maximumFractionDigits: 2 }).format(val);
    }

    function addHelper(field) {
        var helper = document.createElement('div');
        helper.className = 'text-muted mt-1 cdc-helper';
        field.parentElement.appendChild(helper);
        function update() {
            var val = parseFloat(field.value);
            helper.textContent = val ? formatCurrency(val) : '';
        }
        field.addEventListener('input', update);
        update();
    }

    ready(function() {
        var actionInput = document.querySelector('input[name="action"][value="cdc_save_council"]');
        if (!actionInput) return; // only on edit/add page
        var form = actionInput.closest('form');
        if (!form) return;

        var statusSelect = document.getElementById('cdc-post-status');
        var assignee = document.querySelector('select[name="assigned_user"]');
        var uploadBtn = document.getElementById('cdc-upload-doc');
        var msgArea = document.getElementById('cdc-status-msg');
        function flash(msg){
            if(!msgArea) return;
            msgArea.innerHTML = '<div class="alert alert-success mb-0">'+msg+'</div>';
            setTimeout(function(){ msgArea.innerHTML = ''; },3000);
        }
        function sendToolbar(data){
            data.append('action','cdc_update_toolbar');
            data.append('post_id', cdcToolbarData.id);
            data.append('nonce', cdcToolbarData.nonce);
            fetch(ajaxurl,{method:'POST',credentials:'same-origin',body:data})
                .then(function(r){return r.json();})
                .then(function(res){ if(res.success && res.data && res.data.message){ flash(res.data.message); } });
        }
        if(statusSelect){
            statusSelect.addEventListener('change', function(){
                var d=new FormData(); d.append('post_status', statusSelect.value); sendToolbar(d);
            });
        }
        if(assignee){
            assignee.addEventListener('change', function(){
                var d=new FormData(); d.append('assigned_user', assignee.value); sendToolbar(d);
            });
        }
        if(uploadBtn){
            uploadBtn.addEventListener('click', function(e){
                e.preventDefault();
                var file=document.getElementById('cdc-soa');
                var url=form.querySelector('input[name="statement_of_accounts_url"]');
                var year=document.getElementById('cdc-soa-year');
                var existing=form.querySelector('select[name="statement_of_accounts_existing"]');
                var typeSel=form.querySelector('select[name="statement_of_accounts_type"]');
                var d=new FormData();
                d.append('action','cdc_upload_doc');
                d.append('nonce', cdcToolbarData.nonce);
                d.append('council_id', cdcToolbarData.id);
                if(year) d.append('year', year.value);
                if(file && file.files.length){ d.append('file', file.files[0]); }
                if(url && url.value){ d.append('url', url.value); }
                if(existing && existing.value){ d.append('existing', existing.value); }
                if(typeSel){ d.append('doc_type', typeSel.value); }

                var overlay=document.createElement('div');
                overlay.id='cdc-upload-overlay';
                overlay.innerHTML='<span class="spinner is-active"></span><div class="progress w-75 mt-2" style="height:8px"><div class="progress-bar"></div></div><p>Uploading…</p>';
                document.body.appendChild(overlay);
                var bar=overlay.querySelector('.progress-bar');
                var msg=overlay.querySelector('p');
                var xhr=new XMLHttpRequest();
                xhr.open('POST', ajaxurl);
                xhr.withCredentials=true;
                xhr.upload.addEventListener('progress', function(ev){
                    if(ev.lengthComputable){
                        var pct=Math.round((ev.loaded/ev.total)*100);
                        bar.style.width=pct+'%';
                    }
                });
                xhr.onload=function(){
                    var res=null;
                    try{ res=JSON.parse(xhr.responseText); }catch(err){}
                    if(res && res.success){
                        bar.style.width='100%';
                        msg.textContent=res.data.message||'Document added.';
                        setTimeout(function(){ location.reload(); }, 800);
                    }else{
                        msg.textContent=res && res.data && res.data.message ? res.data.message : 'Upload failed';
                        setTimeout(function(){ overlay.remove(); }, 2000);
                    }
                };
                xhr.onerror=function(){
                    msg.textContent='Upload failed';
                    setTimeout(function(){ overlay.remove(); }, 2000);
                };
                xhr.send(d);
            });
        }

        function validateField(field){
            if(!field.required) return;
            if(field.value.trim()===''){ field.classList.add('is-invalid'); }
            else{ field.classList.remove('is-invalid'); }
        }

        form.querySelectorAll('[required]').forEach(function(f){
            validateField(f);
            f.addEventListener('input', function(){
                validateField(f); updateTabState();
            });
        });

        function updateTabState(){
            document.querySelectorAll('.tab-pane').forEach(function(tab){
                var link = document.querySelector('[data-bs-target="#'+tab.id+'"]');
                if(!link) return;
                if(tab.querySelector('.is-invalid')) link.classList.add('text-danger');
                else link.classList.remove('text-danger');
            });
        }
        updateTabState();

        document.querySelectorAll('input[type="number"]').forEach(function(field) {
            // Only add helper if field represents a monetary value
            var meta = field.getAttribute('data-cdc-field') || '';
            var isBand = /^band_[a-h]_properties$/.test(meta);
            var isPopulation = meta === 'population';
            if (!isBand && !isPopulation) {
                addHelper(field);
            }
        });

        var shortField = document.querySelector('[data-cdc-field="current_liabilities"]');
        var longField = document.querySelector('[data-cdc-field="long_term_liabilities"]');
        var leaseField = document.querySelector('[data-cdc-field="finance_lease_pfi_liabilities"]');
        var manualField = document.querySelector('[data-cdc-field="manual_debt_entry"]');
        var adjustmentsField = document.querySelector('[data-cdc-field="debt_adjustments"]');
        var interestField = document.querySelector('[data-cdc-field="interest_paid"]');
        var totalField = document.querySelector('[data-cdc-field="total_debt"]');
        var ratesOutput = document.getElementById('cdc-debt-rates');

        var growthPerSecond = 0;

        function updateAll() {
            var shortVal = parseFloat(shortField ? shortField.value : 0) || 0;
            var longVal = parseFloat(longField ? longField.value : 0) || 0;
            var leaseVal = parseFloat(leaseField ? leaseField.value : 0) || 0;
            var manual = parseFloat(manualField ? manualField.value : 0) || 0;
            var adjustments = parseFloat(adjustmentsField ? adjustmentsField.value : 0) || 0;
            var interest = parseFloat(interestField ? interestField.value : 0) || 0;
            // Total debt is current liabilities + long term liabilities + lease/PFI + manual + adjustments
            var total = shortVal + longVal + leaseVal + manual + adjustments;
            if (totalField) {
                totalField.value = total.toFixed(2);
                totalField.dispatchEvent(new Event('input'));
            }
            var perDay = total / 365;
            var perHour = perDay / 24;
            var perSecond = perHour / 3600;
            ratesOutput.textContent = 'Debt per day: £' + perDay.toFixed(2) + ', per hour: £' + perHour.toFixed(2) + ', per second: £' + perSecond.toFixed(2);

            growthPerSecond = interest / (365 * 24 * 60 * 60);
        }

        if (shortField) shortField.addEventListener('input', updateAll);
        if (longField) longField.addEventListener('input', updateAll);
        if (leaseField) leaseField.addEventListener('input', updateAll);
        if (interestField) interestField.addEventListener('input', updateAll);
        updateAll();

        function handleNaToggle(name){
            var input=document.querySelector('[data-cdc-field="'+name+'"]');
            var checkbox=document.getElementById('cdc-na-'+name);
            var tab=document.getElementById('cdc-na-tab-debt');
            if(!input||!checkbox) return;
            var label=input.closest('tr').querySelector('label');
            var star=label?label.querySelector('.cdc-required-indicator'):null;
            var initial=input.dataset.initialRequired==='1';
            var notAvail=checkbox.checked||(tab&&tab.checked);
            if(initial){
                input.required=!notAvail;
                if(star) star.style.display=notAvail?'none':'';
            }
        }

        ['current_liabilities','long_term_liabilities','finance_lease_pfi_liabilities'].forEach(function(f){
            var cb=document.getElementById('cdc-na-'+f);
            if(cb){
                cb.addEventListener('change',function(){handleNaToggle(f);});
                handleNaToggle(f);
            }
        });
        var tabToggle=document.getElementById('cdc-na-tab-debt');
        if(tabToggle){
            tabToggle.addEventListener('change',function(){
                ['current_liabilities','long_term_liabilities','finance_lease_pfi_liabilities'].forEach(handleNaToggle);
            });
            ['current_liabilities','long_term_liabilities','finance_lease_pfi_liabilities'].forEach(handleNaToggle);
        }

        form.addEventListener('submit', function(){
            var file = document.getElementById('cdc-soa');
            var url = form.querySelector('input[name="statement_of_accounts_url"]');
            if((file && file.files.length>0) || (url && url.value.trim()!=='')){
                var overlay=document.createElement('div');
                overlay.id='cdc-upload-overlay';
                overlay.innerHTML='<span class="spinner is-active"></span><p>Uploading…</p>';
                document.body.appendChild(overlay);
            }
        });
        document.addEventListener('click', function(e){
            var target = e.target.closest('.cdc-extract-ai');
            if(!target) return;
            e.preventDefault();
            var docId = target.value;
                var overlay = document.createElement("div");
                overlay.id = "cdc-ai-overlay";
                overlay.innerHTML = "<span class=\"spinner is-active\"></span><div class=\"progress w-75 mt-2\" style=\"height:8px\"><div class=\"progress-bar\" style=\"width:0%\"></div></div><p></p>";
                var p = overlay.querySelector("p");
                var bar = overlay.querySelector('.progress-bar');
                var maxTime = cdcAiMessages.timeout || 60;
                var elapsed = 0;
                var barTimer = setInterval(function(){
                    elapsed++;
                    var pct = Math.min(100, (elapsed / maxTime) * 100);
                    bar.style.width = pct + '%';
                    if (elapsed >= maxTime) clearInterval(barTimer);
                },1000);
                document.body.appendChild(overlay);

                var steps = cdcAiMessages.steps || [];
                var i = 0;
                p.textContent = steps[i] || '';
                var interval = setInterval(function(){
                    i++;
                    if (i < steps.length) {
                        p.textContent = steps[i];
                    }
                }, 1500);

                var data = new FormData();
                data.append("action","cdc_extract_figures");
                data.append("doc_id", docId);
                fetch(ajaxurl,{method:"POST",credentials:"same-origin",body:data})
                    .then(function(r){return r.json();})
                    .then(function(res){
                        clearInterval(interval);
                        clearInterval(barTimer);
                        var msg = (res.data && res.data.message) || res.message;
                        var tokens = res.data && res.data.tokens ? res.data.tokens : 0;
                        if (tokens) {
                            msg += ' (' + tokens + ' tokens)';
                        }
                        p.textContent = msg || cdcAiMessages.error;
                        setTimeout(function(){location.reload();},1200);
                    })
                    .catch(function(){
                        clearInterval(interval);
                        clearInterval(barTimer);
                        p.textContent = cdcAiMessages.error;
                    });
            });

        function aiOverlay(msg){
            var ov=document.createElement('div');
            ov.id='cdc-ai-overlay';
            ov.innerHTML='<span class="spinner is-active"></span><p>'+msg+'</p>';
            document.body.appendChild(ov);
            return ov;
        }
        function removeOverlay(ov){ if(ov&&ov.parentNode){ ov.parentNode.removeChild(ov); } }

        function ensurePromptModal(){
            var m=document.getElementById('cdc-ai-prompt-modal');
            if(m) return m;
            var html='<div class="modal fade" id="cdc-ai-prompt-modal" tabindex="-1" aria-hidden="true">'+
                '<div class="modal-dialog"><div class="modal-content">'+
                '<div class="modal-header"><h5 class="modal-title">'+cdcAiMessages.editPrompt+'</h5>'+
                '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>'+
                '<div class="modal-body">'+
                '<div id="cdc-ai-response" class="alert alert-info mb-2" style="display:none">'+
                '<div id="cdc-ai-response-text" style="white-space:pre-wrap"></div>'+
                '<button type="button" class="btn btn-success btn-sm mt-2" id="cdc-ai-accept-btn">'+cdcAiMessages.accept+'</button>'+
                '</div>'+
                '<label for="cdc-ai-type" class="form-label">'+cdcAiMessages.typeLabel+'</label>'+
                '<select id="cdc-ai-type" class="form-select mb-2">'+
                '<option value="money">'+cdcAiMessages.typeMoney+'</option>'+
                '<option value="integer">'+cdcAiMessages.typeInteger+'</option>'+
                '<option value="word">'+cdcAiMessages.typeWord+'</option>'+
                '<option value="sentence">'+cdcAiMessages.typeSentence+'</option>'+
                '</select>'+
                '<textarea id="cdc-ai-prompt" class="form-control" rows="3"></textarea></div>'+
                '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'+cdcAiMessages.cancel+'</button>'+
                '<button type="button" class="btn btn-primary" id="cdc-ai-send-btn">'+cdcAiMessages.ask+'</button></div>'+
                '</div></div></div>';
            document.body.insertAdjacentHTML('beforeend', html);
            return document.getElementById('cdc-ai-prompt-modal');
        }

        function showPromptModal(prompt,type,responseText){
            var modalEl=ensurePromptModal();
            var textarea=modalEl.querySelector('#cdc-ai-prompt');
            var select=modalEl.querySelector('#cdc-ai-type');
            var resp=modalEl.querySelector('#cdc-ai-response');
            var respText=modalEl.querySelector('#cdc-ai-response-text');
            var acceptBtn=modalEl.querySelector('#cdc-ai-accept-btn');
            textarea.value=prompt;
            if(select) select.value=type||'';
            if(resp){
                if(responseText){
                    if(respText) respText.textContent=responseText;
                    resp.style.display='block';
                    if(acceptBtn) acceptBtn.style.display='inline-block';
                }else{
                    resp.style.display='none';
                    if(acceptBtn) acceptBtn.style.display='none';
                }
            }
            var modal=bootstrap.Modal.getOrCreateInstance(modalEl);
            return new Promise(function(resolve){
                var done=false;
                function cleanup(){
                    done=true;
                    modalEl.removeEventListener('hidden.bs.modal', onHide);
                    sendBtn.removeEventListener('click', onSend);
                    if(acceptBtn) acceptBtn.removeEventListener('click', onAccept);
                }
                function onHide(){ if(!done){ cleanup(); resolve(null); } }
                function onSend(){ if(!done){ var val=textarea.value; var t=select.value; cleanup(); modal.hide(); resolve({prompt:val,type:t}); } }
                function onAccept(){ if(!done){ cleanup(); modal.hide(); resolve({insert:responseText}); } }
                var sendBtn=modalEl.querySelector('#cdc-ai-send-btn');
                modalEl.addEventListener('hidden.bs.modal', onHide, {once:true});
                sendBtn.addEventListener('click', onSend);
                if(acceptBtn && responseText){ acceptBtn.addEventListener('click', onAccept); }
                modal.show();
            });
        }

        async function askField(field){
            var name=document.querySelector('[data-cdc-field="council_name"]');
            var data=new FormData();
            data.append('action','cdc_ai_clarify_field');
            data.append('field',field);
            data.append('council_id', cdcToolbarData.id);
            data.append('council_name', name?name.value:'');
            var overlay=aiOverlay('Preparing prompt…');
            var res=await fetch(ajaxurl,{method:'POST',credentials:'same-origin',body:data}).then(r=>r.json());
            removeOverlay(overlay);
            if(!res.success||!res.data||!res.data.prompt){
                alert(res.data&&res.data.message?res.data.message:cdcAiMessages.error);
                return;
            }
            var promptInfo=await showPromptModal(res.data.prompt,res.data.type||'',null);
            if(promptInfo===null) return;
            var userPrompt=promptInfo.prompt;
            var ansType=promptInfo.type;

            while(true){
                var overlay2=aiOverlay('Asking AI…');
                var data2=new FormData();
                data2.append('action','cdc_ai_field');
                data2.append('field',field);
                data2.append('council_id', cdcToolbarData.id);
                data2.append('council_name', name?name.value:'');
                data2.append('prompt', userPrompt);
                data2.append('format', ansType);
                var res2=await fetch(ajaxurl,{method:'POST',credentials:'same-origin',body:data2}).then(r=>r.json());
                removeOverlay(overlay2);
                if(res2.success && res2.data){
                    var input=document.querySelector('[data-cdc-field="'+field+'"]');
                    if(input){
                        input.value=res2.data.value || '';
                        input.dispatchEvent(new Event('input'));
                        var info=input.parentElement.querySelector('.cdc-ai-source');
                        if(!info){ info=document.createElement('div'); info.className='cdc-ai-source mt-1'; input.parentElement.appendChild(info); }
                        info.innerHTML=res2.data.source ? 'Source: <a href="'+res2.data.source+'" target="_blank" rel="noopener">'+res2.data.source+'</a>' : '';
                    }
                    break;
                }else if(res2.data && res2.data.response){
                    promptInfo=await showPromptModal(userPrompt,ansType,res2.data.response);
                    if(promptInfo===null) break;
                    if(promptInfo.insert!==undefined){
                        var input2=document.querySelector('[data-cdc-field="'+field+'"]');
                        if(input2){
                            input2.value=promptInfo.insert || '';
                            input2.dispatchEvent(new Event('input'));
                            var info2=input2.parentElement.querySelector('.cdc-ai-source');
                            if(!info2){ info2=document.createElement('div'); info2.className='cdc-ai-source mt-1'; input2.parentElement.appendChild(info2); }
                            info2.textContent='';
                        }
                        break;
                    }
                    userPrompt=promptInfo.prompt;
                    ansType=promptInfo.type;
                }else if(res2.data && res2.data.message){
                    alert(res2.data.message);
                    break;
                }else{
                    alert(cdcAiMessages.error);
                    break;
                }
            }
        }

        document.addEventListener('click',function(ev){
            var b=ev.target.closest('.cdc-ask-ai');
            if(b){
                ev.preventDefault();
                askField(b.dataset.field);
            }
        });

        var askAll=document.getElementById('cdc-ask-ai-all');
        if(askAll){
            askAll.addEventListener('click',async function(ev){
                ev.preventDefault();
                var buttons=document.querySelectorAll('.cdc-ask-ai');
                for(var i=0;i<buttons.length;i++){
                    await askField(buttons[i].dataset.field);
                }
            });
        }
    });
})();
