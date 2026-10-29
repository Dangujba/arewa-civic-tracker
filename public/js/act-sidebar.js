(()=>{
'use strict';
const shell=document.getElementById('portal-shell');
const toggle=document.getElementById('sidebar-toggle');
const sidebar=document.getElementById('portal-sidebar');
if(!shell||!toggle||!sidebar)return;
const key='act-sidebar-collapsed';
const mobile=()=>matchMedia('(max-width:900px)').matches;
const read=()=>{try{return localStorage.getItem(key)==='1'}catch{return false}};
const save=v=>{try{localStorage.setItem(key,v?'1':'0')}catch{}};
const backdrop=document.getElementById('sidebar-backdrop');
if(read())shell.classList.add('sidebar-collapsed');
const update=()=>{
 const small=mobile(),expanded=small?shell.classList.contains('sidebar-mobile-open'):
    !shell.classList.contains('sidebar-collapsed');
 toggle.setAttribute('aria-expanded',String(expanded));
 toggle.setAttribute('aria-label',small?(expanded?'Close navigation':'Open navigation'):
     (expanded?'Collapse navigation':'Expand navigation'));
 sidebar.inert=small&&!expanded;
 document.body.style.overflow=small&&expanded?'hidden':'';
 if(backdrop)backdrop.hidden=!(small&&expanded);
};
const close=(restoreFocus=true)=>{shell.classList.remove('sidebar-mobile-open');update();if(restoreFocus)toggle.focus()};
update();
toggle.addEventListener('click',()=>{
 if(mobile()){
  const opening=!shell.classList.contains('sidebar-mobile-open');
  shell.classList.toggle('sidebar-mobile-open');update();
  if(opening)sidebar.querySelector('.portal-nav a')?.focus();
  else toggle.focus();
 }else{
  shell.classList.toggle('sidebar-collapsed');
  save(shell.classList.contains('sidebar-collapsed'));update();
 }
});
backdrop?.addEventListener('click',()=>close());
document.addEventListener('keydown',event=>{
 if(event.key==='Escape'&&mobile()&&shell.classList.contains('sidebar-mobile-open')){
  event.preventDefault();close();
 }
 if(event.key==='Tab'&&mobile()&&shell.classList.contains('sidebar-mobile-open')){
  const nodes=[...sidebar.querySelectorAll('a,button,[tabindex]')]
    .filter(el=>!el.hasAttribute('disabled')&&el.tabIndex>=0);
  if(!nodes.length)return;
  const first=nodes[0],last=nodes[nodes.length-1];
  if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}
  else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}
 }
});
window.addEventListener('resize',()=>{if(!mobile())shell.classList.remove('sidebar-mobile-open');update()});
sidebar.querySelectorAll('.portal-nav-link').forEach(link=>link.addEventListener('click',()=>{if(mobile())close(false)}));
})();
