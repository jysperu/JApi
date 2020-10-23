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

Para una instalación rápida es suficiente copiar la estructura de carpetas de JApp al directorio público <br>
`/home/username/public_html`<br>
`C:\xampp\htdocs`<br>

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
- [x] Usuario genera la solicitud mediante navegador (eg: http://japp.net/usuario/jrojash)
- [x] Se inician las variables globales (accesibilidad de carpetas en todo momento)
- [x] Se registrar el control de errores (no mostrar errores al usuario final, mantenerlo en modo desarrollo)
    -  [x] Guardar los errores en la base datos
- [x] Se registra el hook del shutdown (para que desconecte la base datos en caso de haberla sido iniciada y/o limpie el buffer)
- [x] Se llama al `/init.php` del _APPPATH_ (ello para poder añadir mas directorios de aplicación en caso sea necesario)
- [x] Se lista todos los directorios de aplicación en el orden según dependencia (incluyendo el _APPPATH_).
- [x] Se registra el autoload para realizar las busquedas de clases dentro de <br>(_incluyendo en los directorios de aplicación adicionales_):
    - [x] `/objects`.<br>El namespace base debe ser `Object`.
    - [x] `/prerequests`.<br>El namespace base debe ser `PreRequest`.
    - [x] `/requests`.<br>El namespace base debe ser `Request`.
    - [x] `/responses`.<br>El namespace base debe ser `Response`.
    - [x] `/configs/classes`.
- [x] Recorrer Directorios de aplicación (incluyendo el _APPPATH_) en modo inversa
- [x] Se procesa el uri de la solicitud<br>En caso de ser llamado como comando se ejecuta el primer parametro enviado (eg: /usuario/jrojash)
    - [x] Se busca parametro de idioma
    - [x] Se redefine el uri formateado con los parametros solicitados; caso contrario, se redefine el uri por defecto considerando a los números como IDs 
    - [x] Se ejecuta el PreRequest del Uri
    - [x] Se ejecuta el Request de Uri
    - [ ] Se llama al response según el tipo de Vista requerida (Html, JSON ó Archivo)
