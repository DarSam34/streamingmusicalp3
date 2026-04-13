Justificación Arquitectónica:

Los controladores de la aplicación se encuentran organizados por rol para garantizar una estricta separación de los contextos de seguridad, de la siguiente manera:

- Controladores de Administración: /admin/php/queries.php
- Controladores de Usuarios y Catálogo: /user/php/queries.php

Esta estructura evita la exposición de métodos administrativos (CRUD globales) a las rutas de acceso público o de nivel de usuario, conformando así un entorno mucho más protegido.
