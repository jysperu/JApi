# JApi

Tiny PHP Framework

### Instalación

El framework trabaja con <a href="https://es.wikipedia.org/wiki/URL_sem%C3%A1ntica" target="_blank">Url Amigable</a> por lo que siempre será requerido hacer la configuración base para que funcione


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

Para una instalación rápida es suficiente copiar la estructura de carpetas de JApi al directorio público <br>
`/home/username/public_html`<br>
`C:\xampp\htdocs`<br>

```php
<?php // index.php
define ('HOMEPATH', __DIR__);
require_once HOMEPATH . '/app.php';
```

Para una instalación mas ordenada se puede copiar la estructura de JApi en un subdirectorio del proyecto pero se debe definir la variable *APPPATH* con la ruta de la aplicación en el index.php <br>
```php
<?php // index.php
define ('HOMEPATH', __DIR__);
define ('COREPATH', HOMEPATH . '/JApi');
define ('APPPATH' , HOMEPATH . '/APP' );
require_once COREPATH . '/app.php';
```

Para una instalación mas segura se puede copiar la estructura de JApi e incluso la ruta de la aplicación fuera del directorio público <br>
```php
<?php // index.php
define ('HOMEPATH', __DIR__);
define ('COREPATH', '/home/username/private/directory/JApi');
define ('APPPATH' , '/home/username/another/private/directory/APP' );
require_once COREPATH . '/app.php';
```


### Extensiones Requeridas y/o Recomendadas

[X] curl
[X] gd
[X] fileinfo
[X] intl
[X] mbstring
[X] mysqli
[ ] exif
[ ] soap


### Configuración Recomendada de PHP

[X] display_errors = Off
[X] display_startup_errors = Off
[X] error_reporting = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED
[X] expose_php = Off
[X] file_uploads = On
[X] allow_url_fopen = On
[ ] log_errors = Off ;; JApi tiene una función para alojar los logs en base datos o archivos
[ ] max_input_time = -1
[ ] short_open_tag = Off
[ ] max_execution_time = 30 ;; Recomendado en ciertas aplicaciones hasta: 300
[ ] max_input_vars = 3000
[ ] post_max_size = 0
[ ] upload_max_filesize = 1024M
[ ] max_file_uploads = 100
[ ] date.timezone = America/Lima
[ ] intl.default_locale = es_PE
[ ] mysqli.max_persistent = -1
[ ] mysqli.allow_persistent = On
[ ] mysqli.default_port = 3306
[ ] session.name = _japisess
[ ] session.auto_start = 1
[ ] session.cookie_lifetime = 0
[ ] mbstring.internal_encoding = UTF-8



### Directorio de Aplicación

Un directorio de aplicación es aquel que contiene todo el código a procesar para las vistas, controladores y/o modelos.

Se puede alojar mas de 1 directorio de aplicación con lo que permite ejecutar un código modular basado en una estructura de directorios.

Siempre se definirá un `COREPATH` y un `APPPATH` (*ambos pueden ser la misma ruta*).

Se puede agregar mas directorios de aplicación en el archivo `/init.php` la cual solo puede estar dentro de la carpeta `APPPATH`.

El nro de orden de lectura del `APPPATH` es **25** y el del `COREPATH` es de **75**, lo que significa que primero se procesará siempre la ruta APPPATH.

Se pueden añadir mas directorio de aplicación con un orden de lectura menor/mayor a los ya definidos para los directorios base, tambíen es posible cambiar el orden de los existentes incluyendo los directorios APPPATH y COREPATH.


### Estructura de Directorio de Aplicación

```
.
├── configs
│   ├── config.php
│   ├── classes
│   ├── functions
│   ├── translates
│   ├── libs
├── objects
├── prerequests
├── requests
├── responses
├── snippets
├── vendor
│   └── autoload.php
├── install
│   └── install.php
└── app.php
```

*Donde*:

Directorio/Archivo | Funcionalidad
---|---
/configs/config.php | Aloja todas las configuraciones las cuales se sobreescribiran según la lectura de directorios de aplicación.
/configs/classes | Aloja todas los archivos de clases que puede llamar el sistema, los mismos son llamados con el Autoload por defecto.
/configs/functions | Aloja todas los archivos de funciones que puede utilizar el sistema. Todos los archivos son llamados al inicio del proceso.
/configs/translates | Aloja todas los archivos de traducciones del sistema. Solo es llamado el archivo del idioma utilizado.
/configs/libs | Aloja todas los archivos de librerías que puede requerir el sistema, se debe indicar el Autoload.
/objects | Aloja todas los archivos de objetos que interactuan con la base datos.
/prerequests | Aloja todas los archivos que se ejecutarán previo al request. Sirven para pre-alojar datos o validar los mismos dando como resultado el cambio de vista o request a ejecutar.
/requests | Aloja todas los archivos que se ejecutarán tras la llamada. Los mismos pueden retornar información directamente como response en caso de llamadas via AJAX u otros.
/responses | Aloja todas los archivos que retornaran las pantallas/vistas con la información.
/snippets | Aloja todas los archivos de micro-códigos las cuales pueden ser utilizados recurrentemente en toda la aplicación y puede servir para mostrar widgets o realizar un conjunto de procedimientos.
/app.php | Archivo inicializador de cada directorio de aplicacion
/vendor | Aloja todas los archivos de librerías externas que puede requerir el sistema, se utiliza el Autoload del archivo ```/vendor/autoload.php```.
/install | Si el directorio existe se procede con la instalación previo a la ejecución de los validadores de ruta. ```/install/install.php```


### Proceso
- [X] Usuario genera la solicitud mediante navegador (eg: http://japi.net/usuario/jrojash)
- [X] Se inician las variables globales (accesibilidad de carpetas en todo momento)
- [X] Se registrar el control de errores (no mostrar errores al usuario final, mantenerlo en modo desarrollo)
    -  [X] Guardar los errores en la base datos
- [X] Se registra el hook del shutdown (para que desconecte la base datos en caso de haberla sido iniciada y/o limpie el buffer)
- [X] Se llama al `/init.php` del _APPPATH_ (ello para poder añadir mas directorios de aplicación en caso sea necesario)
- [X] Se lista todos los directorios de aplicación en el orden según dependencia (incluyendo el _APPPATH_).
- [X] Se registra el autoload para realizar las busquedas de clases dentro de <br>(_incluyendo en los directorios de aplicación adicionales_):
    - [X] `/objects`.<br>El namespace base debe ser `Object`.
    - [X] `/prerequests`.<br>El namespace base debe ser `PreRequest`.
    - [X] `/requests`.<br>El namespace base debe ser `Request`.
    - [X] `/responses`.<br>El namespace base debe ser `Response`.
    - [X] `/configs/classes`.
- [X] Recorrer Directorios de aplicación (incluyendo el _APPPATH_) en modo inversa
    - [X] Se leen todos los archivos de funciones dentro de la carpeta `/configs/functions`
    - [X] Se lee el archivo `/vendor/autoload.php`
    - [X] Se lee el archivo `/configs/config.php`
    - [X] Se ejecuta el archivo `/app.php`

- [X] Se procesa el uri de la solicitud<br>En caso de ser llamado como comando se ejecuta el primer parametro enviado (eg: /usuario/jrojash)
    - [X] Se busca parametro de idioma
    - [X] Se redefine el uri formateado con los parametros solicitados; caso contrario, se redefine el uri por defecto considerando a los números como IDs 
    - [X] Se ejecuta el PreRequest del Uri
    - [X] Se ejecuta el Request de Uri
    - [X] Se llama al response según el tipo de Vista requerida (Html, JSON ó Archivo)
