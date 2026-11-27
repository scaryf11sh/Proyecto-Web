<?php
$variante_nav = $variante_nav ?? 'app';
$ruta_raiz = $ruta_raiz ?? (function_exists('prefijo_raiz') ? prefijo_raiz() : '');
?>
</main>
<?php if ($variante_nav === 'publica'): ?>
<footer class="pie-pagina">
  <div class="pie-pagina__interior">
    <div class="pie-pagina__columna">
      <p class="pie-pagina__titulo">OEM Solicitudes</p>
      <p>Obras Eléctricas Mendivil</p>
    </div>
    <div class="pie-pagina__columna">
      <p class="pie-pagina__titulo">Contacto</p>
      <p>(664) 000 0000</p>
      <p>contacto@ejemplo.com</p>
    </div>
    <div class="pie-pagina__columna">
      <p class="pie-pagina__titulo">Oficina</p>
      <p>Dirección de la oficina</p>
      <p>Tijuana, B.C.</p>
    </div>
    <div class="pie-pagina__columna">
      <p class="pie-pagina__titulo">Legal</p>
      <details class="aviso-privacidad">
        <summary>Aviso de privacidad</summary>
        <p>Los datos que registra se usan solo para atender sus solicitudes de servicio con Obras Eléctricas Mendivil.</p>
      </details>
      <p class="pie-pagina__legal">© 2026 Obras Eléctricas Mendivil</p>
    </div>
  </div>
</footer>
<?php else: ?>
<footer class="pie-pagina pie-pagina--compacto">
  <p>OEM Solicitudes · © 2026 Obras Eléctricas Mendivil</p>
</footer>
<?php endif; ?>
<script src="<?= e($ruta_raiz) ?>js/app.js?v=<?= filemtime(__DIR__ . '/../js/app.js') ?>"></script>
<script src="<?= e($ruta_raiz) ?>js/reportes.js?v=<?= filemtime(__DIR__ . '/../js/reportes.js') ?>"></script>
</body>
</html>
