/*
# Nombre: funciones.js
# Ubicación: assets/js/funciones.js
# Descripción: Funciones utilitarias para la tienda y manejo del modo oscuro
*/
// Funciones básicas para la tienda
function mostrarMensaje(mensaje) {
    alert(mensaje);
}

// Configuración de modo oscuro por defecto
document.addEventListener('DOMContentLoaded', () => {
    const cuerpo = document.body;
    const boton = document.getElementById('modoBtn');
    let modo = localStorage.getItem('modo') || 'oscuro';

    function aplicar() {
        if (modo === 'oscuro') {
            cuerpo.classList.add('dark');
            boton.textContent = '☀';
        } else {
            cuerpo.classList.remove('dark');
            boton.textContent = '🌙';
        }
    }

    aplicar();

    boton.addEventListener('click', () => {
        modo = modo === 'oscuro' ? 'claro' : 'oscuro';
        localStorage.setItem('modo', modo);
        aplicar();
    });
});
