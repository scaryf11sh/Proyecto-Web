(function () {
  'use strict';

  function limpiarDigitosTelefono(valor) {
    return String(valor || '').replace(/\D+/g, '').slice(0, 10);
  }

  function formatearMascaraTelefono(valor) {
    var d = limpiarDigitosTelefono(valor);
    if (d.length === 0) return '';
    if (d.length <= 3) return '(' + d;
    if (d.length <= 6) return '(' + d.slice(0, 3) + ') ' + d.slice(3);
    return '(' + d.slice(0, 3) + ') ' + d.slice(3, 6) + ' ' + d.slice(6, 10);
  }

  function obtenerContenedorCampo(elemento) {
    return elemento ? elemento.closest('.campo') : null;
  }

  function limpiarErrorCampo(control) {
    var campo = obtenerContenedorCampo(control);
    if (!campo) return;
    campo.classList.remove('campo--error');
    var controles = campo.querySelectorAll('input, select, textarea');
    var spanError = campo.querySelector('.campo__error');
    var idError = spanError ? spanError.id : '';

    controles.forEach(function (el) {
      el.removeAttribute('aria-invalid');
      if (idError && el.hasAttribute('aria-describedby')) {
        var ids = el.getAttribute('aria-describedby').split(/\s+/).filter(function (id) {
          return id && id !== idError;
        });
        if (ids.length > 0) {
          el.setAttribute('aria-describedby', ids.join(' '));
        } else {
          el.removeAttribute('aria-describedby');
        }
      }
    });

    if (spanError) {
      spanError.remove();
    }
  }

  function mostrarErrorCampo(control, mensaje) {
    var campo = obtenerContenedorCampo(control);
    if (!campo) return;
    campo.classList.add('campo--error');

    var spanError = campo.querySelector('.campo__error');
    if (!spanError) {
      spanError = document.createElement('span');
      spanError.className = 'campo__error';
      campo.appendChild(spanError);
    }

    var nombreBase = control.id || control.name || 'campo';
    var idError = spanError.id || ('error-' + nombreBase);
    spanError.id = idError;
    spanError.textContent = '! ' + mensaje;

    control.setAttribute('aria-invalid', 'true');
    var actual = control.getAttribute('aria-describedby') || '';
    var lista = actual.split(/\s+/).filter(Boolean);
    if (lista.indexOf(idError) === -1) {
      lista.push(idError);
      control.setAttribute('aria-describedby', lista.join(' '));
    }
  }

  function mensajeRequerido(control, tipoFormulario) {
    var nombre = control.name || control.id || '';
    if (nombre === 'nombre') {
      return tipoFormulario === 'servicio'
        ? 'El nombre del servicio es obligatorio.'
        : 'El nombre completo o razón social es obligatorio.';
    }
    var mapa = {
      correo: 'Ingrese un correo electrónico válido.',
      telefono: 'Ingrese un teléfono válido de 10 dígitos.',
      contrasena: tipoFormulario === 'login'
        ? 'Ingrese su contraseña.'
        : 'La contraseña debe tener al menos 8 caracteres.',
      confirmar_contrasena: 'Las contraseñas no coinciden.',
      aviso_privacidad: 'Debe aceptar el aviso de privacidad.',
      sector: 'Seleccione el tipo de lugar.',
      escala: 'Seleccione el tamaño del trabajo.',
      id_servicio: 'Seleccione un servicio válido.',
      descripcion: 'La descripción es obligatoria.',
      ubicacion: 'La ubicación es obligatoria (máximo 200 caracteres).',
      fecha_visita: 'Requerida para Visita agendada'
    };
    return mapa[nombre] || 'Este campo es obligatorio.';
  }

  function esCorreoValido(valor) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor) && valor.length <= 120;
  }

  function validarControl(control, form) {
    if (control.disabled) {
      limpiarErrorCampo(control);
      return true;
    }

    var tipoFormulario = form ? (form.getAttribute('data-validar') || '') : '';
    var requerido = control.getAttribute('data-requerido') === 'true';
    var tipo = control.getAttribute('data-tipo') || '';
    var minLen = parseInt(control.getAttribute('data-min-len') || '0', 10);
    var coincideSelector = control.getAttribute('data-coincide') || '';

    if (control.type === 'checkbox') {
      if (requerido && !control.checked) {
        mostrarErrorCampo(control, mensajeRequerido(control, tipoFormulario));
        return false;
      }
      limpiarErrorCampo(control);
      return true;
    }

    if (control.type === 'radio') {
      var grupo = form.querySelectorAll('input[type="radio"][name="' + control.name + '"]');
      var algunoMarcado = Array.prototype.some.call(grupo, function (r) { return r.checked; });
      if (!algunoMarcado) {
        mostrarErrorCampo(control, mensajeRequerido(control, tipoFormulario));
        return false;
      }
      limpiarErrorCampo(control);
      return true;
    }

    var valorCrudo = control.value || '';
    var valor = control.type === 'password' ? valorCrudo : valorCrudo.trim();

    if (requerido && valor === '') {
      mostrarErrorCampo(control, mensajeRequerido(control, tipoFormulario));
      return false;
    }

    if (valor !== '' && tipo === 'correo' && !esCorreoValido(valor)) {
      mostrarErrorCampo(control, 'Ingrese un correo electrónico válido.');
      return false;
    }

    if (valor !== '' && tipo === 'telefono' && limpiarDigitosTelefono(valor).length !== 10) {
      mostrarErrorCampo(control, 'Ingrese un teléfono válido de 10 dígitos.');
      return false;
    }

    if (minLen > 0 && valorCrudo.length < minLen) {
      mostrarErrorCampo(control, 'La contraseña debe tener al menos ' + minLen + ' caracteres.');
      return false;
    }

    if (coincideSelector) {
      var objetivo = form.querySelector(coincideSelector);
      if (objetivo && valorCrudo !== objetivo.value) {
        mostrarErrorCampo(control, 'Las contraseñas no coinciden.');
        return false;
      }
    }

    limpiarErrorCampo(control);
    return true;
  }

  function inicializarValidacionFormularios() {
    var formularios = document.querySelectorAll('form[data-validar]');
    formularios.forEach(function (form) {
      form.addEventListener('submit', function (evento) {
        var primerInvalido = null;
        var controles = form.querySelectorAll(
          '[data-requerido="true"], [data-tipo], [data-min-len], [data-coincide]'
        );

        controles.forEach(function (control) {
          if (!validarControl(control, form) && !primerInvalido) {
            primerInvalido = control;
          }
        });

        // En vista cliente, sector y escala son tarjetas radio sin data-requerido en cada input.
        ['sector', 'escala'].forEach(function (nombreGrupo) {
          var radios = form.querySelectorAll('input[type="radio"][name="' + nombreGrupo + '"]');
          if (radios.length > 0 && !validarControl(radios[0], form) && !primerInvalido) {
            primerInvalido = radios[0];
          }
        });

        if (primerInvalido) {
          evento.preventDefault();
          primerInvalido.focus();
        }
      });

      form.addEventListener('input', function (evento) {
        var el = evento.target;
        var campo = obtenerContenedorCampo(el);
        if (campo && campo.classList.contains('campo--error')) {
          validarControl(el, form);
        }
      });

      form.addEventListener('change', function (evento) {
        var el = evento.target;
        var campo = obtenerContenedorCampo(el);
        if (campo && campo.classList.contains('campo--error')) {
          validarControl(el, form);
        }
      });
    });
  }

  function inicializarMascaraTelefono() {
    var camposTel = document.querySelectorAll(
      'input[type="tel"], input[data-tipo="telefono"]'
    );
    camposTel.forEach(function (input) {
      if (input.value) {
        var inicial = limpiarDigitosTelefono(input.value);
        if (inicial.length === 10) {
          input.value = formatearMascaraTelefono(inicial);
        }
      }

      input.addEventListener('input', function () {
        var digitos = limpiarDigitosTelefono(input.value);
        input.value = digitos.length > 0 ? formatearMascaraTelefono(digitos) : '';
      });
    });
  }

  function inicializarTogglePassword() {
    var botones = document.querySelectorAll('[data-toggle-password]');
    botones.forEach(function (boton) {
      boton.addEventListener('click', function () {
        var selector = boton.getAttribute('data-toggle-password');
        var input = selector ? document.querySelector(selector) : null;
        if (!input) return;

        var mostrar = input.type === 'password';
        input.type = mostrar ? 'text' : 'password';
        boton.textContent = mostrar ? 'Ocultar' : 'Mostrar';
        boton.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
      });
    });
  }

  function actualizarTarjetasOpcion() {
    var radios = document.querySelectorAll('.tarjeta-opcion__input');
    radios.forEach(function (radio) {
      var tarjeta = radio.closest('.tarjeta-opcion');
      if (tarjeta) {
        tarjeta.classList.toggle('tarjeta-opcion--activa', radio.checked);
      }
    });
  }

  function inicializarTarjetasOpcion() {
    var selectores = document.querySelectorAll('.tarjeta-opcion__input');
    if (selectores.length === 0) return;

    selectores.forEach(function (el) {
      el.addEventListener('change', actualizarTarjetasOpcion);
    });
    actualizarTarjetasOpcion();
  }

  function inicializarFechaVisitaCondicional() {
    var selectEstado = document.querySelector('select[name="estado"]');
    var inputVisita = document.getElementById('fecha_visita');
    if (!selectEstado || !inputVisita) return;

    var campoVisita = obtenerContenedorCampo(inputVisita);
    var ayudaVisita = campoVisita ? campoVisita.querySelector('.campo__ayuda') : null;

    function sincronizarRequisito() {
      var mantieneVisita = selectEstado.value === '' && !!document.querySelector('.drawer-seccion .badge-estado--visita-agendada');
      var esVisita = selectEstado.value === 'Visita agendada' || mantieneVisita;
      if (esVisita) {
        inputVisita.setAttribute('data-requerido', 'true');
        if (ayudaVisita) ayudaVisita.classList.add('campo__ayuda--destacada');
      } else {
        inputVisita.removeAttribute('data-requerido');
        if (ayudaVisita) ayudaVisita.classList.remove('campo__ayuda--destacada');
        limpiarErrorCampo(inputVisita);
      }
    }

    selectEstado.addEventListener('change', sincronizarRequisito);
    sincronizarRequisito();
  }

  function inicializarConfirmaciones() {
    var formularios = document.querySelectorAll('form[data-confirmar]');
    formularios.forEach(function (form) {
      form.addEventListener('submit', function (evento) {
        var mensaje = form.getAttribute('data-confirmar');
        if (mensaje && !window.confirm(mensaje)) {
          evento.preventDefault();
        }
      });
    });
  }

  function inicializarMenuNavegacion() {
    var botonToggle = document.querySelector('[data-toggle-menu]');
    var menu = document.getElementById('menu-principal');
    if (botonToggle && menu) {
      botonToggle.addEventListener('click', function () {
        var abierto = menu.classList.toggle('barra-nav__menu--abierto');
        botonToggle.setAttribute('aria-expanded', abierto ? 'true' : 'false');
      });
    }

    document.addEventListener('keydown', function (evento) {
      if (evento.key !== 'Escape') return;
      if (botonToggle && menu && menu.classList.contains('barra-nav__menu--abierto')) {
        menu.classList.remove('barra-nav__menu--abierto');
        botonToggle.setAttribute('aria-expanded', 'false');
        botonToggle.focus();
      }
      document.querySelectorAll('details.menu-usuario[open]').forEach(function (detalle) {
        detalle.removeAttribute('open');
      });
    });
  }

  function inicializarAtajoBusqueda() {
    var busqueda = document.querySelector('.barra-filtros__busqueda input');
    if (!busqueda) return;
    document.addEventListener('keydown', function (evento) {
      if (evento.key !== '/' || evento.ctrlKey || evento.metaKey || evento.altKey) return;
      var objetivo = evento.target;
      var tag = objetivo && objetivo.tagName ? objetivo.tagName.toLowerCase() : '';
      if (tag === 'input' || tag === 'textarea' || tag === 'select' || (objetivo && objetivo.isContentEditable)) return;
      evento.preventDefault();
      busqueda.focus();
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    inicializarMenuNavegacion();
    inicializarAtajoBusqueda();
    inicializarMascaraTelefono();
    inicializarTogglePassword();
    inicializarTarjetasOpcion();
    inicializarFechaVisitaCondicional();
    inicializarConfirmaciones();
    inicializarValidacionFormularios();
  });
})();
