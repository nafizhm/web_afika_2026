document.addEventListener('DOMContentLoaded', () => {
 const editor = document.querySelector('.appearance-editor');
 if (!editor) return;
 const nav = editor.querySelector('.appearance-tabs');
 const tabs = [...nav.querySelectorAll('a')];
 const panels = tabs.map(tab => editor.querySelector(tab.hash));
 const form = editor.querySelector('form');
 const status = editor.querySelector('[data-save-status]');
 nav.setAttribute('role','tablist');
 const activate = (index, focus = false) => {
  tabs.forEach((tab,i) => {tab.setAttribute('role','tab');tab.setAttribute('aria-controls',panels[i].id);tab.setAttribute('aria-selected',String(i===index));tab.tabIndex=i===index?0:-1;panels[i].setAttribute('role','tabpanel');panels[i].setAttribute('aria-labelledby',tab.id);panels[i].hidden=i!==index;});
  if(focus)tabs[index].focus();
 };
 tabs.forEach((tab,index) => {
  tab.addEventListener('click', event => {event.preventDefault();activate(index);history.replaceState(null,'',tab.hash);});
  tab.addEventListener('keydown',event=>{let next;if(['ArrowRight','ArrowDown'].includes(event.key))next=(index+1)%tabs.length;if(['ArrowLeft','ArrowUp'].includes(event.key))next=(index+tabs.length-1)%tabs.length;if(event.key==='Home')next=0;if(event.key==='End')next=tabs.length-1;if(next!==undefined){event.preventDefault();activate(next,true);}});
 });
 const errorPanel=editor.querySelector('[data-field-error]')?.closest('[id^=appearance-]');
 const initial=panels.findIndex(panel=>panel===errorPanel || (!errorPanel && '#'+panel.id===location.hash));
 activate(initial>=0?initial:0);
 form.addEventListener('invalid',event=>{const index=panels.findIndex(panel=>panel.contains(event.target));if(index>=0)activate(index);},true);
 form.addEventListener('input',()=>{status.textContent='Ada perubahan yang belum disimpan.';});
 editor.querySelectorAll('input[type=color]').forEach(input=>input.addEventListener('input',()=>{editor.querySelector(`[data-color-label="${input.id}"]`).textContent=input.value.toUpperCase();}));
 editor.querySelectorAll('[data-image-upload]').forEach(input=>{let objectUrl;input.addEventListener('change',()=>{if(objectUrl)URL.revokeObjectURL(objectUrl);const file=input.files?.[0];if(!file || !file.type.startsWith('image/'))return;let preview=editor.querySelector(`[data-preview="${input.dataset.imageUpload}"]`);if(!preview){preview=document.createElement('img');preview.className='website-image-preview';preview.dataset.preview=input.dataset.imageUpload;preview.alt='Pratinjau foto baru';input.closest('label').append(preview);}objectUrl=URL.createObjectURL(file);preview.src=objectUrl;});});
});
