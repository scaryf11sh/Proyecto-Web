# OEM Solicitudes

Sistema web para la gestión de solicitudes de servicio eléctrico de Obras Eléctricas Mendivil.

![PHP 8](https://img.shields.io/badge/PHP-8-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL%20%2F%20MariaDB-4479A1?style=flat-square&logo=mysql&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=flat-square&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat-square&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat-square&logo=javascript&logoColor=black)
![Licencia MIT](https://img.shields.io/badge/Licencia-MIT-3DA639?style=flat-square)

| Dato | Valor |
| --- | --- |
| Universidad | Universidad Autónoma de Baja California (UABC) |
| Facultad | Facultad de Ciencias Químicas e Ingeniería (FCQI) |
| Materia | Aplicaciones Web |
| Grupo | 371 |
| Alumno | Mendivil Martinez Jaime |
| Matrícula | 2200608 |
| Profesora | Barriba Cazares Itzel |

## Descripción

OEM Solicitudes es una aplicación web desarrollada como proyecto escolar para Obras Eléctricas Mendivil, empresa de servicios eléctricos en Tijuana, Baja California. El sistema permite que los clientes registren solicitudes de servicio eléctrico y que el personal administrativo las consulte, actualice y dé seguimiento desde un panel centralizado.

La aplicación se ejecuta sobre PHP 8 con una base de datos MySQL o MariaDB, y la interfaz se construye con HTML5, CSS3 y JavaScript sin frameworks de front-end. Cada solicitud recibe un folio con el formato `OEM-AA-NNNN`, lo que facilita su identificación y seguimiento.

## Funciones principales

- Registro e inicio de sesión con roles de Cliente y Administrador.
- Alta, consulta, edición y eliminación de solicitudes.
- Catálogo de servicios eléctricos disponibles.
- Asignación automática de prioridad según los criterios definidos en la solicitud.
- Generación de folio con el formato `OEM-AA-NNNN`.
- Flujo de estados para el seguimiento de cada solicitud.
- Búsqueda y filtros sobre el listado de solicitudes.
- Reportes con gráficas sobre la operación del servicio.
- Panel del cliente con seguimiento del estado de sus solicitudes.

## Tecnologías

| Capa | Tecnología |
| --- | --- |
| Servidor | PHP 8 |
| Base de datos | MySQL / MariaDB |
| Estructura | HTML5 |
| Estilos | CSS3 |
| Interacción | JavaScript |
| Entorno local | XAMPP |

## Estructura del proyecto

```text
.
├── index.php
├── crear.php
├── editar.php
├── eliminar.php
├── includes/
│   ├── database.php
│   └── funciones.php
├── css/
├── js/
├── img/
├── sql/
│   └── oem_solicitudes.sql
└── docs/
```

## Instalación en XAMPP

1. Copiar la carpeta del proyecto dentro de `htdocs` de la instalación de XAMPP.
2. Iniciar los servicios de Apache y MySQL desde el panel de XAMPP.
3. Abrir phpMyAdmin e importar el archivo `sql/oem_solicitudes.sql`, que crea la base de datos y sus tablas.
4. Verificar los datos de conexión en `includes/database.php`; conviene ajustarlos si el usuario o la contraseña de MySQL difieren de los valores por defecto.
5. Abrir `http://localhost/Proyecto-Web/` en el navegador para acceder a la aplicación.

## Estado del proyecto

En desarrollo. Las funciones descritas se encuentran en implementación y la estructura puede cambiar conforme avance el curso.

## Licencia

Este proyecto se distribuye bajo la Licencia MIT. El texto completo está disponible en [LICENSE](LICENSE).
