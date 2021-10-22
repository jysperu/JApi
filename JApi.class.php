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
	 * $_cache_nmsp_paginaassets
	 * Variable que almacena el namespace para el cache de las páginas estáticas
	 */
	public static $_cache_nmsp_paginaassets = 'JApi_41773ee0ac2ddb550beaca8b88de053f';

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

	/**
	 * $IDS
	 * Variable que almacena los ids u objetos a pasar en los constructores
	 * La variable puede alojar objetos las cuales serán pasados a los constructores de 
	 * las clases PreRequest, Request y Response
	 */
	protected $IDS = [];

	protected $_uriprocess_results = [];

	protected $_process_result = null;

	protected $_response_data = [
		'json' => [],
		'html' => [
			'doctype' => 'html5',
			'tag_attr' => [
				'prefix' => 'og: https://ogp.me/ns#'
			],
			'head' => [
				'tag_attr' => [
					'itemscope'  => '',
					'itemtype'  => 'http://schema.org/WebSite',
				],
				'title' => '',
				'meta' => [
					'name' => [
						'viewport' => 'width=device-width, initial-scale=1, shrink-to-fit=no',
						'HandheldFriendly' => 'True',
						'MobileOptimized' => '320',
						'mobile-web-app-capable' => 'yes',
						'apple-mobile-web-app-capable' => 'yes',
						'robots' => 'noindex, nofollow',
//						'apple-mobile-web-app-title' => '',
//						'application-name' => '',
//						'msapplication-TileColor' => '',
//						'theme-color' => '',
						'generator' => 'JApi@2.0',
					],
					'http-equiv' => [
						'X-UA-Compatible' => 'IE=edge,chrome=1',
					],
					'property' => [],
				],
				'canonical' => '',
				'jsonld' => '',
				'favicon' => '',
			], 
			'body' => [
				'tag_attr' => [], 
				'header_before' => '', 
				'header' => '',
				'header_after' => '',
				'content_before' => '',
				'content' => [], // Callbacks o contenido 
				'content_after' => '',
				'footer_before' => '', 
				'footer' => '',
				'footer_after' => '',
			],
			'force_uri' => null,
			'assets' => [
				'css' => [],
				'js' => [
					'using.js' => [
						'codigo' => 'using.js',
						'uri' => 'https://assets.jys.pe/using.js/using.full.min.js',
						'loaded' => false,
						'orden' => 10,
						'version' => 20201016,
						'position' => 'body',
						'inline' => false,
						'attr' => [],
						'_before' => [],
						'_after' => [
							'Using("jquery", "bootstrap");'
						],
						'deps' => [],
					]
				],
			],
		],
	];

	public static $_doctypes = [
		'xhtml11' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "https://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
		'xhtml1-strict' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "https://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
		'xhtml1-trans' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "https://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
		'xhtml1-frame' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "https://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
		'xhtml-basic11' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "https://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">',
		'html5' => '<!DOCTYPE html>',
		'html4-strict' => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "https://www.w3.org/TR/html4/strict.dtd">',
		'html4-trans' => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "https://www.w3.org/TR/html4/loose.dtd">',
		'html4-frame' => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "https://www.w3.org/TR/html4/frameset.dtd">',
		'mathml1' => '<!DOCTYPE math SYSTEM "https://www.w3.org/Math/DTD/mathml1/mathml.dtd">',
		'mathml2' => '<!DOCTYPE math PUBLIC "-//W3C//DTD MathML 2.0//EN" "https://www.w3.org/Math/DTD/mathml2/mathml2.dtd">',
		'svg10' => '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.0//EN" "https://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">',
		'svg11' => '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "https://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">',
		'svg11-basic' => '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1 Basic//EN" "https://www.w3.org/Graphics/SVG/1.1/DTD/svg11-basic.dtd">',
		'svg11-tiny' => '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1 Tiny//EN" "https://www.w3.org/Graphics/SVG/1.1/DTD/svg11-tiny.dtd">',
		'xhtml-math-svg-xh' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1 plus MathML 2.0 plus SVG 1.1//EN" "https://www.w3.org/2002/04/xhtml-math-svg/xhtml-math-svg.dtd">',
		'xhtml-math-svg-sh' => '<!DOCTYPE svg:svg PUBLIC "-//W3C//DTD XHTML 1.1 plus MathML 2.0 plus SVG 1.1//EN" "https://www.w3.org/2002/04/xhtml-math-svg/xhtml-math-svg.dtd">',
		'xhtml-rdfa-1' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "https://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">',
		'xhtml-rdfa-2' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.1//EN" "https://www.w3.org/MarkUp/DTD/xhtml-rdfa-2.dtd">'
	];

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
	 * filter_clear()
	 */
	public function filter_clear ($key, $priority = null)
	{
		if ( ! empty($priority))
		{
			unset($this->_hooks_filters[$key][$priority]);
		}
		else
		{
			unset($this->_hooks_filters[$key]);
		}
	}

	/**
	 * nonfilter_clear()
	 */
	public function nonfilter_clear ($key, $priority = null)
	{
		if ( ! empty($priority))
		{
			unset($this->_hooks_filters_defs[$key][$priority]);
		}
		else
		{
			unset($this->_hooks_filters_defs[$key]);
		}
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
	 * action_clear()
	 */
	public function action_clear ($key, $priority = null)
	{
		if ( ! empty($priority))
		{
			unset($this->_hooks_actions[$key][$priority]);
		}
		else
		{
			unset($this->_hooks_actions[$key]);
		}
	}

	/**
	 * nonaction_clear()
	 */
	public function nonaction_clear ($key, $priority = null)
	{
		if ( ! empty($priority))
		{
			unset($this->_hooks_actions_defs[$key][$priority]);
		}
		else
		{
			unset($this->_hooks_actions_defs[$key]);
		}
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

		$this -> _send_response();

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
		$this->set_response_type ($type);

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
		$new = str_replace('-', '_', $new);
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
	 * get_timezone()
	 */
	public function get_utc ()
	{
		return $this->utc;
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

	/**
	 * UriProcess($class)
	 * Permite reejecutar algún procesamiento de manera manual externo a la app.php
	 */
	public function UriProcess ($class)
	{
		if ($class === 'All')
		{
			return $this->_init_uriprocess();
		}

		return $this->_init_uriprocess_callback($class);
	}

	public function set_IDS ($new)
	{
		$this->IDS = $new;
		return $this;
	}

	public function add_IDs ($new)
	{
		$this->IDS[] = $new;
		return $this;
	}

	public function get_IDS ()
	{
		return $this->IDS;
	}

	public function get_ProcessResult ($process)
	{
		return $this->_uriprocess_results[$process] ?? false;
	}

	public function GetClassInstancesWithIDS ($_class)
	{
		static $_instances = [];

		if ( ! is_string($_class)) return $_class;
		if (isset($_instances[$_class])) return( $_instances[$_class]);

		try
		{
			$_class_reflect  = new ReflectionClass($_class);
			$_class_instance = $_class_reflect -> newInstanceArgs($this->IDS);
		}
		catch(Exception $e)
		{
			// Class {Clase Llamada} does not have a constructor, so you cannot pass any constructor arguments
			if ( ! preg_match('/does not have a constructor/i', $e->getMessage()))
			{
				throw $e;
			}

			$_class_instance = new $_class();
		}
		$_instances[$_class] = $_class_instance;
		return $_instances[$_class];
	}

	public function GetProcessClass ($for, $uri = NULL)
	{
		static $_home_uri;

		is_null($uri) and $uri =  $this -> URI;

		if ($uri === '/')
		{
			$uri = '/inicio';
			isset($_home_uri) or 
			$_home_uri = $this -> filter_apply('JApi/uri-process/home', $uri, $this->URI, $this->IDS);
			$uri = $_home_uri;
		}

		$_uri = explode('/', $uri);
		empty($_uri[0]) and array_shift($_uri);
		array_unshift($_uri, $for);
		$_uri = array_values($_uri);

		$_class = null;
		$_func = null;
		$_func_params = ['index'];

		$_n = count($_uri) - 1;
		for($_tn = 1; $_tn <= $_n; $_tn++)
		{
			$_uri_t = $_uri;
			$_uri_t = array_map(function($o){
				$o = ucfirst($o);
				$o = str_replace(['.', '-'], ['_', '_'], $o);
				return $o;
			}, $_uri_t);
			$_class = implode('\\', $_uri_t);
			if (class_exists($_class))
			{
				break;
			}

			$_uri_t = $_uri;
			$_uri_t = array_map(function($o){
				$o = ucfirst($o);
				$o = preg_split('/[\.\-\_]/', $o);
				$o = array_map(function($p){
					return ucfirst($p);
				}, $o);
				$o = implode('', $o);
				return $o;
			}, $_uri_t);
			$_class = implode('\\', $_uri_t);
			if (class_exists($_class))
			{
				break;
			}

			$_uri_t = $_uri;
			$_uri_t = array_map(function($o){
				return ucfirst($o);
			}, $_uri_t);
			$_class = implode('\\', $_uri_t);
			if (class_exists($_class))
			{
				break;
			}

			$_uri_t = $_uri;
			$_class = implode('\\', $_uri_t);
			if (class_exists($_class))
			{
				break;
			}

			$_uri2 = $_uri;
			$_uri2_lp = array_pop($_uri2);

			if (preg_match('/\./', $_uri2_lp))
			{
				$_uri2_lp = explode('.', $_uri2_lp);
				array_pop($_uri2_lp);
				$_uri2_lp = implode('.', $_uri2_lp);
				$_uri2[] = $_uri2_lp;

				$_uri_t = $_uri2;
				$_uri_t = array_map(function($o){
					$o = ucfirst($o);
					$o = str_replace(['.', '-'], ['_', '_'], $o);
					return $o;
				}, $_uri_t);
				$_class = implode('\\', $_uri_t);
				if (class_exists($_class))
				{
					break;
				}

				$_uri_t = $_uri2;
				$_uri_t = array_map(function($o){
					$o = ucfirst($o);
					$o = preg_split('/[\.\-\_]/', $o);
					$o = array_map(function($p){
						return ucfirst($p);
					}, $o);
					$o = implode('', $o);
					return $o;
				}, $_uri_t);
				$_class = implode('\\', $_uri_t);
				if (class_exists($_class))
				{
					break;
				}

				$_uri_t = $_uri2;
				$_uri_t = array_map(function($o){
					return ucfirst($o);
				}, $_uri_t);
				$_class = implode('\\', $_uri_t);
				if (class_exists($_class))
				{
					break;
				}

				$_uri_t = $_uri2;
				$_class = implode('\\', $_uri_t);
				if (class_exists($_class))
				{
					break;
				}
			}

			$part = array_pop($_uri);
			array_unshift($_func_params, $part);
			$_class = NULL;
		}

		$_func = array_shift($_func_params);
		count($_func_params) > 0 and array_pop($_func_params);

		$_class = $this -> filter_apply('JApi/uri-process/get-class', $_class, $_func, $_func_params);
		$_class = $this -> filter_apply('JApi/uri-process/get-class/' . $for, $_class, $_func, $_func_params);

		if (is_null($_class))
		{
			return [null, null, null];
		}

		return [
			$_class, 
			$_func, 
			$_func_params
		];
	}

	public function set_process_result ($status, $message = NULL, $code = NULL)
	{
		is_null($this->_process_result) and 
		$this->_process_result = [
			'status' => null,
			'message' => null,
			'code' => null,
		];

		$this->_process_result['status'] = $status;
		is_null($message) or $this->_process_result['message'] = $message;
		is_null($code)    or $this->_process_result['code']    = $code;

		return $this;
	}

	public function del_process_result ()
	{
		$this->_process_result = null;
		return $this;
	}

	public function success($message = NULL, $code = NULL)
	{
		$this->set_process_result('success', $message, $code);
		return $this;
	}

	public function error($error = NULL, $code = NULL)
	{
		$this->set_process_result('error', $error, $code);
		return $this;
	}

	public function notice($message = NULL, $code = NULL)
	{
		$this->set_process_result('notice', $message, $code);
		return $this;
	}

	public function process_result_message($return_html = false, $clear = TRUE)
	{
		if (is_null($this->_process_result))
		{
			if ($return_html)
			{
				return null;
			}
			return $this;
		}

		$_class = [
			'alert',
			'alert-' . $this->_process_result['status'],
		];
		$this->_process_result['status'] === 'error' and $_class[] = 'alert-danger';

		$return = '';
		$return.= '<div class = "' . implode(' ', (array)$_class) . '" >';
		if ( ! is_null($this->_process_result['code']))
		{
			$return.= '<b class="alert-code">' . ucfirst($this->_process_result['status']) . ' #' . $this->_process_result['code'] . '</b>&nbsp;';
		}

		is_null($this->_process_result['message']) and 
		$this->_process_result['message'] = $this->_process_result['status'];

		$return.= '<span class="alert-message">' . $this->_process_result['message'] . '</span>';
		$return.= '</div>';

		$return = $this -> filter_apply('JApi/process-result/message', $return, $this->_process_result['status'], $this->_process_result);

		$clear and
		$this->_process_result = null;

		if ($return_html) return $return;
		echo $return;
		return $this;
	}

	public function exit_iftype($types, $status = NULL)
	{
		$types = (array)$types;
		$types = array_map('mb_strtolower', $types);

		in_array($this->_response_type,  $types) and
		force_exit ($status);

		return $this;
	}

	public function exit_ifhtml($status = NULL, $strict = false)
	{
		return $this->exit_iftype($strict ? ['html'] : ['html', 'body'], $status);
	}

	public function exit_ifjson($status = NULL)
	{
		return $this->exit_iftype(['json', 'cli'], $status);
	}

	/**
	 * Establecer una redirección en caso el Tipo sea
	 * @param	string	$type
	 * @param	string	$link
	 * @return	self
	 */
	public function redirect_iftype($types, $link)
	{
		$types = (array)$types;
		$types = array_map('mb_strtolower', $types);

		in_array($this->_response_type,  $types) and
		redirect ($link);

		return $this;
	}

	/**
	 * Establecer una redirección en caso el Tipo sea
	 * @param	string	$link
	 * @return	self
	 */
	public function redirect_ifhtml($link)
	{
		return $this -> redirect_iftype('html', $link);
	}

	/**
	 * Establecer una redirección en caso el Tipo sea
	 * @param	string	$link
	 * @return	self
	 */
	public function redirect_ifjson($link)
	{
		return $this -> redirect_iftype('json', $link);
	}

	public function addJSON ($key, $val = null)
	{
		if (is_array($key))

		{
			foreach ($key as $_key => $_val)
			{
				$this -> addJSON($_key, $_val);
			}
			return $this;
		}

		$this -> _response_data['json'][$key] = $val;
		return $this;
	}

	public function addHTML ($content)
	{
		if (is_array($content))
		{
			foreach ($content as $msg)
			{
				$this->addHTML($msg);
			}
			return $this;
		}

		$this -> _response_data['html']['body']['content'][] = $content;
		return $this;
	}

	public function force_uri ($uri = null)
	{
		if (preg_match('#^Display#', $uri))
		{
			$uri = str_replace('Display\\', '', $uri);
			$uri = mb_strtolower($uri);
			$uri = explode('::', $uri);
			array_unshift($uri, '');
			$uri = implode('/', $uri);
		}

		if (preg_match('#^http#', $uri))
		{
			$uri = str_replace(url(), '', $uri);
		}

		if (preg_match('#^http#', $uri))
		{
			trigger_error('Se esta forzando a una URI que no esta basado en la ruta de la aplicación: ' . $uri, E_USER_WARNING);
		}

		$this -> _response_data['html']['force_uri'] = $uri;
		return $this;
	}

	public function set_doctype ($new)
	{
		$this -> _response_data['html']['doctype'] = $new;
		return $this;
	}

	public function set_htmltag_attr ($key, $val = '')
	{
		if (is_array($key))
		{
			$this -> _response_data['html']['tag_attr'] = $key;
			return $this;
		}

		$this -> _response_data['html']['tag_attr'][$key] = $val;
		return $this;
	}

	public function set_headtag_attr ($key, $val = '')
	{
		if (is_array($key))
		{
			$this -> _response_data['html']['head']['tag_attr'] = $key;
			return $this;
		}

		$this -> _response_data['html']['head']['tag_attr'][$key] = $val;
		return $this;
	}

	public function set_title ($new)
	{
		$this -> _response_data['html']['head']['title'] = $new;
		return $this;
	}

	public function set_meta ($type, $key, $val = '')
	{
		$this -> _response_data['html']['head']['meta'][$type][$key] = $val;
		return $this;
	}

	public function set_meta_name ($key, $val = '')
	{
		$this -> _response_data['html']['head']['meta']['name'][$key] = $val;
		return $this;
	}

	public function set_meta_property ($key, $val = '')
	{
		$this -> _response_data['html']['head']['meta']['property'][$key] = $val;
		return $this;
	}

	public function set_canonical ($new = '')
	{
		$this -> _response_data['html']['head']['canonical'] = $new;
		return $this;
	}

	public function set_jsonld ($new = '')
	{
		$this -> _response_data['html']['head']['jsonld'] = $new;
		return $this;
	}

	public function set_favicon ($new = '')
	{
		$this -> _response_data['html']['head']['favicon'] = $new;
		return $this;
	}

	public function set_bodytag_attr ($key, $val = '')
	{
		if (is_array($key))
		{
			$this -> _response_data['html']['body']['tag_attr'] = $key;
			return $this;
		}

		$this -> _response_data['html']['body']['tag_attr'][$key] = $val;
		return $this;
	}

	public function set_bodyheader_before ($new = '')
	{
		$this -> _response_data['html']['body']['header_before'] = $new;
		return $this;
	}

	public function set_bodyheader ($new = '')
	{
		$this -> _response_data['html']['body']['header'] = $new;
		return $this;
	}

	public function set_bodyheader_after ($new = '')
	{
		$this -> _response_data['html']['body']['header_after'] = $new;
		return $this;
	}

	public function set_bodycontent_before ($new = '')
	{
		$this -> _response_data['html']['body']['content_before'] = $new;
		return $this;
	}

	public function set_bodycontent ($new = '')
	{
		$this -> _response_data['html']['body']['content'] = $new;
		return $this;
	}

	public function set_bodycontent_after ($new = '')
	{
		$this -> _response_data['html']['body']['content_after'] = $new;
		return $this;
	}

	public function set_bodyfooter_before ($new = '')
	{
		$this -> _response_data['html']['body']['footer_before'] = $new;
		return $this;
	}

	public function set_bodyfooter ($new = '')
	{
		$this -> _response_data['html']['body']['footer'] = $new;
		return $this;
	}

	public function set_bodyfooter_after ($new = '')
	{
		$this -> _response_data['html']['body']['footer_after'] = $new;
		return $this;
	}

	public function set_force_uri ($uri = null)
	{
		return $this -> force_uri($uri);
	}

	public function register_css ($codigo, $uri = NULL, $arr = [])
	{
		$lista =& $this->_response_data['html']['assets']['css'];

		if (is_null($uri) and count($arr) === 0)
		{
			if ( ! isset($lista[$codigo]))
			{
				$uri = $codigo;
				$codigo = NULL;
			}
		}
		elseif (is_array($uri) and count($arr) === 0)
		{
			$arr = $uri;
			
			if (isset($lista[$codigo]))
			{
				$uri = null;
			}
			else
			{
				$uri = $codigo;
				$codigo = NULL;
			}
		}

		if (is_null($codigo))
		{
			$codigo = parse_url($uri, PHP_URL_PATH);
			$codigo = preg_replace('/\.min$/i', '', basename($codigo, '.css'));
		}

		isset($lista[$codigo]) or $lista[$codigo] = [
			'codigo' => $codigo,
			'uri' => $uri,
			'loaded' => false,
			'orden' => 50,
			'version' => null,
			'position' => 'body',
			'inline' => false,
			'attr' => [],
			'deps' => [],
		];

		isset($arr['version']) or $arr['version'] = $lista[$codigo]['version'];
		is_null($uri) and $uri = $lista[$codigo]['uri'];

		if (is_null($lista[$codigo]['version']) or is_null($arr['version']) or $lista[$codigo]['version'] <= $arr['version'])
		{
			$lista[$codigo] = array_merge($lista[$codigo], ['uri' => $uri], $arr);
		}

		return $this;
	}

	public function load_css ($codigo, $uri = NULL, $arr = [])
	{
		$lista =& $this->_response_data['html']['assets']['css'];

		if (is_null($uri) and count($arr) === 0)
		{
			if ( ! isset($lista[$codigo]))
			{
				$uri = $codigo;
				$codigo = NULL;
			}
		}
		elseif (is_array($uri) and count($arr) === 0)
		{
			$arr = $uri;
			
			if (isset($lista[$codigo]))
			{
				$uri = null;
			}
			else
			{
				$uri = $codigo;
				$codigo = NULL;
			}
		}

		if (is_null($codigo))
		{
			$codigo = parse_url($uri, PHP_URL_PATH);
			$codigo = preg_replace('/\.min$/i', '', basename($codigo, '.css'));
		}

		$this -> register_css($codigo, $uri, $arr);
		$lista[$codigo]['loaded'] = true;
		return $this;
	}

	public function load_inline_css ($content, $orden = 80, $position = 'body')
	{
		static $codes = [];

		$lista =& $this->_response_data['html']['assets']['css'];

		if ( ! is_numeric($orden))
		{
			$position = $orden;
			$orden = NULL;
		}

		is_numeric($orden) or $orden = 80;
		in_array(mb_strtolower($position), ['head', 'body']) or $position = 'body';

		isset($codes[$position . '_' . $orden]) or $codes[$position . '_' . $orden] = uniqid($position . '_' . $orden . '_');
		$codigo = $codes[$position . '_' . $orden];
		isset($lista[$codigo]['uri']) and $content = $lista[$codigo]['uri'] . $content;

		$this -> load_css($codigo, $content, [
			'orden' => $orden,
			'position' => $position,
		]);
		$lista[$codigo]['inline'] = true;
		return $this;
	}

	public function register_js ($codigo, $uri = NULL, $arr = [])
	{
		$lista =& $this->_response_data['html']['assets']['js'];

		if (is_null($uri) and count($arr) === 0)
		{
			if ( ! isset($lista[$codigo]))
			{
				$uri = $codigo;
				$codigo = NULL;
			}
		}
		elseif (is_array($uri) and count($arr) === 0)
		{
			$arr = $uri;
			
			if (isset($lista[$codigo]))
			{
				$uri = null;
			}
			else
			{
				$uri = $codigo;
				$codigo = NULL;
			}
		}

		if (is_null($codigo))
		{
			$codigo = parse_url($uri, PHP_URL_PATH);
			$codigo = preg_replace('/\.min$/i', '', basename($codigo, '.js'));
		}

		isset($lista[$codigo]) or $lista[$codigo] = [
			'codigo' => $codigo,
			'uri' => $uri,
			'loaded' => false,
			'orden' => 50,
			'version' => null,
			'position' => 'body',
			'inline' => false,
			'attr' => [],
			'_before' => [],
			'_after' => [],
			'deps' => [],
		];

		isset($arr['version']) or $arr['version'] = $lista[$codigo]['version'];
		is_null($uri) and $uri = $lista[$codigo]['uri'];

		if (is_null($lista[$codigo]['version']) or is_null($arr['version']) or $lista[$codigo]['version'] <= $arr['version'])
		{
			$lista[$codigo] = array_merge($lista[$codigo], ['uri' => $uri], $arr);
		}

		return $this;
	}

	public function load_js ($codigo, $uri = NULL, $arr = [])
	{
		$lista =& $this->_response_data['html']['assets']['js'];

		if (is_null($uri) and count($arr) === 0)
		{
			if ( ! isset($lista[$codigo]))
			{
				$uri = $codigo;
				$codigo = NULL;
			}
		}
		elseif (is_array($uri) and count($arr) === 0)
		{
			$arr = $uri;
			
			if (isset($lista[$codigo]))
			{
				$uri = null;
			}
			else
			{
				$uri = $codigo;
				$codigo = NULL;
			}
		}

		if (is_null($codigo))
		{
			$codigo = parse_url($uri, PHP_URL_PATH);
			$codigo = preg_replace('/\.min$/i', '', basename($codigo, '.js'));
		}

		$this -> register_js($codigo, $uri, $arr);
		$lista[$codigo]['loaded'] = true;
		return $this;
	}

	public function load_inline_js ($content, $orden = 80, $position = 'body')
	{
		static $codes = [];

		$lista =& $this->_response_data['html']['assets']['js'];

		if ( ! is_numeric($orden))
		{
			$position = $orden;
			$orden = NULL;
		}

		is_numeric($orden) or $orden = 80;
		in_array(mb_strtolower($position), ['head', 'body']) or $position = 'body';

		isset($codes[$position . '_' . $orden]) or $codes[$position . '_' . $orden] = uniqid($position . '_' . $orden . '_');
		$codigo = $codes[$position . '_' . $orden];
		isset($lista[$codigo]['uri']) and $content = $lista[$codigo]['uri'] . $content;

		$this -> load_js($codigo, $content, [
			'orden' => $orden,
			'position' => $position,
		]);
		$lista[$codigo]['inline'] = true;
		return $this;
	}

	public function localize_js ($codigo, $content, $when = 'after')
	{
		$lista =& $this->_response_data['html']['assets']['js'];

		if ( ! isset($lista[$codigo]))
		{
			trigger_error('JS con código `' . $codigo . '` no encontrado');
			return $this;
		}

		$lista[$codigo]['_' . $when][] = $content;
		return $this;
	}

	public function snippet ($file, $return_content = TRUE, $declared_variables = [])
	{
		$directory = dirname($file);
		$file_name = basename($file, '.php') . '.php';

		if ($directory === '.')
		{
			$directory = DS;
		}
		elseif ($directory !== '')
		{
			$directory = strtr($directory, '/\\', DS.DS);
			$directory = DS . ltrim($directory, DS);
		}

		$file_view = null;

		$_app_directories_list = $this->get_app_directories();
		foreach($_app_directories_list as $base)
		{
			$file_view = $base . '/snippets' . $directory . DS . $file_name;

			if (file_exists($file_view))
			{
				break;
			}

			$file_view = null;
		}

		if (is_null($file_view))
		{
			trigger_error('Vista `' . $file . '` no encontrado', E_USER_WARNING);
			return NULL;
		}

		if (is_array($return_content))
		{
			$declared_variables = (array)$declared_variables;
			$declared_variables = array_merge($return_content, $declared_variables);

			$return_content = TRUE;
		}

		if ($return_content)
		{
			ob_start();
			extract($declared_variables, EXTR_REFS);
			include $file_view;
			$content = ob_get_contents();
			ob_end_clean();

			return $content;
		}

		return $file_view;
	}

	public function obj ($class, ...$pk)
	{
		$class = str_replace('/', '\\', $class);
		$class = explode('\\', $class);
		empty($class[0]) and array_shift($class);
		$class[0] === 'Objeto' or array_unshift($class, 'Objeto');
		$class = array_values($class);
		$class = implode('\\', $class);

		try
		{
			$_class_reflect  = new ReflectionClass($class);
			$_class_instance = $_class_reflect -> newInstanceArgs($pk);
		}
		catch(Exception $e)
		{
			// Class {Clase Llamada} does not have a constructor, so you cannot pass any constructor arguments
			if ( ! preg_match('/does not have a constructor/i', $e->getMessage()))
			{
				throw $e;
			}

			$_class_instance = new $class();
		}

		return $_class_instance;
	}

	public function translate ($frase, $n = NULL, ...$sprintf)
	{
		static $langs = [], $_lang = null;

		if ($_lang <> $this->LANG)
		{
			$langs = [];
			$_lang = $this->LANG;
		}

		if (count($langs) === 0)
		{
			$_app_directories_list = $this->get_app_directories(true);

			foreach($_app_directories_list as $base)
			{
				$_temp_lang = $_lang;
				$_temp_lang = explode('-', $_temp_lang, 2);
				$_temp_lang = $_temp_lang[0];

				if ($file = $base. DS. 'configs' . DS . 'translates'. DS. $_temp_lang . '.php' and file_exists($file))
				{
					@include $file;
				}

				if ($file = $base. DS. 'configs' . DS . 'translate'. DS. mb_strtolower($_temp_lang) . '.php' and file_exists($file))
				{
					@include $file;
				}

				if ($file = $base. DS. 'configs' . DS . 'translates'. DS. $_lang . '-noerror.php' and file_exists($file))
				{
					@include $file;
				}

				if ($file = $base. DS. 'configs' . DS . 'translates'. DS. $_lang . '.php' and file_exists($file))
				{
					@include $file;
				}

				if ($file = $base. DS. 'configs' . DS . 'translate'. DS. mb_strtolower($_lang) . '.php' and file_exists($file))
				{
					@include $file;
				}
			}
		}

		$_sprintf = function($frase, array $params = [])
		{
			array_unshift($params, $frase);
			return call_user_func_array('sprintf', $params);
		};

		is_null($n) and
		$n = 1;

		$frase_original = $frase;

		$frase_arr = (array)$frase;
		$frase_arr = array_values($frase_arr);
		$frase_count = count($frase_arr);
		$frase_count > 4 and $frase_count = 4;
		$frase = $frase_arr;
		$frase = array_shift($frase);

		switch($frase_count)
		{
			case 2:
					if($n==1) $frase_traduccion = $frase_arr[0];
				else          $frase_traduccion = $frase_arr[1];
				break;
			case 3:
					if($n==1) $frase_traduccion = $frase_arr[0];
				elseif($n==0) $frase_traduccion = $frase_arr[2];
				else          $frase_traduccion = $frase_arr[1];
				break;
			case 4:
					if($n==1) $frase_traduccion = $frase_arr[0];
				elseif($n==0) $frase_traduccion = $frase_arr[2];
				elseif($n <0) $frase_traduccion = $frase_arr[3];
				else          $frase_traduccion = $frase_arr[1];
				break;
			default:
				$frase_traduccion = array_shift($frase_arr);
				break;
		}
		$frase = $frase_traduccion;

		if ( ! isset($langs[$frase_traduccion]) and ! isset($langs[$frase]))
		{
			if ( ! preg_match('/^es/i', $this->LANG))
			{
				$path = mkdir2('/configs/translates', APPPATH);
				$_file_dest = $path . DS . $this->LANG . '-noerror.php';

				file_exists($_file_dest) or 
				file_put_contents($_file_dest, '<?php' .PHP_EOL. '/** Generado automáticamente el ' . date('d/m/Y H:i:s') . ' */'.PHP_EOL);

				$trace = debug_backtrace(false);
				while(count($trace) > 0 and (
					( ! isset($trace[0]['file']))    or 
					(   isset($trace[0]['file'])     and str_replace(JAPIPATH, '', $trace[0]['file']) <> $trace[0]['file']) or 
					(   isset($trace[0]['function']) and in_array   ($trace[0]['function'], ['_t', 'translate']))
				))
				{
					array_shift($trace);
				}

				$filename = __FILE__ . '#' . __LINE__;
				isset($trace[0]) and
				$filename = $trace[0]['file'] . '#' . $trace[0]['line'];

				$frase_esc = str_replace('\'', '\\\'', $frase);

				$message = '' . PHP_EOL;
				$message.= '/**' . PHP_EOL;
				$message.= ' * Traducción por defecto - ' . md5($frase) . PHP_EOL;
				$message.= ' * ' . PHP_EOL;
				$message.= ' * ' . $frase . PHP_EOL;
				$message.= ' * ' . PHP_EOL;
				$message.= ' * Parámetro N: '. $n . PHP_EOL;
				$message.= ' * Parámetros SPrintF: '. count($sprintf) . PHP_EOL;
				if (count($sprintf) > 0)
				{
					$message.= ' * Detalle Parámetros SPrintF: ' . PHP_EOL;
					$message.= ' * ```' . PHP_EOL;
					$message.= ' * '. implode(PHP_EOL . ' * ', explode("\n", json_encode($sprintf, JSON_PRETTY_PRINT))) . PHP_EOL;
					$message.= ' * ```' . PHP_EOL;
				}
				$message.= ' * Ubicado en '. $filename . PHP_EOL;
				$message.= ' */' . PHP_EOL;
				$message.= '$_frase = \'' . $frase_esc . '\';' . PHP_EOL;
				$message.= 'isset($langs[$_frase]) or $langs[$_frase] = ';

				if (is_array($frase_original))
				{
					$message.= '[' . PHP_EOL;
					foreach($frase_original as $_temp_frase)
					{
						$_temp_frase = str_replace('\'', '\\\'', $_temp_frase);
						$message.= '    \'' . $_temp_frase . '\',' . PHP_EOL;
					}
					$message.= ']';
				}
				else
				{
					$message.= '$_frase';
				}
				$message.= ';' . PHP_EOL;

				file_put_contents($_file_dest, $message, FILE_APPEND);
				$this -> action_apply('TraduccionFaltante', $frase, $n, $filename, $sprintf);
			}

			$langs[$frase_traduccion] = $frase_traduccion;
			$langs[$frase] = $frase_traduccion;
		}

		array_unshift($sprintf, $n);

		$traduccion = isset($langs[$frase_traduccion]) ? $langs[$frase_traduccion] : $langs[$frase];
		$traduccion = (array)$traduccion;
		$traduccion = array_values($traduccion);
		$traduccion_count = count($traduccion);
		$traduccion_count > 4 and $traduccion_count = 4;

		switch($traduccion_count)
		{
			case 2:
					if($n==1) $traduccion = $traduccion[0];
				else          $traduccion = $traduccion[1];
				break;
			case 3:
					if($n==1) $traduccion = $traduccion[0];
				elseif($n==0) $traduccion = $traduccion[2];
				else          $traduccion = $traduccion[1];
				break;
			case 4:
					if($n==1) $traduccion = $traduccion[0];
				elseif($n==0) $traduccion = $traduccion[2];
				elseif($n <0) $traduccion = $traduccion[3];
				else          $traduccion = $traduccion[1];
				break;
			default:
				$traduccion = array_shift($traduccion);
				break;
		}

		$traduccion = $_sprintf($traduccion, $sprintf);
		return $traduccion;
	}

	public function response_nocache()
	{
		header('Cache-Control: no-cache, must-revalidate'); //HTTP 1.1
		header('Pragma: no-cache'); //HTTP 1.0
		header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		return $this;
	}

	public function response_cache($days = 365, $for = 'private', $rev = 'no-revalidate')
	{
		$time = 60 * 60 * 24 * $days;
		$cache_expire_date = gmdate("D, d M Y H:i:s", time() + $time);

		header('User-Cache-Control: max-age=' . $time. ', ' . $for . ', ' . $rev); //HTTP 1.1
		header('Cache-Control: max-age=' . $time. ', ' . $for . ', ' . $rev); //HTTP 1.1
		header('Pragma: cache'); //HTTP 1.0
		header('Expires: '.$cache_expire_date.' GMT'); // Date in the future

		return $this;
	}

	protected function _send_response ()
	{
		/** 
		 * Si el tipo de respuesta es **manual** o **file** se enviará tal cual lo que se haya generado en los requests o response
		 * - El buffer fue limpiado antes de ejecutar el _init_uriprocess_request
		 * - Si se quiere cambiar el mime tendrá que ser en el mismo request o response 
		 */
		if (in_array($this->_response_type, ['manual', 'file']))
		{
			return;
		}

		$this -> action_apply('JApi/send-response/before');

		/** Estableciendo el mime */
		$_response_mime = $this -> _response_mime;
		if (is_null($_response_mime))
		{
			switch($this -> _response_type)
			{
				case 'html':case 'body':
					$_response_mime = 'text/html';
					break;
				case 'json':case 'cli':
					$_response_mime = 'application/json';
					break;
			}
		}

		$_response_charset = $this -> _response_charset;
		is_null($_response_charset) and $_response_charset = $this -> config('charset');
		is_null($_response_mime) or array_unshift($this->_headers, 'Content-Type: ' . $_response_mime . '; charset=' . $_response_charset);

		foreach ($this->_headers as $header)
		{
			$header = (array)$header;
			$header[] = true;
			list($_header, $_replace) = $header;

			@header($_header, $_replace);
		}
		$this -> action_apply('JApi/send-response/headers');

		$_buffer_content = $this -> GetAndClear_BufferContent();

		if (in_array($this->_response_type, ['json', 'cli']))
		{
			return $this -> _send_response_json ($_buffer_content);
		}

		return $this -> _send_response_html ($_buffer_content);
	}

	protected function _send_response_json ($_buffer_content)
	{
		$data = (array)$this -> _response_data['json'];

		if ( ! is_null($this->_process_result))
		{
			$data_pr = [
				'status' => $this->_process_result['status'],
				'message' => $this->_process_result['message'],
			];

			is_null($this->_process_result['code']) or $data_pr['code'] = $this->_process_result['code'];
			if ($this->_process_result['status'] === 'error')
			{
				$data_pr['error'] = $data_pr['message'];
				unset($data_pr['message']);
			}

			$data = array_merge($data_pr, $data);
		}

		if ( ! empty($_buffer_content))
		{
			$k = 'message';
			if (isset($data['error']) or (isset($data['message']) and ! is_null($data['message'])))
			{
				$k = 'html_content';
			}
			$data[$k] = $_buffer_content;
		}

		$data = $this -> filter_apply('JAapp/send-response-json/data', $data);

		$result = json_encode($data);

		if ($result === false)
		{
			switch (json_last_error()) {
				case JSON_ERROR_NONE:
					$error = 'No errors';
				break;
				case JSON_ERROR_DEPTH:
					$error = 'Maximum stack depth exceeded';
				break;
				case JSON_ERROR_STATE_MISMATCH:
					$error = 'Underflow or the modes mismatch';
				break;
				case JSON_ERROR_CTRL_CHAR:
					$error = 'Unexpected control character found';
				break;
				case JSON_ERROR_SYNTAX:
					$error = 'Syntax error, malformed JSON';
				break;
				case JSON_ERROR_UTF8:
					$error = 'Malformed UTF-8 characters, possibly incorrectly encoded';
				break;
				case JSON_ERROR_RECURSION:
					$error = 'One or more recursive references in the value to be encoded';
				break;
				case JSON_ERROR_INF_OR_NAN:
					$error = 'One or more NAN or INF values in the value to be encoded';
				break;
				case JSON_ERROR_UNSUPPORTED_TYPE:
					$error = 'A value of a type that cannot be encoded was given';
				default:
					$error = 'Unknown error';
				break;
			}

			trigger_error($error . ':' . PHP_EOL . PHP_EOL . $data, E_USER_ERROR);
			$result = '';
		}

		echo $result;
	}

	public function HtmlAttrs ($attrs)
	{
		if ( ! is_array($attrs))
		{
			$attrs = [$attrs => ''];
		}

		if (count($attrs) === 0)
		{
			return '';
		}

		$attrs = ' ' . implode(' ', array_map(function($key, $val){
			is_array($val) and $val = implode(' ', $val);
			
			return $key . (empty($val) ? '' : '="' . htmlspecialchars($val) . '"');
		}, array_keys($attrs), array_values($attrs)));

		return $attrs;
	}

	protected function _send_response_html ($_buffer_content)
	{
		$data = (array)$this -> _response_data['html'];
		$data = $this -> filter_apply('JAapp/send-response-html/data', $data);

		$_body_content = '';
		foreach($data['body']['content'] as $content)
		{
			if (is_callable($content))
			{
				ob_start();
				$_body_content .= call_user_func($content);
				$_body_content .= ob_get_contents();
				ob_end_clean();
			}
			else
			{
				$_body_content .= $content;
			}
		}

		$_body_content_before = $data['body']['content_before'];
		if (is_callable($_body_content_before))
		{
			ob_start();
			$_body_content_before = call_user_func($_body_content_before);
			$_body_content_before .= ob_get_contents();
			ob_end_clean();
		}

		$_body_content_after = $data['body']['content_after'];
		if (is_callable($_body_content_after))
		{
			ob_start();
			$_body_content_after = call_user_func($_body_content_after);
			$_body_content_after .= ob_get_contents();
			ob_end_clean();
		}

		if ($this->_response_type === 'body')
		{
			$process_result_message = $this -> process_result_message (true);

			$result =   $_body_content_before . 
						$process_result_message . 
						$_body_content . 
						$_buffer_content . 
						$_body_content_after;

			$result = $this -> filter_apply('JApi/send-response-body/content', $result);
			echo $result;
			die();
		}

		$_body_header_before = $data['body']['header_before'];
		if (is_callable($_body_header_before))
		{
			ob_start();
			$_body_header_before = call_user_func($_body_header_before);
			$_body_header_before .= ob_get_contents();
			ob_end_clean();
		}

		$_body_header = $data['body']['header'];
		if (is_callable($_body_header))
		{
			ob_start();
			$_body_header = call_user_func($_body_header);
			$_body_header .= ob_get_contents();
			ob_end_clean();
		}

		$_body_header_after = $data['body']['header_after'];
		if (is_callable($_body_header_after))
		{
			ob_start();
			$_body_header_after = call_user_func($_body_header_after);
			$_body_header_after .= ob_get_contents();
			ob_end_clean();
		}

		$_body_footer_before = $data['body']['footer_before'];
		if (is_callable($_body_footer_before))
		{
			ob_start();
			$_body_footer_before = call_user_func($_body_footer_before);
			$_body_footer_before .= ob_get_contents();
			ob_end_clean();
		}

		$_body_footer = $data['body']['footer'];
		if (is_callable($_body_footer))
		{
			ob_start();
			$_body_footer = call_user_func($_body_footer);
			$_body_footer .= ob_get_contents();
			ob_end_clean();
		}

		$_body_footer_after = $data['body']['footer_after'];
		if (is_callable($_body_footer_after))
		{
			ob_start();
			$_body_footer_after = call_user_func($_body_footer_after);
			$_body_footer_after .= ob_get_contents();
			ob_end_clean();
		}

		$process_result_message = $this -> process_result_message (true);

		$_head_jsonld = $data['head']['jsonld'];
		if (is_callable($_head_jsonld))
		{
			ob_start();
			$_head_jsonld = call_user_func($_head_jsonld);
			$_head_jsonld .= ob_get_contents();
			ob_end_clean();
		}

		$_head_favicon = $data['head']['favicon'];
		if (is_callable($_head_favicon))
		{
			ob_start();
			$_head_favicon = call_user_func($_head_favicon);
			$_head_favicon .= ob_get_contents();
			ob_end_clean();
		}
		if (empty($_head_favicon))
		{
			$_head_favicon = '<link rel="shortcut icon" href="' . url('base') . '/favicon.ico">';
		}

		$_html_tag_before = '';
		$_head_tag_before = '';
		$_head_html = '';
		$_head_tag_after = '';
		$_body_tag_before = '';
		$_body_html = '';
		$_body_tag_after = '';
		$_html_tag_after = '';

		$_body_content =	$_body_content_before . 
							$process_result_message . 
							$_body_content . 
							$_buffer_content . 
							$_body_content_after;

		$_body_content = $this -> filter_apply('JApi/send-response-html/body-content', $_body_content);

		$_body_html = 	$_body_header_before . 
						$_body_header . 
						$_body_header_after . 
						$_body_content . 
						$_body_footer_before . 
						$_body_footer . 
						$_body_footer_after;

		$_body_html = $this -> filter_apply('JApi/send-response-html/body', $_body_html);

		$title =& $data['head']['title'];
		empty($title) and $title = 'Plataforma';

		$title = $this -> filter_apply('JApi/send-response-html/title', $title);
		$title = $this -> filter_apply('title', $title, $data);

		isset($data['tag_attr']['lang']) or 
		$data['tag_attr']['lang'] = $this->LANG;

		empty($data['head']['canonical']) and $data['head']['canonical'] = url('full'); 

		$doctype = $data['doctype'];
		isset(self::$_doctypes[$doctype]) and $doctype = self::$_doctypes[$doctype];
		$_html_tag_before .= $doctype . PHP_EOL;

		$_html_tag_before .= '<html' . $this -> HtmlAttrs($data['tag_attr']) . '>' . PHP_EOL;
		$_head_tag_before .= '<head' . $this -> HtmlAttrs($data['head']['tag_attr']) . '>' . PHP_EOL;
		$_body_tag_before .= '<body' . $this -> HtmlAttrs($data['body']['tag_attr']) . '>' . PHP_EOL;

		$_response_charset = $this -> _response_charset;
		is_null($_response_charset) and $_response_charset = $this -> config('charset');
		$_head_html .= '<meta charset="' . $_response_charset . '">' . PHP_EOL;
		$_head_html .= '<meta http-equiv="Content-Type" content="text/html; charset=' . $_response_charset . '" />' . PHP_EOL;

		if ( ! isset($data['head']['meta']['name']['apple-mobile-web-app-title']) or 
			   empty($data['head']['meta']['name']['apple-mobile-web-app-title']))
		{
			$data['head']['meta']['name']['apple-mobile-web-app-title'] = $title;
		}

		if ( ! isset($data['head']['meta']['name']['application-name']) or 
			   empty($data['head']['meta']['name']['application-name']))
		{
			$data['head']['meta']['name']['application-name'] = 'Aplicación basada en JApi';
		}

		$data['head']['meta'] = $this -> filter_apply('JApi/send-response-html/head-meta', $data['head']['meta'], $_head_html);
		foreach($data['head']['meta'] as $param => $dats)
		{
			foreach($dats as $key => $val)
			{
				is_array($val) and $val = implode(',', $val);
				$_head_html .= '<meta ' . $param . '="' . htmlspecialchars($key) . '" content="' . htmlspecialchars($val) . '" />' . PHP_EOL;
			}
		}

		$_head_html .= '<title itemprop="name">' . $title . '</title>' . PHP_EOL;
		$_head_html .= '<link rel="canonical" href="' . $data['head']['canonical'] . '" itemprop="url" />' . PHP_EOL;

		empty($_head_jsonld) or $_head_html .= $_head_jsonld . PHP_EOL;
		empty($_head_favicon) or $_head_html .= $_head_favicon . PHP_EOL;

		$_head_html .= '<base href="' . url('base') . '" />' . PHP_EOL;

		$_head_html_script = '';

		/** Añadir el script location.base en $_head_html_script */
		$_head_html_script .= 'location.base="' . url('base') . '";';
		$_head_html_script .= 'location.full="' . url('full') . '";';
		$_head_html_script .= 'location.cookie="' . url('cookie-base') . '";';

		/** Añadir el script force_uri en $_head_html_script */
		$force_uri = $data['force_uri'];
		if (is_null($force_uri))
		{
			$force_uri =  url('path');
			if (count($_GET) > 0)
			{
				$force_uri .= '?' . http_build_query($_GET);
			}
		}
		$force_uri = url('base') . $force_uri;
		$_head_html_script .= 'history.replaceState([], "", "' . $force_uri . '");';

		$_head_html_script = $this -> filter_apply('JApi/send-response-html/head/script', $_head_html_script);

		if ( ! empty($_head_html_script))
		{
			$_head_html .= '<script>';
			$_head_html .= $_head_html_script . PHP_EOL;
			$_head_html .= '</script>' . PHP_EOL;
		}

		$_head_html = $this -> filter_apply('JApi/send-response-html/head', $_head_html);

		$data_assets_css = $this -> _reorder_assets($data['assets']['css']);
		$data_assets_js = $this -> _reorder_assets($data['assets']['js']);

		$data_assets_css_head_noinline = array_filter($data_assets_css, function($o){
			return $o['loaded'] and $o['position'] === 'head' and ! $o['inline'];
		});
		$data_assets_css_head_inline = array_filter($data_assets_css, function($o){
			return $o['loaded'] and $o['position'] === 'head' and $o['inline'];
		});
		$data_assets_css_body_noinline = array_filter($data_assets_css, function($o){
			return $o['loaded'] and $o['position'] === 'body' and ! $o['inline'];
		});
		$data_assets_css_body_inline = array_filter($data_assets_css, function($o){
			return $o['loaded'] and $o['position'] === 'body' and $o['inline'];
		});
		$data_assets_js_head_noinline = array_filter($data_assets_js, function($o){
			return $o['loaded'] and $o['position'] === 'head' and ! $o['inline'];
		});
		$data_assets_js_head_inline = array_filter($data_assets_js, function($o){
			return $o['loaded'] and $o['position'] === 'head' and $o['inline'];
		});
		$data_assets_js_body_noinline = array_filter($data_assets_js, function($o){
			return $o['loaded'] and $o['position'] === 'body' and ! $o['inline'];
		});


		$data_assets_js_body_inline = array_filter($data_assets_js, function($o){
			return $o['loaded'] and $o['position'] === 'body' and $o['inline'];
		});

		$parsed = explode('<script>', $_body_html);
		if (count($parsed) > 1)
		{
			$_body_html = array_shift($parsed);
			foreach($parsed as $_temp)
			{
				$_temp = explode('</script>', $_temp, 2);
				$_body_html.= $_temp[1];
				$data_assets_js_body_inline[] = [
					'codigo' => uniqid('body_inline_js_founds_'),
					'uri' => $_temp[0],
					'loaded' => true,
					'version' => null,
					'inline' => true,
					'attr' => [],
					'deps' => [],
					'position' => 'body',
					'orden_original' => 99,
					'orden' => 99,
				];
			}
		}

		$parsed = explode('<style>', $_body_html);
		if (count($parsed) > 1)
		{
			$_body_html = array_shift($parsed);
			foreach($parsed as $_temp)
			{
				$_temp = explode('</style>', $_temp, 2);
				$_body_html.= $_temp[1];
				$data_assets_css_body_inline[] = [
					'codigo' => uniqid('body_inline_css_founds_'),
					'uri' => $_temp[0],
					'loaded' => true,
					'version' => null,
					'inline' => true,
					'attr' => [],
					'deps' => [],
					'position' => 'body',
					'orden_original' => 99,
					'orden' => 99,
				];
			}
		}

		foreach($data_assets_css_head_noinline as $dats)
		{
			$dats = array_merge([
				'codigo'    => NULL,
				'uri'       => NULL,
				'version'   => NULL,
				'attr'      => [],
			], $dats);

			$attr = array_merge([
				'rel' => 'stylesheet',
				'type' => 'text/css',
			], (array)$dats['attr']);

			if ( ! empty($dats['uri']))
			{
				$attr['href'] = $dats['uri'];
				if ( ! is_null($dats['version']))
				{
					$_has_sign = preg_match('/\?/i', $attr['href']);
					$attr['href'] .= ($_has_sign ? '&' : '?') . $dats['version'];
				}
			}
			
			$_head_html .= PHP_EOL . '<link' . $this -> HtmlAttrs ($attr) . ' />';
		}

		foreach($data_assets_js_head_noinline as $dats)
		{
			$dats = array_merge([
				'codigo'    => NULL,
				'uri'       => NULL,
				'version'   => NULL,
				'attr'      => [],
				'_before'   => [],
				'_after'   => [],
			], $dats);

			$attr = array_merge([
				'type' => 'application/javascript',
			], (array)$dats['attr']);

			if ( ! empty($dats['uri']))
			{
				$attr['src'] = $dats['uri'];
				if ( ! is_null($dats['version']))
				{
					$_has_sign = preg_match('/\?/i', $attr['href']);
					$attr['src'] .= ($_has_sign ? '&' : '?') . $dats['version'];
				}
			}

			foreach($dats['_before'] as $_tmp_script)
			{
				function_exists('js_compressor') and $_tmp_script = js_compressor($_tmp_script);
				$_head_html .= PHP_EOL . '<script>' . $_tmp_script . '</script>';
			}

			$_head_html .= PHP_EOL . '<script' . $this -> HtmlAttrs ($attr) . '></script>';

			foreach($dats['_after'] as $_tmp_script)
			{
				function_exists('js_compressor') and $_tmp_script = js_compressor($_tmp_script);
				$_head_html .= PHP_EOL . '<script>' . $_tmp_script . '</script>';
			}
		}

		foreach($data_assets_css_head_inline as $dats)
		{
			$dats = array_merge([
				'codigo'    => NULL,
				'uri'       => NULL,
				'attr'      => [],
			], $dats);

			$attr = array_merge([
				'rel' => 'stylesheet',
				'type' => 'text/css',
			], (array)$dats['attr']);

			$content = $dats['uri'];
			function_exists('css_compressor') and $content = css_compressor($content);

			$_head_html .= PHP_EOL . '<style' . $this -> HtmlAttrs ($attr) . '>' . $content . '</style>';
		}

		foreach($data_assets_js_head_inline as $dats)
		{
			$dats = array_merge([
				'codigo'    => NULL,
				'uri'       => NULL,
				'attr'      => [],
			], $dats);

			$attr = array_merge([
				'type' => 'application/javascript',
			], (array)$dats['attr']);

			$content = $dats['uri'];
			function_exists('js_compressor') and $content = js_compressor($content);

			$_head_html .= PHP_EOL . '<script' . $this -> HtmlAttrs ($attr) . '>' . $content . '</script>';
		}

		foreach($data_assets_css_body_noinline as $dats)
		{
			$dats = array_merge([
				'codigo'    => NULL,
				'uri'       => NULL,
				'version'   => NULL,
				'attr'      => [],
			], $dats);

			$attr = array_merge([
				'rel' => 'stylesheet',
				'type' => 'text/css',
			], (array)$dats['attr']);

			if ( ! empty($dats['uri']))
			{
				$attr['href'] = $dats['uri'];
				if ( ! is_null($dats['version']))
				{
					$_has_sign = preg_match('/\?/i', $attr['href']);
					$attr['href'] .= ($_has_sign ? '&' : '?') . $dats['version'];
				}
			}
			
			$_body_html .= PHP_EOL . '<link' . $this -> HtmlAttrs ($attr) . ' />';
		}

		foreach($data_assets_js_body_noinline as $dats)
		{
			$dats = array_merge([
				'codigo'    => NULL,
				'uri'       => NULL,
				'version'   => NULL,
				'attr'      => [],
				'_before'   => [],
				'_after'   => [],
			], $dats);

			$attr = array_merge([
				'type' => 'application/javascript',
			], (array)$dats['attr']);

			if ( ! empty($dats['uri']))
			{
				$attr['src'] = $dats['uri'];
				if ( ! is_null($dats['version']))
				{
					$_has_sign = preg_match('/\?/i', $attr['src']);
					$attr['src'] .= ($_has_sign ? '&' : '?') . $dats['version'];
				}
			}

			foreach($dats['_before'] as $_tmp_script)
			{
				function_exists('js_compressor') and $_tmp_script = js_compressor($_tmp_script);
				$_body_html .= PHP_EOL . '<script>' . $_tmp_script . '</script>';

			}

			$_body_html .= PHP_EOL . '<script' . $this -> HtmlAttrs ($attr) . '></script>';

			foreach($dats['_after'] as $_tmp_script)
			{
				function_exists('js_compressor') and $_tmp_script = js_compressor($_tmp_script);
				$_body_html .= PHP_EOL . '<script>' . $_tmp_script . '</script>';
			}
		}

		foreach($data_assets_css_body_inline as $dats)
		{
			$dats = array_merge([
				'codigo'    => NULL,
				'uri'       => NULL,
				'attr'      => [],
			], $dats);

			$attr = array_merge([
				'rel' => 'stylesheet',
				'type' => 'text/css',
			], (array)$dats['attr']);

			$content = $dats['uri'];
			function_exists('css_compressor') and $content = css_compressor($content);

			$_body_html .= PHP_EOL . '<style' . $this -> HtmlAttrs ($attr) . '>' . $content . '</style>';
		}

		foreach($data_assets_js_body_inline as $dats)
		{
			$dats = array_merge([
				'codigo'    => NULL,
				'uri'       => NULL,
				'attr'      => [],
			], $dats);

			$attr = array_merge([
				'type' => 'application/javascript',
			], (array)$dats['attr']);

			$content = $dats['uri'];
			function_exists('js_compressor') and $content = js_compressor($content);

			$_body_html .= PHP_EOL . '<script' . $this -> HtmlAttrs ($attr) . '>' . $content . '</script>';
		}

		$_head_tag_after .= PHP_EOL . '</head>' . PHP_EOL;
		$_body_tag_after .= PHP_EOL . '</body>' . PHP_EOL;
		$_html_tag_after .= '</html>';

		$result =   $_html_tag_before . 
					$_head_tag_before . 
					$_head_html . 
					$_head_tag_after . 
					$_body_tag_before . 
					$_body_html . 
					$_body_tag_after . 
					$_html_tag_after;

		$result = $this -> filter_apply('JApi/send-response-html/result', $result);

		@header('Referrer-Policy: no-referrer-when-downgrade');
		echo $result;
	}

	protected function _reorder_assets ($arr)
	{
		// Validar Dependencias
		foreach($arr as &$item)
		{
			$position = $item['position'];
			unset($item['position']);
			$item['position'] = $position;
			$item['orden_original'] = $item['orden'];
			unset($item['orden']);

			if (count($item['deps']) === 0)
			{
				$item['orden'] = $item['orden_original'];
			}
			unset($item);
		}

		$validar = true;
		$validar_c = 10;
		while($validar and $validar_c > 0)
		{
			$validar = false;
			$validar_c--;

			foreach($arr as &$item)
			{
				if (isset($item['orden']))
				{
					continue;
				}

				if (count($item['deps']) === 0)
				{
					$item['orden'] = $item['orden_original'];
					continue;
				}

				$_orden_settng = true;
				$_orden_setted = $item['orden_original'];
				$_position_new = $item['position'];
				foreach($item['deps'] as $_dep)
				{
					if ( ! isset($arr[$_dep]))
					{
						// Dependencia invalida
						continue;
					}

					if (isset($arr[$_dep]['orden']))
					{
						if ($_orden_setted < $arr[$_dep]['orden'])
						{
							$_orden_setted = $arr[$_dep]['orden'] + 0.01;
							if ($arr[$_dep]['position'] === 'body')
							{
								$_position_new = 'body';
							}
							if ( ! $arr[$_dep]['loaded'])
							{
								$item['orden'] = $item['orden_original'];
								$item['loaded'] = false;
								continue 2;
							}
						}
						continue;
					}
					
					$validar = true;
					$_orden_settng = false;
				}

				if ($_orden_settng)
				{
					$item['orden'] = $_orden_setted;
					$item['position'] = $_position_new;
					continue;
				}
			}
		}

		usort($arr, function($a, $b){
			if ($a['orden'] === $b['orden']) return 0;
			return $a['orden'] < $b['orden'] ? -1 : 1;
		});

		return $arr;
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
		$class_file_lista = array_unique($class_file_lista);

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
					$_tmp_file = $_dirbase . $class_file;
					if (file_exists($_tmp_file) and class_exists($_class_required, FALSE) === FALSE)
					{
						require_once $_tmp_file;
						return;
					}
				}
			}
		}

		foreach($class_dir_base_array as $class_dir_base)
		{
			foreach($directories as $directory)
			{
				$_dirbase = $directory . $class_dir_base;

				foreach($class_file_lista as $class_file)
				{
					$_tmp_file = $_dirbase . $class_file;
					if (file_exists($_tmp_file) and class_exists($class, FALSE) === FALSE)
					{
						require_once $_tmp_file;
						return;
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
			) or isset($_GET['json']) or isset($_GET['cron']) or preg_match('/\.json$/i',  $this -> URI)
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
			$_url_path =& url('path');
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
	 * _init_get_uri_ids()
	 */
	protected function _init_get_uri_ids ()
	{
		$this -> IDS = $this -> filter_apply ('JApi/Uri/IDS', $this -> IDS, $this -> URI);

		// Quitar los números del URI
		$_uri = explode('/', $this -> URI);
		empty($_uri[0]) and array_shift($_uri);

		$_uri_new = [];
		foreach($_uri as $_uri_part)
		{
			if (preg_match('/^[0-9]+$/', $_uri_part))
			{
				$this -> IDS[] = $_uri_part;
			}
			else
			{
				$_uri_new[] = $_uri_part;
			}
		}
		$this -> URI = '/' . implode('/', $_uri_new);
	}

	/**
	 * _init_uriprocess()
	 */
	protected function _init_uriprocess ()
	{
		$routes_bases = self::$_routes_bases;
		foreach($routes_bases as $route)
		{
			$this -> _init_uriprocess_callback ($route);
		}

		$this -> action_apply ('JApi/uri-process/validations');

		in_array($this->_response_type, ['file', 'manual']) and
		$this -> set_response_type ($this->_response_type);

		foreach(['Request', 'Response'] as $route)
		{
			$this -> _init_uriprocess_callback ($route);
		}
	}

	/**
	 * _init_uriprocess_callback()
	 */
	protected function _init_uriprocess_callback ($Process)
	{
		$Process_lower = mb_strtolower($Process);
		$_uri_process = true;
		$_uri_process = $this -> filter_apply ('JApi/uri-process/' . $Process      , $_uri_process, $this->URI, $this->IDS);
		$_uri_process = $this -> filter_apply ('JApi/uri-process/' . $Process_lower, $_uri_process, $this->URI, $this->IDS);

		if ( ! $_uri_process) return;

		$this -> action_apply ('JApi/uri-process/' . $Process       . '/before');
		$this -> action_apply ('JApi/uri-process/' . $Process_lower . '/before');

		list($_class, $_func, $_params) = $this -> GetProcessClass ($Process);
		$_class  = $this -> filter_apply ('JApi/uri-process/' . $Process_lower . '/class',       $_class, $_func, $_params, $this->URI, $this->IDS);
		$_func   = $this -> filter_apply ('JApi/uri-process/' . $Process_lower . '/func',        $_func, $_params, $_class, $this->URI, $this->IDS);
		$_params = $this -> filter_apply ('JApi/uri-process/' . $Process_lower . '/func_params', $_params, $_func, $_class, $this->URI, $this->IDS);

		if (is_null($_class))
		{
			$Process === 'Response' and 
			$this->_init_uriprocess_response_404();
			return;
		}

		$_class_instance = $this -> GetClassInstancesWithIDS($_class);

		$method = mb_strtoupper($this -> _rqs_method);
		$type   = mb_strtoupper($this -> _response_type);

		foreach([$method . '_', ''] as $x)
		{
			foreach([$type . '_', ''] as $y)
			{
				if ($_func_tmp = $x . $y . $_func and is_callable([$_class_instance, $_func_tmp]))
				{
					$_func = $_func_tmp;
					break 2;
				}

				if ($_func_tmp = $y . $x . $_func and is_callable([$_class_instance, $_func_tmp]))
				{
					$_func = $_func_tmp;
					break 2;
				}
			}
		}

		if (is_null($_func) or ! is_callable([$_class_instance, $_func]))
		{
			$Process === 'Response' and 
			$this->_init_uriprocess_response_404();
			return;
		}

		call_user_func_array([$_class_instance, $_func], $_params);
		$this->_uriprocess_results[$Process] = true;

		$this -> action_apply ('JApi/uri-process/' . $Process      );
		$this -> action_apply ('JApi/uri-process/' . $Process_lower);
	}

	/**
	 * _init_uriprocess_response_404()
	 * Solo los response tipo html y body pueden retornar una página de error
	 * Si es json o cli debe retornar el mensaje de procesamiento,
	 * Si es file o manual se debe retornar de manera independiente la pagina de no-existe si es el caso
	 */
	protected function _init_uriprocess_response_404 ()
	{
		if ( ! in_array($this->_response_type, ['html', 'body']))
		{
			// Solo se mostrará una página de error404 en los retornos tipo html o body
			return;
		}

		$uri = '/error404';
		$uri = $this -> filter_apply('JApi/uri-process/error404', $uri, $this->URI, $this->IDS);

		http_code(404, 'Página no encontrada');
		list($_class, $_func, $_params) = $this -> GetProcessClass ('Response', $uri);

		if (is_null($_class)) return;

		$_class_instance = $this -> GetClassInstancesWithIDS($_class);

		if ( ! is_callable([$_class_instance, $_func]))
		{
			return;
		}

		call_user_func_array([$_class_instance, $_func], $_params);
	}

	/**
	 * init()
	 */
	public function init ()
	{
		static $_init = true;

		if ( ! $_init) return $this;
		$_init = false;

//		$__debug_start_mem  = memory_get_usage();
//		$__debug_start_time = microtime(true);

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

//		$__debug_end_mem  = memory_get_usage();
//		$__debug_end_time = microtime(true);
//
//		$__debug_used_mem  = $__debug_end_mem - $__debug_start_mem;
//		$__debug_used_time = $__debug_end_time - $__debug_start_time;
//
//		die_array([
//			'memoria utilizada' => transform_size($__debug_used_mem),
//			'tiempo de proceso' => convertir_tiempo($__debug_used_time),
//		]);

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

		/** Obtener IDs del enlace */
		$this -> _init_get_uri_ids();

		/** Inicio el procesamiento del URI */
		$this -> action_apply ('JApi/uri-process/before', $this->URI, $this->IDS);

		/**
		 * Procesar acciones de validacion de autenticación
		 * Aquí se puede cambiar el URI en caso de no estar logueado dependiendo de la URI
		 * Pueden existir URIs que se permitan sin logueo
		 */
		$this -> action_apply ('JApi/uri-process/login', $this->URI, $this->IDS);
		$this -> action_apply ('JApi/uri-process/auth', $this->URI, $this->IDS);
		$this -> action_apply ('JApi/uri-process/authenticate', $this->URI, $this->IDS);

		/** Iniciando el proceso del uri */
		$this -> _init_uriprocess ();

		/** Finaliza el procesamiento del URI */
		$this -> action_apply ('JApi/uri-process/after', $this->URI, $this->IDS);
	}

}