/* Translate add-on for Codychat 10.1 | Author: M Kashif | MIT License */
/* Translate (2026-11-14): tap-to-translate on every message, plus optional auto-translate into the reader's language. */
(function(){
	'use strict';
	if(window.CTR) return;
	var C = window.CTR_CFG || { me: 0, lang: 'en', auto: 0, set: 0, langs: {} };
	var URL = 'addons/cnr_translate/system/tr.php';
	var ICON = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h9M8.5 3v2M6 5c.6 3.2 2.6 5.8 5.5 7.3M11 5c-.8 3.8-3.3 6.8-6.5 8.3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M12.5 21l4-9.5 4 9.5M14 17.6h5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	var orig = new WeakMap(), queue = { 1: {}, 2: {} }, qTimer = null, io = null, busy = 0;

	if(!C.set && navigator.language){
		var nb = navigator.language.split('-')[0];
		if(nb !== C.lang && C.langs[nb]){ C.lang = nb; }
	}
	function esc(s){ var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
	function langName(code){ var n = C.langs[code] || code; var p = n.split(' '); return p.length > 1 ? p.slice(1).join(' ') : n; }

	/* find the parts of a message row */
	function info(li){
		if(!li || li.nodeType !== 1) return null;
		if(li.classList.contains('ch_logs') && !li.classList.contains('sys_log')){
			var lc = li.querySelector('.log_content'), b = li.querySelector('.chat_message .mbubble, .chat_message .chat_tmessage');
			if(!lc || !b) return null;
			var id = parseInt(li.getAttribute('data') || lc.getAttribute('data-id'), 10) || 0;
			return { kind: 1, id: id, bubble: b, mine: (parseInt(lc.getAttribute('data-user') || li.getAttribute('data-user'), 10) || 0) === C.me, spot: li.querySelector('.cdate') };
		}
		if(li.classList.contains('privlog')){
			var pb = li.querySelector('.target_private, .hunter_private');
			if(!pb) return null;
			return { kind: 2, id: parseInt(li.getAttribute('data-id'), 10) || 0, bubble: pb, mine: li.classList.contains('outpriv'), spot: li.querySelector('.prdate') };
		}
		return null;
	}
	function hasText(b){ var t = (b.textContent || '').replace(/https?:\/\/\S+/g, '').trim(); return /\p{L}.*\p{L}/u.test(t); }

	function tag(li){
		if(li._ctr) return;
		var m = info(li);
		if(!m || !m.id || m.mine || !m.spot || !hasText(m.bubble)) { li._ctr = 1; return; }
		li._ctr = 1;
		var btn = document.createElement('span');
		btn.className = 'ctr-btn'; btn.title = 'Translate'; btn.setAttribute('role', 'button'); btn.innerHTML = ICON;
		m.spot.insertBefore(btn, m.spot.firstChild);
		if(C.auto && io){ io.observe(li); }
	}
	function scan(root){
		var rows = (root || document).querySelectorAll('li.ch_logs, li.privlog');
		for(var i = 0; i < rows.length; i++){ tag(rows[i]); }
	}

	/* show / hide */
	function show(li, r){
		var m = info(li); if(!m) return;
		if(!orig.has(m.bubble)){ orig.set(m.bubble, m.bubble.innerHTML); }
		m.bubble.innerHTML = '<span class="ctr-txt" dir="auto">' + esc(r.t) + '</span><span class="ctr-meta">' + esc(r.from || '') + ' → ' + esc(langName(r.to || C.lang)) + ' · <a class="ctr-orig">Original</a></span>';
		li.classList.add('ctr-on'); li.classList.remove('ctr-wait');
	}
	function restore(li){
		var m = info(li); if(!m) return;
		if(orig.has(m.bubble)){ m.bubble.innerHTML = orig.get(m.bubble); orig.delete(m.bubble); }
		li.classList.remove('ctr-on');
	}
	function rowFor(kind, id){ return document.getElementById((kind === 1 ? 'log' : 'priv') + id); }

	/* ask the server, a few at a time */
	function want(li, manual){
		var m = info(li); if(!m || !m.id) return;
		if(li._ctrSame){ if(manual) flash(li, 'Already in ' + langName(C.lang)); return; }
		if(li._ctrDone && li._ctrDone.to === C.lang){ show(li, li._ctrDone); return; }
		li.classList.add('ctr-wait'); if(manual) li._ctrManual = 1;
		queue[m.kind][m.id] = 1;
		clearTimeout(qTimer); qTimer = setTimeout(flush, manual ? 0 : 350);
	}
	function flush(){
		[1, 2].forEach(function(kind){
			var ids = Object.keys(queue[kind]); if(!ids.length) return;
			queue[kind] = {};
			for(var i = 0; i < ids.length; i += 8){ send(kind, ids.slice(i, i + 8)); }
		});
	}
	function send(kind, ids){
		busy++;
		$.post(URL, { act: 'tr', kind: kind, ids: ids.join(','), to: C.lang }, function(r){
			busy--;
			if(!r || !r.ok){ ids.forEach(function(id){ var li = rowFor(kind, id); if(li){ li.classList.remove('ctr-wait'); } }); return; }
			ids.forEach(function(id){
				var li = rowFor(kind, id), x = r.r[id]; if(!li) return;
				li.classList.remove('ctr-wait');
				var manual = li._ctrManual; li._ctrManual = 0;
				if(!x){ return; }
				if(x.t){ x.to = r.to; li._ctrDone = x; if(r.to === C.lang){ show(li, x); } }
				else if(x.same){ li._ctrSame = 1; if(manual) flash(li, 'Already in ' + langName(C.lang)); }
				else if(x.none){ if(manual) flash(li, 'Nothing to translate'); }
				else if(x.wait){ if(manual) flash(li, 'Slow down a little'); }
				else if(x.fail){ if(manual) flash(li, 'Couldn\'t translate, try again'); }
			});
		}, 'json').fail(function(){ busy--; ids.forEach(function(id){ var li = rowFor(kind, id); if(li){ li.classList.remove('ctr-wait'); } }); });
	}
	function flash(li, text){
		var m = info(li); if(!m) return;
		var old = li.querySelector('.ctr-flash'); if(old) old.remove();
		var f = document.createElement('span'); f.className = 'ctr-flash'; f.textContent = text;
		m.bubble.parentNode.appendChild(f);
		setTimeout(function(){ f.remove(); }, 2200);
	}

	/* taps */
	$(document).on('click', '.ctr-btn', function(e){
		e.preventDefault(); e.stopPropagation();
		var li = $(this).closest('li')[0]; if(!li) return;
		if(li.classList.contains('ctr-on')){ restore(li); li._ctrKeep = 1; }
		else { li._ctrKeep = 0; want(li, true); }
	});
	$(document).on('click', '.ctr-orig', function(e){
		e.preventDefault(); e.stopPropagation();
		var li = $(this).closest('li')[0]; if(li){ restore(li); li._ctrKeep = 1; }
	});

	/* auto mode: translate messages as they scroll into view */
	function startAuto(){
		if(!window.IntersectionObserver) return;
		if(!io){
			io = new IntersectionObserver(function(list){
				list.forEach(function(en){
					if(!en.isIntersecting) return;
					io.unobserve(en.target);
					var li = en.target;
					if(C.auto && !li._ctrKeep && !li.classList.contains('ctr-on')){ want(li, false); }
				});
			}, { rootMargin: '120px 0px' });
		}
		var rows = document.querySelectorAll('li.ch_logs, li.privlog');
		for(var i = 0; i < rows.length; i++){ if(rows[i].querySelector('.ctr-btn')){ io.observe(rows[i]); } }
	}
	function resetAll(){
		var on = document.querySelectorAll('li.ctr-on');
		for(var i = 0; i < on.length; i++){ restore(on[i]); }
		var rows = document.querySelectorAll('li.ch_logs, li.privlog');
		for(var j = 0; j < rows.length; j++){ rows[j]._ctrSame = 0; rows[j]._ctrKeep = 0; }
	}

	/* settings sheet */
	function panel(){
		var opts = '';
		Object.keys(C.langs).forEach(function(k){ opts += '<option value="' + k + '"' + (k === C.lang ? ' selected' : '') + '>' + esc(C.langs[k]) + '</option>'; });
		var html = '<div class="ctr-panel"><div class="ctr-head"><span class="ctr-badge">' + ICON + '</span><div><b>Translate</b><p>Read every message in your own language.</p></div></div>' +
			'<p class="ctr-label">My language</p><select id="ctr-lang" class="full_input">' + opts + '</select>' +
			'<div class="ctr-row"><span>Auto-translate messages</span><label class="ctr-switch"><input type="checkbox" id="ctr-auto"' + (C.auto ? ' checked' : '') + '><span></span></label></div>' +
			'<p class="ctr-note">Tap the translate icon next to any message to see it in your language. With auto-translate on, messages in other languages are translated as they appear. Tap Original to see what was really written.</p></div>';
		if(typeof showModal === 'function'){ showModal(html, 400); } else if(typeof overModal === 'function'){ overModal(html); }
	}
	function save(){
		var l = $('#ctr-lang').val(), a = $('#ctr-auto').is(':checked') ? 1 : 0;
		$.post(URL, { act: 'set', lang: l, auto: a }, function(r){
			if(!r || !r.ok){ if(typeof callSaved === 'function'){ callSaved('Not saved', 3); } return; }
			var changed = (r.lang !== C.lang);
			C.lang = r.lang; C.auto = r.auto; C.set = 1;
			if(changed){ resetAll(); }
			if(C.auto){ startAuto(); }
			if(typeof callSaved === 'function'){ callSaved('Saved', 1); }
		}, 'json');
	}
	$(document).on('change', '#ctr-lang, #ctr-auto', save);

	function forget(li){ var m = info(li); if(m){ orig.delete(m.bubble); } li.classList.remove('ctr-on', 'ctr-wait'); li._ctrDone = 0; li._ctrSame = 0; li._ctrKeep = 0; if(C.auto && io && li.querySelector('.ctr-btn')){ io.observe(li); } }
	window.CTR = { panel: panel, scan: scan, forget: forget };
	$(function(){
		scan();
		if(window.MutationObserver){
			var mo = new MutationObserver(function(muts){
				for(var i = 0; i < muts.length; i++){
					var add = muts[i].addedNodes;
					for(var j = 0; j < add.length; j++){
						var n = add[j]; if(n.nodeType !== 1) continue;
						if(n.tagName === 'LI'){ tag(n); } else if(n.querySelectorAll){ scan(n); }
					}
				}
			});
			['show_chat', 'show_private', 'private_center', 'chat_logs_container'].forEach(function(id){ var el = document.getElementById(id); if(el){ mo.observe(el, { childList: true, subtree: true }); } });
		}
		if(C.auto){ startAuto(); }
		if(typeof appSettingMenu === 'function'){ appSettingMenu('language', 'Translate', 'CTR.panel()'); }
	});
})();
