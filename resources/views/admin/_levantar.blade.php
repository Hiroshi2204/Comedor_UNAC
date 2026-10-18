{{-- Botón para levantar una sanción activa. Requiere $s (Sancion). --}}
<details class="editar">
    <summary>Levantar</summary>
    <form method="POST" action="{{ route('admin.sanciones.levantar', $s) }}" data-una-vez
          onsubmit="return confirm('¿Levantar la sanción de este estudiante? Su conteo de inasistencias volverá a cero.')">
        @csrf
        <div class="campo" style="margin-top:12px; min-width:240px">
            <label for="nota-{{ $s->id }}">Motivo (opcional)</label>
            <textarea id="nota-{{ $s->id }}" name="nota" rows="2" maxlength="250" placeholder="Ej. Restitución autorizada por OBU"></textarea>
        </div>
        <div class="acciones" style="margin-top:10px"><button type="submit" class="btn btn-primario btn-mini">Confirmar</button></div>
    </form>
</details>
