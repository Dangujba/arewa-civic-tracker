(() => {
  'use strict';
  const input=document.getElementById('act-profile-file');
  const label=document.getElementById('act-profile-file-name');
  const preview=document.getElementById('act-profile-preview');
  let previousUrl=null;
  if (!input||!preview||!label) return;
  input.addEventListener('change',() => {
    const file=input.files?.[0];
    if(previousUrl){URL.revokeObjectURL(previousUrl);previousUrl=null}
    if(!file){label.textContent='No photo selected';return}
    if(!['image/jpeg','image/png','image/webp'].includes(file.type) || file.size>2*1024*1024){
      label.textContent='Choose a JPEG, PNG or WebP image smaller than 2 MB.';
      input.value='';
      return;
    }
    label.textContent=file.name;
    previousUrl=URL.createObjectURL(file);
    const image=document.createElement('img');
    image.alt='Preview of your selected profile photo';
    image.width=100;image.height=100;image.src=previousUrl;
    preview.replaceChildren(image);
  });
  window.addEventListener('pagehide',() => {if(previousUrl)URL.revokeObjectURL(previousUrl)},{once:true});
})();
