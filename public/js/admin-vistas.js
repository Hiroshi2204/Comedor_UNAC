// Pantallas internas del administrador: evita el doble envío y resalta la zona de carga.
(function () {
    document.querySelectorAll('form[data-una-vez]').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (e.defaultPrevented) { return; }          // el usuario canceló el confirm()
            var b = f.querySelector('button[type=submit]');
            if (b) { setTimeout(function () { b.disabled = true; b.textContent = 'Procesando…'; }, 0); }
        });
    });

    var zona = document.getElementById('zona'), archivo = document.getElementById('archivo');
    if (zona && archivo) {
        ['dragenter', 'dragover'].forEach(function (t) {
            zona.addEventListener(t, function (e) { e.preventDefault(); zona.classList.add('arrastrando'); });
        });
        ['dragleave', 'drop'].forEach(function (t) {
            zona.addEventListener(t, function () { zona.classList.remove('arrastrando'); });
        });
        zona.addEventListener('drop', function (e) {
            e.preventDefault();
            if (e.dataTransfer && e.dataTransfer.files.length) { archivo.files = e.dataTransfer.files; }
        });
    }

    // al volver con «atrás» los botones no deben quedar bloqueados
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('form[data-una-vez] button[type=submit]').forEach(function (b) { b.disabled = false; });
    });
})();
