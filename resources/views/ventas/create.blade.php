@extends('app')
@section('title','Nueva venta')

@section('content')
<div class="page venta-nueva">
  <div class="venta-wrap">
    <!-- IZQUIERDA -->
    <div class="venta-left card">

      <!-- Buscador con sugerencias de PRODUCTOS -->
      <div class="search-wrap xl">
        <i class="ri-search-line"></i>
        <input id="buscador" type="text" placeholder="Buscar producto por nombre…">
        <div id="sugg" class="sugg hidden"></div>
      </div>

      <!-- Controles superiores -->
      <div class="top-controls grid4">
        <div class="ctrl"><label>Cantidad</label><input id="ctrl_cantidad" type="number" min="1" step="1" value="1"></div>
        <div class="ctrl"><label>Stock</label><input id="ctrl_stock" type="number" value="0" readonly></div>
        <div class="ctrl"><label>P. venta</label><input id="ctrl_precio" type="number" step="0.01" value="0.00"></div>
        <div class="ctrl"><label>Descuento</label><input id="ctrl_desc" type="number" step="0.01" value="0.00"></div>
        <div class="ctrl full">
          <button id="btnAgregar" class="btn add"><i class="ri-add-circle-line"></i> Agregar</button>
        </div>
      </div>

      <!-- Tabla -->
      <div class="tabla-box soft">
        <table class="table compact" id="tbl">
          <thead>
            <tr>
              <th style="width:60px">#</th>
              <th>Artículo</th>
              <th style="width:130px">Cantidad</th>
              <th style="width:140px">P. venta</th>
              <th style="width:140px">Descuento</th>
              <th style="width:140px">Subtotal</th>
              <th style="width:60px"></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <!-- Pie -->
      <div class="footer-left">
        <button type="button" class="btn danger" onclick="history.back()">
          <i class="ri-close-line"></i> Cancelar venta
        </button>
        <div class="total-box">
          <span>Total Bs</span>
          <strong id="totalTxt">0.00</strong>
        </div>
      </div>
    </div>

    <!-- DERECHA -->
    <div class="venta-right">
      <form id="ventaForm" method="POST" action="{{ route('ventas.store') }}">
        @csrf

        <div class="card">
          <h3 class="card-title"><i class="ri-file-list-2-line"></i> Datos de la venta</h3>

          {{-- ===== Cliente con buscador ===== --}}
          <label>Cliente</label>
          <div class="search-wrap" id="clientePicker">
            <i class="ri-user-search-line"></i>
            <input id="clienteSearch" type="text" placeholder="Buscar cliente por nombre… (o dejar vacío para público)">
            <div id="suggClientes" class="sugg hidden"></div>
          </div>
          <input type="hidden" name="cliente_id" id="cliente_id" value="">

          <div class="row" style="margin-top:8px">
            <button type="button" id="btnNuevoCliente" class="btn btn-outline">
              <i class="ri-user-add-line"></i> Nuevo cliente
            </button>
            <button type="button" id="btnPublico" class="btn btn-outline">
              <i class="ri-user-3-line"></i> Público en general
            </button>
          </div>

          {{-- ===== ¿Emitir recibo? ===== --}}
          <div class="row" style="margin-top:12px">
            <label style="font-weight:700">¿Emitir recibo?</label>
            <label style="display:flex;align-items:center;gap:8px">
              <input id="emitirRecibo" type="checkbox" checked>
              <span>Sí</span>
            </label>
          </div>

          <div id="comprobantesBox" class="two" style="margin-top:6px">
            <div>
              <label>Tipo comprobante</label>
              <select id="tipoComp" name="tipo_comprobante">
                <option value="Ticket">Ticket</option>
                <option value="Factura">Factura</option>
              </select>
            </div>
            <div>
              <label>Folio</label>
              <input id="folio" name="folio" type="text" value="{{ now()->format('YmdHis') }}">
            </div>
          </div>

          <label>Observación</label>
          <input type="text" name="observacion" placeholder="(opcional)">

          {{-- hidden para mandar 1/0 --}}
          <input type="hidden" name="emitir_recibo" id="emitir_recibo" value="1">
        </div>

        <div class="card">
          <h3 class="card-title"><i class="ri-cash-line"></i> Realizar venta</h3>

          <div class="total-badge" id="totalBadge">0.00</div>

          <label>Cantidad recibida</label>
          <input id="recibido" type="number" step="0.01" value="0.00">

          <label>Cambio</label>
          <input id="cambio" type="number" step="0.01" value="0.00" readonly>

          <button type="submit" class="btn primary" style="width:100%;margin-top:10px">
            <i class="ri-check-line"></i> Aceptar
          </button>
          <button type="button" id="btnTicket" class="btn btn-outline" style="width:100%;margin-top:8px">
            🧾 Imprimir recibo
            </button>

        </div>

        <div id="itemsHidden"></div>
      </form>
    </div>
  </div>
</div>

{{-- ============ Modal: Nuevo cliente ============ --}}
<div id="modalCliente" class="modal">
  <div class="modal-content">
    <button type="button" class="close" id="closeCliente">&times;</button>
    <h3 class="modal-title">Nuevo cliente</h3>
    <form id="formNuevoCliente" method="POST" action="{{ route('clientes.store') }}">
      @csrf
      <input type="text" name="nombre" placeholder="Nombre completo" required>
      <input type="text" name="documento" placeholder="Documento (opcional)">
      <input type="text" name="telefono" placeholder="Teléfono (opcional)">
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" id="cancelCliente">Cancelar</button>
        <button type="submit" class="btn primary">Guardar</button>
      </div>
    </form>
    <small style="display:block;margin-top:8px;color:#64748b">
      Al guardar, se registrará el cliente y volverás a esta pantalla.
    </small>
  </div>
</div>

@if ($errors->any())
  <div class="alert alert-danger" style="margin-top:12px">
    <ul style="margin:0;padding-left:16px">
      @foreach($errors->all() as $e)
        <li>{{ $e }}</li>
      @endforeach
    </ul>
  </div>
@endif
@endsection

@push('scripts')
<script>
/* ─────────────────────────────────────────────────────────
   1) Arrays de datos desde PHP
   ───────────────────────────────────────────────────────── */
const PRODUCTOS = @json($productosForJs);
/* Clientes (id, nombre) para el buscador) */
const CLIENTES  = @json(collect($clientes)->map->only(['id','nombre']));

/* ─────────────────────────────────────────────────────────
   2) Referencias comunes (productos + venta)
   ───────────────────────────────────────────────────────── */
const $buscador   = document.getElementById('buscador');
const $sugg       = document.getElementById('sugg');
const $qty        = document.getElementById('ctrl_cantidad');
const $stock      = document.getElementById('ctrl_stock');
const $precio     = document.getElementById('ctrl_precio');
const $desc       = document.getElementById('ctrl_desc');
const $btnAdd     = document.getElementById('btnAgregar');
const $tbody      = document.querySelector('#tbl tbody');
const $totalTxt   = document.getElementById('totalTxt');
const $totalBadge = document.getElementById('totalBadge');
const $recibido   = document.getElementById('recibido');
const $cambio     = document.getElementById('cambio');
const $itemsHidden= document.getElementById('itemsHidden');
const $form       = document.getElementById('ventaForm');

/* Helpers */
const money = n => Number(n||0).toFixed(2);
const norm  = s => (s||'').toString().normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
const debounce = (fn,ms=150)=>{ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms) } };

/* ─────────────────────────────────────────────────────────
   3) AUTOCOMPLETE de PRODUCTOS (igual que lo que ya tenías)
   ───────────────────────────────────────────────────────── */
let productoSel = null, loteSel = null, suggIndex = -1, suggItems = [];
function elegirLote(p){ return p?.lotes?.find(l => (l.stock||0) > 0) || p?.lotes?.[0] || null; }
function disponibilidadYPrecio(p){ const l=elegirLote(p); return {ok:!!l,price:Number(l?.precio||0)}; }

function closeSugg(){ $sugg.classList.add('hidden'); $sugg.innerHTML=''; suggIndex=-1; suggItems=[]; }
function renderSugerencias(list){
  if(!list.length){ closeSugg(); return; }
  suggItems=list;
  $sugg.innerHTML=list.map((p,i)=>{
    const {ok,price}=disponibilidadYPrecio(p);
    return `<div class="sugg-item${i===suggIndex?' active':''}" data-id="${p.id}">
      <div class="sugg-icon">📦</div>
      <div><div class="sugg-title">${p.nombre}</div><div class="sugg-sub">P. venta Bs ${price.toFixed(2)}</div></div>
      <div>${ ok ? '<span class="badge-ok">Disponible</span>' : '<span class="badge-bad">Agotado</span>' }</div>
    </div>`;
  }).join('');
  $sugg.classList.remove('hidden');
}
function pickById(id){
  const p = PRODUCTOS.find(x=>x.id===id); if(!p) return;
  productoSel=p; loteSel=elegirLote(p);
  $stock.value=Number(loteSel?.stock||0);
  $precio.value=Number(loteSel?.precio||0).toFixed(2);
  $buscador.value=p.nombre; closeSugg();
}
$buscador.addEventListener('input', debounce(()=>{
  const q=norm($buscador.value.trim()); productoSel=null; loteSel=null; $stock.value='0'; $precio.value='0.00';
  if(q.length<1){ closeSugg(); return; }
  renderSugerencias(PRODUCTOS.filter(p=>norm(p.nombre).includes(q)).slice(0,20));
},120));
$buscador.addEventListener('keydown',(e)=>{
  if(e.key==='Enter'){ e.preventDefault(); agregarFila(); return; }
  if($sugg.classList.contains('hidden')) return;
  const max=suggItems.length-1;
  if(e.key==='ArrowDown'){ e.preventDefault(); suggIndex=Math.min(max,suggIndex+1); renderSugerencias(suggItems); }
  else if(e.key==='ArrowUp'){ e.preventDefault(); suggIndex=Math.max(0,suggIndex-1); renderSugerencias(suggItems); }
  else if(e.key==='Escape'){ closeSugg(); }
});
$sugg.addEventListener('click',e=>{ const row=e.target.closest('.sugg-item'); if(!row) return; pickById(+row.dataset.id); });
$sugg.addEventListener('dblclick',e=>{ const row=e.target.closest('.sugg-item'); if(!row) return; pickById(+row.dataset.id); agregarFila(); });
document.addEventListener('click',e=>{ if(!e.target.closest('.search-wrap')) closeSugg(); });

function resolverProductoDesdeInput(){
  const q=norm($buscador.value.trim()); if(!q) return false;
  const matches=PRODUCTOS.filter(p=>norm(p.nombre).includes(q));
  const exact=PRODUCTOS.find(p=>norm(p.nombre)===q);
  const elegido = exact || (matches.length===1 ? matches[0] : null);
  if(!elegido) return false;
  productoSel=elegido; loteSel=elegirLote(elegido);
  $stock.value=Number(loteSel?.stock||0);
  $precio.value=Number(loteSel?.precio||0).toFixed(2);
  return true;
}
function agregarFila(){
  if(!productoSel || !loteSel){
    if(!resolverProductoDesdeInput()){ alert('Busca y selecciona un producto.'); return; }
  }
  const cant=parseInt($qty.value||'0'), precio=parseFloat($precio.value||'0'), desc=parseFloat($desc.value||'0');
  if(cant<=0){ alert('Cantidad inválida'); return; }
  if(cant>(loteSel.stock||0)){ alert('No hay stock suficiente'); return; }

  const tr=document.createElement('tr');
  tr.dataset.pid=productoSel.id; tr.dataset.lid=loteSel.id; tr.dataset.price=precio.toString();
  const idx=$tbody.children.length+1; const sub=Math.max(0,cant*precio - desc);
  tr.innerHTML=`
    <td>${idx}</td>
    <td><div style="display:flex;flex-direction:column"><strong>${productoSel.nombre}</strong><small>${loteSel.label ?? ('Lote '+loteSel.id)}</small></div></td>
    <td><input class="in qty" type="number" min="1" step="1" value="${cant}"></td>
    <td><input class="in price" type="number" min="0" step="0.01" value="${money(precio)}"></td>
    <td><input class="in disc"  type="number" min="0" step="0.01" value="${money(desc)}"></td>
    <td class="sub">Bs ${money(sub)}</td>
    <td><button type="button" class="btn btn-outline del"><i class="ri-delete-bin-6-line"></i></button></td>`;
  $tbody.appendChild(tr); recalcTotal(); $qty.value=1; $desc.value='0.00'; $buscador.focus(); productoSel=null; loteSel=null;
}
$btnAdd.addEventListener('click', agregarFila);
[$qty,$precio,$desc].forEach(el=>el.addEventListener('keydown',e=>{ if(e.key==='Enter'){ e.preventDefault(); agregarFila(); } }));
$tbody.addEventListener('input',e=>{
  const tr=e.target.closest('tr'); if(!tr) return;
  const qty=parseFloat(tr.querySelector('.qty').value||'0'), price=parseFloat(tr.querySelector('.price').value||'0'), disc=parseFloat(tr.querySelector('.disc').value||'0');
  tr.querySelector('.sub').textContent='Bs '+money(Math.max(0,qty*price-disc)); recalcTotal();
});
$tbody.addEventListener('click',e=>{ if(!e.target.closest('.del')) return; e.target.closest('tr').remove(); [...$tbody.children].forEach((tr,i)=>tr.children[0].textContent=i+1); recalcTotal(); });
function recalcTotal(){
  let t=0; [...$tbody.querySelectorAll('.sub')].forEach(td=>{ t+=parseFloat(td.textContent.replace('Bs','')||'0'); });
  $totalTxt.textContent=money(t); $totalBadge.textContent=money(t);
  const recibido=parseFloat($recibido.value||'0'); $cambio.value=money(Math.max(0,recibido-t));
}
$recibido.addEventListener('input', recalcTotal);

/* ─────────────────────────────────────────────────────────
   4) AUTOCOMPLETE de CLIENTES + “Público” + Modal
   ───────────────────────────────────────────────────────── */
const $clienteSearch = document.getElementById('clienteSearch');
const $suggClientes  = document.getElementById('suggClientes');
const $clienteId     = document.getElementById('cliente_id');
const $btnPublico    = document.getElementById('btnPublico');
const $btnNuevoCliente = document.getElementById('btnNuevoCliente');

let cIndex = -1, cItems = [];

function closeSuggClientes(){ $suggClientes.classList.add('hidden'); $suggClientes.innerHTML=''; cIndex=-1; cItems=[]; }
function renderSuggClientes(list){
  if(!list.length){ closeSuggClientes(); return; }
  cItems=list;
  $suggClientes.innerHTML=list.map((c,i)=>`
    <div class="sugg-item${i===cIndex?' active':''}" data-id="${c.id}">
      <div class="sugg-icon">👤</div>
      <div><div class="sugg-title">${c.nombre}</div></div>
      <div></div>
    </div>`).join('');
  $suggClientes.classList.remove('hidden');
}
function pickCliente(id){
  const c = CLIENTES.find(x=>x.id==id);
  if(!c) return;
  $clienteId.value = c.id;
  $clienteSearch.value = c.nombre;
  closeSuggClientes();
}

$clienteSearch.addEventListener('input', debounce(()=>{
  const q=norm($clienteSearch.value.trim());
  $clienteId.value='';
  if(q.length<1){ closeSuggClientes(); return; }
  renderSuggClientes(CLIENTES.filter(c=>norm(c.nombre).includes(q)).slice(0,20));
},120));

$clienteSearch.addEventListener('keydown', (e)=>{
  if(e.key==='Enter'){
    e.preventDefault();
    // si hay 1 resultado visible, tómarlo; si no, queda público
    if(cItems.length===1){ pickCliente(cItems[0].id); }
    else closeSuggClientes();
    return;
  }
  if($suggClientes.classList.contains('hidden')) return;
  const max=cItems.length-1;
  if(e.key==='ArrowDown'){ e.preventDefault(); cIndex=Math.min(max,cIndex+1); renderSuggClientes(cItems); }
  else if(e.key==='ArrowUp'){ e.preventDefault(); cIndex=Math.max(0,cIndex-1); renderSuggClientes(cItems); }
  else if(e.key==='Escape'){ closeSuggClientes(); }
});

$suggClientes.addEventListener('click', e=>{
  const row = e.target.closest('.sugg-item'); if(!row) return;
  pickCliente(+row.dataset.id);
});
$suggClientes.addEventListener('dblclick', e=>{
  const row = e.target.closest('.sugg-item'); if(!row) return;
  pickCliente(+row.dataset.id);
});
document.addEventListener('click', e=>{ if(!e.target.closest('#clientePicker')) closeSuggClientes(); });

$btnPublico.addEventListener('click', ()=>{
  $clienteId.value=''; $clienteSearch.value=''; closeSuggClientes();
});

/* ===== Modal “Nuevo cliente” (AJAX) ===== */
const $modalCliente = document.getElementById('modalCliente');
const $formNuevoCliente = document.getElementById('formNuevoCliente');
const $closeCliente = document.getElementById('closeCliente');
const $cancelCliente = document.getElementById('cancelCliente');
const tokenCliente = $formNuevoCliente.querySelector('input[name=_token]').value;

function cerrarModalCliente(){ $modalCliente.style.display='none'; }
function abrirModalCliente(){ $modalCliente.style.display='block'; }

document.getElementById('btnNuevoCliente').addEventListener('click', abrirModalCliente);
$closeCliente.addEventListener('click', cerrarModalCliente);
$cancelCliente.addEventListener('click', cerrarModalCliente);

// Intercepta el submit y crea por AJAX
$formNuevoCliente.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const fd = new FormData($formNuevoCliente);

  try{
    const resp = await fetch($formNuevoCliente.action, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': tokenCliente, 'Accept': 'application/json' },
      body: fd
    });

    if(resp.status === 422){
      const data = await resp.json();
      alert('Revisa los campos:\n- ' + Object.values(data.errors).flat().join('\n- '));
      return;
    }

    if(!resp.ok){
      alert('No se pudo crear el cliente.'); return;
    }

    const nuevo = await resp.json(); // {id, nombre, ...}
    // 1) Añadir a la lista local de clientes
    CLIENTES.push({id:nuevo.id, nombre:nuevo.nombre});
    // 2) Preseleccionarlo en la venta
    document.getElementById('cliente_id').value = nuevo.id;
    document.getElementById('clienteSearch').value = nuevo.nombre;
    // 3) Cerrar modal y limpiar
    $formNuevoCliente.reset();
    cerrarModalCliente();

  }catch(err){
    console.error(err);
    alert('Error de red al crear cliente.');
  }
});

/* ─────────────────────────────────────────────────────────
   5) ¿Emitir recibo? (toggle muestra/oculta)
   ───────────────────────────────────────────────────────── */
const $emitirRecibo = document.getElementById('emitirRecibo');
const $emitir_recibo = document.getElementById('emitir_recibo');
const $comprobantesBox = document.getElementById('comprobantesBox');

function syncReciboUI(){
  const on = $emitirRecibo.checked;
  $emitir_recibo.value = on ? '1' : '0';
  $comprobantesBox.style.display = on ? '' : 'none';
}
$emitirRecibo.addEventListener('change', syncReciboUI);
syncReciboUI();

/* ─────────────────────────────────────────────────────────
   6) Envío: construir items[i][...]
   ───────────────────────────────────────────────────────── */
$form.addEventListener('submit', (e)=>{
  $itemsHidden.innerHTML='';
  const rows=[...$tbody.children];
  if(rows.length===0){ e.preventDefault(); alert('Agrega al menos un renglón de venta.'); return; }
  rows.forEach((tr,i)=>{
    addHidden(`items[${i}][producto_id]`, tr.dataset.pid);
    addHidden(`items[${i}][lote_id]`,     tr.dataset.lid);
    addHidden(`items[${i}][cantidad]`,    tr.querySelector('.qty').value);
    addHidden(`items[${i}][precio]`,      tr.querySelector('.price').value);
    addHidden(`items[${i}][descuento]`,   tr.querySelector('.disc').value);
  });
});
function addHidden(name, value){
  const i=document.createElement('input'); i.type='hidden'; i.name=name; i.value=value; $itemsHidden.appendChild(i);
}
/* ===== Ticket térmico (80mm) ===== */
const $btnTicket = document.getElementById('btnTicket');

function hayItems(){ return document.querySelectorAll('#tbl tbody tr').length > 0; }

function ticketHTML(){
  const negocio = 'Pharmacy';                  // cámbialo si quieres
  const ahora   = new Date();
  const fecha   = ahora.toLocaleString();
  const cliente = document.getElementById('clienteSearch').value || 'Público en general';
  const recibido = parseFloat($recibido.value||'0');
  const total = parseFloat($totalBadge.textContent||'0');
  const cambio = Math.max(0, recibido - total);

  // filas
  let filas = '';
  document.querySelectorAll('#tbl tbody tr').forEach(tr=>{
    const nombre = tr.querySelector('td:nth-child(2) strong')?.textContent || '';
    const qty = tr.querySelector('.qty')?.value || '0';
    const price = tr.querySelector('.price')?.value || '0';
    const sub = tr.querySelector('.sub')?.textContent.replace('Bs','').trim() || '0';
    filas += `
      <tr>
        <td>${nombre}<br><small>x${qty} @ ${Number(price).toFixed(2)}</small></td>
        <td style="text-align:right">${Number(sub).toFixed(2)}</td>
      </tr>`;
  });

  return `
  <html>
  <head>
    <meta charset="utf-8">
    <title>Ticket</title>
    <style>
      @page{ size: 80mm auto; margin: 0; }
      body{ font-family: ui-monospace, SFMono-Regular, Menlo, monospace; margin:0; }
      .wrap{ width:80mm; padding:10px 8px; }
      h1{ font-size:14px; margin:0 0 6px; text-align:center }
      .muted{ color:#444; font-size:12px; text-align:center; margin-bottom:8px }
      table{ width:100%; border-collapse:collapse; font-size:12px }
      td{ padding:2px 0 }
      .line{ border-top:1px dashed #999; margin:6px 0 }
      .tot{ font-weight:700 }
      .right{ text-align:right }
      .center{ text-align:center }
    </style>
  </head>
  <body onload="window.print(); setTimeout(()=>window.close(), 300);">
    <div class="wrap">
      <h1>${negocio}</h1>
      <div class="muted">${fecha}</div>
      <div class="muted">Cliente: ${cliente}</div>
      <div class="line"></div>
      <table>${filas}</table>
      <div class="line"></div>
      <table>
        <tr><td class="tot">TOTAL</td><td class="right tot">${total.toFixed(2)}</td></tr>
        <tr><td>Recibido</td><td class="right">${recibido.toFixed(2)}</td></tr>
        <tr><td>Cambio</td><td class="right">${cambio.toFixed(2)}</td></tr>
      </table>
      <div class="line"></div>
      <div class="center">¡Gracias por su compra!</div>
    </div>
  </body>
  </html>`;
}

$btnTicket.addEventListener('click', ()=>{
  if(!hayItems()){ alert('Agrega al menos un artículo para imprimir.'); return; }
  const win = window.open('', '_blank', 'width=400,height=600');
  win.document.open();
  win.document.write(ticketHTML());
  win.document.close();
});

</script>
@endpush
