(function(){
    document.addEventListener('DOMContentLoaded',function(){
        function setup(id){
            var input=document.getElementById(id);
            if(!input) return;
            var button=document.getElementById(id+'-button');
            var remove=document.getElementById(id+'-remove');
            var preview=document.getElementById(id+'-preview');
            var frame;
            function update(url){
                if(preview){
                    preview.innerHTML=url?'<img src="'+url+'" style="max-width:150px;height:auto"/>':'';
                }
                if(remove){ remove.style.display=url?'':'none'; }
            }
            if(button){
                button.addEventListener('click',function(e){
                    e.preventDefault();
                    if(frame){ frame.open(); return; }
                    frame=wp.media({title:CDC_MEDIA_SELECT.title,button:{text:CDC_MEDIA_SELECT.button},multiple:false});
                    frame.on('select',function(){
                        var attachment=frame.state().get('selection').first().toJSON();
                        input.value=attachment.id;
                        update(attachment.url);
                    });
                    frame.open();
                });
            }
            if(remove){
                remove.addEventListener('click',function(e){
                    e.preventDefault();
                    input.value='';
                    update('');
                });
            }
            if(input.value){
                var url=input.dataset.url;
                update(url);
            }
        }
        setup('cdc-default-thumbnail');
        setup('cdc-sharing-image');
    });
})();
