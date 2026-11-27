<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funciones.php';

iniciar_sesion();
$usuario = usuario_actual();

$titulo = 'Inicio';
$pagina_activa = 'inicio';
$variante_nav = 'publica';
$body_class = 'pagina-publica';
$ruta_raiz = '';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/nav.php';
?>
<div class="inicio">
  <section class="inicio__hero">
    <div class="inicio__cuerpo">
    <p class="inicio__marca">Obras Eléctricas Mendivil · Tijuana</p>
    <h1 class="inicio__titulo">Servicio eléctrico con seguimiento en línea</h1>
    <p class="inicio__texto">Envíe su solicitud desde esta página y revise en qué etapa va, sin llamadas de seguimiento.</p>
    <div class="inicio__acciones">
<?php if ($usuario): ?>
      <a href="panel.php" class="btn btn--primario">Ir a mi panel</a>
<?php else: ?>
      <a href="registro.php" class="btn btn--primario">Crear cuenta</a>
      <a href="login.php" class="btn btn--secundario">Iniciar sesión</a>
<?php endif; ?>
    </div>
    </div>
    <picture class="inicio__captura">
      <source srcset="img/panel-admin.webp" type="image/webp">
      <img src="img/panel-admin.png" width="1280" height="800" alt="Panel de administración de OEM Solicitudes con indicadores y solicitudes por atender">
    </picture>
  </section>

  <div class="inicio__columnas">
  <section class="inicio__seccion" id="como-funciona" aria-labelledby="titulo-como-funciona">
    <h2 class="inicio__subtitulo" id="titulo-como-funciona">Cómo funciona</h2>
    <ol class="inicio__lista">
      <li class="inicio__item"><span class="inicio__n" aria-hidden="true">1</span><h3>Enviar la solicitud</h3><p>Describa el trabajo y el lugar; queda registrada en línea.</p></li>
      <li class="inicio__item"><span class="inicio__n" aria-hidden="true">2</span><h3>Visita sin costo</h3><p>Un técnico revisa el lugar y define qué se necesita.</p></li>
      <li class="inicio__item"><span class="inicio__n" aria-hidden="true">3</span><h3>Presupuesto y seguimiento</h3><p>Reciba el presupuesto y consulte el avance en el portal.</p></li>
    </ol>
  </section>

  <section class="inicio__seccion" id="sectores" aria-labelledby="titulo-sectores">
    <h2 class="inicio__subtitulo" id="titulo-sectores">Sectores</h2>
    <ul class="inicio__lista">
      <li class="inicio__item inicio__item--simple"><h3>Industrial</h3><p>Plantas, naves y parques industriales.</p></li>
      <li class="inicio__item inicio__item--simple"><h3>Comercial</h3><p>Locales, tiendas, plazas y oficinas.</p></li>
      <li class="inicio__item inicio__item--simple"><h3>Residencial</h3><p>Casas y residencias.</p></li>
    </ul>
  </section>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
