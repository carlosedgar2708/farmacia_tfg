@extends('app')
@section('title','Nueva compra')

@section('content')
<div class="page compra-nueva">
  <div class="venta-wrap">

    <!-- IZQUIERDA -->
    <div class="venta-left card">
      <h3 style="margin-bottom:10px">🧾 Registrar compra</h3>

      <!-- CONTROLES -->
      <div class="top-controls grid4">

        <!-- Producto -->
        <div class="ctrl full">
          <label>Producto</label>

          <div class="search-wrap xl" id="productoPicker">
            <i class="ri-search-line"></i>
            <input id="buscadorProducto" type="text" placeholder="Buscar producto por nombre o código…">
            <div id="suggProducto" class="sugg hidden"></div>
          </div>
        </div>

        <div class="ctrl">
          <label>N° Lote</label>
          <input id="ctrl_nro_lote" type="text" placeholder="Ej: P002-L3">
        </div>

        <div class="ctrl">
          <label>Vencimiento</label>
          <input id="ctrl_vence" type="date">
        </div>

        <div class="ctrl">
          <label>Costo unitario</label>
          <input id="ctrl_costo" type="number" step="0.01" value="0.00" min="0">
        </div>

        <div class="ctrl">
          <label style="display:flex;gap:8px;align-items:center">
            <input type="checkbox" id="chkCostoTotal">
            <span>Costo total del lote</span>
          </label>
          <input id="ctrl_costo_total" type="number" step="0.01" value="0.00" min="0" disabled>
        </div>

        <div class="ctrl">
          <label>Cantidad</label>
          <input id="ctrl_cantidad" type="number" min="1" step="1" value="1">
        </div>

        <div class="ctrl full">
          <button id="btnAgregar" class="btn add" type="button">
            <i class="ri-add-circle-line"></i> Agregar
          </button>
        </div>
      </div>

      <!-- TABLA -->
      <div class="tabla-box soft">
        <table class="table compact" id="tbl">
          <thead>
            <tr>
              <th style="width:60px">#</th>
              <th>Producto</th>
              <th style="width:150px">N° Lote</th>
              <th style="width:150px">Vence</th>
              <th style="width:140px">Costo U.</th>
              <th style="width:120px">Cantidad</th>
              <th style="width:140px">Subtotal</th>
              <th style="width:60px"></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <!-- PIE -->
      <div class="footer-left">
        <button type="button" class="btn danger" onclick="history.back()">
          <i class="ri-close-line"></i> Cancelar compra
        </button>

        <div class="total-box">
          <span>Total Bs</span>
          <strong id="totalTxt">0.00</strong>
        </div>
      </div>
    </div>

    <!-- DERECHA -->
    <div class="venta-right">
      <form id="compraForm" method="POST" action="{{ route('compras.store') }}">
        @csrf

        <div class="card">
          <h3 class="card-title"><i class="ri-truck-line"></i> Datos de la compra</h3>

          <label>Proveedor</label>
          <div class="search-wrap" id="proveedorPicker">
            <i class="ri-user-search-line"></i>
            <input id="buscadorProveedor" type="text" placeholder="Buscar proveedor por nombre…">
            <div id="suggProveedor" class="sugg hidden"></div>
          </div>
          <input type="hidden" name="proveedor_id" id="proveedor_id">

          <label>Observación</label>
          <input type="text" name="observacion" placeholder="(opcional)">
        </div>

        <div class="card">
          <h3 class="card-title"><i class="ri-cash-line"></i> Confirmar compra</h3>

          <div class="total-badge" id="totalBadge">0.00</div>

          <button type="submit" class="btn primary" style="width:100%;margin-top:10px">
            <i class="ri-check-line"></i> Guardar compra
          </button>
        </div>

        <div id="itemsHidden"></div>
      </form>
    </div>

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

{{-- ================= Modal: Nuevo producto ================= --}}
<div id="modalProducto" class="modal">
  <div class="modal-content">
    <button type="button" class="close" id="closeProducto">&times;</button>
    <h3 class="modal-title">Nuevo producto</h3>

    <form id="formNuevoProducto" method="POST" action="{{ route('productos.store') }}">
      @csrf
      <input type="text" name="codigo" placeholder="Código (ej: P010)" required>
      <input type="text" name="nombre" placeholder="Nombre" required>
      <input type="number" name="precio_venta" step="0.01" min="0" placeholder="Precio venta" required>
      <label style="display:flex;gap:8px;align-items:center;margin-bottom:12px">
        <input type="checkbox" name="es_inyectable" value="1">
        <span>Es inyectable</span>
      </label>
      <textarea name="description" placeholder="Descripción (opcional)"></textarea>

      <div class="modal-actions">
        <button type="button" class="btn btn-outline" id="cancelProducto">Cancelar</button>
        <button type="submit" class="btn primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

{{-- ================= Modal: Nuevo proveedor ================= --}}
<div id="modalProveedor" class="modal">
  <div class="modal-content">
    <button type="button" class="close" id="closeProveedor">&times;</button>
    <h3 class="modal-title">Nuevo proveedor</h3>

    <form id="formNuevoProveedor" method="POST" action="{{ route('proveedors.store') }}">
      @csrf
      <input type="text" name="nombre" placeholder="Nombre proveedor" required>
      <input type="text" name="contacto" placeholder="Contacto (opcional)">
      <input type="text" name="telefono" placeholder="Teléfono (opcional)">

      <div class="modal-actions">
        <button type="button" class="btn btn-outline" id="cancelProveedor">Cancelar</button>
        <button type="submit" class="btn primary">Guardar</button>
      </div>
    </form>
  </div>
</div>
@endsection


@push('scripts')
<script>
/* =========================================================
   DATOS PHP
   ========================================================= */
const PRODUCTOS   = @json($productosForJs);
const PROVEEDORES = @json($proveedoresForJs);

/* Helpers */
const norm = s => (s||'').toString().normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
const debounce = (fn,ms=150)=>{ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms) } };
const money = n => Number(n||0).toFixed(2);

/* refs compra */
const $buscProd   = document.getElementById('buscadorProducto');
const $suggProd   = document.getElementById('suggProducto');
const $nroLote    = document.getElementById('ctrl_nro_lote');
const $vence      = document.getElementById('ctrl_vence');
const $costoUnit  = document.getElementById('ctrl_costo');
const $cantidad   = document.getElementById('ctrl_cantidad');
const $btnAdd     = document.getElementById('btnAgregar');
const $tbody      = document.querySelector('#tbl tbody');
const $totalTxt   = document.getElementById('totalTxt');
const $totalBadge = document.getElementById('totalBadge');
const $itemsHidden= document.getElementById('itemsHidden');
const $form       = document.getElementById('compraForm');

/* total lote */
const $chkTotal   = document.getElementById('chkCostoTotal');
const $costoTotal = document.getElementById('ctrl_costo_total');

/* =========================================================
   COSTO TOTAL DEL LOTE → CALCULA UNIT
   ========================================================= */
function syncCostoInputs(){
  const totalMode = $chkTotal.checked;
  $costoTotal.disabled = !totalMode;
  $costoUnit.disabled  = totalMode;

  if(totalMode) calcUnitFromTotal();
  else calcTotalFromUnit();
}

function calcUnitFromTotal(){
  const cant = parseInt($cantidad.value||'0');
  const total = parseFloat($costoTotal.value||'0');
  if(cant>0) $costoUnit.value = money(total / cant);
}

function calcTotalFromUnit(){
  const cant = parseInt($cantidad.value||'0');
  const unit = parseFloat($costoUnit.value||'0');
  if(cant>0) $costoTotal.value = money(unit * cant);
}

$chkTotal.addEventListener('change', syncCostoInputs);
$costoTotal.addEventListener('input', calcUnitFromTotal);
$costoUnit.addEventListener('input', calcTotalFromUnit);
$cantidad.addEventListener('input', ()=>{
  if($chkTotal.checked) calcUnitFromTotal();
  else calcTotalFromUnit();
});
syncCostoInputs();

/* =========================================================
   AUTOCOMPLETE PRODUCTOS
   ========================================================= */
let productoSel = null, pIndex=-1, pItems=[];

function closeSuggProd(){
  $suggProd.classList.add('hidden');
  $suggProd.innerHTML='';
  pIndex=-1; pItems=[];
}

function renderSuggProd(list, q){
  pItems = list;

  if(!list.length){
    $suggProd.innerHTML = `
      <div class="sugg-item" id="btnNuevoProdSuggest">
        <div class="sugg-icon">➕</div>
        <div>
          <div class="sugg-title">Nuevo producto</div>
          <div class="sugg-sub">No existe “${q}”. Crear ahora</div>
        </div>
      </div>`;
    $suggProd.classList.remove('hidden');
    return;
  }

  $suggProd.innerHTML = list.map((p,i)=>`
    <div class="sugg-item ${i===pIndex?'active':''}" data-id="${p.id}">
      <div class="sugg-icon">📦</div>
      <div>
        <div class="sugg-title">${p.nombre}</div>
        <div class="sugg-sub">Código: ${p.codigo}</div>
      </div>
    </div>
  `).join('');

  $suggProd.classList.remove('hidden');
}

function pickProducto(id){
  const p = PRODUCTOS.find(x=>x.id===id);
  if(!p) return;
  productoSel = p;
  $buscProd.value = `${p.codigo} - ${p.nombre}`;
  closeSuggProd();
}

$buscProd.addEventListener('input', debounce(()=>{
  const q = norm($buscProd.value.trim());
  productoSel=null;
  if(q.length<1){ closeSuggProd(); return; }

  const list = PRODUCTOS.filter(p =>
    norm(p.nombre).includes(q) || norm(p.codigo).includes(q)
  ).slice(0,20);

  renderSuggProd(list, $buscProd.value.trim());
},120));

$buscProd.addEventListener('keydown', e=>{
  if($suggProd.classList.contains('hidden')) return;
  const max = pItems.length-1;

  if(e.key==='ArrowDown'){ e.preventDefault(); pIndex=Math.min(max,pIndex+1); renderSuggProd(pItems,$buscProd.value); }
  else if(e.key==='ArrowUp'){ e.preventDefault(); pIndex=Math.max(0,pIndex-1); renderSuggProd(pItems,$buscProd.value); }
  else if(e.key==='Escape'){ closeSuggProd(); }
  else if(e.key==='Enter'){
    e.preventDefault();
    if(pItems[pIndex]) pickProducto(pItems[pIndex].id);
  }
});

$suggProd.addEventListener('click', e=>{
  const row = e.target.closest('.sugg-item');
  if(!row) return;

  if(row.id === 'btnNuevoProdSuggest'){
    abrirModalProducto();
    closeSuggProd();
    return;
  }

  pickProducto(+row.dataset.id);
});

document.addEventListener('click', e=>{
  if(!e.target.closest('#productoPicker')) closeSuggProd();
});

/* =========================================================
   AGREGAR FILA (usa productoSel)
   ========================================================= */
function agregarFila(){
  if(!productoSel){
    alert('Selecciona un producto desde el buscador');
    $buscProd.focus();
    return;
  }

  const productoId = productoSel.id;
  const nro  = ($nroLote.value||'').trim();
  const vence= $vence.value || '';
  const costo= parseFloat($costoUnit.value||'0');
  const cant = parseInt($cantidad.value||'0');

  if(!nro){ alert('N° de lote obligatorio'); return; }
  if(costo < 0){ alert('Costo inválido'); return; }
  if(cant <= 0){ alert('Cantidad inválida'); return; }

  const dup = [...$tbody.children].some(tr =>
    tr.dataset.pid==productoId && tr.dataset.nro==nro
  );
  if(dup){ alert('Ese producto/lote ya está en la compra.'); return; }

  const idx = $tbody.children.length + 1;
  const sub = cant * costo;

  const tr = document.createElement('tr');
  tr.dataset.pid = productoId;
  tr.dataset.nro = nro;
  tr.dataset.vence = vence;
  tr.dataset.costo = costo.toString();

  tr.innerHTML = `
    <td>${idx}</td>
    <td><strong>${productoSel.nombre}</strong></td>
    <td>${nro}</td>
    <td>${vence || '-'}</td>
    <td><input class="in costo_unit" type="number" step="0.01" min="0" value="${money(costo)}"></td>
    <td><input class="in qty" type="number" min="1" step="1" value="${cant}"></td>
    <td class="sub">Bs ${money(sub)}</td>
    <td><button type="button" class="btn btn-outline del"><i class="ri-delete-bin-6-line"></i></button></td>
  `;

  $tbody.appendChild(tr);
  recalcTotal();

  $nroLote.value='';
  $vence.value='';
  $costoUnit.value='0.00';
  $cantidad.value=1;
  $costoTotal.value='0.00';
  productoSel = null;
  $buscProd.value='';
  $buscProd.focus();
}

$btnAdd.addEventListener('click', agregarFila);
[$nroLote,$vence,$costoUnit,$cantidad].forEach(el=>{
  el.addEventListener('keydown',e=>{
    if(e.key==='Enter'){ e.preventDefault(); agregarFila(); }
  });
});

/* cambios dentro de tabla */
$tbody.addEventListener('input', e=>{
  const tr = e.target.closest('tr'); if(!tr) return;
  const costo = parseFloat(tr.querySelector('.costo_unit').value||'0');
  const cant  = parseFloat(tr.querySelector('.qty').value||'0');
  tr.dataset.costo = costo.toString();
  tr.querySelector('.sub').textContent = 'Bs ' + money(costo*cant);
  recalcTotal();
});

$tbody.addEventListener('click', e=>{
  if(!e.target.closest('.del')) return;
  e.target.closest('tr').remove();
  [...$tbody.children].forEach((tr,i)=>tr.children[0].textContent=i+1);
  recalcTotal();
});

/* total */
function recalcTotal(){
  let t=0;
  [...$tbody.querySelectorAll('.sub')].forEach(td=>{
    t += parseFloat(td.textContent.replace('Bs','')||'0');
  });
  $totalTxt.textContent = money(t);
  $totalBadge.textContent = money(t);
}

/* submit items */
$form.addEventListener('submit', (e)=>{
  $itemsHidden.innerHTML='';
  const rows=[...$tbody.children];
  if(rows.length===0){
    e.preventDefault();
    alert('Agrega al menos un renglón de compra.');
    return;
  }

  rows.forEach((tr,i)=>{
    addHidden(`items[${i}][producto_id]`, tr.dataset.pid);
    addHidden(`items[${i}][nro_lote]`, tr.dataset.nro);
    addHidden(`items[${i}][fecha_vencimiento]`, tr.dataset.vence);
    addHidden(`items[${i}][costo_unitario]`, tr.querySelector('.costo_unit').value);
    addHidden(`items[${i}][cantidad]`, tr.querySelector('.qty').value);
  });
});

function addHidden(name, value){
  const i=document.createElement('input');
  i.type='hidden'; i.name=name; i.value=value ?? '';
  $itemsHidden.appendChild(i);
}

/* =========================================================
   AUTOCOMPLETE PROVEEDORES (igual a productos)
   ========================================================= */
const $buscProv = document.getElementById('buscadorProveedor');
const $suggProv = document.getElementById('suggProveedor');
const $provId   = document.getElementById('proveedor_id');

let provSel=null, prIndex=-1, prItems=[];

function closeSuggProv(){
  $suggProv.classList.add('hidden');
  $suggProv.innerHTML='';
  prIndex=-1; prItems=[];
}

function renderSuggProv(list, q){
  prItems=list;

  if(!list.length){
    $suggProv.innerHTML = `
      <div class="sugg-item" id="btnNuevoProvSuggest">
        <div class="sugg-icon">➕</div>
        <div>
          <div class="sugg-title">Nuevo proveedor</div>
          <div class="sugg-sub">No existe “${q}”. Crear ahora</div>
        </div>
      </div>`;
    $suggProv.classList.remove('hidden');
    return;
  }

  $suggProv.innerHTML = list.map((pr,i)=>`
    <div class="sugg-item ${i===prIndex?'active':''}" data-id="${pr.id}">
      <div class="sugg-icon">🚚</div>
      <div>
        <div class="sugg-title">${pr.nombre}</div>
        ${pr.telefono ? `<div class="sugg-sub">Tel: ${pr.telefono}</div>` : ``}
      </div>
    </div>
  `).join('');

  $suggProv.classList.remove('hidden');
}

function pickProveedor(id){
  const pr = PROVEEDORES.find(x=>x.id==id);
  if(!pr) return;
  provSel = pr;
  $provId.value = pr.id;
  $buscProv.value = pr.nombre;
  closeSuggProv();
}

$buscProv.addEventListener('input', debounce(()=>{
  const q = norm($buscProv.value.trim());
  provSel=null; $provId.value='';
  if(q.length<1){ closeSuggProv(); return; }

  const list = PROVEEDORES
    .filter(pr => norm(pr.nombre).includes(q))
    .slice(0,20);

  renderSuggProv(list, $buscProv.value.trim());
},120));

$buscProv.addEventListener('keydown', e=>{
  if($suggProv.classList.contains('hidden')) return;
  const max = prItems.length-1;

  if(e.key==='ArrowDown'){ e.preventDefault(); prIndex=Math.min(max,prIndex+1); renderSuggProv(prItems,$buscProv.value); }
  else if(e.key==='ArrowUp'){ e.preventDefault(); prIndex=Math.max(0,prIndex-1); renderSuggProv(prItems,$buscProv.value); }
  else if(e.key==='Escape'){ closeSuggProv(); }
  else if(e.key==='Enter'){
    e.preventDefault();
    if(prItems[prIndex]) pickProveedor(prItems[prIndex].id);
  }
});

$suggProv.addEventListener('click', e=>{
  const row = e.target.closest('.sugg-item'); if(!row) return;

  if(row.id==='btnNuevoProvSuggest'){
    abrirModalProveedor();
    closeSuggProv();
    return;
  }

  pickProveedor(+row.dataset.id);
});

document.addEventListener('click', e=>{
  if(!e.target.closest('#proveedorPicker')) closeSuggProv();
});

/* =========================================================
   MODAL PRODUCTO (AJAX)
   ========================================================= */
const $modalProducto = document.getElementById('modalProducto');
const $formNuevoProducto = document.getElementById('formNuevoProducto');
const tokenProd = $formNuevoProducto.querySelector('input[name=_token]').value;

function abrirModalProducto(){ $modalProducto.style.display='block'; }
function cerrarModalProducto(){ $modalProducto.style.display='none'; }

document.getElementById('closeProducto').addEventListener('click', cerrarModalProducto);
document.getElementById('cancelProducto').addEventListener('click', cerrarModalProducto);

$formNuevoProducto.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const fd = new FormData($formNuevoProducto);

  const resp = await fetch($formNuevoProducto.action,{
    method:'POST',
    headers:{ 'X-CSRF-TOKEN':tokenProd, 'Accept':'application/json' },
    body:fd
  });

  if(resp.status===422){
    const data = await resp.json();
    alert('Revisa campos:\n- '+Object.values(data.errors).flat().join('\n- '));
    return;
  }
  if(!resp.ok){ alert('No se pudo crear producto'); return; }

  const nuevo = await resp.json(); // {id,codigo,nombre,...}

  PRODUCTOS.push({
    id: nuevo.id,
    codigo: nuevo.codigo,
    nombre: nuevo.nombre
  });

  pickProducto(nuevo.id);

  $formNuevoProducto.reset();
  cerrarModalProducto();
});

/* =========================================================
   MODAL PROVEEDOR (AJAX)
   ========================================================= */
const $modalProveedor = document.getElementById('modalProveedor');
const $formNuevoProveedor = document.getElementById('formNuevoProveedor');
const tokenProv = $formNuevoProveedor.querySelector('input[name=_token]').value;

function abrirModalProveedor(){ $modalProveedor.style.display='block'; }
function cerrarModalProveedor(){ $modalProveedor.style.display='none'; }

document.getElementById('closeProveedor').addEventListener('click', cerrarModalProveedor);
document.getElementById('cancelProveedor').addEventListener('click', cerrarModalProveedor);

$formNuevoProveedor.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const fd = new FormData($formNuevoProveedor);

  const resp = await fetch($formNuevoProveedor.action,{
    method:'POST',
    headers:{ 'X-CSRF-TOKEN':tokenProv, 'Accept':'application/json' },
    body:fd
  });

  if(resp.status===422){
    const data = await resp.json();
    alert('Revisa campos:\n- '+Object.values(data.errors).flat().join('\n- '));
    return;
  }
  if(!resp.ok){ alert('No se pudo crear proveedor'); return; }

  const nuevo = await resp.json();

  PROVEEDORES.push({
    id: nuevo.id,
    nombre: nuevo.nombre,
    telefono: nuevo.telefono ?? null
  });

  pickProveedor(nuevo.id);

  $formNuevoProveedor.reset();
  cerrarModalProveedor();
});
</script>
@endpush
