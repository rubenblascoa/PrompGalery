// Variables globales
let activeModal = null;

// Funciones de Modal
function openModal(type, e) {
    if (e) e.stopPropagation();
    const overlay = document.getElementById('modal-overlay');
    document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
    const target = document.getElementById('modal-' + type);
    if (target) {
        target.style.display = 'block';
    }
    overlay.classList.add('open');
    activeModal = type;
}

function closeModal(e) {
    if (e && e.target !== document.getElementById('modal-overlay')) return;
    const overlay = document.getElementById('modal-overlay');
    if (overlay) {
        overlay.classList.remove('open');
    }
    activeModal = null;
}

// Escuchar tecla Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && activeModal) {
        closeModal({ target: document.getElementById('modal-overlay') });
    }
});

// Funciones de Voto
function vote(btn, e) {
    e.stopPropagation();
    const col = btn.closest('.vote-col');
    const count = col.querySelector('.vote-count');
    const cur = parseInt(count.textContent);
    
    if (btn.classList.contains('voted')) {
        btn.classList.remove('voted');
        count.textContent = cur - 1;
    } else {
        btn.classList.add('voted');
        count.textContent = cur + 1;
    }
}

// Función para actualizar contador de caracteres
function updateCount() {
    const ta = document.getElementById('prompt-text');
    const cc = document.getElementById('char-count');
    if (ta && cc) {
        cc.textContent = ta.value.length + ' caracteres';
    }
}

// Copiar prompt al portapapeles
function copyPrompt(btn) {
    if (!btn) {
        btn = event.target;
    }
    const promptContent = btn.closest('.card-body').querySelector('.card-preview p');
    if (promptContent) {
        const text = promptContent.textContent;
        navigator.clipboard.writeText(text).then(() => {
            const originalText = btn.textContent;
            btn.textContent = '✓ Copiado!';
            btn.style.color = 'var(--green)';
            setTimeout(() => {
                btn.textContent = originalText;
                btn.style.color = '';
            }, 2000);
        });
    }
}

// Cambiar vista
function setView(v) {
    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
    event.target.classList.add('active');
}

// Evento para botones de sort
document.addEventListener('DOMContentLoaded', function() {
    // Sort buttons
    document.querySelectorAll('.sort-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.sort-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Sidebar items
    document.querySelectorAll('.sidebar-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Modal overlay click
    const overlay = document.getElementById('modal-overlay');
    if (overlay) {
        overlay.addEventListener('click', closeModal);
    }
});

// AJAX para votar
function votarPrompt(promptId, tipo) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/promptvault_mvc/prompts/votar', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            // Actualizar contadores
            console.log('Voto registrado', response);
        }
    };
    
    xhr.send(JSON.stringify({
        prompt_id: promptId,
        tipo: tipo
    }));
}

// Buscar en tiempo real
function buscarPrompts(query) {
    if (query.length < 2) return;
    
    const xhr = new XMLHttpRequest();
    xhr.open('GET', '/promptvault_mvc/prompts/buscar?q=' + encodeURIComponent(query), true);
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            const resultados = JSON.parse(xhr.responseText);
            console.log('Resultados:', resultados);
        }
    };
    
    xhr.send();
}

// Validar formulario
function validarFormulario(form) {
    const titulo = form.querySelector('input[name="titulo"]');
    const contenido = form.querySelector('textarea[name="contenido"]');
    
    if (!titulo.value.trim()) {
        alert('El título es requerido');
        return false;
    }
    
    if (!contenido.value.trim()) {
        alert('El contenido es requerido');
        return false;
    }
    
    if (contenido.value.length < 20) {
        alert('El contenido debe tener al menos 20 caracteres');
        return false;
    }
    
    return true;
}

async function verDetalle(id) {
    const response = await fetch(`${RUTA_URL}/prompts/detalle/${id}`);
    const data = await response.json();
    
    const container = document.getElementById('detalle-contenido');
    container.innerHTML = `
        <div class="modal-title">${data.prompt.titulo}</div>
        <div class="card-meta" style="margin: 10px 0;">
            <span class="user-chip-name">@${data.prompt.usuario_nombre}</span>
            <span class="category-badge cat-code">${data.prompt.categoria}</span>
        </div>
        <div class="card-preview" style="background: var(--bg); padding: 15px; border-radius: 8px;">
            <p style="font-family: 'JetBrains Mono'; white-space: pre-wrap;">${data.prompt.contenido}</p>
        </div>
        <div style="margin-top: 20px;">
            <h4>Comentarios (${data.comentarios.length})</h4>
            ${data.comentarios.map(c => `
                <div class="mini-card" style="margin-top: 10px;">
                    <div class="mini-card-meta">@${c.usuario_nombre} - ${c.fecha_creacion}</div>
                    <div style="font-size: 13px;">${c.contenido}</div>
                </div>
            `).join('')}
        </div>
    `;
    openModal('view');
}

// Actualiza tu openModal para que no interfiera
function openModal(type) {
  const overlay = document.getElementById('modal-overlay');
  document.querySelectorAll('.modal').forEach(m => m.style.display='none');
  document.getElementById('modal-'+type).style.display='block';
  overlay.classList.add('open');
}


// 1. Modales
function openModal(type, e) {
  if(e) e.stopPropagation();
  const overlay = document.getElementById('modal-overlay');
  document.querySelectorAll('.modal').forEach(m => m.style.display='none');
  const target = document.getElementById('modal-'+type);
  if(target) target.style.display='block';
  overlay.classList.add('open');
  activeModal = type;
}

function closeModal(e) {
  if(e && e.target !== document.getElementById('modal-overlay')) return;
  document.getElementById('modal-overlay').classList.remove('open');
  activeModal = null;
}

document.addEventListener('keydown', e => { 
    if(e.key==='Escape') closeModal({target:document.getElementById('modal-overlay')}); 
});

// 2. Ver Detalles del Prompt (AJAX)
async function verDetalle(id) {
    const response = await fetch(`${RUTA_URL}/prompts/detalle/${id}`);
    const data = await response.json();
    
    const container = document.getElementById('detalle-contenido');
    container.innerHTML = `
        <div class="modal-title">${data.prompt.titulo}</div>
        <div class="card-meta" style="margin: 12px 0;">
            <span class="user-chip-name" style="color:var(--accent2)">@${data.prompt.usuario_nombre}</span>
            <span class="dot"></span>
            <span class="category-badge cat-code">${data.prompt.categoria}</span>
        </div>
        
        <div class="card-preview" style="background:var(--bg); border:1px solid var(--border); padding:18px; border-radius:10px;">
            <p style="font-size:13px; font-family:'JetBrains Mono',monospace; white-space:pre-wrap;">${data.prompt.contenido}</p>
        </div>

        <div style="font-size:13px; font-weight:600; margin-top:20px; margin-bottom:10px;">Comentarios (${data.comentarios.length})</div>
        <div id="lista-comentarios">
            ${data.comentarios.map(c => `
                <div style="background:var(--bg3); border-radius:8px; padding:12px; margin-bottom:8px; border:1px solid var(--border)">
                    <div style="font-size:12px; color:var(--accent2); margin-bottom:6px;">@${c.usuario_nombre}</div>
                    <div style="font-size:13px; color:var(--text2); line-height:1.6;">${c.contenido}</div>
                </div>
            `).join('')}
        </div>

        <div style="display:flex; gap:10px; margin-top:12px">
            <input type="hidden" id="prompt-id-comentario" value="${data.prompt.id}">
            <input class="form-input" type="text" id="texto-comentario" placeholder="Escribe un comentario..." style="flex:1">
            <button class="btn-primary" onclick="enviarComentario()" style="white-space:nowrap; padding:10px 16px">Enviar</button>
        </div>
    `;
    openModal('view');
}

// 3. Enviar Comentario (AJAX)
async function enviarComentario() {
    const promptId = document.getElementById('prompt-id-comentario').value;
    const contenido = document.getElementById('texto-comentario').value;
    
    if(!contenido.trim()) return;

    const formData = new FormData();
    formData.append('prompt_id', promptId);
    formData.append('contenido', contenido);

    await fetch(`${RUTA_URL}/prompts/comentar`, {
        method: 'POST',
        body: formData
    });

    // Recargar modal para ver el nuevo comentario
    verDetalle(promptId); 
}

// 4. Votar (AJAX) - Con actualización visual inmediata
async function vote(btn, e, promptId, tipo) {
    e.stopPropagation(); // Evita que se abra el modal al votar
    
    const countDiv = btn.parentElement.querySelector('.vote-count');
    let currentVotes = parseInt(countDiv.textContent);

    if(btn.classList.contains('voted')){
        btn.classList.remove('voted');
        countDiv.textContent = currentVotes - 1;
    } else {
        btn.classList.add('voted');
        countDiv.textContent = currentVotes + 1;
        // Petición al backend
        await fetch(`${RUTA_URL}/prompts/votar/${promptId}/${tipo}`, { method: 'POST' });
    }
}


// Función para el Login
async function loginReal() {
    const email = document.querySelector('input[type="email"]').value;
    const pass = document.querySelector('input[type="password"]').value;

    if(!email || !pass) return showToast('⚠️ Rellena todos los campos', 'error');

    const datos = new FormData();
    datos.append('email', email);
    datos.append('contraseña', pass);

    try {
        const url = '/login'; // Ruta definida en tu Router
        const respuesta = await fetch(url, { method: 'POST', body: datos });
        const resultado = await respuesta.json();

        if(resultado.resultado === 'ok') {
            showToast('✓ ' + resultado.mensaje, 'success');
            // Actualizar la interfaz (cambiar botón Entrar por Perfil)
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast('❌ ' + resultado.mensaje, 'error');
        }
    } catch (error) {
        showToast('Error de conexión', 'error');
    }
}

// Función para el Registro
async function registrarReal() {
    // Supongamos que tienes un formulario con id 'form-registro'
    const formData = new FormData(document.getElementById('form-registro'));
    
    try {
        const respuesta = await fetch('/registro', { method: 'POST', body: formData });
        const resultado = await respuesta.json();

        if(resultado.resultado === 'ok') {
            showToast('🚀 ¡Cuenta creada! Ahora puedes loguearte', 'success');
            closeModal({target: document.getElementById('modal-overlay')});
        } else {
            showToast(resultado.mensaje || 'Error en el registro', 'error');
        }
    } catch (error) {
        showToast('Error de conexión', 'error');
    }
}




// ═══════════════════════════════════════════════════════════
// DATA
// ═══════════════════════════════════════════════════════════
const EXPLORE_DATA = [
  {cat:'Código',catClass:'cat-code',emoji:'💻',title:'Senior Code Reviewer con SOLID',preview:'Eres un senior software engineer con 15+ años de experiencia. Al revisar código identifica bugs, vulnerabilidades de seguridad y aplica principios SOLID...',votes:847,copies:3200,comments:234,model:'Claude',author:'sara_ai',tags:['#code-review','#SOLID','#security']},
  {cat:'Escritura',catClass:'cat-writing',emoji:'✍️',title:'Ghostwriter viral para Twitter/X',preview:'Actúa como ghostwriter especialista en contenido técnico viral. Transforma conceptos complejos en hilos de Twitter que generen engagement masivo...',votes:312,copies:1100,comments:89,model:'Claude 3.5',author:'miguel_r',tags:['#twitter','#viral','#ghostwriting']},
  {cat:'Análisis',catClass:'cat-analysis',emoji:'📊',title:'Analista financiero DCF completo',preview:'Eres un analista cuantitativo de hedge fund. Genera resúmenes ejecutivos, análisis DCF con supuestos justificados y comparativa sectorial...',votes:198,copies:890,comments:45,model:'GPT-4o',author:'alex_lm',tags:['#finanzas','#DCF']},
  {cat:'Chatbot',catClass:'cat-chat',emoji:'💬',title:'Terapeuta cognitivo-conductual TCC',preview:'Eres un terapeuta virtual especializado en TCC. Mantén contexto, detecta patrones cognitivos distorsionados y guía con técnicas basadas en evidencia...',votes:156,copies:710,comments:112,model:'Claude Opus',author:'prompt_guru',tags:['#terapia','#bienestar']},
  {cat:'Código',catClass:'cat-code',emoji:'💻',title:'Arquitecto de sistemas distribuidos',preview:'Eres un arquitecto de software con experiencia en sistemas FAANG. Diseña microservicios escalables clarificando requisitos funcionales y no funcionales...',votes:134,copies:590,comments:67,model:'Claude Opus',author:'dev_sage',tags:['#microservicios','#scalability']},
  {cat:'Escritura',catClass:'cat-writing',emoji:'✍️',title:'Copywriter de email marketing',preview:'Eres el mejor copywriter de email marketing especialista en SaaS. Crea secuencias de bienvenida usando el framework PAS que convierten al 40%...',votes:98,copies:420,comments:31,model:'GPT-4',author:'lucia_brand',tags:['#email','#copywriting']},
  {cat:'Imagen',catClass:'cat-image',emoji:'🎨',title:'Prompt maestro para Midjourney v6',preview:'Genera prompts cinematográficos para Midjourney con estructura: sujeto principal + estilo fotográfico + iluminación + cámara + mood + postprocesado...',votes:267,copies:1800,comments:78,model:'Midjourney',author:'arte_ia',tags:['#midjourney','#imagen']},
  {cat:'Razonamiento',catClass:'cat-reason',emoji:'🧠',title:'Razonador step-by-step avanzado',preview:'Usa chain-of-thought estructurado. Descompón el problema en pasos, verifica cada conclusión antes de avanzar, identifica asunciones y evalúa alternativas...',votes:189,copies:650,comments:54,model:'o1-preview',author:'think_deep',tags:['#chain-of-thought','#reasoning']},
  {cat:'Análisis',catClass:'cat-analysis',emoji:'📊',title:'Auditor de producto SaaS',preview:'Eres un product manager senior. Analiza métricas de producto (churn, LTV, CAC, NPS) e identifica cuellos de botella en el funnel de conversión con recomendaciones accionables...',votes:145,copies:380,comments:39,model:'GPT-4',author:'pm_master',tags:['#SaaS','#product']},
];

const COLLECTIONS_DATA = [
  {emoji:'🚀',name:'Prompts para startups',desc:'Los mejores prompts para founders: pitch decks, análisis de competencia, estrategia de producto y más.',count:47,saves:2300,color:'linear-gradient(135deg,#7C6FFF22,#5CE1E622)',author:'prompt_guru'},
  {emoji:'🎓',name:'Aprendizaje acelerado',desc:'Prompts de estudio, resúmenes, Feynman technique y flashcards automáticas para aprender más rápido.',count:31,saves:1800,color:'linear-gradient(135deg,#3DDC8422,#5CE1E622)',author:'alex_lm'},
  {emoji:'💼',name:'Productivity suite',desc:'Organiza tu semana, gestiona proyectos, redacta emails y automatiza tareas repetitivas.',count:28,saves:1200,color:'linear-gradient(135deg,#FFB54722,#FF6B6B22)',author:'sara_ai'},
  {emoji:'🤖',name:'System prompts top',desc:'Los mejores system prompts para crear asistentes personalizados y chatbots especializados.',count:52,saves:3100,color:'linear-gradient(135deg,#7C6FFF22,#FF79C622)',author:'dev_sage'},
  {emoji:'💻',name:'Code & Dev 2025',desc:'Prompts actualizados para programadores: debugging, arquitectura, code review y documentación.',count:63,saves:4200,color:'linear-gradient(135deg,#5CE1E622,#7C6FFF22)',author:'dev_sage'},
  {emoji:'📈',name:'Marketing & Growth',desc:'SEO, content marketing, ads copy, CRO y growth hacking con los mejores prompts de marketing.',count:38,saves:1600,color:'linear-gradient(135deg,#FF6B6B22,#FFB54722)',author:'lucia_brand'},
  {emoji:'🎨',name:'Creatividad & Arte',desc:'Prompts para Midjourney, DALL-E, escritura creativa, guiones y producción visual.',count:44,saves:2800,color:'linear-gradient(135deg,#FF79C622,#5CE1E622)',author:'arte_ia'},
  {emoji:'🔬',name:'Investigación & Ciencia',desc:'Prompts para revisar papers, sintetizar investigaciones, diseñar experimentos y analizar datos.',count:22,saves:890,color:'linear-gradient(135deg,#3DDC8422,#FFB54722)',author:'think_deep'},
];

const LB_CREATORS = [
  {rank:1,init:'SA',grad:'#7C6FFF,#5CE1E6',name:'Sara Alvarez',handle:'@sara_ai',prompts:142,votes:38200,weekly:847,change:'up'},
  {rank:2,init:'PG',grad:'#7C6FFF,#FF6B6B',name:'Prompt Guru',handle:'@prompt_guru',prompts:203,votes:44100,weekly:756,change:'up'},
  {rank:3,init:'AL',grad:'#3DDC84,#5CE1E6',name:'Alex LM',handle:'@alex_lm',prompts:98,votes:21400,weekly:612,change:'same'},
  {rank:4,init:'AV',grad:'#7C6FFF,#5CE1E6',name:'Alex Vidal',handle:'@alex_vidal',prompts:67,votes:12800,weekly:441,change:'up'},
  {rank:5,init:'DS',grad:'#5CE1E6,#3DDC84',name:'Dev Sage',handle:'@dev_sage',prompts:89,votes:18600,weekly:388,change:'down'},
  {rank:6,init:'MR',grad:'#FF6B6B,#FFB547',name:'Miguel R',handle:'@miguel_r',prompts:67,votes:14800,weekly:312,change:'up'},
  {rank:7,init:'LB',grad:'#FF79C6,#FFB547',name:'Lucia Brand',handle:'@lucia_brand',prompts:54,votes:9200,weekly:267,change:'up'},
  {rank:8,init:'TD',grad:'#3DDC84,#FFB547',name:'Think Deep',handle:'@think_deep',prompts:41,votes:7800,weekly:189,change:'same'},
];

// ═══════════════════════════════════════════════════════════
// NAVIGATION
// ═══════════════════════════════════════════════════════════
function goPage(page) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.getElementById('page-'+page).classList.add('active');
  document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
  const nl = document.getElementById('nl-'+page);
  if(nl) nl.classList.add('active');
  closeNotifications();
  if(page==='explore') buildExplore();
  if(page==='collections') buildCollections();
  if(page==='leaderboard') buildLeaderboard();
  window.scrollTo(0,0);
}

function sidebarActive(el) {
  el.closest('.sidebar').querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('active'));
  el.classList.add('active');
}

// ═══════════════════════════════════════════════════════════
// MODALS
// ═══════════════════════════════════════════════════════════
function openModal(type) {
  const overlay = document.getElementById('modal-overlay');
  document.querySelectorAll('.modal').forEach(m => m.style.display='none');
  const target = document.getElementById('modal-'+type);
  if(target){ target.style.display='block'; }
  overlay.classList.add('open');
}
function closeModal(e) {
  if(e && e.target !== document.getElementById('modal-overlay')) return;
  document.getElementById('modal-overlay').classList.remove('open');
}
document.addEventListener('keydown', e => {
  if(e.key==='Escape') closeModal({target:document.getElementById('modal-overlay')});
  if((e.metaKey||e.ctrlKey) && e.key==='k') { e.preventDefault(); document.getElementById('nav-search-input').focus(); }
});

// ═══════════════════════════════════════════════════════════
// NOTIFICATIONS
// ═══════════════════════════════════════════════════════════
function toggleNotifications() {
  const p = document.getElementById('notif-panel');
  p.classList.toggle('open');
  if(p.classList.contains('open')) document.querySelector('.notif-dot').style.display='none';
}
function closeNotifications() { document.getElementById('notif-panel').classList.remove('open'); }
function markAllRead() { document.querySelectorAll('.notif-dot').forEach(d => d.style.display='none'); showToast('✓ Todas marcadas como leídas','success'); }
document.addEventListener('click', e => { if(!e.target.closest('#notif-panel') && !e.target.closest('#notif-btn')) closeNotifications(); });

// ═══════════════════════════════════════════════════════════
// VOTES
// ═══════════════════════════════════════════════════════════
function vote(btn, e) {
  if(e) e.stopPropagation();
  const col = btn.closest('.vote-col');
  const count = col.querySelector('.vote-count');
  const cur = parseInt(count.textContent.replace(',',''));
  const isUp = btn.textContent==='▲';
  const wasVoted = btn.classList.contains('voted');
  col.querySelectorAll('.vote-btn').forEach(b => b.classList.remove('voted'));
  if(!wasVoted) { btn.classList.add('voted'); count.textContent = isUp ? cur+1 : cur-1; }
  else { count.textContent = isUp ? cur-1 : cur+1; }
}

// ═══════════════════════════════════════════════════════════
// COPY / SAVE / ACTIONS
// ═══════════════════════════════════════════════════════════
function copyAction(e) {
  e.stopPropagation();
  navigator.clipboard.writeText('Prompt copiado de PromptVault 🚀').catch(()=>{});
  showToast('🔗 Prompt copiado al portapapeles','success');
}
function copyPromptFull(btn) {
  const text = document.getElementById('view-prompt-text').textContent;
  navigator.clipboard.writeText(text).catch(()=>{});
  const orig = btn.textContent; btn.textContent='✓ Copiado!'; btn.style.color='var(--green)';
  setTimeout(()=>{ btn.textContent=orig; btn.style.color=''; }, 2000);
  showToast('🔗 Prompt copiado al portapapeles','success');
}
function saveAction(el, e) {
  if(e) e.stopPropagation();
  const span = el.querySelector('span') || el;
  if(span.textContent.includes('Guardado')) { span.innerHTML='🔖 <span>Guardar</span>'; showToast('🔖 Eliminado de guardados','info'); }
  else { span.innerHTML='✅ <span>Guardado</span>'; el.classList.add('active'); showToast('✅ Guardado en tu colección','success'); }
}
function publishPrompt() {
  closeModal({target:document.getElementById('modal-overlay')});
  showToast('🚀 Prompt publicado correctamente','success');
}
function createCollection() {
  closeModal({target:document.getElementById('modal-overlay')});
  showToast('📁 Colección creada','success');
}
function saveProfile() {
  closeModal({target:document.getElementById('modal-overlay')});
  showToast('✓ Perfil actualizado','success');
}
function submitComment() {
  const inp = document.getElementById('new-comment');
  if(!inp.value.trim()) return;
  showToast('💬 Comentario publicado','success');
  inp.value='';
}
function toggleFollow(btn) {
  if(btn.classList.contains('following')) { btn.classList.remove('following'); btn.textContent='+ Seguir'; showToast('Usuario dejado de seguir','info'); }
  else { btn.classList.add('following'); btn.textContent='✓ Siguiendo'; showToast('✓ ¡Ahora sigues a @sara_ai!','success'); }
}

// ═══════════════════════════════════════════════════════════
// FEED INTERACTIONS
// ═══════════════════════════════════════════════════════════
function sortFeed(btn, mode) {
  document.querySelectorAll('.sort-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  showToast(`🔀 Ordenando por ${mode}...`,'info');
}
function filterCategory(cat) {
  document.getElementById('feed-label').textContent = '📂 ' + cat;
  showToast(`📂 Filtrando: ${cat}`,'info');
}
function filterFeed(val) {
  const cards = document.querySelectorAll('#feed-container .prompt-card, #feed-container .featured-card');
  cards.forEach(c => {
    const title = c.querySelector('.card-title')?.textContent.toLowerCase() || '';
    c.style.display = !val || title.includes(val.toLowerCase()) ? '' : 'none';
  });
}
function handleNavSearch() {
  const v = document.getElementById('nav-search-input').value;
  if(v) { goPage('explore'); document.getElementById('explore-search-input').value=v; filterExplore(v); }
}
function loadMore() { showToast('📄 Cargando más prompts...','info'); }

// ═══════════════════════════════════════════════════════════
// EXPLORE PAGE
// ═══════════════════════════════════════════════════════════
function buildExplore(data) {
  const d = data || EXPLORE_DATA;
  const grid = document.getElementById('explore-grid');
  grid.innerHTML = d.map(p => `
    <div class="explore-card" onclick="openModal('view')">
      <div class="explore-card-cat">
        <span class="category-badge ${p.catClass}">${p.emoji} ${p.cat}</span>
      </div>
      <div class="explore-card-title">${p.title}</div>
      <div class="explore-card-preview">${p.preview}</div>
      <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:12px">${p.tags.map(t=>`<span class="tag">${t}</span>`).join('')}</div>
      <div class="explore-card-footer">
        <div class="explore-card-stats">
          <span>▲ ${p.votes.toLocaleString()}</span>
          <span>💬 ${p.comments}</span>
          <span>🔗 ${p.copies >= 1000 ? (p.copies/1000).toFixed(1)+'k' : p.copies}</span>
        </div>
        <div style="display:flex;align-items:center;gap:6px">
          <div class="model-chip" style="margin:0"><div class="model-dot"></div>${p.model}</div>
        </div>
      </div>
    </div>
  `).join('');
}
function filterExplore(val) {
  const filtered = val ? EXPLORE_DATA.filter(p => p.title.toLowerCase().includes(val.toLowerCase()) || p.preview.toLowerCase().includes(val.toLowerCase()) || p.tags.some(t=>t.includes(val.toLowerCase()))) : EXPLORE_DATA;
  buildExplore(filtered);
}
function filterChipActive(el, key) {
  el.closest('.filter-row').querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
  el.classList.add('active');
}

// ═══════════════════════════════════════════════════════════
// COLLECTIONS PAGE
// ═══════════════════════════════════════════════════════════
function buildCollections() {
  const grid = document.getElementById('collections-grid');
  grid.innerHTML = COLLECTIONS_DATA.map(c => `
    <div class="collection-card">
      <div class="collection-banner" style="background:${c.color}">
        <span>${c.emoji}</span>
      </div>
      <div class="collection-body">
        <div class="collection-name">${c.name}</div>
        <div class="collection-desc">${c.desc}</div>
        <div class="collection-meta">
          <span>${c.count} prompts · ${(c.saves/1000).toFixed(1)}k guardados</span>
          <button class="collection-save-btn" onclick="event.stopPropagation();showToast('✅ Colección guardada','success')">+ Guardar</button>
        </div>
      </div>
    </div>
  `).join('');
}

// ═══════════════════════════════════════════════════════════
// LEADERBOARD PAGE
// ═══════════════════════════════════════════════════════════
function buildLeaderboard() {
  const content = document.getElementById('lb-content');
  const top3 = LB_CREATORS.slice(0,3);
  const rest = LB_CREATORS.slice(3);
  const medals = ['🥇','🥈','🥉'];
  const classes = ['first','second','third'];
  const ptColors = ['var(--amber)','var(--text2)','#cd7f32'];
  content.innerHTML = `
    <div class="lb-podium">
      ${[top3[1],top3[0],top3[2]].map((u,i) => {
        const realRank = i===0?1:i===1?0:2;
        return `
        <div class="lb-podium-card ${classes[i===0?1:i===1?0:2]}" style="padding-top:${i===1?'28px':'20px'}" onclick="openModal('profile')">
          <div class="lb-rank-num">#${realRank===0?2:realRank===1?1:3}</div>
          <div class="lb-rank">${medals[i===0?1:i===1?0:2]}</div>
          <div class="lb-avatar" style="background:linear-gradient(135deg,${u.grad})">${u.init}</div>
          <div class="lb-name">${u.name}</div>
          <div class="lb-handle">${u.handle}</div>
          <div class="lb-points" style="color:${ptColors[i===0?1:i===1?0:2]}">+${u.weekly.toLocaleString()}</div>
          <div style="font-size:11px;color:var(--text3);margin-top:2px">${u.prompts} prompts</div>
        </div>`;
      }).join('')}
    </div>
    <div class="lb-table">
      ${rest.map(u => `
        <div class="lb-row" onclick="openModal('profile')">
          <div class="lb-row-rank">#${u.rank}</div>
          <div class="avatar-sm" style="background:linear-gradient(135deg,${u.grad});width:36px;height:36px;font-size:13px;flex-shrink:0">${u.init}</div>
          <div class="lb-row-info">
            <div class="lb-row-name">${u.name}</div>
            <div class="lb-row-sub">${u.handle} · ${u.prompts} prompts · ${(u.votes/1000).toFixed(1)}k votos</div>
          </div>
          <div class="lb-row-stat">
            <div class="lb-row-num" style="color:var(--green)">+${u.weekly}</div>
            <div class="lb-change ${u.change}">↑ subiendo</div>
          </div>
        </div>
      `).join('')}
    </div>`;
}
function lbTabActive(el, mode) {
  document.querySelectorAll('.lb-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  showToast(`📊 Ranking de ${mode}...`,'info');
}

// ═══════════════════════════════════════════════════════════
// PROFILE TABS
// ═══════════════════════════════════════════════════════════
function profileTab(el, tab) {
  document.querySelectorAll('#profile-tabs .tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  const content = document.getElementById('profile-tab-content');
  if(tab==='saved') { content.innerHTML='<div class="empty-state"><div class="empty-icon">🔖</div><div class="empty-title">7 prompts guardados</div><div class="empty-sub">Los prompts que guardas aparecen aquí</div></div>'; }
  else if(tab==='collections') { content.innerHTML='<div class="empty-state"><div class="empty-icon">📁</div><div class="empty-title">8 colecciones creadas</div><div class="empty-sub">Tus colecciones de prompts</div></div>'; }
  else if(tab==='activity') { content.innerHTML='<div class="empty-state"><div class="empty-icon">📊</div><div class="empty-title">Analíticas detalladas</div><div class="empty-sub">Próximamente: gráficos de rendimiento de tus prompts</div></div>'; }
}

// ═══════════════════════════════════════════════════════════
// AUTH
// ═══════════════════════════════════════════════════════════
function socialLogin(provider) {
  closeModal({target:document.getElementById('modal-overlay')});
  showToast(`✓ Conectado con ${provider}`,'success');
}
function emailLogin() {
  closeModal({target:document.getElementById('modal-overlay')});
  showToast('✓ Sesión iniciada correctamente','success');
}

// ═══════════════════════════════════════════════════════════
// TESTER (PLAYGROUND)
// ═══════════════════════════════════════════════════════════
const SAMPLE_PROMPTS = {
  code: {system:'Eres un asistente de programación experto.',prompt:`Eres un senior software engineer con 15+ años de experiencia. Al revisar código:

1. Identifica bugs potenciales y vulnerabilidades de seguridad
2. Sugiere mejoras de rendimiento con complejidad O(n)
3. Aplica principios SOLID y patrones de diseño apropiados
4. Explica el "por qué" de cada sugerencia
5. Da ejemplos de código corregido

Formato de respuesta:
🐛 BUGS: [lista crítica]
⚡ PERFORMANCE: [optimizaciones]
🏗️ ARQUITECTURA: [mejoras estructurales]
✅ POSITIVO: [qué está bien hecho]`},
  writer: {system:'Eres un experto en contenido viral.',prompt:'Actúa como ghostwriter especialista en contenido técnico viral. Tu objetivo es transformar conceptos complejos en hilos de Twitter que generen engagement masivo. Formato: hook devastador en <280 chars → desarrollo en 8-10 tweets concisos → CTA final. Para cada tweet incluye emojis estratégicos y datos concretos.'},
  analyst: {system:'Eres un analista financiero de hedge fund.',prompt:'Eres un analista cuantitativo con experiencia en hedge funds. Dado cualquier empresa, genera: 1) Resumen ejecutivo (5 puntos) 2) Análisis DCF con supuestos 3) Comparativa sectorial 4) Riesgos principales 5) Recomendación de inversión con precio objetivo. Sé preciso y usa datos reales cuando estén disponibles.'},
};

let testerHistory = [];
let testerRunning = false;

function loadSamplePrompt(type) {
  const s = SAMPLE_PROMPTS[type];
  document.getElementById('tester-system').value = s.system;
  document.getElementById('tester-prompt').value = s.prompt;
  document.getElementById('tester-char').textContent = s.prompt.length + ' caracteres';
  showToast('📋 Prompt de muestra cargado','info');
}

document.getElementById('tester-prompt').addEventListener('input', function() {
  document.getElementById('tester-char').textContent = this.value.length + ' caracteres';
});

async function runTesterPrompt() {
  if(testerRunning) return;
  const sys = document.getElementById('tester-system').value.trim();
  const prompt = document.getElementById('tester-prompt').value.trim();
  if(!prompt) { showToast('⚠️ Escribe un prompt primero','error'); return; }
  
  testerRunning = true;
  document.getElementById('tester-status').textContent = '⚡ Ejecutando...';
  
  const chat = document.getElementById('tester-chat');
  chat.innerHTML = '';
  testerHistory = [];
  
  const userMsg = document.createElement('div');
  userMsg.className = 'tester-message user';
  userMsg.textContent = prompt;
  chat.appendChild(userMsg);
  
  const thinkMsg = document.createElement('div');
  thinkMsg.className = 'tester-message ai thinking';
  thinkMsg.innerHTML = '<div class="typing-indicator"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div>';
  chat.appendChild(thinkMsg);
  chat.scrollTop = chat.scrollHeight;
  
  try {
    const msgs = [{role:'user', content: prompt}];
    testerHistory = [...msgs];
    const body = {model:'claude-sonnet-4-20250514', max_tokens:1000, messages: msgs};
    if(sys) body.system = sys;
    
    const res = await fetch('https://api.anthropic.com/v1/messages', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(body)
    });
    const data = await res.json();
    const reply = data.content?.map(c => c.text||'').join('') || 'Sin respuesta.';
    
    thinkMsg.remove();
    const aiMsg = document.createElement('div');
    aiMsg.className = 'tester-message ai';
    aiMsg.textContent = reply;
    chat.appendChild(aiMsg);
    testerHistory.push({role:'assistant', content: reply});
    document.getElementById('tester-status').textContent = '✓ Completado · ' + reply.length + ' chars';
  } catch(err) {
    thinkMsg.remove();
    const errMsg = document.createElement('div');
    errMsg.className = 'tester-message ai';
    errMsg.style.color = 'var(--coral)';
    errMsg.textContent = '⚠️ Error: ' + (err.message || 'Inténtalo de nuevo');
    chat.appendChild(errMsg);
    document.getElementById('tester-status').textContent = '✗ Error al ejecutar';
  }
  testerRunning = false;
  chat.scrollTop = chat.scrollHeight;
}

async function sendFollowup() {
  const inp = document.getElementById('tester-followup');
  const text = inp.value.trim();
  if(!text || testerRunning) return;
  if(testerHistory.length === 0) { showToast('⚠️ Ejecuta el prompt primero','error'); return; }
  
  inp.value = '';
  inp.style.height = 'auto';
  testerRunning = true;
  
  const chat = document.getElementById('tester-chat');
  const userMsg = document.createElement('div');
  userMsg.className = 'tester-message user';
  userMsg.textContent = text;
  chat.appendChild(userMsg);
  
  const thinkMsg = document.createElement('div');
  thinkMsg.className = 'tester-message ai thinking';
  thinkMsg.innerHTML = '<div class="typing-indicator"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div>';
  chat.appendChild(thinkMsg);
  chat.scrollTop = chat.scrollHeight;
  
  testerHistory.push({role:'user', content: text});
  
  try {
    const sys = document.getElementById('tester-system').value.trim();
    const body = {model:'claude-sonnet-4-20250514', max_tokens:1000, messages: testerHistory};
    if(sys) body.system = sys;
    
    const res = await fetch('https://api.anthropic.com/v1/messages', {
      method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body)
    });
    const data = await res.json();
    const reply = data.content?.map(c=>c.text||'').join('') || 'Sin respuesta.';
    
    thinkMsg.remove();
    const aiMsg = document.createElement('div');
    aiMsg.className = 'tester-message ai';
    aiMsg.textContent = reply;
    chat.appendChild(aiMsg);
    testerHistory.push({role:'assistant', content: reply});
  } catch(err) {
    thinkMsg.remove();
    const errMsg = document.createElement('div');
    errMsg.className = 'tester-message ai';
    errMsg.style.color = 'var(--coral)';
    errMsg.textContent = '⚠️ Error: ' + err.message;
    chat.appendChild(errMsg);
  }
  testerRunning = false;
  chat.scrollTop = chat.scrollHeight;
}

function clearTester() {
  document.getElementById('tester-system').value = '';
  document.getElementById('tester-prompt').value = '';
  document.getElementById('tester-char').textContent = '0 caracteres';
  document.getElementById('tester-chat').innerHTML = '<div style="text-align:center;padding:40px 20px;color:var(--text3)"><div style="font-size:36px;margin-bottom:12px">⚡</div><div style="font-size:14px;font-weight:500;color:var(--text2)">Escribe un prompt y ejecuta</div><div style="font-size:12px;margin-top:6px">La respuesta de Claude aparecerá aquí</div></div>';
  document.getElementById('tester-status').textContent = 'Listo para ejecutar';
  testerHistory = [];
}

function autoResize(el) {
  el.style.height='auto';
  el.style.height=Math.min(el.scrollHeight,120)+'px';
}

// ═══════════════════════════════════════════════════════════
// TOAST
// ═══════════════════════════════════════════════════════════
function showToast(msg, type='info') {
  const container = document.getElementById('toast-container');
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.textContent = msg;
  container.appendChild(toast);
  setTimeout(()=>{
    toast.style.animation='slideOut 0.3s ease forwards';
    setTimeout(()=>toast.remove(), 300);
  }, 2800);
}

// ═══════════════════════════════════════════════════════════
// SORT BTNS (multiple groups)
// ═══════════════════════════════════════════════════════════
document.querySelectorAll('.feed-sort').forEach(group => {
  group.querySelectorAll('.sort-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      group.querySelectorAll('.sort-btn').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
    });
  });
});

// Init
buildExplore();
buildCollections();
buildLeaderboard();
