(function () {
  'use strict';

  function leerDatosReportes() {
    var nodo = document.getElementById('reportes-datos');
    if (!nodo) return null;
    try {
      return JSON.parse(nodo.textContent || '{}');
    } catch (e) {
      return null;
    }
  }

  function calcularRangoPeriodo(atajo, fechaReferencia) {
    var ref = /^\d{4}-\d{2}-\d{2}$/.test(fechaReferencia || '') ? fechaReferencia : '2026-10-25';
    var partes = ref.split('-');
    var anio = parseInt(partes[0], 10);
    var mes = parseInt(partes[1], 10);

    if (atajo === 'mes') {
      return {
        desde: partes[0] + '-' + partes[1] + '-01',
        hasta: ref
      };
    }

    if (atajo === 'trimestre') {
      var mesInicio = mes - 3;
      var anioInicio = anio;
      if (mesInicio <= 0) {
        mesInicio += 12;
        anioInicio -= 1;
      }
      var mesTexto = mesInicio < 10 ? '0' + mesInicio : String(mesInicio);
      return {
        desde: anioInicio + '-' + mesTexto + '-01',
        hasta: ref
      };
    }

    if (atajo === 'anio') {
      return {
        desde: partes[0] + '-01-01',
        hasta: ref
      };
    }

    return null;
  }

  function inicializarAtajosPeriodo(datos) {
    var botones = document.querySelectorAll('[data-atajo-periodo]');
    var form = document.getElementById('form-reportes');
    var inputDesde = document.getElementById('filtro-desde');
    var inputHasta = document.getElementById('filtro-hasta');
    if (botones.length === 0 || !inputDesde || !inputHasta) return;

    var fechaRef = datos && datos.fecha_referencia ? datos.fecha_referencia : '2026-10-25';

    botones.forEach(function (boton) {
      boton.addEventListener('click', function () {
        var atajo = boton.getAttribute('data-atajo-periodo');
        var rango = calcularRangoPeriodo(atajo, fechaRef);
        if (!rango) return;

        inputDesde.value = rango.desde;
        inputHasta.value = rango.hasta;

        botones.forEach(function (b) {
          b.classList.toggle('grupo-segmentado__boton--activo', b === boton);
        });

        if (form) {
          form.submit();
        }
      });
    });
  }

  function dibujarGraficaI1(canvas, filas) {
    if (!canvas || typeof canvas.getContext !== 'function') return;
    var ctx = canvas.getContext('2d');
    if (!ctx) return;

    var contenedor = canvas.parentElement;
    var anchoCss = Math.max(260, (contenedor && contenedor.clientWidth) ? contenedor.clientWidth : 520);
    var altoCss = anchoCss < 360 ? 190 : 220;
    var dpr = window.devicePixelRatio || 1;

    canvas.width = Math.round(anchoCss * dpr);
    canvas.height = Math.round(altoCss * dpr);
    canvas.style.width = anchoCss + 'px';
    canvas.style.height = altoCss + 'px';
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.clearRect(0, 0, anchoCss, altoCss);

    var datos = Array.isArray(filas) ? filas : [];
    var padIzq = 36;
    var padDer = 18;
    var padArr = 26;
    var padAba = 32;
    var anchoArea = anchoCss - padIzq - padDer;
    var altoArea = altoCss - padArr - padAba;

    var maxDato = datos.reduce(function (acc, item) {
      var c = Number(item && item.cantidad) || 0;
      return c > acc ? c : acc;
    }, 0);
    var maxEje = Math.max(25, Math.ceil(maxDato / 5) * 5);
    var pasoEje = Math.max(5, Math.round(maxEje / 5));

    ctx.font = '600 12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
    ctx.textBaseline = 'middle';

    for (var valor = 0; valor <= maxEje; valor += pasoEje) {
      var proporcion = valor / maxEje;
      var y = padArr + altoArea - proporcion * altoArea;

      ctx.strokeStyle = valor === 0 ? '#111111' : '#d0d5dd';
      ctx.lineWidth = valor === 0 ? 1.5 : 1;
      ctx.beginPath();
      ctx.moveTo(padIzq, y);
      ctx.lineTo(padIzq + anchoArea, y);
      ctx.stroke();

      ctx.fillStyle = '#555555';
      ctx.textAlign = 'right';
      ctx.fillText(String(valor), padIzq - 8, y);
    }

    if (datos.length === 0) return;

    var puntos = datos.map(function (fila, i) {
      var x = datos.length === 1
        ? padIzq + anchoArea / 2
        : padIzq + (i / (datos.length - 1)) * anchoArea;
      var cantidad = Number(fila.cantidad) || 0;
      var y = padArr + altoArea - (cantidad / maxEje) * altoArea;
      return {
        x: x,
        y: y,
        etiqueta: String(fila.etiqueta || ''),
        cantidad: cantidad
      };
    });

    ctx.strokeStyle = '#111111';
    ctx.lineWidth = 2.5;
    ctx.lineJoin = 'round';
    ctx.lineCap = 'round';
    ctx.beginPath();
    puntos.forEach(function (p, i) {
      if (i === 0) ctx.moveTo(p.x, p.y);
      else ctx.lineTo(p.x, p.y);
    });
    ctx.stroke();

    puntos.forEach(function (p) {
      ctx.fillStyle = '#f2b705';
      ctx.strokeStyle = '#111111';
      ctx.lineWidth = 2;
      ctx.beginPath();
      ctx.arc(p.x, p.y, 4.5, 0, Math.PI * 2);
      ctx.fill();
      ctx.stroke();

      ctx.fillStyle = '#111111';
      ctx.font = '700 12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'bottom';
      ctx.fillText(String(p.cantidad), p.x, Math.max(14, p.y - 8));

      ctx.fillStyle = '#555555';
      ctx.font = '600 12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
      ctx.textBaseline = 'top';
      ctx.fillText(p.etiqueta, p.x, padArr + altoArea + 8);
    });
  }

  function actualizarGraficaI2(contenedor, filas) {
    if (!contenedor) return;

    var modificadores = [
      '',
      'barra-horizontal--secundaria',
      'barra-horizontal--terciaria',
      'barra-horizontal--cuaternaria'
    ];
    var barras = contenedor.querySelectorAll('.barra-horizontal');
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    barras.forEach(function (barra, idx) {
      var mod = modificadores[idx] || 'barra-horizontal--cuaternaria';
      if (mod) barra.classList.add(mod);
      if (!reduceMotion) {
        barra.style.transition = 'width 160ms ease-out';
      }
      if (Array.isArray(filas) && filas[idx] && typeof filas[idx].porcentaje !== 'undefined') {
        barra.style.width = Math.max(0, Math.min(100, Number(filas[idx].porcentaje) || 0)) + '%';
      }
    });
  }

  function enriquecerTablaI3(filas) {
    var contenedorI3 = document.getElementById('grafica-i3');
    if (!contenedorI3) return;

    var filasTr = contenedorI3.querySelectorAll('tbody tr');
    filasTr.forEach(function (tr, idx) {
      var celdas = tr.querySelectorAll('td');
      if (celdas.length < 3 || celdas[0].classList.contains('texto-negrita')) return;
      if (celdas[2].querySelector('.barra-estado-mini')) return;

      var pct = (Array.isArray(filas) && filas[idx])
        ? Number(filas[idx].porcentaje) || 0
        : parseFloat(celdas[2].textContent) || 0;

      var barra = document.createElement('span');
      barra.className = 'barra-estado-mini';
      barra.style.width = Math.max(2, Math.round(pct * 1.1)) + 'px';
      barra.setAttribute('aria-hidden', 'true');
      celdas[2].insertBefore(barra, celdas[2].firstChild);
    });
  }

  function enriquecerGraficaI4(datos) {
    var contenedor = document.getElementById('grafica-i4');
    if (!contenedor) return;

    var filas = (datos && Array.isArray(datos.i4_tiempo_visita)) ? datos.i4_tiempo_visita : [];
    if (filas.length === 0) {
      contenedor.querySelectorAll('tbody tr').forEach(function (tr) {
        var celdas = tr.querySelectorAll('td');
        if (celdas.length >= 3 && !celdas[0].classList.contains('texto-negrita')) {
          filas.push({
            sector: celdas[0].textContent.trim(),
            con_visita: parseInt(celdas[1].textContent.trim(), 10) || 0,
            dias_promedio: parseFloat(celdas[2].textContent.trim()) || 0
          });
        }
      });
    }

    if (filas.length === 0 || contenedor.querySelector('svg[data-grafica="i4"]')) return;

    var maxDias = filas.reduce(function (acc, f) {
      return f.dias_promedio > acc ? f.dias_promedio : acc;
    }, 5);

    var ns = 'http://www.w3.org/2000/svg';
    var altoSvg = filas.length * 30 + 10;
    var svg = document.createElementNS(ns, 'svg');
    svg.setAttribute('viewBox', '0 0 320 ' + altoSvg);
    svg.setAttribute('width', '100%');
    svg.setAttribute('height', String(altoSvg));
    svg.setAttribute('role', 'img');
    svg.setAttribute('aria-label', 'Días promedio hasta la visita por tipo de lugar');
    svg.setAttribute('data-grafica', 'i4');
    svg.style.marginBottom = '0.75rem';

    filas.forEach(function (f, i) {
      var y = 6 + i * 28;
      var anchoBarra = maxDias > 0 ? Math.max(4, Math.round((f.dias_promedio / maxDias) * 155)) : 4;

      var textoSector = document.createElementNS(ns, 'text');
      textoSector.setAttribute('x', '0');
      textoSector.setAttribute('y', String(y + 13));
      textoSector.setAttribute('fill', '#111111');
      textoSector.setAttribute('font-size', '12');
      textoSector.setAttribute('font-weight', '600');
      textoSector.textContent = f.sector;
      svg.appendChild(textoSector);

      var rect = document.createElementNS(ns, 'rect');
      rect.setAttribute('x', '92');
      rect.setAttribute('y', String(y + 2));
      rect.setAttribute('width', String(anchoBarra));
      rect.setAttribute('height', '14');
      rect.setAttribute('rx', '3');
      rect.setAttribute('fill', i === 0 ? '#111111' : (i === 1 ? '#555555' : '#7a7f87'));
      svg.appendChild(rect);

      var textoValor = document.createElementNS(ns, 'text');
      textoValor.setAttribute('x', '316');
      textoValor.setAttribute('y', String(y + 13));
      textoValor.setAttribute('text-anchor', 'end');
      textoValor.setAttribute('fill', '#555555');
      textoValor.setAttribute('font-size', '12');
      textoValor.setAttribute('font-weight', '600');
      textoValor.textContent = f.dias_promedio.toFixed(1) + ' d';
      svg.appendChild(textoValor);
    });

    contenedor.insertBefore(svg, contenedor.firstChild);
  }

  document.addEventListener('DOMContentLoaded', function () {
    var datos = leerDatosReportes();
    inicializarAtajosPeriodo(datos);
    if (!datos) return;

    var canvasI1 = document.getElementById('grafica-i1');
    var contenedorI2 = document.getElementById('grafica-i2');

    function renderizarTodo() {
      if (canvasI1) {
        dibujarGraficaI1(canvasI1, datos.i1_por_mes || []);
      }
      if (contenedorI2) {
        actualizarGraficaI2(contenedorI2, datos.i2_por_canal || []);
      }
      enriquecerTablaI3(datos.i3_por_estado || []);
      enriquecerGraficaI4(datos);
    }

    renderizarTodo();

    var rafId = null;
    window.addEventListener('resize', function () {
      if (rafId) window.cancelAnimationFrame(rafId);
      rafId = window.requestAnimationFrame(function () {
        if (canvasI1) {
          dibujarGraficaI1(canvasI1, datos.i1_por_mes || []);
        }
      });
    });
  });
})();
