(function(){
    function init(el){
        if(!window.CountUp) return;
        var target=parseFloat(el.dataset.target)||0;
        var start=parseFloat(el.dataset.start)||0;
        var growth=parseFloat(el.dataset.growth)||0;
        var prefix=el.dataset.prefix||'';
        var decimals=2;
        var counter=new CountUp(el,start,target,{decimalPlaces:decimals,prefix:prefix});
        if(!counter.error){
            counter.start(function(){
                if(growth!==0){
                    setInterval(function(){
                        start+=growth;
                        el.textContent=prefix+start.toLocaleString('en-GB',{minimumFractionDigits:decimals,maximumFractionDigits:decimals});
                    },1000);
                }
            });
        }
    }
    document.addEventListener('DOMContentLoaded',function(){
        document.querySelectorAll('.cdc-counter').forEach(init);
    });
})();
