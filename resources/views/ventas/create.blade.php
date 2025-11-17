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

/* Helpers específicos de productos */
function stockTotalProducto(producto) {
  return (producto?.lotes || []).reduce((sum, l) => sum + (l.stock || 0), 0);
}
function precioSugeridoProducto(producto) {
  const lotes = producto?.lotes || [];
  const conStock = lotes.find(l => (l.stock || 0) > 0) || lotes[0];
  return Number(conStock?.precio || 0);
}

/* ─────────────────────────────────────────────────────────
   3) AUTOCOMPLETE de PRODUCTOS
   ───────────────────────────────────────────────────────── */
let productoSel = null;
let suggIndex = -1, suggItems = [];

function closeSugg(){
  $sugg.classList.add('hidden');
  $sugg.innerHTML='';
  suggIndex=-1;
  suggItems=[];
}
function renderSugerencias(list){
  if(!list.length){ closeSugg(); return; }
  suggItems=list;
  $sugg.innerHTML=list.map((p,i)=>{
    const stockTot = stockTotalProducto(p);
    const price = precioSugeridoProducto(p);
    return `<div class="sugg-item${i===suggIndex?' active':''}" data-id="${p.id}">
      <div class="sugg-icon">📦</div>
      <div>
        <div class="sugg-title">${p.nombre}</div>
        <div class="sugg-sub">P. venta Bs ${price.toFixed(2)} · Stock: ${stockTot}</div>
      </div>
      <div>${ stockTot > 0 ? '<span class="badge-ok">Disponible</span>' : '<span class="badge-bad">Agotado</span>' }</div>
    </div>`;
  }).join('');
  $sugg.classList.remove('hidden');
}
function pickById(id){
  const p = PRODUCTOS.find(x=>x.id===id);
  if(!p) return;
  productoSel = p;
  $stock.value  = stockTotalProducto(p);
  $precio.value = precioSugeridoProducto(p).toFixed(2);
  $buscador.value = p.nombre;
  closeSugg();
}

$buscador.addEventListener('input', debounce(()=>{
  const q=norm($buscador.value.trim());
  productoSel=null;
  $stock.value='0';
  $precio.value='0.00';
  if(q.length<1){ closeSugg(); return; }
  renderSugerencias(PRODUCTOS.filter(p=>norm(p.nombre).includes(q)).slice(0,20));
},120));

$buscador.addEventListener('keydown',(e)=>{
  if(e.key==='Enter'){
    e.preventDefault();
    agregarFila();
    return;
  }
  if($sugg.classList.contains('hidden')) return;
  const max=suggItems.length-1;
  if(e.key==='ArrowDown'){
    e.preventDefault();
    suggIndex=Math.min(max,suggIndex+1);
    renderSugerencias(suggItems);
  }else if(e.key==='ArrowUp'){
    e.preventDefault();
    suggIndex=Math.max(0,suggIndex-1);
    renderSugerencias(suggItems);
  }else if(e.key==='Escape'){
    closeSugg();
  }
});
$sugg.addEventListener('click',e=>{
  const row=e.target.closest('.sugg-item');
  if(!row) return;
  pickById(+row.dataset.id);
});
$sugg.addEventListener('dblclick',e=>{
  const row=e.target.closest('.sugg-item');
  if(!row) return;
  pickById(+row.dataset.id);
});
document.addEventListener('click',e=>{
  if(!e.target.closest('.search-wrap')) closeSugg();
});

function resolverProductoDesdeInput(){
  const q=norm($buscador.value.trim());
  if(!q) return false;
  const matches=PRODUCTOS.filter(p=>norm(p.nombre).includes(q));
  const exact=PRODUCTOS.find(p=>norm(p.nombre)===q);
  const elegido = exact || (matches.length===1 ? matches[0] : null);
  if(!elegido) return false;
  productoSel = elegido;
  $stock.value  = stockTotalProducto(elegido);
  $precio.value = precioSugeridoProducto(elegido).toFixed(2);
  return true;
}

function agregarFila(){
  if(!productoSel){
    if(!resolverProductoDesdeInput()){
      alert('Busca y selecciona un producto.');
      return;
    }
  }

  const cant  = parseInt($qty.value||'0');
  const precio= parseFloat($precio.value||'0');
  const desc  = parseFloat($desc.value||'0');
  if(cant<=0){
    alert('Cantidad inválida');
    return;
  }

  const stockTot = stockTotalProducto(productoSel);
  if(cant > stockTot){
    alert('No hay stock suficiente. Stock total disponible: ' + stockTot);
    return;
  }

  const tr=document.createElement('tr');
  tr.dataset.pid   = productoSel.id;
  tr.dataset.price = precio.toString();

  const idx = $tbody.children.length+1;
  const sub = Math.max(0,cant*precio - desc);

  tr.innerHTML=`
    <td>${idx}</td>
    <td>
      <div style="display:flex;flex-direction:column">
        <strong>${productoSel.nombre}</strong>
      </div>
    </td>
    <td><input class="in qty" type="number" min="1" step="1" value="${cant}"></td>
    <td><input class="in price" type="number" min="0" step="0.01" value="${money(precio)}"></td>
    <td><input class="in disc"  type="number" min="0" step="0.01" value="${money(desc)}"></td>
    <td class="sub">Bs ${money(sub)}</td>
    <td><button type="button" class="btn btn-outline del"><i class="ri-delete-bin-6-line"></i></button></td>
  `;
  $tbody.appendChild(tr);

  recalcTotal();
  $qty.value=1;
  $desc.value='0.00';
  $buscador.focus();
  productoSel=null;      // para obligar a seleccionar de nuevo
  $stock.value='0';
}

/* botón Agregar e inputs */
$btnAdd.addEventListener('click', agregarFila);
[$qty,$precio,$desc].forEach(el=>el.addEventListener('keydown',e=>{
  if(e.key==='Enter'){
    e.preventDefault();
    agregarFila();
  }
}));

/* cambios en filas (qty/precio/desc) */
$tbody.addEventListener('input',e=>{
  const tr=e.target.closest('tr');
  if(!tr) return;
  const qty=parseFloat(tr.querySelector('.qty').value||'0');
  const price=parseFloat(tr.querySelector('.price').value||'0');
  const disc=parseFloat(tr.querySelector('.disc').value||'0');
  tr.querySelector('.sub').textContent='Bs '+money(Math.max(0,qty*price-disc));
  recalcTotal();
});

/* eliminar fila */
$tbody.addEventListener('click',e=>{
  if(!e.target.closest('.del')) return;
  e.target.closest('tr').remove();
  [...$tbody.children].forEach((tr,i)=>tr.children[0].textContent=i+1);
  recalcTotal();
});

/* total / cambio */
function recalcTotal(){
  let t=0;
  [...$tbody.querySelectorAll('.sub')].forEach(td=>{
    t+=parseFloat(td.textContent.replace('Bs','')||'0');
  });
  $totalTxt.textContent = money(t);
  $totalBadge.textContent = money(t);
  const recibido=parseFloat($recibido.value||'0');
  $cambio.value = money(Math.max(0,recibido-t));
}
$recibido.addEventListener('input', recalcTotal);

/* ─────────────────────────────────────────────────────────
   4) AUTOCOMPLETE de CLIENTES + “Público” + Modal
   ───────────────────────────────────────────────────────── */
// (esta parte la dejo igual que la tuya)
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
document.addEventListener('click', e=>{
  if(!e.target.closest('#clientePicker')) closeSuggClientes();
});
$btnPublico.addEventListener('click', ()=>{
  $clienteId.value=''; $clienteSearch.value=''; closeSuggClientes();
});

/* ===== Modal “Nuevo cliente” (AJAX) ===== */
const $modalCliente    = document.getElementById('modalCliente');
const $formNuevoCliente= document.getElementById('formNuevoCliente');
const $closeCliente    = document.getElementById('closeCliente');
const $cancelCliente   = document.getElementById('cancelCliente');
const tokenCliente     = $formNuevoCliente.querySelector('input[name=_token]').value;

function cerrarModalCliente(){ $modalCliente.style.display='none'; }
function abrirModalCliente(){ $modalCliente.style.display='block'; }

document.getElementById('btnNuevoCliente').addEventListener('click', abrirModalCliente);
$closeCliente.addEventListener('click', cerrarModalCliente);
$cancelCliente.addEventListener('click', cerrarModalCliente);
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
    const nuevo = await resp.json();
    CLIENTES.push({id:nuevo.id, nombre:nuevo.nombre});
    document.getElementById('cliente_id').value = nuevo.id;
    document.getElementById('clienteSearch').value = nuevo.nombre;
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
const $emitirRecibo   = document.getElementById('emitirRecibo');
const $emitir_recibo  = document.getElementById('emitir_recibo');
const $comprobantesBox= document.getElementById('comprobantesBox');

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
  if(rows.length===0){
    e.preventDefault();
    alert('Agrega al menos un renglón de venta.');
    return;
  }
  rows.forEach((tr,i)=>{
    addHidden(`items[${i}][producto_id]`, tr.dataset.pid);
    // YA NO ENVIAMOS lote_id -> el backend repartirá por lotes
    addHidden(`items[${i}][cantidad]`,    tr.querySelector('.qty').value);
    addHidden(`items[${i}][precio]`,      tr.querySelector('.price').value);
    addHidden(`items[${i}][descuento]`,   tr.querySelector('.disc').value);
  });
});
function addHidden(name, value){
  const i=document.createElement('input');
  i.type='hidden';
  i.name=name;
  i.value=value;
  $itemsHidden.appendChild(i);
}

/* ===== Ticket térmico (80mm) ===== */
// dejo tu código de ticket igual
const $btnTicket = document.getElementById('btnTicket');
function hayItems(){ return document.querySelectorAll('#tbl tbody tr').length > 0; }
// ... resto del ticket igual que ya lo tienes ...
</script>
@endpush
