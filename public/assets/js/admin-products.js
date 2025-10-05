// admin-products.js — full migration of script that used to live inline in admin/products/form.php
(function(){
  'use strict';

  // Utils
  function formatMoney(v){ const n=isNaN(v)?0:Number(v); return n.toLocaleString('pt-BR',{style:'currency',currency:'BRL'}); }
  function brToFloat(v){ if(v==null)return 0; const raw=String(v).trim(); return raw.includes(',')?parseFloat(raw.replace(/\./g,'').replace(',','.'))||0:parseFloat(raw)||0; }
  function toggleBlock(el,on){ if(!el) return; el.classList.toggle('hidden',!on); el.setAttribute('aria-hidden',String(!on)); }
  function ensureMinMax(scope){ if(!scope) return; scope.querySelectorAll('input[name$="[min]"]').forEach(minEl=>{ const wrap=minEl.closest('.cust-group')||minEl.closest('.group-card')||scope; const maxEl=wrap.querySelector('input[name$="[max]"]'); if(!maxEl) return; const min=Number(minEl.value||0), max=Number(maxEl.value||0); if(max && max<min) maxEl.value=min; }); }

  // ===== contador descrição & preview imagem (já na Parte 1) =====
  const descField=document.getElementById('description');
  const descCounter=document.getElementById('description-counter');
  function syncDescCounter(){ if(!descField || !descCounter) return; const size=descField.value.trim().length; descCounter.textContent=`${size} caractere${size===1?'':'s'}`; }
  descField?.addEventListener('input', syncDescCounter);
  syncDescCounter();

  // ===== Visibilidade de Combo =====
  const groupsToggle=document.getElementById('groups-toggle');
  const hiddenUse=document.getElementById('use_groups_hidden');
  const groupsWrap=document.getElementById('groups-wrap');
  function syncGroupsVisibility(){ if(groupsToggle){ toggleBlock(groupsWrap,!!groupsToggle.checked); groupsToggle.setAttribute('aria-expanded', groupsToggle.checked?'true':'false'); } }
  groupsToggle?.addEventListener('change', e=>{ if(hiddenUse) hiddenUse.value=e.target.checked?'1':'0'; syncGroupsVisibility(); });
  syncGroupsVisibility();

  // ===== COMBO wiring =====
  const gContainer=document.getElementById('groups-container'),
        addGroupBtn=document.getElementById('add-group'),
        tplGroup=document.getElementById('tpl-group'),
        tplItem=document.getElementById('tpl-item');
  const typeSelect=document.getElementById('type');
  const customizationCard=document.getElementById('customization-card');

  function updateItemPrice(row){
    const sel=row.querySelector('.product-select');
    const box=row.querySelector('.sp-price');
    const price=sel?.selectedOptions?.[0]?.dataset?.price ?? '0';
    const num=Number(String(price).replace(/\./g,'').replace(',','.'))||0;
    if(box) box.textContent=formatMoney(num);
    return num;
  }
  function setDefaultFlag(row,on){
    const flag=row.querySelector('.combo-default-flag');
    const btn=row.querySelector('.combo-default-toggle');
    if(flag) flag.value=on?'1':'0';
    if(btn) btn.classList.toggle('is-active',!!on);
  }
  function setCustomFlag(row,on){
    const flag=row.querySelector('.combo-custom-flag');
    const btn=row.querySelector('.combo-custom-toggle');
    if(flag) flag.value=on?'1':'0';
    if(btn) btn.classList.toggle('is-active',!!on);
  }
  function syncCustomizationControls(row){
    const sel=row.querySelector('.product-select');
    const opt=sel?.selectedOptions?.[0];
    const allow=opt?.dataset.allowCustomize==='1';
    const count=Number(opt?.dataset.ingredients||'0');
    const can=allow && count>2;
    const btn=row.querySelector('.combo-custom-toggle');
    const wrapper=row.querySelector('.combo-custom-wrapper');
    const group=row.closest('.group-card');
    const groupEnabled=group?.dataset.customGroup==='1';
    const typeIsCombo=typeSelect?.value==='combo';
    if(btn){ btn.classList.toggle('hidden',!can); }
    if(!can){ setCustomFlag(row,false); }
    if(wrapper){
      const shouldShow=can && groupEnabled && typeIsCombo;
      wrapper.classList.toggle('hidden', !shouldShow);
    }
  }
  function updateGroupFooter(groupEl){
    let sum=0;
    groupEl?.querySelectorAll('.item-row').forEach(r=>{
      const flag=r.querySelector('.combo-default-flag');
      if(flag?.value==='1') sum+=updateItemPrice(r);
    });
    const footer=groupEl?.querySelector('.group-base-price');
    if(footer) footer.textContent=`Preço base: ${formatMoney(sum)}`;
  }
  function wireItemRow(row){
    const sel=row.querySelector('.product-select');
    const defaultBtn=row.querySelector('.combo-default-toggle');
    const customBtn=row.querySelector('.combo-custom-toggle');
    if(sel){
      sel.addEventListener('change',()=>{
        updateItemPrice(row);
        syncCustomizationControls(row);
        updateGroupFooter(row.closest('.group-card'));
      });
      updateItemPrice(row);
      syncCustomizationControls(row);
    }
    if(defaultBtn){
      defaultBtn.addEventListener('click',()=>{
        const group=row.closest('.group-card');
        const wasActive=defaultBtn.classList.contains('is-active');
        group?.querySelectorAll('.item-row').forEach(r=>setDefaultFlag(r,false));
        if(!wasActive){ setDefaultFlag(row,true); }
        else{ setDefaultFlag(row,false); }
        updateGroupFooter(group);
      });
    }
    if(customBtn){
      customBtn.addEventListener('click',()=>{
        const active=customBtn.classList.contains('is-active');
        setCustomFlag(row,!active);
      });
    }
    const initDefault=row.querySelector('.combo-default-flag');
    if(initDefault){ setDefaultFlag(row, initDefault.value==='1'); }
    const initCustom=row.querySelector('.combo-custom-flag');
    if(initCustom){ setCustomFlag(row, initCustom.value==='1'); }
  }
  function refreshGroupCustomBox(groupEl){
    if(!groupEl) return;
    const info=groupEl.querySelector('.combo-group-customizable');
    const isComboType=typeSelect?.value==='combo';
    if(info){
      info.classList.toggle('hidden', !isComboType);
      const switchEl=info.querySelector('.combo-group-custom-switch');
      if(switchEl){
        switchEl.disabled=!isComboType;
        switchEl.checked=isComboType && groupEl.dataset.customGroup==='1';
      }
    }
    groupEl.querySelectorAll('.item-row').forEach(r=>syncCustomizationControls(r));
  }
  function refreshGroupCustomBoxes(){ document.querySelectorAll('.group-card').forEach(refreshGroupCustomBox); }
  function setGroupCustomState(groupEl, enabled){
    if(!groupEl) return;
    groupEl.dataset.customGroup = enabled ? '1' : '0';
    const switchEl=groupEl.querySelector('.combo-group-custom-switch');
    if(switchEl){ switchEl.checked = !!enabled; }
    groupEl.querySelectorAll('.item-row').forEach(row=>{
      const sel=row.querySelector('.product-select');
      const opt=sel?.selectedOptions?.[0];
      const allow=opt?.dataset.allowCustomize==='1';
      const count=Number(opt?.dataset.ingredients||'0');
      if(enabled && allow && count>2){ setCustomFlag(row,true); }
      if(!enabled){ setCustomFlag(row,false); }
      syncCustomizationControls(row);
    });
  }
  function wireGroupCard(groupEl){
    if(!groupEl) return;
    groupEl.querySelectorAll('.item-row').forEach(wireItemRow);
    updateGroupFooter(groupEl);
    if(!groupEl.dataset.comboGroupWired){
      const switchEl=groupEl.querySelector('.combo-group-custom-switch');
      if(switchEl){
        switchEl.addEventListener('change', ()=>{
          setGroupCustomState(groupEl, !!switchEl.checked);
        });
      }
      groupEl.dataset.comboGroupWired='1';
    }
    refreshGroupCustomBox(groupEl);
  }
  document.querySelectorAll('.group-card').forEach(wireGroupCard);

  let gIndex=gContainer?Array.from(gContainer.children).length:0;
  function addGroup(){
    const gi=gIndex++;
    const html=tplGroup.innerHTML.replaceAll('__GI__',gi);
    const wrap=document.createElement('div'); wrap.innerHTML=html.trim();
    const el=wrap.firstElementChild;
    gContainer.appendChild(el);
    wireGroupCard(el);
    refreshComboGroupOrder();
    return el;
  }
  function nextItemIndex(groupEl){ const idxs=Array.from(groupEl.querySelectorAll('.item-row')).map(r=>Number(r.dataset.itemIndex||0)); return idxs.length?Math.max(...idxs)+1:0; }
  function addItem(groupEl){
    const gi=Number(groupEl.dataset.index);
    const ii=nextItemIndex(groupEl);
    const html=tplItem.innerHTML.replaceAll('__GI__',gi).replaceAll('__II__',ii);
    const wrap=document.createElement('div'); wrap.innerHTML=html.trim();
    const row=wrap.firstElementChild;
    const footer=groupEl.querySelector('.group-base-price')?.parentElement;
    (footer?groupEl.insertBefore(row,footer):groupEl.appendChild(row));
    row.dataset.itemIndex=ii;
    wireItemRow(row);
    updateGroupFooter(groupEl);
    if(groupEl.dataset.customGroup==='1'){ setGroupCustomState(groupEl,true); }
    return row;
  }
  addGroupBtn?.addEventListener('click', ()=>{ const group=addGroup(); refreshGroupCustomBox(group); });
  gContainer?.addEventListener('click', ev=>{ const t=ev.target; if(t.classList.contains('add-item')){ const group=t.closest('.group-card'); const row=addItem(group); if(group?.dataset.customGroup==='1'){ setGroupCustomState(group,true); } else if(row){ syncCustomizationControls(row); } } if(t.classList.contains('remove-group')){ t.closest('.group-card')?.remove(); refreshComboGroupOrder(); } if(t.classList.contains('remove-item')){ const g=t.closest('.group-card'); t.closest('.item-row')?.remove(); if(g) updateGroupFooter(g); } });

  // ===== DRAG & DROP — COMBO =====
  let comboDragging=null, comboGhost=null;
  function getDragAfterElement(container,y,selector){
    const siblings=Array.from(container.querySelectorAll(selector)).filter(el=>el!==comboDragging);
    let closest={offset:Number.NEGATIVE_INFINITY,element:null};
    for(const child of siblings){
      const box=child.getBoundingClientRect(); const offset=y-(box.top+box.height/2);
      if(offset<0 && offset>closest.offset){ closest={offset,element:child}; }
    }
    return closest.element;
  }
  function refreshComboGroupOrder(){
    gContainer?.querySelectorAll('.group-card').forEach((g,idx)=>{
      g.dataset.index=idx;
      const inp=g.querySelector('.combo-order-input'); if(inp) inp.value=String(idx);
      // renumera nomes para manter índices coerentes (opcional: se não quiser, remova)
    });
  }
  gContainer?.addEventListener('dragstart', e=>{
    const handle=e.target.closest('.combo-drag-handle'); if(!handle){ e.preventDefault(); return; }
    const card=handle.closest('.group-card'); if(!card){ e.preventDefault(); return; }
    comboDragging=card; card.classList.add('dragging');
    if(e.dataTransfer){
      e.dataTransfer.effectAllowed='move'; e.dataTransfer.setData('text/plain','');
      const rect=card.getBoundingClientRect();
      const ghost=card.cloneNode(true);
      ghost.classList.add('combo-drag-ghost'); ghost.style.width=`${rect.width}px`; ghost.style.height=`${rect.height}px`;
      ghost.style.position='fixed'; ghost.style.top='-9999px'; ghost.style.left='-9999px'; ghost.style.opacity='0.85'; ghost.style.pointerEvents='none';
      document.body.appendChild(ghost); comboGhost=ghost;
      const offsetX=(e.clientX-rect.left)||rect.width/2, offsetY=(e.clientY-rect.top)||rect.height/2;
      e.dataTransfer.setDragImage(ghost, offsetX, offsetY);
    }
  });
  gContainer?.addEventListener('dragend', ()=>{
    if(comboDragging){ comboDragging.classList.remove('dragging'); comboDragging=null; refreshComboGroupOrder(); }
    if(comboGhost){ comboGhost.remove(); comboGhost=null; }
  });
  gContainer?.addEventListener('dragover', e=>{
    if(!comboDragging) return; e.preventDefault();
    const after=getDragAfterElement(gContainer, e.clientY, '.group-card');
    if(!after){ gContainer.appendChild(comboDragging); }
    else if(after!==comboDragging){ gContainer.insertBefore(comboDragging, after); }
  });
  gContainer?.addEventListener('drop', e=>{ if(!comboDragging) return; e.preventDefault(); refreshComboGroupOrder(); });

  // ===== PERSONALIZAÇÃO =====
  const custToggle=document.getElementById('customization-enabled');
  const custHidden=document.getElementById('customization-enabled-hidden');
  const custWrap=document.getElementById('customization-wrap');
  const custCont=document.getElementById('cust-groups-container');
  const custAddGrp=document.getElementById('cust-add-group');
  const tplCustGrp=document.getElementById('tpl-cust-group');
  const tplCustItm=document.getElementById('tpl-cust-item');

  function refreshCustGroupOrder(){
    custCont?.querySelectorAll('.cust-group').forEach((g,idx)=>{
      const order=g.querySelector('.cust-order-input'); if(order) order.value=String(idx);
    });
  }
  function updateCustItem(itemEl){
    if(!itemEl) return;
    const groupEl=itemEl.closest('.cust-group');
    const mode=groupEl?.dataset.mode==='choice'?'choice':'extra';
    const limits=itemEl.querySelector('.cust-limits');
    const minInput=itemEl.querySelector('.cust-min-input');
    const maxInput=itemEl.querySelector('.cust-max-input');
    const qtyWrap=itemEl.querySelector('.cust-default-qty-wrap');
    const qtyInput=itemEl.querySelector('.cust-default-qty');
    const checkbox=itemEl.querySelector('.cust-default-toggle');
    const flag=itemEl.querySelector('.cust-default-flag');

    let min=Number(minInput?.value ?? 0), max=Number(maxInput?.value ?? min);
    if(mode==='choice'){
      min=0; max=1;
      if(minInput){ minInput.value='0'; minInput.readOnly=true; }
      if(maxInput){ maxInput.value='1'; maxInput.readOnly=true; }
    }else{
      if(Number.isNaN(min)||min<0) min=0;
      if(Number.isNaN(max)||max<min) max=min;
      if(minInput){ minInput.value=String(min); minInput.readOnly=false; }
      if(maxInput){ maxInput.value=String(max); maxInput.readOnly=false; }
    }
    if(limits){ limits.dataset.min=String(min); limits.dataset.max=String(max); }
    if(qtyInput){ qtyInput.min=String(min); qtyInput.max=String(max); if(qtyInput.value===''||Number(qtyInput.value)<min) qtyInput.value=String(min); if(Number(qtyInput.value)>max) qtyInput.value=String(max); }

    const isActive=!!checkbox?.checked; if(flag) flag.value=isActive?'1':'0';
    if(!isActive && qtyInput){ qtyInput.value=String(min); }
    if(qtyWrap){ qtyWrap.classList.toggle('hidden', mode==='choice' || !isActive); }
  }
  function applyCustMode(groupEl){
    const select=groupEl.querySelector('.cust-mode-select');
    const choiceWrap=groupEl.querySelector('.cust-choice-settings');
    const addItemBtn=groupEl.querySelector('.cust-add-item');
    const addChoiceBtn=groupEl.querySelector('.cust-add-choice');
    const mode=select?.value==='choice'?'choice':'extra'; groupEl.dataset.mode=mode;
    toggleBlock(choiceWrap, mode==='choice');
    if(addItemBtn) addItemBtn.textContent = mode==='choice' ? '+ Opção' : '+ Ingrediente';
    if(addChoiceBtn) addChoiceBtn.classList.toggle('hidden', mode==='choice');
    groupEl.querySelectorAll('.cust-limits-wrap').forEach(w=>w.classList.toggle('hidden', mode==='choice'));
    groupEl.querySelectorAll('.cust-item').forEach(updateCustItem);
  }
  function wireCustItem(itemEl){
    if(!itemEl) return;
    const flag=itemEl.querySelector('.cust-default-flag');
    const checkbox=itemEl.querySelector('.cust-default-toggle');
    if(flag && checkbox){ checkbox.checked = flag.value==='1'; }
    if(checkbox && !checkbox.dataset.wired){
      checkbox.addEventListener('change', ()=>{
        if(flag){ flag.value = checkbox.checked ? '1' : '0'; }
        updateCustItem(itemEl);
      });
      checkbox.dataset.wired='1';
    }
    updateCustItem(itemEl);
  }
  function wireCustGroup(groupEl){
    if(!groupEl) return;
    const select=groupEl.querySelector('.cust-mode-select');
    if(select && !groupEl.dataset.mode){ groupEl.dataset.mode = select.value==='choice' ? 'choice' : 'extra'; }
    else if(select){ select.value = groupEl.dataset.mode==='choice' ? 'choice' : 'extra'; }
    if(select && !select.dataset.wired){
      select.addEventListener('change', ()=>{
        groupEl.dataset.mode = select.value==='choice' ? 'choice' : 'extra';
        applyCustMode(groupEl);
      });
      select.dataset.wired='1';
    }
    groupEl.querySelectorAll('.cust-item').forEach(wireCustItem);
    applyCustMode(groupEl);
  }
  function nextCustGroupIndex(){
    const idxs=Array.from(custCont.querySelectorAll('.cust-group')).map(g=>Number(g.dataset.index||0));
    return idxs.length?Math.max(...idxs)+1:0;
  }
  function nextCustItemIndex(groupEl){
    const idxs=Array.from(groupEl.querySelectorAll('.cust-item')).map(r=>Number(r.dataset.itemIndex||0));
    return idxs.length?Math.max(...idxs)+1:0;
  }
  function addCustGroup(){
    const gi=nextCustGroupIndex();
    const html=tplCustGrp.innerHTML.replaceAll('__CGI__',gi);
    const wrap=document.createElement('div'); wrap.innerHTML=html.trim();
    const node=wrap.firstElementChild;
    custCont.appendChild(node);
    wireCustGroup(node);
    refreshCustGroupOrder();
    return node;
  }
  function addCustItem(groupEl){
    const gi=Number(groupEl.dataset.index);
    const ii=nextCustItemIndex(groupEl);
    const html=tplCustItm.innerHTML.replaceAll('__CGI__',gi).replaceAll('__CII__',ii);
    const wrap=document.createElement('div'); wrap.innerHTML=html.trim();
    const row=wrap.firstElementChild;
    const footer=Array.from(groupEl.children).find(el=>el.matches('.flex.border-t, .border-t'));
    (footer?groupEl.insertBefore(row,footer):groupEl.appendChild(row));
    row.dataset.itemIndex=ii;
    wireCustItem(row); applyCustMode(groupEl);
    return row;
  }
  custAddGrp?.addEventListener('click', addCustGroup);
  custCont?.addEventListener('click', e=>{
    const t=e.target;
    if(t.classList.contains('cust-add-item')){ addCustItem(t.closest('.cust-group')); }
    else if(t.classList.contains('cust-add-choice')){ const g=t.closest('.cust-group'); const sel=g?.querySelector('.cust-mode-select'); if(sel){ sel.value='choice'; } applyCustMode(g); addCustItem(g); }
    else if(t.classList.contains('cust-remove-group')){ t.closest('.cust-group')?.remove(); refreshCustGroupOrder(); }
    else if(t.classList.contains('cust-remove-item')){ t.closest('.cust-item')?.remove(); }
  });

  // DRAG & DROP — PERSONALIZAÇÃO
  let custDragging=null, custGhost=null;
  function getCustAfterElement(container,y){
    const siblings=Array.from(container.querySelectorAll('.cust-group')).filter(el=>el!==custDragging);
    let closest={offset:Number.NEGATIVE_INFINITY,element:null};
    for(const child of siblings){
      const box=child.getBoundingClientRect(); const offset=y-(box.top+box.height/2);
      if(offset<0 && offset>closest.offset){ closest={offset,element:child}; }
    }
    return closest.element;
  }
  custCont?.addEventListener('dragstart', e=>{
    const handle=e.target.closest('.cust-drag-handle'); if(!handle){ e.preventDefault(); return; }
    const group=handle.closest('.cust-group'); if(!group){ e.preventDefault(); return; }
    custDragging=group; group.classList.add('dragging');
    if(e.dataTransfer){
      e.dataTransfer.effectAllowed='move'; e.dataTransfer.setData('text/plain','');
      const rect=group.getBoundingClientRect();
      const ghost=group.cloneNode(true);
      ghost.classList.add('cust-drag-ghost'); ghost.style.width=`${rect.width}px`; ghost.style.height=`${rect.height}px`;
      ghost.style.position='fixed'; ghost.style.top='-9999px'; ghost.style.left='-9999px'; ghost.style.opacity='0.85'; ghost.style.pointerEvents='none';
      document.body.appendChild(ghost); custGhost=ghost;
      const offsetX=(e.clientX-rect.left)||rect.width/2, offsetY=(e.clientY-rect.top)||rect.height/2;
      e.dataTransfer.setDragImage(ghost, offsetX, offsetY);
    }
  });
  custCont?.addEventListener('dragend', ()=>{
    if(custDragging){ custDragging.classList.remove('dragging'); custDragging=null; refreshCustGroupOrder(); }
    if(custGhost){ custGhost.remove(); custGhost=null; }
  });
  custCont?.addEventListener('dragover', e=>{
    if(!custDragging) return; e.preventDefault();
    const after=getCustAfterElement(custCont, e.clientY);
    if(!after){ custCont.appendChild(custDragging); }
    else if(after!==custDragging){ custCont.insertBefore(custDragging, after); }
  });
  custCont?.addEventListener('drop', e=>{ if(!custDragging) return; e.preventDefault(); refreshCustGroupOrder(); });

  // ===== toggle Personalização =====
  function syncCust(){ const on=!!custToggle?.checked; if(custHidden) custHidden.value=on?'1':'0'; toggleBlock(custWrap,on); }
  custToggle?.addEventListener('change', syncCust); syncCust();

  function syncProductTypeSections(){
    const isCombo=typeSelect?.value==='combo';
    if(customizationCard){ toggleBlock(customizationCard, !isCombo); }
    if(custToggle){
      custToggle.disabled=!!isCombo;
      if(isCombo){
        if(custToggle.checked){ custToggle.checked=false; }
        if(custHidden) custHidden.value='0';
        syncCust();
      }
    }
    refreshGroupCustomBoxes();
  }
  typeSelect?.addEventListener('change', syncProductTypeSections);
  syncProductTypeSections();

  // ===== validação & normalização no submit =====
  document.getElementById('product-form')?.addEventListener('submit', (e)=>{
    const name=document.getElementById('name');
    if(!name.value.trim()){ e.preventDefault(); alert('Informe o nome do produto.'); name.focus(); return; }

    const priceEl=document.getElementById('price'); if(priceEl){ priceEl.value=String(brToFloat(priceEl.value||'0')); }
    const promoEl=document.getElementById('promo_price');
    if(promoEl){ const raw=promoEl.value==null?'':String(promoEl.value).trim(); promoEl.value = raw==='' ? '' : String(brToFloat(raw)); }
    const price=parseFloat((priceEl?.value||'0')); const promoRaw=promoEl?.value ?? ''; const promo = promoRaw==='' ? null : parseFloat(promoRaw||'0');
    if(promoEl && promo!==null && !Number.isNaN(promo)){
      if(price<=0 || promo<=0){ promoEl.value=''; }
      else if(promo>=price){ e.preventDefault(); alert('O preço promocional deve ser menor que o preço base.'); promoEl.focus(); return; }
    }

    if(groupsToggle && groupsToggle.checked){
      const gs=gContainer.querySelectorAll('.group-card');
      if(!gs.length){ e.preventDefault(); alert('Adicione pelo menos um grupo de opções do combo.'); return; }
      for(const g of gs){
        const gname=g.querySelector('input[name^="groups"][name$="[name]"]'); const items=g.querySelectorAll('.item-row');
        ensureMinMax(g);
        const minEl=g.querySelector('input[name$="[min]"]'), maxEl=g.querySelector('input[name$="[max]"]');
        const min=Number(minEl?.value||0), max=Number(maxEl?.value||0);
        if(max && max<min){ e.preventDefault(); alert('No grupo "'+(gname.value||'')+'", o máximo não pode ser menor que o mínimo.'); maxEl.focus(); return; }
        if(!gname.value.trim() || !items.length){ e.preventDefault(); alert('Cada grupo do combo precisa de nome e ao menos um item.'); gname.focus(); return; }
        for(const it of items){ const sel=it.querySelector('select.product-select'); if(!sel.value){ e.preventDefault(); alert('Selecione um produto simples para cada item do combo.'); sel.focus(); return; } }
      }
    }

    if(custToggle && custToggle.checked){
      const cgs=custCont.querySelectorAll('.cust-group');
      if(!cgs.length){ e.preventDefault(); alert('Adicione pelo menos um grupo de personalização.'); return; }
      for(const cg of cgs){
        const nameEl=cg.querySelector('input[name^="customization"][name$="[name]"]'); const items=cg.querySelectorAll('.cust-item');
        if(!nameEl.value.trim()){ e.preventDefault(); alert('Cada grupo de personalização precisa de um nome.'); nameEl.focus(); return; }
        if(!items.length){ e.preventDefault(); alert('Adicione pelo menos um ingrediente no grupo "'+(nameEl.value||'')+'".'); return; }
        for(const it of items){
          const sel=it.querySelector('.cust-ingredient-select'); if(!sel || !sel.value){ e.preventDefault(); alert('Selecione um ingrediente em cada item do grupo "'+(nameEl.value||'')+'".'); sel?.focus(); return; }
          const limits=it.querySelector('.cust-limits'); const min=limits?Number(limits.dataset.min ?? 0):0; const max=limits?Number(limits.dataset.max ?? 1):1;
          const toggleCheckbox=it.querySelector('.cust-default-toggle'); const qty=it.querySelector('.cust-default-qty');
          if(toggleCheckbox?.checked){ const val=qty ? Number(qty.value||min) : min; if(val<min || val>max){ e.preventDefault(); alert('A quantidade padrão precisa estar entre o mínimo e máximo do ingrediente escolhido.'); qty?.focus(); return; } }
        }
      }
    }
  });

  // Inicializações
  document.querySelectorAll('.cust-group').forEach(wireCustGroup);
  refreshCustGroupOrder();
  refreshComboGroupOrder();

})();
