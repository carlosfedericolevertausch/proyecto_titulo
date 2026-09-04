/**
 * Script de integración de SweetAlert2 con diseño Gobierno de Chile
 */

document.addEventListener('DOMContentLoaded', function() {
  // Confirmación genérica de eliminación para formularios/botones con clase .btn-confirmar-eliminar
  const botonesEliminar = document.querySelectorAll('.btn-confirmar-eliminar');
  
  botonesEliminar.forEach(boton => {
    boton.addEventListener('click', function(e) {
      e.preventDefault();
      const url = this.getAttribute('href') || this.dataset.url;
      const itemNombre = this.dataset.nombre || 'este registro';

      Swal.fire({
        title: '¿Está seguro de eliminar?',
        html: `Se eliminará permanentemente: <strong>${itemNombre}</strong>. Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#eb3c46',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-trash"></i> Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          if (this.tagName === 'FORM' || this.type === 'submit') {
            this.closest('form').submit();
          } else if (url) {
            window.location.href = url;
          }
        }
      });
    });
  });
});

/**
 * Función para disparar notificaciones SweetAlert2 personalizadas
 */
function notificar(titulo, texto, icono = 'info') {
  Swal.fire({
    title: titulo,
    text: texto,
    icon: icono,
    confirmButtonColor: '#0f69b4',
    confirmButtonText: 'Entendido'
  });
}
