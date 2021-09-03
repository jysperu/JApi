<?php
/**
 * /JApi/JApi.class.php
 * @filesource
 */

defined('HOMEPATH') or exit('Archivo no se puede llamar directamente');

/**
 * JApi
 * Clase Maestra del framework
 */
class JApi
{
	//=================================================================================//
	//==== VARIABLES ESTÁTICAS                                                    =====//
	//=================================================================================//

	/**
	 * $_dir_functions
	 * Variable que almacena la ruta de la carpeta que contiene los archivos de funciones
	 */
	public static $_dir_functions = '/configs/functions';

	/**
	 * $_file_install
	 * Variable que almacena la ruta del archivo install.php
	 */
	public static $_file_install = '/install/install.php';

	/**
	 * $_file_app
	 * Variable que almacena la ruta del archivo app.php
	 */
	public static $_file_app = '/app.php';

	/**
	 * $_cache_nmsp_paginaestatica
	 * Variable que almacena el namespace para el cache de las páginas estáticas
	 */
	public static $_cache_nmsp_paginaestatica = 'JApi_d8034d571a95b0b8230e5910b8ff8518';

	/**
	 * $_autoload_bases
	 * Bases de espacios de nombres de las clases para el autoload
	 */
	public static $_autoload_bases = [
		'Objeto'    , // Carpeta `objetos`   aloja todos los controladores de objetos de la base datos
		'Request'   , // Carpeta `requests`  aloja todos los procesadores de solicitudes
		'Response'  , // Carpeta `responses` aloja todas las respuestas HTML
	];

	/**
	 * $_routes_bases
	 * Bases de espacios de nombres de las clases que contienen las directrices para redirección de contenido
	 * Estas bases se añaden al autoload y al momento de procesar la solicitud son leídos en el orden que se encuentra
	 * Después de estas directrices se leen recien las directrices Request y Response para el procesamiento de las solicitudes
	 */
	public static $_routes_bases = [
		'ObjRoute'  , // Carpeta `objroutes`   enfocado primordialmente para la validación de existencia de los objetos
		'ReRoute'   , // Carpeta `reroutes`    sirve para redirigir las rutas enmascaradas (eg: /cuenta -> /usuario/perfil/datos)
		'AlwRoute'  , // Carpeta `alwroutes`   comprobación de permisos
		'PreRequest', // Carpeta `prerequests` validación previa al procesamiento de la solicitud
	];

	//=================================================================================//
	//==== CONSTRUCTORES                                                          =====//
	//=================================================================================//

	/**
	 * instance()
	 *
	 * @return JApi
	 */
	public static function instance ()
	{
		static $_instance;
		if ( ! $_instance) $_instance = new self();
		return $_instance;
	}

	/**
	 * __construct()
	 */
	protected function __construct ()
	{}

	//=================================================================================//
	//==== VARIABLES                                                              =====//
	//=================================================================================//

	/**
	 * $_app_directories_rvs
	 * Aloja todas los directorios como llaves y el orden como valor
	 * Ayuda a identificar si se ha cargado ya un directorio de aplicación no esté en mas de 1 orden
	 */
	protected $_app_directories_rvs = [];

	/**
	 * $_app_directories
	 * Aloja todas los directorios de aplicaciones con la llave primaria equivalente a la prioridad asignada
	 */
	protected $_app_directories = [];

	/**
	 * $_app_directories_list
	 * Aloja todas los directorios en el orden generado por la prioridad
	 */
	protected $_app_directories_list = [];

	/**
	 * $_app_directories_labeled
	 * Aloja todas los directorios en el orden generado por la prioridad
	 */
	protected $_app_directories_labeled = [];

	/**
	 * $_ob_level
	 */
	protected $_ob_level = null;

	/**
	 * $_hooks_filters
	 * Variable que almacena todas las funciones aplicables para los filtros
	 */
	protected $_hooks_filters = [];

	/**
	 * $_hooks_filters_defs
	 * Variable que almacena todas las funciones aplicables para los filtros 
	 * por defecto cuando no se hayan asignado alguno
	 */
	protected $_hooks_filters_defs = [];

	/**
	 * $_hooks_actions
	 * Variable que almacena todas las funciones aplicables para los actions
	 */
	protected $_hooks_actions = [];

	/**
	 * $_hooks_actions_defs
	 * Variable que almacena todas las funciones aplicables para los actions
	 * por defecto cuando no se hayan asignado alguno
	 */
	protected $_hooks_actions_defs = [];

	/**
	 * $_config
	 * Variable que almacena todas las configuraciones de aplicación
	 */
	protected $_config = [];

	/**
	 * $_rqs_method
	 * Variable que almacena el método utilizado de la solicitud de coneccion
	 */
	protected $_rqs_method;

	/**
	 * $URI
	 * Variable que almacena el uri de la solicitud
	 */
	protected $URI;

	/**
	 * $_rqs_uri_inicial
	 * Variable que almacena el uri de la solicitud
	 */
	protected $_rqs_uri_inicial;

	/**
	 * $_headers
	 */
	protected $_headers = [];

	/**
	 * $_response_type
	 * Variable que almacena el tipo de respuesta que se entregará a la solicitud
	 * Posibles Valores:
	 * - html
	 * - body
	 * - json
	 * - cli
	 * - file
	 * - manual
	 */
	protected $_response_type = 'html';

	/**
	 * $_response_charset
	 */
	protected $_response_charset = null;

	/**
	 * $_response_mime
	 */
	protected $_response_mime = null;

	/**
	 * $LANG
	 * Variable que almacena el lenguaje de la solicitud
	 */
	protected $LANG;

	/**
	 * $timezone
	 * Variable que almacena la zona horaria
	 */
	protected $timezone;

	/**
	 * $utc
	 * Variable que almacena el utc del usuario
	 */
	protected $utc;

	/**
	 * $_cache_instances
	 */
	protected $_cache_instances = [];

	//=================================================================================//
	//==== MÉTODOS                                                                =====//
	//=================================================================================//

	/**
	 * add_app_directory ()
	 * Función que permite añadir directorios de aplicación las cuales serán usados para buscar y procesar 
	 * la información para la solicitud del usuario
	 *
	 * @param $directory String Directorio a añadir
	 * @param $orden Integer Prioridad de lectura del directorio
	 * @return self
	 */
	public function add_app_directory ($directory, $orden = 50, $label = null)
	{
		$_directory = $directory;

		if (($_temp = realpath($directory)) !== FALSE)
		{
			$directory = $_temp;
		}
		else
		{
			$directory = strtr(
				rtrim($directory, '/\\'),
				'/\\',
				DS . DS
			);
		}

		if ( ! file_exists($directory) ||  ! is_dir($directory))
		{
			return $this;
		}

		is_null($label) and $label = $directory;
		$this->_app_directories_labeled[$directory] = $label;

		isset($this->_app_directories[$orden]) or $this->_app_directories[$orden] = [];
		$this->_app_directories[$orden][] = $directory;

		/** validando que no se haya agregado antes */
		//_app_directories_rvs con índice $directory ¿aun no se ha asignado, además $directory es una ruta?
		if (isset($this->_app_directories_rvs[$directory]) and $this->_app_directories_rvs[$directory] !== $orden)
		{
			$in_orden = $this->_app_directories_rvs[$directory];
			$idk = array_search($directory, $this->_app_directories[$in_orden]);
			if ($idk !== false)
			{
				unset($this->_app_directories[$in_orden][$idk]);
				$this->_app_directories[$in_orden] = array_values($this->_app_directories[$in_orden]);
			}
		}

		/** actualizando el orden del directorio en el cacheo de comprobación de agregados */
		$this->_app_directories_rvs[$directory] = $orden;

		/** Cacheando la lista */
		$_app_directories_list = [];
		$_app_directories = $this->_app_directories;
		ksort($_app_directories);

		foreach($_app_directories as $orden => $directories)
		{
			foreach($directories as $directory)
			{
				$_app_directories_list[] = $directory;
			}
		}
		$this->_app_directories_list = $_app_directories_list;

		return $this;
	}

	/**
	 * get_app_directories ()
	 * Función que retorna los directorios de aplicación
	 *
	 * @param $reverse Boolean Indica si se retornará la lista de manera invertida
	 * @return Array
	 */
	public function get_app_directories ($reverse = FALSE)
	{
		$_app_directories_list = $this->_app_directories_list;
		$reverse and $_app_directories_list = array_reverse($_app_directories_list);

		return $_app_directories_list;
	}

	/**
	 * map_app_directories ()
	 * Función que ejecuta una función establecida con todos los directorios de aplicación como parametro
	 *
	 * @param $callback Callable Función a ejecutar
	 * @param $reverse Boolean Indica si la función a ejecutar se hará a la lista invertida
	 * @return self
	 */
	public function map_app_directories ($callback, $reverse = FALSE)
	{
		$_app_directories_list = $this->get_app_directories($reverse);
		array_map($callback, $_app_directories_list);

		return $this;
	}

	/**
	 * get_app_directories_labels ()
	 * Función que retorna los directorios y sus nombres
	 *
	 * @param $reverse Boolean Indica si se retornará la lista de manera invertida
	 * @return Array
	 */
	public function get_app_directories_labels ($reverse = FALSE)
	{
		$_app_directories_list = $this->_app_directories_list;
		$reverse and $_app_directories_list = array_reverse($_app_directories_list);
		$_app_directories_list = array_combine($_app_directories_list, array_map(function($o){
			return $this->_app_directories_labeled[$o];
		}, $_app_directories_list));

		return $_app_directories_list;
	}

	/**
	 * filter_add()
	 * Agrega funciones programadas para filtrar variables
	 *
	 * @param String $key Hook
	 * @param Callable $function Función a ejecutar
	 * @param Integer $priority Prioridad (Orden) a ejecutar la función cuando es llamado el Hook
	 * @return Boolean
	 */
	public function filter_add ($key, $function, $priority = 50)
	{
		if (empty($key))
		{
			return FALSE;
		}

		is_numeric($priority) OR $priority = 50;
		$priority = (int)$priority;

		$this->_hooks_filters[$key][$priority][] = $function;
		return TRUE;
	}

	/**
	 * non_filtered()
	 * Agrega funciones programadas para filtrar variables
	 * por defecto cuando no se hayan asignado alguno
	 *
	 * @param String $key Hook
	 * @param Callable $function Función a ejecutar
	 * @param Integer $priority Prioridad (Orden) a ejecutar la función cuando es llamado el Hook
	 * @return Boolean
	 */
	public function non_filtered ($key, $function, $priority = 50)
	{
		if (empty($key))
		{
			return FALSE;
		}

		is_numeric($priority) OR $priority = 50;
		$priority = (int)$priority;

		$this->_hooks_filters_defs[$key][$priority][] = $function;
		return TRUE;
	}

	/**
	 * filter_apply()
	 * Ejecuta funciones para validar o cambiar una variable
	 *
	 * @param String $key Hook
	 * @param Mixed	&...$params Parametros a enviar en las funciones del Hook (Referenced)
	 * @return Mixed $params[0] || NULL
	 */
	public function filter_apply ($key, &...$params)
	{
		if (empty($key))
		{
			trigger_error('Hook es requerido', E_USER_WARNING);
			return NULL;
		}

		count($params) === 0 and $params[0] = NULL;

		if ( ! isset($this->_hooks_filters[$key]) OR count($this->_hooks_filters[$key]) === 0)
		{
			if ( ! isset($this->_hooks_filters_defs[$key]) OR count($this->_hooks_filters_defs[$key]) === 0)
			{
				return $params[0];
			}

			$functions = $this->_hooks_filters_defs[$key];
		}
		else
		{
			$functions = $this->_hooks_filters[$key];
		}

		krsort($functions);

		$params_0 = $params[0]; ## Valor a retornar
		foreach($functions as $priority => $funcs){
			foreach($funcs as $func){
				$return = call_user_func_array($func, $params);

				if ( ! is_null($return) and $params_0 === $params[0])
				{
					## El parametro 0 no ha cambiado por referencia 
					## y en cambio la función ha retornado un valor no NULO 
					## por lo tanto le asigna el valor retornado
					$params[0] = $return;
				}

				$params_0 = $params[0]; ## Valor a retornar
			}
		}

		return $params_0;
	}

	/**
	 * action_add()
	 * Agrega funciones programadas
	 *
	 * @param String $key Hook
	 * @param Callable $function Función a ejecutar
	 * @param Integer $priority Prioridad (orden) a ejecutar la función
	 * @return Boolean
	 */
	public function action_add ($key, $function, $priority = 50)
	{
		if (empty($key))
		{
			return FALSE;
		}

		is_numeric($priority) OR $priority = 50;
		$priority = (int)$priority;

		$this->_hooks_actions[$key][$priority][] = $function;
		return TRUE;
	}

	/**
	 * non_actioned()
	 * Agrega funciones programadas
	 * por defecto cuando no se hayan asignado alguno
	 *
	 * @param String $key Hook
	 * @param Callable $function Función a ejecutar
	 * @param Integer $priority	Prioridad (orden) a ejecutar la función
	 * @return Boolean
	 */
	public function non_actioned ($key, $function, $priority = 50)
	{
		if (empty($key))
		{
			return FALSE;
		}

		is_numeric($priority) OR $priority = 50;
		$priority = (int)$priority;

		$this->_hooks_actions_defs[$key][$priority][] = $function;
		return TRUE;
	}

	/**
	 * action_apply()
	 * Ejecuta las funciones programadas
	 *
	 * @param String $key Hook
	 * @param Mixed &...$params Parametros a enviar en las funciones del Hook (Referenced)
	 * @return Boolean || NULL
	 */
	public function action_apply ($key, &...$params)
	{
		if (empty($key))
		{
			trigger_error('Hook es requerido', E_USER_WARNING);
			return NULL;
		}

		$RESULT = NULL;

		if ( ! isset($this->_hooks_actions[$key]) OR count($this->_hooks_actions[$key]) === 0)
		{
			if ( ! isset($this->_hooks_actions_defs[$key]) OR count($this->_hooks_actions_defs[$key]) === 0)
			{
				return $RESULT;
			}

			$functions = $this->_hooks_actions_defs[$key];
		}
		else
		{
			$functions = $this->_hooks_actions[$key];
		}

		krsort($functions);

		foreach($functions as $priority => $funcs){
			foreach($funcs as $func){
				$RESULT = call_user_func_array($func, $params);
			}
		}

		return $RESULT;
	}

	/**
	 * _handler_shutdown()
	 */
	public function _handler_shutdown ()
	{
		$last_error = error_get_last();

		if ( isset($last_error) &&
			($last_error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING)))
		{
			_handler_error($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
		}

//		$this -> _send_response();

		$this -> action_apply('do_when_end');
		$this -> action_apply('shutdown');

		flush();
	}

	/**
	 * _save_logger_default()
	 */
	public function _save_logger_default ($data)
	{
		extract($data);

		// Guardar en archivo
		$log_file = mkdir2('/logs', APPPATH) . DS . date('Ymd') . '.log';
		$log_file_exists = file_exists($log_file);

		$msg_file = '';
		$msg_file.= $severity . ' (' . $code . ') en ' . $filepath . '#' . $line . PHP_EOL;
		$msg_file.= '---' . PHP_EOL;
		$msg_file.= $message . PHP_EOL;
		$msg_file.= '---' . PHP_EOL;
		$msg_file.= implode(PHP_EOL, $meta['trace_slim']) . PHP_EOL;
		$msg_file.= '---' . PHP_EOL;

		unset($meta['server'], $meta['trace_slim'], $meta['trace_original'], $meta['URL_loadable'], $meta['IPADRESS_loadable']);
		isset($meta['url']) and $meta['url'] = $meta['url']['full-wq'];
		isset($meta['ip_address']) and $meta['ip_address'] = $meta['ip_address']['ip_address'];

		$msg_file.= json_encode($meta, JSON_PRETTY_PRINT) . PHP_EOL;
		$msg_file.= '***' . PHP_EOL;

		file_put_contents($log_file, $msg_file . PHP_EOL . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);

		if ( ! $log_file_exists)
		{
			chmod($log_file, 0644);
		}

		static $_max_error_counter = 10;
		if (   --$_max_error_counter <= 0)
		{
			file_put_contents($log_file, 'LIMIT ERROR PRINTED' . PHP_EOL, FILE_APPEND | LOCK_EX);
			exit();
		}
	}

	/**
	 * force_exit()
	 */
	public function force_exit($status = NULL)
	{
		exit ($status);
	}

	/**
	 * config()
	 *
	 * Obtiene y retorna la configuración.
	 *
	 * La función lee los archivos de configuración generales tales como los de JCore 
	 * y los que se encuentran en la carpeta 'config' de APPPATH (directorio de la aplicación)
	 *
	 * @param String $get Permite obtener una configuración específica, si es NULL entonces devolverá toda la configuración.
	 * @param Array $replace Reemplaza algunas opciones de la variable $config leida
	 * @param Boolean $force si es FALSE, entonces validará que el valor a "reemplazar" no exista previamente (solo inserta no reemplaza)
	 * @return	Mixed
	 */
	public function &config($get = NULL, Array $replace = [], bool $force = FALSE)
	{
		foreach ($replace as $key => $val)
		{
			if ( ! $force and isset($this->_config[$key]))
			{
				continue;
			}

			$this->_config[$key] = $val;
		}

		if ($get === 'array' or is_null($get))
		{
			return $this->_config;
		}

		isset($this->_config[$get]) or $this->_config[$get] = NULL;
		return $this->_config[$get];
	}

	/**
	 * GetAndClear_BufferContent()
	 */
	public function GetAndClear_BufferContent ()
	{
		$_buffer_content = '';
		while (ob_get_level() > $this->_ob_level)
		{
			$_buffer_content .= ob_get_contents();
			ob_end_clean();
		}

		return $_buffer_content;
	}

	public function ResponseAs ($type, $charset = NULL, $mime = NULL)
	{
		$this->set_response_type (mb_strtolower($type));

		isset($charset) and 
		$this->set_response_charset($charset);

		isset($mime) and
		$this->set_response_mime ($mime);

		return $this;
	}

	/**
	 * get_rqs_method()
	 */
	public function get_rqs_method ()
	{
		return $this->_rqs_method;
	}

	/**
	 * set_URI()
	 */
	public function set_URI ($new)
	{
		$this->URI = $new;
		return $this;
	}

	/**
	 * get_URI()
	 */
	public function get_URI ()
	{
		return $this->URI;
	}

	/**
	 * get_rqs_uri_inicial()
	 */
	public function get_rqs_uri_inicial ()
	{
		return $this->_rqs_uri_inicial;
	}

	/**
	 * set_headers()
	 */
	public function set_headers ($new)
	{
		$this->_headers = $new;
		return $this;
	}

	/**
	 * add_header()
	 */
	public function add_header ($new)
	{
		$this->_headers[] = $new;
		return $this;
	}

	/**
	 * get_headers()
	 */
	public function get_headers ()
	{
		return $this->_headers;
	}

	/**
	 * del_header()
	 */
	public function del_header ($old)
	{
		$index = array_search($old, $this->_headers);
		if ($index !== false)
		{
			unset($this->_headers[$index]);
			$this->_headers = array_values($this->_headers);
		}
		return $this;
	}

	/**
	 * set_response_type()
	 */
	public function set_response_type ($new)
	{
		$this->_response_type = mb_strtolower($new);

		switch($this -> _response_type)
		{
			case 'html': case 'body':
				$this -> set_response_mime('text/html');
				break;
			case 'json': case 'cli':
				$this -> set_response_mime('application/json');
				break;
		}

		return $this;
	}

	/**
	 * get_response_type()
	 */
	public function get_response_type ()
	{
		return $this->_response_type;
	}

	/**
	 * set_response_charset()
	 */
	public function set_response_charset ($new)
	{
		$new = mb_strtoupper($new);
		$charset = $this -> _response_charset = $new;

		ini_set('default_charset', $charset);
		ini_set('php.internal_encoding', $charset);
		mb_substitute_character('none');
		define('UTF8_ENABLED', defined('PREG_BAD_UTF8_ERROR') && $charset === 'UTF-8');

		return $this;
	}

	/**
	 * get_response_charset()
	 */
	public function get_response_charset ()
	{
		return $this->_response_charset;
	}

	/**
	 * set_response_mime()
	 */
	public function set_response_mime ($new)
	{
		$this->_response_mime = $new;
		return $this;
	}

	/**
	 * get_response_mime()
	 */
	public function get_response_mime ()
	{
		return $this->_response_mime;
	}

	/**
	 * set_LANG()
	 */
	public function set_LANG ($new, $setcookie = false)
	{
		$lang = $this->LANG = $new;

		! ISCOMMAND and 
		$setcookie and
		setcookie('lang', $lang, time() + 60 * 60 * 7 * 4 * 12, '/');
		\Locale::setDefault($lang);

		$this->action_apply('JApi/LANG', $this->LANG);
		return $this;
	}

	/**
	 * get_LANG()
	 */
	public function get_LANG ()
	{
		return $this->LANG;
	}

	/**
	 * get_Locale()
	 */
	public function get_Locale ()
	{
		return \Locale::getDefault();
	}

	/**
	 * set_timezone()
	 */
	public function set_timezone ($new)
	{
		$timezone = $this->timezone = $new;

		date_default_timezone_set($timezone);
		$_utc_dtz = new DateTimeZone(date_default_timezone_get());
		$_utc_dt  = new DateTime('now', $_utc_dtz);
		$_utc_offset = $_utc_dtz->getOffset($_utc_dt);

		$this->utc = sprintf( "%s%02d:%02d", ( $_utc_offset >= 0 ) ? '+' : '-', abs( $_utc_offset / 3600 ), abs( $_utc_offset % 3600 ) );

		$this->action_apply('JApi/timezone', $this->timezone, $this->utc);
		$this->action_apply('JApi/utc', $this->utc, $this->timezone);
		return $this;
	}

	/**
	 * get_timezone()
	 */
	public function get_timezone ()
	{
		return $this->timezone;
	}

	/**
	 * Cache()
	 */
	public function Cache ($namespace = '@', ? Int $lifetime = null, ? String $dir = null, ? String $adapter_class = null)
	{
		if ( ! isset($this->_cache_instances[$namespace]))
		{
			if ( ! isset($adapter_class))
			{
				$adapter_class = 'Symfony\Component\Cache\Adapter\FilesystemAdapter';
				$adapter_class = $this->filter_apply('CacheAdapter', $adapter_class, $namespace);
			}

			$this->_cache_instances[$namespace] = new $adapter_class ($namespace, $lifetime ?? CACHE_LIFETIME, $dir ?? CACHE_DIR);
		}
		return $this->_cache_instances[$namespace];
	}

	/**
	 * CachePrune()
	 */
	public function CachePrune ($namespace = '@')
	{
		isset($this->_cache_instances[$namespace]) and
		$this->_cache_instances[$namespace] -> prune();
	}

	/**
	 * AllCachePrune()
	 */
	public function AllCachePrune ()
	{
		foreach($this->_cache_instances as $instance)
		$instance -> prune();

		$this->action_apply('AllCachePrune'); // para enviar el comando de limpiar todas las caches que no se han instanciado
	}

	/**
	 * CachePaginaEstatica()
	 */
	public function CachePaginaEstatica ($content, ? String $id = null)
	{
		$id = $id ?? REQUEST_HASH;

		$cache = $this -> Cache(self::$_cache_nmsp_paginaestatica);
		$item = $cache -> getItem($id);

		$val = [
			't' => $this -> _response_type,
			'c' => $this -> _response_charset,
			'm' => $this -> _response_mime,
			'b' => $content
		];
		$item -> set($val);

		return $cache->save($item);
	}

	//=================================================================================//
	//==== INCIALIZADOR                                                           =====//
	//=================================================================================//

	/**
	 * _init_variables()
	 * Función que comprueba y/o declara las variables requeridas para el framework
	 */
	protected function _init_variables ()
	{
		// HOMEPATH ya esta declarado

		/**
		 * JAPIPATH
		 * Directorio del JApi
		 * @global
		 */
		defined('JAPIPATH') or define('JAPIPATH', __DIR__);

		/**
		 * COREPATH
		 * Directorio de los archivos del núcleo de la aplicación
		 * @global
		 */
		defined('COREPATH') or define('COREPATH', JAPIPATH);

		/**
		 * APPPATH
		 * Directorio de aplicación por defecto
		 * @global
		 */
		defined('APPPATH') or define('APPPATH', COREPATH);

		/**
		 * APPNMSPC
		 * Identificador de aplicación JApi (Opcional)
		 * @global
		 */
		defined('APPNMSPC') or define('APPNMSPC', 'pe.jys.JApi');

		/** DIRECTORY_SEPARATOR */
		defined('DS') or define('DS', DIRECTORY_SEPARATOR);

		/**
		 * ISCOMMAND
		 * Identifica si se está ejecutando la solicitud mediante comando o desde interfaz web
		 */
		defined('ISCOMMAND') or define('ISCOMMAND', (substr(PHP_SAPI, 0, 3) === 'cli' ? 'cli' : defined('STDIN')));

		/**
		 * cdkdsp
		 * Identifica el valor de la variable cookie a usar para la identificación del dispositivo
		 */
		defined('cdkdsp') or define('cdkdsp', 'cdkdsp');

		/**
		 * force_ssl
		 * Indica si se desea forzar el uso del SSL
		 * Si es NULO entonces no se redirecciona
		 * Si es TRUE entonces fuerza el uso de https
		 * Si es FALSE entonces fuerza el uso de http mas no de https
		 */
		defined('force_ssl') or define('force_ssl', null);

		/**
		 * force_www
		 * Indica si se desea forzar el uso del WWW
		 * Si es NULO entonces no se redirecciona
		 * Si es TRUE entonces fuerza el uso de www
		 * Si es FALSE entonces fuerza al no uso del www
		 */
		defined('force_www') or define('force_www', null);

		/**
		 * FORCE_RSP_TYPE
		 * Indica si se desea forzar el resultado de la solicitud a un tipo determinado
		 */
		// defined('FORCE_RSP_TYPE') or define('FORCE_RSP_TYPE', 'body');

		/**
		 * check_lang_uri
		 * Indica si se desea revizar el código del lenguaje en el path del uri
		 * El código corresponde a 02 dígitos al inicio del path
		 */
		defined('check_lang_uri') or define('check_lang_uri', false);

		/**
		 * CACHE_DIR
		 * Indica el path por defecto a guardar la cache
		 */
		defined('CACHE_DIR')       or define('CACHE_DIR', APPPATH . DS . 'cache');

		/**
		 * CACHE_LIFETIME
		 * Indica el tiempo de vida por defecto de la cache
		 */
		defined('CACHE_LIFETIME')      or define('CACHE_LIFETIME', 60 * 60 * 24 * 7 *4 *12); // 1 AÑO
	}

	/**
	 * _init_load_functions()
	 * Función que permite buscar los archivos de funciones
	 * Habilitado subcarpetas de la carpeta principal a fin de poder distribuir los archivos de funciones
	 */
	protected function _init_load_functions ($dir = NULL)
	{
		$_functions_dir = $dir . self::$_dir_functions;
		if ( ! file_exists($_functions_dir) ||! is_dir($_functions_dir)) return;

		$_search_dir = null;
		$_search_dir = function ($dir) use (&$_search_dir) {
			$archivos = scandir($dir);
			foreach($archivos as $archivo)
			{
				if (in_array($archivo, ['.', '..'])) continue;

				if (is_dir($dir . DS . $archivo))
				{
					$_search_dir ($dir . DS . $archivo);
					continue;
				}

				if ( ! preg_match('/\.php$/i', $archivo)) continue;

				@include_once ($dir . DS . $archivo);
			}
		};
		$_search_dir($_functions_dir);
	}

	/**
	 * _init_load_vendor()
	 * Función que permite buscar los archivos /vendor/autoload.php de los directorios
	 */
	protected function _init_load_vendor ($dir = NULL)
	{
		static $_dir_autoload = '/vendor/autoload.php';

		$_autoload_vendor = $dir . $_dir_autoload;
		if ( ! file_exists($_autoload_vendor)) return;

		@include_once ($_autoload_vendor);
	}

	/**
	 * _init_autoload()
	 */
	protected function _init_autoload ($class)
	{
		static $_bs = '\\';
		$_bases = array_merge([], self::$_autoload_bases, self::$_routes_bases);

		$_class_required = $class;
		$class = trim($class, $_bs);

		$class_parts = explode($_bs, $class);
		$class_dir_base = '/configs/classes';

		if (count($class_parts) > 1 and in_array($class_parts[0], $_bases))
		{
			$class_dir_base = array_shift($class_parts);
			$class_dir_base = mb_strtolower($class_dir_base);
			$class_dir_base = '/' . $class_dir_base . 's';
		}

		$class = implode($_bs, $class_parts);

		$class_file_lista = []; // Posibilidades de directorio
		$class_file_lista[] = '/' . str_replace($_bs, '/', $class) . '.php';
		$class_file_lista[] = '/' . str_replace($_bs, '/', mb_strtolower($class)) . '.php';
		$class_file_lista[] = '/' . str_replace($_bs, '/', mb_strtolower(str_replace('-', '_', $class))) . '.php';

		$directories = $this->get_app_directories();

		$class_dir_base_array = [$class_dir_base];
		$class_dir_base === '/configs/classes' and $class_dir_base_array[] = '/configs/libs';

		foreach($class_dir_base_array as $class_dir_base)
		{
			foreach($directories as $directory)
			{
				$_dirbase = $directory . $class_dir_base;

				foreach($class_file_lista as $class_file)
				{
					if ($_tmp_file = $_dirbase . $class_file and file_exists($_tmp_file))
					{
						if (class_exists($_class_required, FALSE) === FALSE and class_exists($class, FALSE) === FALSE)
						{
							require_once $_tmp_file;
						}
					}
				}
			}
		}
	}

	/**
	 * _init_load_config()
	 */
	protected function _init_load_config ($dir = NULL)
	{
		$_config_file = $dir . '/configs/config.php';
		$_config_file = $this->filter_apply('JApi/Config/File', $_config_file, $dir);

		if ( ! file_exists($_config_file)) return;

		$config =& $this->_config;
		$config = $this->filter_apply('JApi/Config', $config, $dir, $_config_file);

		@include_once ($_config_file);
	}

	/**
	 * _init_install()
	 */
	protected function _init_install ($dir = NULL)
	{
		$_install_file = $dir . self::$_file_install;
		$_installed_file = $_install_file . '.bckp';
		if ( ! file_exists($_install_file)) return;

		$this->action_apply('JApi/install/start', $dir, $_install_file);
		@include_once ($_install_file);
		$this->action_apply('JApi/install/end', $dir, $_install_file);

		if (file_exists($_install_file))
		{
			@rename($_install_file, $_installed_file);
		}
	}

	/**
	 * _init_load_app()
	 */
	protected function _init_load_app ($dir = NULL)
	{
		$_app_file = $dir . self::$_file_app;
		if ( ! file_exists($_app_file)) return;

		$this->action_apply('JApi/app.php', $dir, $_app_file);
		@include_once ($_app_file);
	}

	protected function _init_default_response_type ($response_type = null)
	{
		if (defined('FORCE_RSP_TYPE'))
		{
			return $this -> set_response_type(FORCE_RSP_TYPE);
		}

		if (isset($_GET['contentOnly']) or (isset($_GET['_']) and $_GET['_'] === 'co'))
		{
			return $this -> set_response_type('body');
		}

		if (
			(
				isset($_SERVER['HTTP_X_REQUESTED_WITH']) and 
				(
					mb_strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' or 
					mb_strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'androidapp' or 
					mb_strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'iosapp'
				)
			) or isset($_GET['json']) or isset($_GET['cron'])
		)
		{
			return $this -> set_response_type('json');
		}

		if (ISCOMMAND)
		{
			return $this -> set_response_type('cli');
		}

		return $this -> set_response_type('html');
	}

	protected function _init_default_response_charset ($response_charset = null)
	{
		/** Estableciendo los charsets a todo lo que corresponde */
		$charset = $this->config('charset');
		$this->set_response_charset($charset);
	}

	protected function _init_default_lang ($lang = null)
	{
		/**
		 * Procesar URI para validación de idioma
		 * Se procesa la validación del lenguaje de manera prioritaria
		 * por si el identificador del idioma esta en el URI y se deba omitir 
		 * para el procesamiento del mismo
		 */
		if ( ! ISCOMMAND and check_lang_uri) :

		// Comprobar que no se ha enviado por uri (Prioridad 1)
		$_uri = explode('/', $this -> URI);
		empty($_uri[0]) and array_shift($_uri);

		$_uri_lang = array_shift($_uri);
		if (preg_match('/^([a-z]{2}|[A-Z]{2}|[a-z]{2}\-[A-Z]{2})$/', $_uri_lang))
		{
			$this -> _rqs_uri_inicial = $this -> URI = '/' . implode('/', $_uri);
			$_url_path =& $this -> url('path');
			$_url_path = $this -> _rqs_uri_inicial;

			return $this->set_LANG($_uri_lang, false);
		}

		elseif ( ! ISCOMMAND)

		// Si ya existe una cookie (Prioridad 2)
		if (isset($_COOKIE['lang']))
		{
			return $this->set_LANG($_COOKIE['lang'], false);
		}

		endif;

		// Si esta en la configuración (Prioridad 3)
		$_config_lang = $this->config('lang');
		if ( ! is_null($_config_lang))
		{
			return $this->set_LANG($_config_lang, false);
		}

		// Detectar el idioma (Prioridad 4)
		$_srv_lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : 'es';
		$_srv_lang = explode(',', $_srv_lang);
		$_srv_lang = array_shift($_srv_lang);
		empty($_srv_lang) and $_srv_lang = 'es';

		return $this->set_LANG($_srv_lang, false);
	}

	protected function _init_default_timezone ($lang = null)
	{
		/** Estableciendo el timezone a todo lo que corresponde */
		$timezone = $this->config('timezone');
		$this->set_timezone($timezone);
	}

	protected function _init_nonhooks ()
	{
		/** Registrando el hook para guardar los errores en caso no se haya registrado */
		$this->non_actioned('SaveLogger', [$this, '_save_logger_default']);

		/** Registrando el hook para guardar los errores en caso no se haya registrado */
		$this->non_actioned('JApi/set_response_type', [$this, '_init_default_response_type']);

		/** Registrando el hook para guardar los errores en caso no se haya registrado */
		$this->non_actioned('JApi/set_response_charset', [$this, '_init_default_response_charset']);

		/** Registrando el hook para guardar los errores en caso no se haya registrado */
		$this->non_actioned('JApi/set_LANG', [$this, '_init_default_lang']);

		/** Registrando el hook para guardar los errores en caso no se haya registrado */
		$this->non_actioned('JApi/set_timezone', [$this, '_init_default_timezone']);
	}

	/**
	 * init()
	 */
	public function init ()
	{
		static $_init = true;

		if ( ! $_init) return $this;
		$_init = false;

		/** Iniciando el leído del buffer */
		$this->_ob_level = ob_get_level();
		ob_start();

		/** Iniciando las variables _SESSION */
		session_start();

		/** Definiendo las variables necesarias */
		$this -> _init_variables ();

		/** Corrigiendo directorio base cuando se ejecuta como comando */
		ISCOMMAND and chdir(APPPATH);

		/** Añadiendo los directorios de aplicaciones base */
		$this
		-> add_app_directory(APPPATH , 25, 'APPPATH' ) // Orden 25 (será leído al inicio, a menos que se ponga otro directorio con menor orden)
		-> add_app_directory(COREPATH, 50, 'COREPATH') // Orden 50 (será leido al medio, a menos que se ponga otro directorio con mayor orden)
		-> add_app_directory(JAPIPATH, 75, 'JAPIPATH') // Orden 75 (será leido al final, a menos que se ponga otro directorio con mayor orden)
		;

		/**
		 * Se llama al /init.php del APPPATH o la función JApiInit en caso existan
		 * En este momento se puede:
		 * - Añadir mas directorios de aplicación
		 * - Añadir Hooks (add_action, add_filter)
		 * - Bloquear requests no deseados
		 * - Iniciar control de dispositivos u otros
		 *
		 * > **Recordar Que:** Hasta este punto solo se ha cargado la clase principal
		 */
		file_exists (COREPATH . '/init.php') and 
		require_once COREPATH . '/init.php';

		file_exists (APPPATH . '/init.php') and 
		require_once APPPATH . '/init.php';

		function_exists('JApiInit') and 
		JApiInit($this);

		$this -> action_apply('JApi/InitLoaded');

		/**
		 * Cargando todos los archivos de funciones
		 * El orden a recorrer es de menor a mayor para que los directorios prioritarios puedan crear primero las funciones actualizadas
		 */
		$this -> map_app_directories ([$this, '_init_load_functions']);
		$this -> action_apply('JApi/FunctionsLoaded');

		/** Redirigir SSL */
		if ( ! ISCOMMAND and ! is_null(force_ssl))
		{
			$actual = url('https');

			if ($actual !== force_ssl)
			{
				$url = url('array');
				$url['scheme'] = (force_ssl ? 'https' : 'http');
				redirect($url);
				exit();
			}
		}

		/** Redirigir WWW */
		if ( ! ISCOMMAND and ! is_null(force_www))
		{
			$actual = url('www');

			if ($actual !== force_www)
			{
				$url = url('array');
				$url['host'] = (force_www ? ('www.' . $url['host']) : preg_replace('/^www\./i', '', $url['host']));
				redirect($url);
				exit();
			}
		}

		/** Control de Errores */
		ini_set('display_errors', 0);
		error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);

		/** Definiendo el handler para los posibles errores */
		set_error_handler('_handler_error');

		/** Definiendo el handler para las posibles excepciones */
		set_exception_handler('_handler_exception');

		/** Definiendo el handler para cuando finalice el request ya sea con o sin response */
		register_shutdown_function([$this, '_handler_shutdown']);

		/** Registra los hooks por defecto */
		$this->_init_nonhooks();

		/**
		 * Cargando el autoload de la carpeta vendor
		 * El orden a recorrer es de menor a mayor para que los directoreios prioritarios puedan cargar sus librerías actualizadas
		 */
		$this -> map_app_directories ([$this, '_init_load_vendor']);

		/** Iniciando autoload */
		spl_autoload_register([$this, '_init_autoload']);

		/**
		 * Cargando la configuración de la aplicación
		 * El orden a recorrer es de mayor a menor para que los directorios prioritariosde puedan sobreescribir los por defecto
		 */
		$this -> map_app_directories ([$this, '_init_load_config'], true);
		$this -> action_apply('JApi/ConfigLoaded');

		/**
		 * Procesando todos los /install/install.php
		 * El orden a recorrer es de menor a mayor para que los directorios prioritarios puedan instalar sus requerimientos primero
		 */
		$this -> map_app_directories ([$this, '_init_install']);
		$this -> action_apply('JApi/Installed');

		/**
		 * Procesando todos los app.php
		 * El orden a recorrer es de menor a mayor para que los directorios prioritarios puedan procesar sus requerimientos primero
		 */
		$this -> map_app_directories ([$this, '_init_load_app']);
		$this -> action_apply('JApi/APPLoaded');

		/** Completando configuración para iniciar el request y response */
		$this -> _headers = [];
		$this -> _rqs_method = url('request_method');
		$this -> _rqs_uri_inicial = $this -> URI = url('path');

		$this -> action_apply ('JApi/set_response_type',    $this -> _response_type, $this);
		$this -> action_apply ('JApi/set_response_charset', $this -> _response_charset,       $this);
		$this -> action_apply ('JApi/set_LANG',             $this -> LANG,           $this);
		$this -> action_apply ('JApi/set_timezone',         $this -> timezone,       $this);

		$this -> action_apply('JApi/Config/Complete');

		$this -> action_apply('JApi/Config/Complete');

		/** Generar el hash del request para buscar si esta en la cache de `paginas-estaticas` (URI, _rqs_method, params in GET and POST, LANG, timezone) */
		$_request_hash = md5(json_encode([
			$this -> URI,
			$this -> _rqs_method,
			$this -> LANG,
			$this -> timezone,
			request('array'),
		]));
		$_request_hash = $this->filter_apply('JApi/Request/Hash', $_request_hash, $this -> URI, $this -> _rqs_method, $this -> LANG, $this -> timezone, $this);
		defined('REQUEST_HASH') or define('REQUEST_HASH', $_request_hash);

		/** Si hay una cache guardada del REQUEST_HASH entonces retorna el contenido y finaliza el proceso */
		$cache = $this -> Cache(self::$_cache_nmsp_paginaestatica);
		$item = $cache -> getItem(REQUEST_HASH);
		if ($item->isHit())
		{
			$value = $item->get();
			$this -> GetAndClear_BufferContent();

			$this -> ResponseAs($value['t'], $value['c'], $value['m']);
			die($value['b']);
		}
	}

}