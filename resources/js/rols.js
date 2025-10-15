/* public/js/rols.js */

function slugify(str){
  return (str||"")
    .toString()
    .normalize("NFD").replace(/[\u0300-\u036f]/g,"") // quita acentos
    .toLowerCase()
    .replace(/[^a-z0-9]+/g,"-")                      // separa por guiones
    .replace(/(^-|-$)+/g,"");                        // quita guiones extremos
}

function showModal(){
  document.getElementById("rolModal").style.display = "block";
}

function closeModal(){
  document.getElementById("rolModal").style.display = "none";
}

function setFormDisabled(disabled){
  ["nombre","slug","descripcion"].forEach(id=>{
    const el = document.getElementById(id);
    el.disabled = disabled;
  });
}

function openCreateModal(){
  showModal();
  document.getElementById("modalTitle").innerText = "Nuevo Rol";
  document.getElementById("rolForm").action = window.routesRolsStore; // se setea desde Blade
  document.getElementById("methodField").value = "POST";
  document.getElementById("modalMode").value = "create";

  document.getElementById("nombre").value = "";
  document.getElementById("slug").value = "";
  document.getElementById("descripcion").value = "";

  setFormDisabled(false);
  const submit = document.getElementById("submitBtn");
  submit.style.display = "";
  submit.innerText = "Guardar";
  document.getElementById("cancelBtn").innerText = "Cancelar";
}

function openEditModal(btn){
  const id = btn.dataset.id;
  const nombre = btn.dataset.nombre || "";
  const slug = btn.dataset.slug || "";
  const descripcion = btn.dataset.descripcion || "";

  showModal();
  document.getElementById("modalTitle").innerText = "Editar Rol";
  document.getElementById("rolForm").action = "/rols/" + id;
  document.getElementById("methodField").value = "PUT";
  document.getElementById("modalMode").value = "edit";

  document.getElementById("nombre").value = nombre;
  document.getElementById("slug").value = slug;
  document.getElementById("descripcion").value = descripcion;

  setFormDisabled(false);
  const submit = document.getElementById("submitBtn");
  submit.style.display = "";
  submit.innerText = "Actualizar";
  document.getElementById("cancelBtn").innerText = "Cancelar";
}

function openViewModal(btn){
  const nombre = btn.dataset.nombre || "";
  const slug = btn.dataset.slug || "";
  const descripcion = btn.dataset.descripcion || "";

  showModal();
  document.getElementById("modalTitle").innerText = "Detalle del Rol";
  document.getElementById("rolForm").action = "#";        // no envía
  document.getElementById("methodField").value = "GET";
  document.getElementById("modalMode").value = "view";

  document.getElementById("nombre").value = nombre;
  document.getElementById("slug").value = slug;
  document.getElementById("descripcion").value = descripcion;

  setFormDisabled(true);
  document.getElementById("submitBtn").style.display = "none";
  document.getElementById("cancelBtn").innerText = "Cerrar";
}

// cerrar modal si se hace click en el fondo
window.addEventListener("click", (e)=>{
  const modal = document.getElementById("rolModal");
  if(e.target === modal) closeModal();
});

// autogenerar slug mientras se escribe el nombre (solo en create)
(function autoSlugWireup(){
  const nombreEl = document.getElementById("nombre");
  const slugEl = document.getElementById("slug");
  let touched = false;
  if(!nombreEl || !slugEl) return;

  slugEl.addEventListener("input", ()=>{ touched = slugEl.value.trim().length>0; });
  nombreEl.addEventListener("input", ()=>{
    if(document.getElementById("modalMode").value === "create" && !touched){
      slugEl.value = slugify(nombreEl.value);
    }
  });
})();
