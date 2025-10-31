@extends('app')
@section('title','Nueva venta')

@section('content')
<section class="hero">
  <div class="panel" style="padding:0">
    <div class="venta-wrap">
      <!-- COLUMNA IZQUIERDA -->
      <div class="venta-left">
        {{-- Buscador por nombre --}}
        <!-- Buscador por nombre con sugerencias -->
        <div class="search-wrap">
        <span class="icon">🔍</span>
        <input id="buscador" type="text" placeholder="Buscar por el nombre">
        <div id="sugg" class="sugg hidden"></div>
        </div>


        {{-- Controles superiores --}}
        <div class="row top-controls">
          <div class="ctrl">
            <label>Cantidad</label>
            <input id="ctrl_cantidad" type="number" min="1" step="1" value="1">
          </div>
          <div class="ctrl">
            <label>Stock</label>
            <input id="ctrl_stock" type="number" step="1" value="0" readonly>
          </div>
          <div class="ctrl">
            <label>P. venta</label>
            <input id="ctrl_precio" type="number" min="0" step="0.01" value="0.00">
          </div>
          <div class="ctrl">
            <label>Descuento</label>
            <input id="ctrl_desc" type="number" min="0" step="0.01" value="0.00">
          </div>
          <div class="ctrl">
            <label>&nbsp;</label>
            <button id="btnAgregar" class="btn">✔ Agregar</button>
          </div>
        </div>

        {{-- Grilla ítems --}}
        <div class="tabla-box">
          <table class="table compact" id="tbl">
            <thead>
            <tr>
              <th style="width:48px">#</th>
              <th>Artículo</th>
              <th style="width:110px">Cantidad</th>
              <th style="width:120px">P. venta</th>
              <th style="width:120px">Descuento</th>
              <th style="width:120px">Subtotal</th>
              <th style="width:48px"></th>
            </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

        {{-- Pie izquierdo --}}
        <div class="row footer-left">
          <button type="button" class="btn btn-outline" onclick="history.back()">✖ Cancelar venta</button>
          <div class="total-box">
            <span>Total $</span>
            <strong id="totalTxt">0.00</strong>
          </div>
        </div>
      </div>

      <!-- COLUMNA DERECHA -->
      <div class="venta-right">
        <form id="ventaForm" method="POST" action="{{ route('ventas.store') }}">
          @csrf

          <div class="card">
            <div class="card-title">Datos de la venta</div>

            <label>Cliente</label>
            <select name="cliente_id" id="cliente_id">
              <option value="">Público en general</option>
              @foreach($clientes as $c)
                <option value="{{ $c->id }}">{{ $c->nombre }}</option>
              @endforeach
            </select>

            <div class="two">
              <div>
                <label>Tipo comprobante</label>
                <select id="tipoComp">
                  <option>Ticket</option>
                  <option>Factura</option>
                </select>
              </div>
              <div>
                <label>Folio</label>
                <input id="folio" type="text" value="{{ now()->format('YmdHis') }}">
              </div>
            </div>

            <label>Observación</label>
            <input type="text" name="observacion" placeholder="(opcional)">
          </div>

          <div class="card">
            <div class="card-title">Realizar venta</div>

            <div class="total-badge" id="totalBadge">0.00</div>

            <label>Cantidad recibida</label>
            <input id="recibido" type="number" min="0" step="0.01" value="0.00">

            <label>Cambio</label>
            <input id="cambio" type="number" step="0.01" value="0.00" readonly>

            <button type="submit" class="btn primary" style="width:100%;margin-top:8px">✔ Aceptar</button>
          </div>

          {{-- aquí pondremos los inputs items[i][...] al enviar --}}
          <div id="itemsHidden"></div>
        </form>
      </div>
    </div>
  </div>
  <div class="shadow"></div>
</section>

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
<style>
  .venta-wrap{display:grid;grid-template-columns:1.6fr .9fr;gap:16px;padding:16px}
  .venta-left{display:flex;flex-direction:column;gap:12px}
  .venta-right{display:flex;flex-direction:column;gap:12px}
  .row{display:flex;gap:12px;align-items:center}
  .search-bar input{flex:1}
  .search-bar .icon{opacity:.6}
  .top-controls .ctrl{display:flex;flex-direction:column;gap:6px}
  .tabla-box{background:#fff;border-radius:12px;padding:8px}
  .footer-left{justify-content:space-between}
  .total-box{display:flex;align-items:center;gap:8px;font-size:18px}
  .card{background:#ffffff;border-radius:12px;padding:12px;box-shadow:0 2px 10px rgba(0,0,0,.06)}
  .card-title{font-weight:700;margin-bottom:8px}
  .two{display:grid;grid-template-columns:1fr 1fr;gap:8px}
  .total-badge{font-size:28px;font-weight:800;text-align:center;background:#f1f5f9;border-radius:10px;padding:8px;margin:8px 0}
  .table.compact th,.table.compact td{padding:8px}
  .btn.primary{background:#3b82f6;color:#fff}
</style>

<script>
/** ===== Datos del backend (productos + lotes con stock y precio por lote) ===== */
const PRODUCTOS = @json($productosForJs);

/** ===== Estado ===== */
const $buscador   = document.getElementById('buscador');
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

let productoSel = null;   // {id, nombre, lotes:[{id,label,precio,stock}]}
let loteSel     = null;   // {id,label,precio,stock}

/** ===== Helpers ===== */
const money=(n)=> Number(n||0).toFixed(2);

/** ===== Autocompletar con sugerencias ===== */

const $sugg = document.getElementById('sugg');
let suggIndex = -1;              // índice activo en la lista visual
let suggItems = [];              // productos renderizados

function disponibilidadYPrecio(p){
  const conStock = p.lotes.some(l=> (l.stock || 0) > 0);
  // precio: primero con stock; si ninguno, el del primer lote disponible
  const lotePrecio = (p.lotes.find(l=> (l.stock||0)>0) || p.lotes[0] || {precio:0});
  return { ok: conStock, price: Number(lotePrecio.precio||0) };
}

function renderSugerencias(list){
  if (!list.length){
    $sugg.classList.add('hidden');
    $sugg.innerHTML = '';
    suggIndex = -1; suggItems = [];
    return;
  }

  suggItems = list;
  $sugg.innerHTML = list.map((p,i)=>{
    const {ok, price} = disponibilidadYPrecio(p);
    return `
      <div class="sugg-item${i===suggIndex?' active':''}" data-id="${p.id}">
        <div class="sugg-icon">📦</div>
        <div>
          <div class="sugg-title">${p.nombre}</div>
          <div class="sugg-sub">P. venta $ ${price.toFixed(2)}</div>
        </div>
        <div>${ ok ? '<span class="badge-ok">Disponible</span>' : '<span class="badge-bad">Agotado</span>' }</div>
      </div>
    `;
  }).join('');
  $sugg.classList.remove('hidden');
}

function pickById(id){
  const p = PRODUCTOS.find(x=> x.id === id);
  if(!p) return;

  productoSel = p;

  // Lote seleccionado: primero con stock; si no, el primero
  const l = p.lotes.find(x=> (x.stock||0)>0) || p.lotes[0];
  loteSel = l || null;

  if(loteSel){
    $stock.value  = Number(loteSel.stock||0);
    $precio.value = Number(loteSel.precio||0).toFixed(2);
  }else{
    $stock.value  = 0;
    $precio.value = '0.00';
  }

  $buscador.value = p.nombre;
  closeSugg();
}

function closeSugg(){
  $sugg.classList.add('hidden');
  $sugg.innerHTML = '';
  suggIndex = -1; suggItems = [];
}

$buscador.addEventListener('input', ()=>{
  const q = $buscador.value.trim().toLowerCase();
  productoSel = null; loteSel = null;
  $stock.value='0'; $precio.value='0.00';
  if(q.length < 1){ closeSugg(); return; }

  // filtrar por coincidencia simple
  const res = PRODUCTOS.filter(p => p.nombre.toLowerCase().includes(q)).slice(0,20);
  renderSugerencias(res);
});

$sugg.addEventListener('click', (e)=>{
  const row = e.target.closest('.sugg-item');
  if(!row) return;
  pickById(parseInt(row.dataset.id));
});

$buscador.addEventListener('keydown', (e)=>{
  // navegación de la lista
  if ($sugg.classList.contains('hidden')) return;

  const max = suggItems.length - 1;
  if (e.key === 'ArrowDown'){
    e.preventDefault();
    suggIndex = Math.min(max, suggIndex + 1);
    renderSugerencias(suggItems);
  } else if (e.key === 'ArrowUp'){
    e.preventDefault();
    suggIndex = Math.max(0, suggIndex - 1);
    renderSugerencias(suggItems);
  } else if (e.key === 'Enter'){
    e.preventDefault();
    if (suggIndex >= 0 && suggIndex <= max){
      pickById(suggItems[suggIndex].id);
    }
  } else if (e.key === 'Escape'){
    closeSugg();
  }
});

// cierra al click fuera
document.addEventListener('click', (e)=>{
  if (!e.target.closest('.search-wrap')) closeSugg();
});


/** ===== Agregar renglón ===== */
$btnAdd.addEventListener('click', ()=>{
  if(!productoSel || !loteSel){
    alert('Busca y selecciona un producto con lote disponible.');
    return;
  }
  const cant   = parseInt($qty.value||'0');
  const precio = parseFloat($precio.value||'0');
  const desc   = parseFloat($desc.value||'0');

  if(cant<=0){ alert('Cantidad inválida'); return; }
  if(cant > (loteSel.stock||0)){ alert('No hay stock suficiente en el lote'); return; }

  // Construir fila
  const tr = document.createElement('tr');
  tr.dataset.pid   = productoSel.id;
  tr.dataset.pname = productoSel.nombre;
  tr.dataset.lid   = loteSel.id;
  tr.dataset.price = precio.toString();

  const idx = $tbody.children.length + 1;

  const sub = Math.max(0, cant*precio - desc);

  tr.innerHTML = `
    <td>${idx}</td>
    <td>
      <div style="display:flex;flex-direction:column">
        <strong>${productoSel.nombre}</strong>
        <small>${loteSel.label}</small>
      </div>
    </td>
    <td><input class="in qty"   type="number" min="1" step="1"   value="${cant}"></td>
    <td><input class="in price" type="number" min="0" step="0.01" value="${money(precio)}"></td>
    <td><input class="in disc"  type="number" min="0" step="0.01" value="${money(desc)}"></td>
    <td class="sub">$ ${money(sub)}</td>
    <td><button type="button" class="btn btn-outline del">🗑</button></td>
  `;
  $tbody.appendChild(tr);
  recalcTotal();

  // Reset controles cantidad/desc
  $qty.value = 1;
  $desc.value= '0.00';
});

/** ===== Delegación: cambios en cantidad/precio/descuento y eliminar ===== */
$tbody.addEventListener('input', (e)=>{
  const tr = e.target.closest('tr');
  if(!tr) return;
  const qty   = parseFloat(tr.querySelector('.qty').value||'0');
  const price = parseFloat(tr.querySelector('.price').value||'0');
  const disc  = parseFloat(tr.querySelector('.disc').value||'0');
  const sub   = Math.max(0, qty*price - disc);
  tr.querySelector('.sub').textContent = '$ '+money(sub);
  recalcTotal();
});

$tbody.addEventListener('click', (e)=>{
  if(!e.target.classList.contains('del')) return;
  e.target.closest('tr').remove();
  reindex();
  recalcTotal();
});

function reindex(){
  [...$tbody.children].forEach((tr,i)=>{
    tr.children[0].textContent = i+1;
  });
}

function recalcTotal(){
  let t=0;
  [...$tbody.querySelectorAll('.sub')].forEach(td=>{
    t += parseFloat(td.textContent.replace('$','')||'0');
  });
  $totalTxt.textContent   = money(t);
  $totalBadge.textContent = money(t);
  const recibido = parseFloat($recibido.value||'0');
  $cambio.value = money(Math.max(0, recibido - t));
}

$recibido.addEventListener('input', recalcTotal);

/** ===== Envío: construir items[i][...] ocultos de la tabla ===== */
$form.addEventListener('submit', (e)=>{
  // limpiar
  $itemsHidden.innerHTML = '';

  const rows = [...$tbody.children];
  if(rows.length===0){
    e.preventDefault();
    alert('Agrega al menos un renglón de venta.');
    return;
  }

  rows.forEach((tr,i)=>{
    const producto_id = tr.dataset.pid;
    const lote_id     = tr.dataset.lid;
    const cantidad    = tr.querySelector('.qty').value;
    const precio      = tr.querySelector('.price').value;
    const descuento   = tr.querySelector('.disc').value;

    // inputs hidden
    addHidden(`items[${i}][producto_id]`, producto_id);
    addHidden(`items[${i}][lote_id]`, lote_id);
    addHidden(`items[${i}][cantidad]`, cantidad);
    addHidden(`items[${i}][precio]`, precio);
    addHidden(`items[${i}][descuento]`, descuento);
  });
});

function addHidden(name, value){
  const i = document.createElement('input');
  i.type = 'hidden'; i.name = name; i.value = value;
  $itemsHidden.appendChild(i);
}

</script>
@endpush
