(()=>{
'use strict';
const reduce=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const toast=(message,error=false)=>{let root=document.getElementById('toast-stack');if(!root){root=document.createElement('div');root.className='toast-stack';root.id='toast-stack';root.setAttribute('aria-live','polite');document.body.appendChild(root)}const e=document.createElement('div');e.className='toast'+(error?' error':'');e.textContent=message;root.appendChild(e);setTimeout(()=>e.remove(),5500)};
const busy=(button,on)=>{if(!button)return; if(on){if(button.dataset.busy==='1')return;button.dataset.busy='1';button.dataset.label=button.innerHTML;button.disabled=true;button.setAttribute('aria-busy','true');button.innerHTML='<span class="spin" aria-hidden="true"></span><span>Processing...</span>'}else{button.innerHTML=button.dataset.label||button.innerHTML;button.disabled=false;button.removeAttribute('aria-busy');button.dataset.busy='0'}};
const nav=document.querySelector('[data-mobile-nav]');if(nav){nav.addEventListener('click',()=>{const el=document.getElementById('site-navigation');const open=el.classList.toggle('open');nav.setAttribute('aria-expanded',String(open));const icon=nav.querySelector('i');if(icon){icon.classList.toggle('fa-bars',!open);icon.classList.toggle('fa-xmark',open)}});document.querySelectorAll('#site-navigation a').forEach(a=>a.addEventListener('click',()=>{document.getElementById('site-navigation').classList.remove('open');nav.setAttribute('aria-expanded','false')}))}
const gallery=document.querySelector('[data-carousel]');
if(gallery){
  const slides=[...gallery.querySelectorAll('.carousel-slide')],dots=[...gallery.querySelectorAll('.carousel-dot')];let active=0,timer;
  const show=n=>{active=(n+slides.length)%slides.length;slides.forEach((slide,i)=>{slide.classList.toggle('active',i===active);slide.setAttribute('aria-hidden',i===active?'false':'true')});dots.forEach((dot,i)=>{dot.classList.toggle('active',i===active);dot.setAttribute('aria-selected',i===active?'true':'false')})};
  const restart=()=>{clearInterval(timer);if(!reduce)timer=setInterval(()=>show(active+1),6500)};
  dots.forEach(d=>d.addEventListener('click',()=>{show(Number(d.dataset.slide));restart()}));
  gallery.querySelector('[data-slide-prev]').addEventListener('click',()=>{show(active-1);restart()});
  gallery.querySelector('[data-slide-next]').addEventListener('click',()=>{show(active+1);restart()});
  gallery.addEventListener('mouseenter',()=>clearInterval(timer));gallery.addEventListener('mouseleave',restart);
  gallery.addEventListener('focusin',()=>clearInterval(timer));gallery.addEventListener('focusout',restart);
  gallery.addEventListener('keydown',e=>{if(e.key==='ArrowLeft'||e.key==='ArrowRight'){e.preventDefault();show(active+(e.key==='ArrowRight'?1:-1));restart()}});
  document.addEventListener('visibilitychange',()=>document.hidden?clearInterval(timer):restart());restart();
}
document.addEventListener('submit',async e=>{
  const form=e.target;if(!(form instanceof HTMLFormElement)||form.hasAttribute('data-no-ajax')||form.dataset.offlineManaged==='1')return;
  const method=(form.getAttribute('method')||'GET').toUpperCase();if(method==='GET')return;
  e.preventDefault();
  const button=e.submitter||form.querySelector('button[type=submit],input[type=submit]');if(button?.dataset.busy==='1')return;
  const notice=form.querySelector('[data-form-status]');if(notice){notice.hidden=false;notice.textContent='Submitting securely…'}
  form.querySelectorAll('[data-error-for]').forEach(node=>node.remove());
  busy(button,true);
  try{
    const res=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin',redirect:'follow'});
    if(res.status===422){const data=await res.json();for(const [field,messages] of Object.entries(data.errors||{})){const input=form.querySelector('[name="'+CSS.escape(field)+'"]');if(input){input.setAttribute('aria-invalid','true');const error=document.createElement('small');error.dataset.errorFor=field;error.style.color='#ac3430';error.textContent=messages.join(' ');input.insertAdjacentElement('afterend',error)}}toast(data.message||'Please check the highlighted fields.',true);if(notice)notice.textContent='Please correct the highlighted fields.';return}
    if(res.status===423){const data=await res.json().catch(()=>({}));toast(data.message||'Update your account security before proceeding.',true);if(data.redirect)window.location.assign(data.redirect);return}
    if(res.status===419){toast('Your session expired. Refresh and sign in again.',true);return}
    if(res.status===429){toast('Too many attempts. Please retry shortly.',true);return}
    if(!res.ok){toast('The request could not be completed ('+res.status+'). Please retry.',true);return}
    if(res.redirected){window.location.assign(res.url);return}
    const ct=res.headers.get('content-type')||'';
    if(ct.includes('application/json')){const data=await res.json();toast(data.message||'Saved successfully');if(data.redirect){window.location.assign(data.redirect)}else if(data.reload){window.location.reload()}}
    else{toast('Saved successfully.');window.location.reload()}
  }catch(err){toast('Network interrupted. Your request may not have completed. Please check before retrying.',true);if(notice)notice.textContent='Network interrupted.'}
  finally{busy(button,false)}
});
document.querySelectorAll('a.btn,a.btn-outline').forEach(el=>el.addEventListener('click',()=>{const href=el.getAttribute('href');if(!href||href.startsWith('#')||el.dataset.busy==='1')return;el.setAttribute('aria-busy','true');const mark=document.createElement('span');mark.className='spin';mark.setAttribute('aria-hidden','true');el.prepend(mark);setTimeout(()=>mark.remove(),2200)}));
})();
