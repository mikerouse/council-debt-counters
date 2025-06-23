(function(document){
  document.addEventListener('DOMContentLoaded',function(){
    const form=document.querySelector('.cdc-fig-form');
    if(!form) return;
    if(document.cookie.includes('cdcFigSubmitted')){
      form.style.display='none';
      const box=form.nextElementSibling;
      if(box){
        box.className='alert alert-success mt-3';
        box.textContent=cdcFig.success;
      }
      return;
    }
    const spinner=form.querySelector('.spinner-border');
    form.addEventListener('submit',function(e){
      e.preventDefault();
      const button=form.querySelector('button[type="submit"]');
      const original=button.innerHTML;
      button.disabled=true;
      spinner.classList.remove('d-none');
      button.innerHTML=cdcFig.submitting;
      const send=function(token){
        const data=new FormData(form);
        if(token){data.set('g-recaptcha-response',token);}
        data.append('action','cdc_submit_figure');
        fetch(cdcFig.ajaxUrl,{method:'POST',credentials:'same-origin',body:data})
        .then(r=>r.json())
        .then(res=>{
          const box=form.nextElementSibling;
          if(res.success){
            form.reset();
            box.className='alert alert-success mt-3';
            box.textContent=res.data||cdcFig.success;
            document.cookie='cdcFigSubmitted=1; path=/';
            form.style.display='none';
          }else{
            box.className='alert alert-danger mt-3';
            box.textContent=res.data||cdcFig.failure;
          }
        })
        .catch(()=>{
          const box=form.nextElementSibling;
          box.className='alert alert-danger mt-3';
          box.textContent=cdcFig.failure;
        })
        .finally(()=>{
          button.disabled=false;
          button.innerHTML=original;
          spinner.classList.add('d-none');
        });
      };
      if(cdcFig.siteKey){
        grecaptcha.enterprise.ready(function(){
          grecaptcha.enterprise.execute(cdcFig.siteKey,{action:'figure'}).then(send);
        });
      }else{
        send('');
      }
    });
  });
})(document);
