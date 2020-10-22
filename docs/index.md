## JApi

Tiny PHP FrameWork

### Instalación

El framework trabaja con [Url Amigable](https://es.wikipedia.org/wiki/URL_sem%C3%A1ntica) por lo que siempre será requerido hacer la configuración base para que funcione


*Si se trabaja con Apache*:
```apache
## .htaccess
<IfModule mod_rewrite.c>
    RewriteEngine on
    RewriteRule ^index\.php$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    Rewriterule . index.php [L]
</IfModule>
```

*Si se trabaja con NGinx*:
```nginx
## /etc/nginx/conf.d/default.conf
location / {
    try_files $uri $uri/ /index.php?$args;
}
```

Para una instalación rápida es suficiente copiar la estructura de carpetas de JApp al directorio público <br>
```/home/username/public_html```<br>
```C:\xampp\htdocs```<br>

```php
<?php // index.php
define ('HOMEPATH', __DIR__);
require_once HOMEPATH . '/app.php';
```

Para una instalación mas ordenada se puede copiar la estructura de JApp en un subdirectorio del proyecto pero se debe definir la variable *APPPATH* con la ruta de la aplicación en el index.php <br>
```php
<?php // index.php
define ('HOMEPATH', __DIR__);
define ('COREPATH', HOMEPATH . '/JApp');
define ('APPPATH' , HOMEPATH . '/APP' );
require_once COREPATH . '/app.php';
```

Para una instalación mas segura se puede copiar la estructura de JApp e incluso la ruta de la aplicación fuera del directorio público <br>
```php
<?php // index.php
define ('HOMEPATH', __DIR__);
define ('COREPATH', '/home/username/private/directory/JApp');
define ('APPPATH' , '/home/username/another/private/directory/APP' );
require_once COREPATH . '/app.php';
```

### Soporte o Contacto

¿Tienes problemas con el framework? [Póngase en contacto con el servicio de asistencia] (https://www.jys.pe/contacto) y lo ayudaremos a resolverlo.
