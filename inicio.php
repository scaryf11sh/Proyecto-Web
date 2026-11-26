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
<section class="hero">
  <div class="hero__interior">
    <div class="hero__contenido">
      <p class="hero__marca">Obras Eléctricas Mendivil · Tijuana</p>
      <h1 class="hero__titulo">Servicio eléctrico con seguimiento en línea</h1>
      <p class="hero__texto">
        Instalaciones, reparaciones y mantenimiento eléctrico para industrias, comercios y hogares de Tijuana.
        Envíe su solicitud desde esta página y revise en cualquier momento en qué etapa va.
        Sin llamadas de seguimiento.
      </p>
      <div class="hero__acciones">
<?php if ($usuario): ?>
        <a href="panel.php" class="btn btn--primario">Ir a mi panel</a>
<?php else: ?>
        <a href="registro.php" class="btn btn--primario">Crear cuenta</a>
        <a href="login.php" class="btn btn--secundario">Iniciar sesión</a>
<?php endif; ?>
      </div>
    </div>
    <figure class="hero__imagen">
      <img src="img/hero-instalacion.svg" width="640" height="520" alt="Tablero eléctrico abierto con interruptores alineados y cables que salen por conductos">
    </figure>
  </div>
</section>

<section class="seccion" id="como-funciona" aria-labelledby="titulo-como-funciona">
  <div class="seccion__interior seccion__interior--lateral">
    <h2 class="seccion__titulo" id="titulo-como-funciona">Cómo funciona</h2>
    <ol class="pasos">
      <li class="paso">
        <span class="paso__numero" aria-hidden="true">1</span>
        <h3 class="paso__titulo">Enviar la solicitud</h3>
        <p class="paso__texto">Describa el trabajo, el tipo de lugar y su tamaño. Queda registrada en línea sin necesidad de llamar.</p>
      </li>
      <li class="paso">
        <span class="paso__numero" aria-hidden="true">2</span>
        <h3 class="paso__titulo">Visita sin costo</h3>
        <p class="paso__texto">Un técnico visita el lugar sin costo para revisar el trabajo y definir qué se necesita.</p>
      </li>
      <li class="paso">
        <span class="paso__numero" aria-hidden="true">3</span>
        <h3 class="paso__titulo">Presupuesto y seguimiento</h3>
        <p class="paso__texto">Reciba el presupuesto y consulte en el portal cómo avanza su solicitud.</p>
      </li>
    </ol>
  </div>
</section>

<section class="seccion seccion--alt" id="sectores" aria-labelledby="titulo-sectores">
  <div class="seccion__interior">
    <h2 class="seccion__titulo" id="titulo-sectores">Sectores</h2>
    <div class="sectores-grid">
      <article class="tarjeta-sector">
        <img class="tarjeta-sector__imagen" src="img/sector-industrial.svg" width="600" height="400" alt="Nave industrial con techo en dientes de sierra y torre de alta tensión" loading="lazy">
        <h3 class="tarjeta-sector__titulo">Industrial</h3>
        <p class="tarjeta-sector__texto">Plantas, naves y parques industriales</p>
      </article>
      <article class="tarjeta-sector">
        <img class="tarjeta-sector__imagen" src="img/sector-comercial.svg" width="600" height="400" alt="Local comercial con toldo rayado, puerta y aparador" loading="lazy">
        <h3 class="tarjeta-sector__titulo">Comercial</h3>
        <p class="tarjeta-sector__texto">Locales, tiendas, plazas y oficinas</p>
      </article>
      <article class="tarjeta-sector">
        <img class="tarjeta-sector__imagen" src="img/sector-residencial.svg" width="600" height="400" alt="Casa con techo a dos aguas, ventanas y medidor de luz" loading="lazy">
        <h3 class="tarjeta-sector__titulo">Residencial</h3>
        <p class="tarjeta-sector__texto">Casas y residencias</p>
      </article>
    </div>
  </div>
</section>

<section class="seccion seccion--cta" aria-labelledby="titulo-cta">
  <div class="seccion__interior">
    <div class="banda-cta">
      <div>
        <h2 class="banda-cta__titulo" id="titulo-cta">Solicitud de servicio en línea</h2>
        <p class="banda-cta__texto">Cree su cuenta, envíe la primera solicitud y siga su avance desde el panel.</p>
      </div>
<?php if ($usuario): ?>
      <a href="panel.php" class="btn btn--primario">Ir a mi panel</a>
<?php else: ?>
      <a href="registro.php" class="btn btn--primario">Crear cuenta</a>
<?php endif; ?>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
