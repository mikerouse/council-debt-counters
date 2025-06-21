(document=>{
  document.addEventListener('DOMContentLoaded',()=>{
    const form=document.querySelector('.cdc-waste-form');
    if(!form)return;
    form.addEventListener('submit',e=>{
      e.preventDefault();
      const button=form.querySelector('button[type="submit"]');
      const original=button.innerHTML;
      button.disabled=true;
      button.innerHTML='<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>'+cdcWhistle.submitting;
      const handle=token=>{
        const data=new FormData(form);
        if(token){data.set('g-recaptcha-response',token);}
        data.append('action','cdc_report_waste');
        fetch(cdcWhistle.ajaxUrl,{method:'POST',credentials:'same-origin',body:data})
          .then(r=>r.json())
          .then(res=>{
            const box=form.nextElementSibling;
            if(res.success){
              form.reset();
              box.className='alert alert-success cdc-response mt-3';
              box.textContent=cdcWhistle.success;
            }else{
              box.className='alert alert-danger cdc-response mt-3';
              box.textContent=res.data||cdcWhistle.failure;
            }
          })
          .catch(()=>{
            const box=form.nextElementSibling;
            box.className='alert alert-danger cdc-response mt-3';
            box.textContent=cdcWhistle.failure;
          })
          .finally(()=>{
            button.disabled=false;
            button.innerHTML=original;
          });
      };
      if(cdcWhistle.siteKey){
        grecaptcha.enterprise.ready(()=>{
          grecaptcha.enterprise.execute(cdcWhistle.siteKey,{action:'report'}).then(handle);
        });
      }else{
        handle('');
      }
    });
  });
})(document);
