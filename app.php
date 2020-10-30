<?php
/**
 * /JApi/app.php
 * @filesource
 */

defined('HOMEPATH') or exit('Archivo no se puede llamar directamente');

if (function_exists('APP')) return APP(); // previene no ser leido doble vez

if ( ! class_exists('JApi'))
{
	/**
	 * JApi
	 * Clase Maestra del framework
	 */
	class JApi
	{
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
		 * $_app_directories
		 * Aloja todas los directorios en el orden generado por la prioridad
		 */
		protected $_app_directories_list = [];

		/**
		 * add_app_directory ()
		 * Función que permite añadir directorios de aplicación las cuales serán usados para buscar y procesar 
		 * la información para la solicitud del usuario
		 *
		 * @param $directory String Directorio a añadir
		 * @param $orden Integer Prioridad de lectura del directorio
		 * @return self
		 */
		public function add_app_directory ($directory, $orden = 50)
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
				trigger_error('Directorio `' . $_directory . '` no existe', E_USER_WARNING);
				return $this;
			}

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
		public function map_app_directories (callable $callback, $reverse = FALSE)
		{
			$_app_directories_list = $this->get_app_directories($reverse);
			array_map($callback, $_app_directories_list);

			return $this;
		}

		protected $_ob_level = null;

		/**
		 * init()
		 */
		public function init ()
		{
			static $_init = true;

			if ( ! $_init) return $this;
			$_init = false;

			$this -> _init_variables ();
			$this -> _init_noerrors ();

			/** Corrigiendo directorio base cuando se ejecuta como comando */
			$this->is_command() and chdir(COREPATH);

			/** Iniciando el leído del buffer */
			$this->_ob_level = ob_get_level();
			ob_start();

			/** Iniciando las variables _SESSION */
			session_start();

			/** Añadiendo los directorios de aplicaciones base */
			$this
			-> add_app_directory(APPPATH , 25) // Orden 25 (será leído al inicio, a menos que se ponga otro directorio con menor orden)
			-> add_app_directory(COREPATH, 75) // Orden 75 (será leido al final, a menos que se ponga otro directorio con mayor orden)
			;

			/**
			 * Se llama al /init.php del APPPATH
			 * En este archivo se puede:
			 * - Añadir mas directorios de aplicación
			 * - Establecer Formatos de Uri
			 * - Añadir Hooks (add_action, add_filter)
			 * **Recordar Que:** Aún no se han cargado hasta este punto solo la clase y controlado los errores
			 */
			file_exists (APPPATH . '/init.php') and 
			require_once APPPATH . '/init.php';

			/** Definiendo el handler para cuando finalice el request ya sea con o sin response */
			register_shutdown_function([$this, '_handler_shutdown']);

			/** Si se abre alguna conección a la base datos, es recomendado cerrarla */
			$this -> action_add('do_when_end', [$this, 'sql_stop_all']);

			/** Iniciando autoload */
			spl_autoload_register([$this, '_init_autoload']);

			$this

			/**
			 * Cargando todos los archivos de funciones
			 * El orden a recorrer es de menor a mayor para que los directorios prioritarios puedan crear primero las funciones actualizadas
			 */
			-> map_app_directories ([$this, '_init_load_functions'])

			/**
			 * Cargando el autoload de la carpeta vendor
			 * El orden a recorrer es de menor a mayor para que los directoreios prioritarios puedan cargar sus librerías actualizadas
			 */
			-> map_app_directories ([$this, '_init_load_vendor'])

			/**
			 * Cargando la configuración de la aplicación
			 * El orden a recorrer es de mayor a menor para que los directorios prioritariosde puedan sobreescribir los por defecto
			 */
			-> map_app_directories ([$this, '_init_load_config'], true)
			;

			$this -> _rqs_method = $this -> url('request_method');
			$this -> _rqs_uri_inicial = $this -> URI = $this -> url('path');

			if (defined('FORCE_RSP_TYPE'))
			{
				$this -> _response_type = FORCE_RSP_TYPE;
			}
			elseif (isset($_GET['contentOnly']) or (isset($_GET['_']) and $_GET['_'] === 'co'))
			{
				$this -> _response_type = 'body';
			}
			elseif (
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
				$this -> _response_type = 'json';
			}
			elseif ($this -> is_command())
			{
				$this -> _response_type = 'cli';
			}

			$this -> _headers = [];

			if ( ! $this->is_command()):
				
			/** Comprobar si se llego con HTTPS o si no se requiere */
			$HTTPS = $this -> url('https');
			$HTTPS_def = $this -> config('https');

			if ( ! is_null($HTTPS_def) and $HTTPS !== $HTTPS_def)
			{
				$scheme =& url('scheme');

				$scheme = $HTTPS_def ? 'https' : 'http';
				$this -> redirect( $this -> build_url( $this -> url('array')));
				exit();
			}

			/** Comprobar si se cargo WWW o si no se requiere */
			$WWW = $this -> url('www');
			$WWW_def = $this -> config('www', ['www' => NULL]);

			if ( ! is_null($WWW_def) and $WWW !== $WWW_def)
			{
				$host =& $this -> url('host');

				if ($WWW_def)
				{
					$host = 'www.' . $host;
				}
				else
				{
					$host = preg_replace('/^www\./i', '', $host);
				}

				$this -> redirect( $this -> build_url( $this -> url('array')));
				exit();
			}

			/**
			 * Procesar URI para validación de idioma
			 * Se procesa la validación del lenguaje de manera prioritaria
			 * por si el identificador del idioma esta en el URI y se deba omitir 
			 * para el procesamiento del mismo
			 */
			$_uri_process_lang = true;
			$_uri_process_lang = $this -> filter_apply ('JApi/uri-process/lang', $_uri_process_lang, $this->URI, $this->IDS);

			if ($_uri_process_lang)
			{
				$this -> action_apply ('JApi/uri-process/lang/before');

				/** Obtener el idioma dentro del uri */
				$this -> _init_uriprocess_lang ();

				$this -> action_apply ('JApi/uri-process/lang/after');
			}

			endif;

			/** Estableciendo los charsets a todo lo que corresponde */
			$charset = $this->config('charset');
			$charset = mb_strtoupper($charset);
			ini_set('default_charset', $charset);
			ini_set('php.internal_encoding', $charset);
			@ini_set('mbstring.internal_encoding', $charset);
			mb_substitute_character('none');
			@ini_set('iconv.internal_encoding', $charset);
			define('UTF8_ENABLED', defined('PREG_BAD_UTF8_ERROR') && $charset === 'UTF-8');

			/** Estableciendo el timezone a todo lo que corresponde */
			$timezone = $this->config('timezone');
			date_default_timezone_set($timezone);

			$this

			/**
			 * Procesando todos los /install/install.php
			 * El orden a recorrer es de menor a mayor para que los directorios prioritarios puedan instalar sus requerimientos primero
			 */
			-> map_app_directories ([$this, '_init_install'])

			/**
			 * Procesando todos los app.php
			 * El orden a recorrer es de menor a mayor para que los directorios prioritarios puedan procesar sus requerimientos primero
			 */
			-> map_app_directories ([$this, '_init_load_app'])
			;

			/** Iniciando el proceso del uri */
			$this -> _init_uriprocess();
		}

		/**
		 * _init_variables()
		 */
		protected function _init_variables ()
		{
			// HOMEPATH ya esta declarado

			/**
			 * COREPATH
			 * Directorio del JApi
			 * @global
			 */
			defined('COREPATH') or define('COREPATH', __DIR__);

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
		}

		/**
		 * _init_noerrors()
		 */
		protected function _init_noerrors ()
		{
			ini_set('display_errors', 0);
			error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);

			/** Definiendo el handler para los posibles errores */
			set_error_handler([$this, '_handler_error']);

			/** Definiendo el handler para las posibles excepciones */
			set_exception_handler([$this, '_handler_exception']);
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
				$this
				-> _handler_error($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
			}

			$this -> _send_response();

			$this -> action_apply('do_when_end');
			$this -> action_apply('shutdown');

			flush();
		}

		/**
		 * _handler_error()
		 */
		public function _handler_error ($severity, $message, $filepath, $line)
		{
			if (($severity & (E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED)) !== $severity)
			{
				return;
			}

			$is_error = (((E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR | E_USER_ERROR) & $severity) === $severity);

			if ($is_error)
			{
				$this->http_code(500);
			}

			$this->logger($message, 
				   $severity, 
				   $severity, 
				   [], 
				   $filepath, 
				   $line);

			if ($is_error)
			{
				exit(1);
			}
		}

		/**
		 * _handler_exception()
		 */
		public function _handler_exception ($exception)
		{
			$this->logger($exception);

			$this->is_cli() OR 
			$this->http_code(500);

			exit(1);
		}

		/**
		 * _init_autoload()
		 */
		protected function _init_autoload ($class)
		{
			static $_bs = '\\';

			$_class_required = $class;
			$class = trim($class, $_bs);

			$class_parts = explode($_bs, $class);
			$class_dir_base = '/configs/classes';

			if (count($class_parts) > 1 and in_array($class_parts[0], [
				'Object',
				'PreRequest',
				'Request',
				'Response'
			]))
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

		/**
		 * _init_load_functions()
		 */
		protected function _init_load_functions ($dir = NULL)
		{
			$_functions_dir = $dir . '/configs/functions';
			if ( ! file_exists($_functions_dir) ||! is_dir($_functions_dir)) return;

			$_functions_files = scandir($_functions_dir);
			foreach($_functions_files as $_file)
			{
				if (in_array($_file, ['.', '..'])) continue;
				if ( ! preg_match('/\.php$/i', $_file)) continue;

				@include_once ($_functions_dir . DS . $_file);
			}
		}

		/**
		 * _init_load_vendor()
		 */
		protected function _init_load_vendor ($dir = NULL)
		{
			$_autoload_vendor = $dir . '/vendor/autoload.php';
			if ( ! file_exists($_autoload_vendor)) return;

			@include_once ($_autoload_vendor);
		}

		/**
		 * $_config
		 * Variable que almacena todas las configuraciones de aplicación
		 */
		protected $_config = [];

		/**
		 * _init_load_config()
		 */
		protected function _init_load_config ($dir = NULL)
		{
			$_config_file = $dir . '/configs/config.php';
			if ( ! file_exists($_config_file)) return;

			$config =& $this->_config;

			$config = $this->filter_apply('JApi/config', $config, $dir, $_config_file);

			@include_once ($_config_file);
		}

		/**
		 * _init_install()
		 */
		protected function _init_install ($dir = NULL)
		{
			$_install_file = $dir . '/install/install.php';
			if ( ! file_exists($_install_file)) return;

			$this->action_apply('JApi/install/start', $dir, $_install_file);

			@include_once ($_install_file);
			unlink($_install_file); // los borra para que no se vuelvan a ejecutar

			$this->action_apply('JApi/install/end', $dir, $_install_file);
		}

		/**
		 * _init_load_app()
		 */
		protected function _init_load_app ($dir = NULL)
		{
			$_app_file = $dir . '/app.php';
			if ( ! file_exists($_app_file)) return;

			$this->action_apply('JApi/app.php', $dir, $_app_file);

			@include_once ($_app_file);
		}

		/**
		 * $URI
		 * Variable que almacena el uri de la solicitud
		 */
		protected $URI;
		public function set_URI ($new)
		{
			$this->URI = $new;
			return $this;
		}
		public function get_URI ()
		{
			return $this->URI;
		}

		/**
		 * $IDS
		 * Variable que almacena los ids u objetos a pasar en los constructores
		 * La variable puede alojar objetos las cuales serán pasados a los constructores de 
		 * las clases PreRequest, Request y Response
		 */
		protected $IDS = [];
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

		/**
		 * $LANG
		 * Variable que almacena el lenguaje de la solicitud
		 */
		protected $LANG;
		public function set_LANG ($new)
		{
			$this->LANG = $new;
			return $this;
		}
		public function get_LANG ()
		{
			return $this->LANG;
		}

		/**
		 * $_rqs_method
		 * Variable que almacena el método utilizado de la solicitud de coneccion
		 */
		protected $_rqs_method;
		public function get_rqs_method ()
		{
			return $this->_rqs_method;
		}

		/**
		 * $_rqs_uri_inicial
		 * Variable que almacena el uri de la solicitud
		 */
		protected $_rqs_uri_inicial;
		public function get_rqs_uri_inicial ()
		{
			return $this->_rqs_uri_inicial;
		}

		/**
		 * _init_uriprocess()
		 */
		protected function _init_uriprocess ()
		{
			/**
			 * Preparación del URI
			 * Principalmente sirve para formatear el URI obteniendo los IDS 
			 * de objetos pasados por url
			 */
			$this -> _init_uriprocess_prepare();

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

			/**
			 * Inicio del procesamiento **PreRequest** del URI
			 * El pre-request sirve para validar temas de autorización de alguna pantalla 
			 * o la administración de un objeto
			 */
			$_uri_process_prerequest = true;
			$_uri_process_prerequest = $this -> filter_apply ('JApi/uri-process/prerequest', $_uri_process_prerequest, $this->URI, $this->IDS);

			if ($_uri_process_prerequest)
			{
				$this -> action_apply ('JApi/uri-process/prerequest/before');

				/** Obtiene el prerequest, valida que no requiera logueo y lo intenta procesar */
				$this -> _init_uriprocess_prerequest ();

				$this -> action_apply ('JApi/uri-process/prerequest/after');
			}

			if (in_array($this->_response_type, ['file', 'manual']))
			{
				$this -> set_response_type ($this->_response_type);
			}

			/**
			 * Inicio del procesamiento **Request** del URI
			 * El request sirve para realizar procedimientos previo a la respuesta
			 */
			$_uri_process_request = true;
			$_uri_process_request = $this -> filter_apply ('JApi/uri-process/request', $_uri_process_request, $this->URI, $this->IDS);

			if ($_uri_process_request)
			{
				$this -> action_apply ('JApi/uri-process/request/before');

				/** Obtiene el request, valida que no requiera logueo y lo intenta procesar */
				$this -> _init_uriprocess_request ();

				$this -> action_apply ('JApi/uri-process/request/after');
			}

			/**
			 * Inicio del procesamiento **Response** del URI
			 * El response sirve para entregar la información (contenido json, html u otro) a la solicitud
			 */
			$_uri_process_response = true;
			$_uri_process_response = $this -> filter_apply ('JApi/uri-process/response', $_uri_process_response, $this->URI, $this->IDS);

			if ($_uri_process_response)
			{
				$this -> action_apply ('JApi/uri-process/response/before');

				/** Obtiene el response, valida que no requiera logueo y lo intenta procesar */
				$this -> _init_uriprocess_response ();

				$this -> action_apply ('JApi/uri-process/response/after');
			}

			/** Finaliza el procesamiento del URI */
			$this -> action_apply ('JApi/uri-process/after', $this->URI, $this->IDS);
		}

		/**
		 * _init_uriprocess_lang()
		 */
		protected function _init_uriprocess_lang ()
		{
			// Comprobar que no se ha enviado por uri (Prioridad 1)
			$_uri = explode('/', $this -> URI);
			empty($_uri[0]) and array_shift($_uri);

			$_uri_lang = array_shift($_uri);
			if (preg_match('/^([a-z]{2}|[A-Z]{2}|[a-z]{2}\-[A-Z]{2})$/', $_uri_lang))
			{
				$_uri_lang = mb_strtoupper(mb_substr($_uri_lang, 0, 2));
				$this->LANG = $_uri_lang;
				setcookie('lang', $_uri_lang, time() + 60*60*7*4*12, '/');

				$this -> _rqs_uri_inicial = $this -> URI = '/' . implode('/', $_uri);
				$_url_path =& $this -> url('path');
				$_url_path = $this -> _rqs_uri_inicial;

				return $this;
			}

			// Si ya existe una cookie (Prioridad 2)
			if (isset($_COOKIE['lang']))
			{
				$this->LANG = $_COOKIE['lang'];
				return $this;
			}

			// Si esta en la configuración (Prioridad 3)
			$_config_lang = $this->config('lang');
			if ( ! is_null($_config_lang))
			{
				$this->LANG = $_config_lang;
				return $this;
			}

			// Detectar el idioma (Prioridad 4)
			$_srv_lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : 'es';
			$_srv_lang = mb_strtoupper(mb_substr($_srv_lang, 0, 2));
			$this->LANG = $_srv_lang;
			setcookie('lang', $_srv_lang, 60*60*7*4*12, '/');
			return $this;
		}

		/**
		 * _init_uriprocess_prepare()
		 */
		protected function _init_uriprocess_prepare ()
		{
			$this -> IDS = $this -> filter_apply ('JApi/uri-process/check-ids', $this -> IDS, $this->URI);

			$_uri = explode('/', $this -> URI);
			empty($_uri[0]) and array_shift($_uri);

			$_uri_new = [];
			foreach($_uri as $_uri_part)
			{
				if (preg_match('/^[0-9]$/', $_uri_part))
				{
					$this -> IDS[] = $_uri_part;
				}
				else
				{
					$_uri_new[] = $_uri_part;
				}
			}
			$this -> URI = '/' . implode('/', $_uri_new);

			$this -> IDS = $this -> filter_apply ('JApi/uri-process/ids', $this -> IDS, $this->URI);
		}

		protected function _obtaing_class_and_func ($for, $uri = NULL)
		{
			is_null($uri) and $uri =  $this -> URI;

			if ($uri === '/')
			{
				$uri = '/inicio';
				$uri = $this -> filter_apply('JApi/uri-process/home', $uri, $this->URI, $this->IDS);
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
				$_class = implode('\\', $_uri_t);
				if (class_exists($_class))
				{
					break;
				}

				$_uri_t = $_uri;
				$_uri_t = array_map(function($o){
					$o[0] = mb_strtoupper($o);
					return $o;
				}, $_uri_t);
				$_class = implode('\\', $_uri_t);
				if (class_exists($_class))
				{
					break;
				}

				$_uri_t = $_uri;
				$_uri_t = array_map(function($o){
					$o[0] = mb_strtoupper($o);
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
					$o[0] = mb_strtoupper($o);
					$o = preg_split('/[\.\-\_]/', $o);
					$o = array_map(function($p){
						$p[0] = mb_strtoupper($p);
						return $p;
					}, $o);
					$o = implode('', $o);
					return $o;
				}, $_uri_t);
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
					$_class = implode('\\', $_uri_t);
					if (class_exists($_class))
					{
						break;
					}

					$_uri_t = $_uri2;
					$_uri_t = array_map(function($o){
						$o[0] = mb_strtoupper($o);
						return $o;
					}, $_uri_t);
					$_class = implode('\\', $_uri_t);
					if (class_exists($_class))
					{
						break;
					}

					$_uri_t = $_uri2;
					$_uri_t = array_map(function($o){
						$o[0] = mb_strtoupper($o);
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
						$o[0] = mb_strtoupper($o);
						$o = preg_split('/[\.\-\_]/', $o);
						$o = array_map(function($p){
							$p[0] = mb_strtoupper($p);
							return $p;
						}, $o);
						$o = implode('', $o);
						return $o;
					}, $_uri_t);
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

			$this -> filter_apply('JApi/uri-process/get-class', $_class, $_func, $_func_params);
			$this -> filter_apply('JApi/uri-process/get-class/' . $for, $_class, $_func, $_func_params);

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

		protected $_uriprocess_prerequest_result = false;

		/**
		 * _init_uriprocess_prerequest()
		 */
		protected function _init_uriprocess_prerequest ()
		{
			list($_class, $_func, $_func_params) = $this -> _obtaing_class_and_func ('PreRequest');

			if (is_null($_class)) return;

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

			foreach([mb_strtoupper($this -> _rqs_method) . '_', ''] as $x)
			{
				foreach([mb_strtoupper($this -> _response_type) . '_', ''] as $y)
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

			if ( ! is_callable([$_class_instance, $_func]))
			{
				return;
			}

			call_user_func_array([$_class_instance, $_func], $_func_params);
			$this->_uriprocess_prerequest_result = true;
		}

		protected $_uriprocess_request_result = false;

		/**
		 * _init_uriprocess_request()
		 */
		protected function _init_uriprocess_request ()
		{
			list($_class, $_func, $_func_params) = $this -> _obtaing_class_and_func ('Request');

			if (is_null($_class)) return;

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

			foreach([mb_strtoupper($this -> _rqs_method) . '_', ''] as $x)
			{
				foreach([mb_strtoupper($this -> _response_type) . '_', ''] as $y)
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

			if ( ! is_callable([$_class_instance, $_func]))
			{
				return;
			}

			call_user_func_array([$_class_instance, $_func], $_func_params);
			$this->_uriprocess_request_result = true;
		}

		/**
		 * _init_uriprocess_response()
		 */
		protected function _init_uriprocess_response ()
		{
			list($_class, $_func, $_func_params) = $this -> _obtaing_class_and_func ('Response');

			if (is_null($_class)) return $this->_init_uriprocess_response_404();

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

			foreach([mb_strtoupper($this -> _rqs_method) . '_', ''] as $x)
			{
				foreach([mb_strtoupper($this -> _response_type) . '_', ''] as $y)
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

			if ( ! is_callable([$_class_instance, $_func]))
			{
				return $this->_init_uriprocess_response_404();
			}

			call_user_func_array([$_class_instance, $_func], $_func_params);
			$this->_uriprocess_request_result = true;
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
				return;
			}

			$uri = '/error404';
			$uri = $this -> filter_apply('JApi/uri-process/error404', $uri, $this->URI, $this->IDS);

			$this -> http_code(404, 'Página no encontrada');

			list($_class, $_func, $_func_params) = $this -> _obtaing_class_and_func ('Response', $uri);

			if (is_null($_class)) return;

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

			if ( ! is_callable([$_class_instance, $_func]))
			{
				return;
			}

			call_user_func_array([$_class_instance, $_func], $_func_params);
		}

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
		public function set_response_type ($new)
		{
			$this->_response_type = mb_strtolower($new);

			if (in_array($this->_response_type, ['file', 'manual']))
			{
				/** 
				 * Se limpia el posible buffer generado 
				 * Sirve para que el contenido retornado sea exactamente lo que se desea mediante el request o el response
				 */
				$posible_content = $this->_getandclear_buffer_content();

				if ( ! empty(trim($posible_content)))
				{
					trigger_error('Previo contenido detectado en respuestas a la solicitud tipo `' . $this -> _response_type . '`: ' . 
								  PHP_EOL.PHP_EOL .
								  $posible_content, E_USER_WARNING);
				}
			}

			return $this;
		}
		public function get_response_type ()
		{
			return $this->_response_type;
		}

		protected $_response_mime = null;
		public function set_response_mime ($new)
		{
			$this->_response_mime = $new;
			return $this;
		}
		public function get_response_mime ()
		{
			return $this->_response_mime;
		}

		protected $_response_charset = null;
		public function set_response_charset ($new)
		{
			$this->_response_charset = $new;
			return $this;
		}
		public function get_response_charset ()
		{
			return $this->_response_charset;
		}

		public function ResponseAs ($type, $mime = NULL, $charset = NULL)
		{
			$this->set_response_type (mb_strtolower($type));
			$this->_response_mime = $mime;
			$this->_response_charset = $charset;
			return $this;
		}

		protected $_headers = [];
		public function set_headers ($new)
		{
			$this->_headers = $new;
			return $this;
		}
		public function add_header ($new)
		{
			$this->_headers[] = $new;
			return $this;
		}
		public function get_headers ()
		{
			return $this->_headers;
		}
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

		protected $_process_result = null;
		public function set_process_result ($status, $message = NULL, $code = NULL)
		{
			is_null($this->_process_result) and $this->_process_result = [
				'status' => null,
				'message' => null,
				'code' => null,
			];

			$this->_process_result['status'] = $status;
			is_null($message) or $this->_process_result['message'] = $message;
			is_null($code) or $this->_process_result['code'] = $code;

			return $this;
		}
		public function del_process_result ()
		{
			$this->_process_result = null;
			return $this;
		}
		function process_result_message($return_html = false, $clear = TRUE)
		{
			if (is_null($this->_process_result))
			{
				if ($return_html) return null;

				return $this;
			}

			$_class = [
				'alert',
				'alert-' . $this->_process_result['status'],
			];
			$this->_process_result['status'] === 'error' and $_class[] = 'alert-danger';

			$return = '';
			$return.= '<div class="' . implode(' ', (array)$_class) . '">';
			if ( ! is_null($this->_process_result['code']))
			{
				$return.= '<b class="alert-code">Error #' . $this->_process_result['code'] . '</b>&nbsp;';
			}

			is_null($this->_process_result['message']) and $this->_process_result['message'] = $this->_process_result['status'];
			$return.= '<span class="alert-message">' . $this->_process_result['message'] . '</span>';

			$return.= '</div>';

			$return = $this -> filter_apply('JApi/process-result/message', $return, $this->_process_result['status'], $this->_process_result);

			if ($clear)
			{
				$this->_process_result = null;
			}

			if ($return_html) return $return;
			echo $return;

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


		public function force_exit($status = NULL)
		{
			exit ($status);
		}

		public function exit_iftype($types, $status = NULL)
		{
			$types = (array)$types;
			$types = array_map('mb_strtolower', $types);

			if (in_array($this->_response_type,  $types))
			{
				exit ($status);
			}

			return $this;
		}

		public function exit_ifhtml($status = NULL)
		{
			if (in_array($this->_response_type,  ['html', 'body']))
			{
				exit ($status);
			}

			return $this;
		}

		public function exit_ifjson($status = NULL)
		{
			if (in_array($this->_response_type, ['json', 'cli']))
			{
				exit ($status);
			}

			return $this;
		}

		protected $_redirects_it = [];

		/**
		 * Establecer una redirección en caso el Tipo sea
		 * @param	string	$type
		 * @param	string	$link
		 * @return	self
		 */
		public function redirect_iftype($type, $link)
		{
			$this -> _redirects_it[$type] = $link;
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

		/**
		 * Establecer una redirección en caso el Tipo sea
		 * @param	string	$link
		 * @return	self
		 */
		public function redirect($url, $query = NULL)
		{
			error_reporting(0);

			$parsed_url = parse_url($url);

			isset($parsed_url['scheme']) or $parsed_url['scheme'] = $this->url('scheme');
			if ( ! isset($parsed_url['host']))
			{
				$parsed_url['host'] = $this->url('host');
				$parsed_url['path'] = $this->url('srvpublic_path') . '/' . ltrim($parsed_url['path'], '/');
			}

			if ( ! is_null($query))
			{
				isset($parsed_url['query'])    or $parsed_url['query']  = [];
				is_array($parsed_url['query']) or $parsed_url['query']  = parse_str($parsed_url['query']);

				$parsed_url['query'] = array_merge($parsed_url['query'], $query);
			}

			$url =  $this -> build_url ($parsed_url);

			$this->_getandclear_buffer_content(); // El contenido no será reportado como error

			header('Location: ' . $url) OR die('<script>location.replace("' . $url . '");</script>');
			die();
		}

		/**
		 * build_url()
		 * Construye una URL
		 *
		 * @param	array	$parsed_url	Partes de la URL a construir {@see http://www.php.net/manual/en/function.parse-url.php}
		 * @return	string
		 */
		function build_url($parsed_url)
		{
			isset($parsed_url['query']) and is_array($parsed_url['query']) and 
			$parsed_url['query'] = http_build_query($parsed_url['query']);

			$scheme   = isset($parsed_url['scheme'])  ? $parsed_url['scheme']  : '';
			$host     = isset($parsed_url['host'])    ? $parsed_url['host']    : '';
			$port     = isset($parsed_url['port'])    ? $parsed_url['port']    : '';
			$user     = isset($parsed_url['user'])    ? $parsed_url['user']    : '';
			$pass     = isset($parsed_url['pass'])    ? $parsed_url['pass']    : '';
			$path     = isset($parsed_url['path'])    ? $parsed_url['path']    : '';
			$query    = isset($parsed_url['query'])   ? $parsed_url['query']   : '';
			$fragment = isset($parsed_url['fragment'])? $parsed_url['fragment']: '';

			if (in_array($port, [80, 443]))
			{
				## Son puertos webs que dependen del scheme
				empty($scheme) and $scheme = $port === 80 ? 'http' : 'https';
				$port = '';
			}

			empty($scheme)   or $scheme .= '://';
			empty($port)     or $port    = ':' . $port;
			empty($pass)     or $pass    = ':' . $pass;
			empty($query)    or $query   = '?' . $query;
			empty($fragment) or $fragment= '#' . $fragment;

			$pass     = ($user || $pass) ? "$pass@" : '';

			return $scheme . $user . $pass . $host . $port . $path . $query . $fragment;
		}

		/**
		 * getUTC()
		 * Obtiene el UTC del timezone actual
		 *
		 * @return string
		 */
		function getUTC()
		{
			$_utc_dtz = new DateTimeZone(date_default_timezone_get());
			$_utc_dt  = new DateTime('now', $_utc_dtz);
			$_utc_offset = $_utc_dtz->getOffset($_utc_dt);

			return sprintf( "%s%02d:%02d", ( $_utc_offset >= 0 ) ? '+' : '-', abs( $_utc_offset / 3600 ), abs( $_utc_offset % 3600 ) );
		}

		protected function _getandclear_buffer_content ()
		{
			$_buffer_content = '';
			while (ob_get_level() > $this->_ob_level)
			{
				$_buffer_content .= ob_get_contents();
            	ob_end_clean();
			}

			return $_buffer_content;
		}

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
//							'apple-mobile-web-app-title' => '',
//							'application-name' => '',
//							'msapplication-TileColor' => '',
//							'theme-color' => '',
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
				$uri = str_replace($this -> url(), '', $uri);
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

			if ( ! is_null($_response_mime))
			{
				array_unshift($this->_headers, 'Content-Type: ' . $_response_mime . '; charset=' . $_response_charset);
			}

			foreach ($this->_headers as $header)
			{
				$header = (array)$header;
				$header[] = true;
				list($_header, $_replace) = $header;

				@header($_header, $_replace);
			}

			$this -> action_apply('JApi/send-response/headers');

			$_buffer_content = $this->_getandclear_buffer_content();

			if (isset($this->_redirects_it[$this->_response_type]))
			{
				$this->redirect ($this->_redirects_it[$this->_response_type]);
				return;
			}

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
			die();
		}

		protected static $_doctypes = [
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

		protected function _html_attrs ($attrs)
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
				$_head_favicon = '<link rel="shortcut icon" href="' . $this -> url('base') . '/favicon.ico">';
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
			empty($title) and $title = 'Oficina';

			$title = $this -> filter_apply('JApi/send-response-html/title', $title);
			$title = $this -> filter_apply('title', $title, $data);

			isset($data['tag_attr']['lang']) or 
			$data['tag_attr']['lang'] = $this->LANG;

			empty($data['head']['canonical']) and $data['head']['canonical'] = $this -> url('full'); 

			$doctype = $data['doctype'];
			isset(self::$_doctypes[$doctype]) and $doctype = self::$_doctypes[$doctype];
			$_html_tag_before .= $doctype . PHP_EOL;

			$_html_tag_before .= '<html' . $this -> _html_attrs($data['tag_attr']) . '>' . PHP_EOL;
			$_head_tag_before .= '<head' . $this -> _html_attrs($data['head']['tag_attr']) . '>' . PHP_EOL;
			$_body_tag_before .= '<body' . $this -> _html_attrs($data['body']['tag_attr']) . '>' . PHP_EOL;

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

			$_head_html .= '<base href="' . $this -> url('base') . '" />' . PHP_EOL;
			$_head_html .= '<script>';

			/** Añadir el script location.base en $_head_html */
			$_head_html .= 'location.base="' . $this -> url('base') . '";';
			$_head_html .= 'location.full="' . $this -> url('full') . '";';
			$_head_html .= 'location.cookie="' . $this -> url('cookie-base') . '";';

			/** Añadir el script force_uri en $_head_html */
			$force_uri = $data['force_uri'];
			if (is_null($force_uri))
			{
				$force_uri =  $this -> url('path');
				if (count($_GET) > 0)
				{
					$force_uri .= '?' . http_build_query($_GET);
				}
			}
			$force_uri = $this -> url('base') . $this -> url('srvpublic_path') . $force_uri;
			$_head_html .= 'history.replaceState([], "", "' . $force_uri . '");';

			$_head_html .= '</script>' . PHP_EOL;

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
				
				$_head_html .= PHP_EOL . '<link' . $this -> _html_attrs ($attr) . ' />';
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

				$_head_html .= PHP_EOL . '<script' . $this -> _html_attrs ($attr) . '></script>';

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

				$_head_html .= PHP_EOL . '<style' . $this -> _html_attrs ($attr) . '>' . $content . '</style>';
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

				$_head_html .= PHP_EOL . '<script' . $this -> _html_attrs ($attr) . '>' . $content . '</script>';
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
				
				$_body_html .= PHP_EOL . '<link' . $this -> _html_attrs ($attr) . ' />';
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
						$_has_sign = preg_match('/\?/i', $attr['href']);
						$attr['src'] .= ($_has_sign ? '&' : '?') . $dats['version'];
					}
				}

				foreach($dats['_before'] as $_tmp_script)
				{
					function_exists('js_compressor') and $_tmp_script = js_compressor($_tmp_script);
					$_body_html .= PHP_EOL . '<script>' . $_tmp_script . '</script>';
				}

				$_body_html .= PHP_EOL . '<script' . $this -> _html_attrs ($attr) . '></script>';

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

				$_body_html .= PHP_EOL . '<style' . $this -> _html_attrs ($attr) . '>' . $content . '</style>';
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

				$_body_html .= PHP_EOL . '<script' . $this -> _html_attrs ($attr) . '>' . $content . '</script>';
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
			die();
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

		//============= HELPERS =============//

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
		function non_actioned ($key, $function, $priority = 50)
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
		function action_apply ($key, ...$params)
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
		 * logger()
		 * Función que guarda los logs
		 *
		 * @param BasicException|Exception|TypeError|Error|string 	$message	El mensaje reportado
		 * @param int|null 		$code		(Optional) El código del error
		 * @param string|null	$severity	(Optional) La severidad del error
		 * @param array|null 	$meta		(Optional) Los metas del error
		 * @param string|null 	$filepath	(Optional) El archivo donde se produjo el error
		 * @param int|null 		$line		(Optional) La linea del archivo donde se produjo el error
		 * @param array|null 	$trace		(Optional) La ruta que tomó la ejecución hasta llegar al error
		 * @return void
		 */
		public function logger ($message, $code = NULL, $severity = NULL, $meta = NULL, $filepath = NULL, $line = NULL, $trace = NULL)
		{
			/**
			 * Listado de Levels de Errores
			 * @static
			 * @global
			 */
			static $error_levels = 
			[
				E_ERROR			    =>	'Error',				
				E_WARNING		    =>	'Warning',				
				E_PARSE			    =>	'Parsing Error',		
				E_NOTICE		    =>	'Notice',				

				E_CORE_ERROR		=>	'Core Error',		
				E_CORE_WARNING		=>	'Core Warning',		

				E_COMPILE_ERROR		=>	'Compile Error',	
				E_COMPILE_WARNING	=>	'Compile Warning',	

				E_USER_ERROR		=>	'User Error',		
				E_USER_DEPRECATED	=>	'User Deprecated',	
				E_USER_WARNING		=>	'User Warning',		
				E_USER_NOTICE		=>	'User Notice',		

				E_STRICT		    =>	'Runtime Notice'		
			];

			(is_array($severity) and is_null($meta)) and $meta = $severity and $severity = NULL;

			is_null($code) and $code = 0;

			is_null($meta) and $meta = [];
			is_array($meta) or $meta = (array)$meta;

			$meta['datetime'] = date('l d/m/Y H:i:s');
			$meta['time'] = time();
			$meta['microtime'] = microtime();
			$meta['microtime_float'] = microtime(true);

			if ($message instanceof BasicException)
			{
				$exception = $message;

				$meta = array_merge($exception->getMeta(), $meta);
				is_null($severity) and $severity = 'BasicException';
				$meta['class'] = get_class($exception);
			}
			elseif ($message instanceof Exception)
			{
				$exception = $message;

				is_null($severity) and $severity = 'Exception';
				$meta['class'] = get_class($exception);
			}
			elseif ($message instanceof TypeError)
			{
				$exception = $message;

				is_null($severity) and $severity = 'Error';
				$meta['class'] = get_class($exception);
			}
			elseif ($message instanceof Error)
			{
				$exception = $message;

				is_null($severity) and $severity = 'Error';
				$meta['class'] = get_class($exception);
			}

			if (isset($exception))
			{
				$message  = $exception->getMessage();

				is_null($filepath) and $filepath = $exception->getFile();
				is_null($line)     and $line     = $exception->getLine();
				is_null($trace)    and $trace    = $exception->getTrace();
				$code == 0         and $code     = $exception->getCode();
			}

			is_null($severity) and $severity = E_USER_NOTICE;

			$severity = isset($error_levels[$severity]) ? $error_levels[$severity] : $severity;

			is_null($message) and $message = '[NULL]';

			if (is_null($trace))
			{
				$trace = debug_backtrace(false);
			}

			$trace_original = $trace;

			if (in_array($trace[0]['function'], ['logger']))
			{
				array_shift($trace);
			}

			if (in_array($trace[0]['function'], ['_handler_exception', '_handler_error']))
			{
				array_shift($trace);
			}

			if (in_array($trace[0]['function'], ['trigger_error']))
			{
				array_shift($trace);
			}

			$_japi_funcs_file = COREPATH . DS . 'configs' . DS . 'functions' . DS . 'JApi.php';
			while(count($trace) > 0 and isset($trace[0]['file']) and in_array($trace[0]['file'], [__FILE__, $_japi_funcs_file]))
			{
				array_shift($trace);
			}

			if (isset($trace[0]))
			{
				$filepath === __FILE__ and $line = NULL;
				$filepath === __FILE__ and $filepath = NULL;

				$filepath === $_japi_funcs_file and $line = NULL;
				$filepath === $_japi_funcs_file and $filepath = NULL;

				is_null($filepath) and $filepath = $trace[0]['file'];
				is_null($line) and $line = $trace[0]['line'];

				isset($trace[0]['class']) and ! isset($meta['class']) and $meta['class'] = $trace[0]['class'];
				isset($trace[0]['function']) and ! isset($meta['function']) and $meta['function'] = $trace[0]['function'];
			}

			$meta['MYSQL_history'] = $this->_MYSQL_history;

			$SER = [];
			foreach($_SERVER as $x => $y)
			{
				if (preg_match('/^((GATEWAY|HTTP|QUERY|REMOTE|REQUEST|SCRIPT|CONTENT)\_|REDIRECT_URL|REDIRECT_STATUS|PHP_SELF|SERVER\_(ADDR|NAME|PORT|PROTOCOL))/i', $x))
				{
					$SER[$x] = $y;
				}
			}

			$meta['server'] = $SER;

			try
			{
				$url = $this->url('array');
			}
			catch (\BasicException $e){}
			catch (\Exception $e){}
			catch (\TypeError $e){}
			catch (\Error $e){}
			finally
			{
				$meta['URL_loadable'] = isset($url);
			}

			isset($url) and
			$meta['url'] = $url;

			try
			{
				$ip_address = $this->ip_address('array');
			}
			catch (\BasicException $e){}
			catch (\Exception $e){}
			catch (\TypeError $e){}
			catch (\Error $e){}
			finally
			{
				$meta['IPADRESS_loadable'] = isset($url);
			}

			isset($ip_address) and
			$meta['ip_address'] = $ip_address;

			$meta['cdkdsp'] = isset($_COOKIE['cdkdsp'])  ? $_COOKIE['cdkdsp']  : NULL; // Código de Dispositivo

			$trace_slim = array_map(function($arr){
				return $arr['file'] . '#' . $arr['line'];
			}, $trace);
			$meta['trace_slim'] = $trace_slim;
			$meta['trace_original'] = $trace_original;

			try
			{
				$CON_logs = $this->use_CON_logs()->CON_logs; // Conecta la DB de logs en caso de que no esté conectada

				if ($this -> sql_trans('NUMTRANS', $CON_logs) !== 0)
				{
					$this -> sql_trans(false, $CON_logs);
				}

				if ( ! $this -> sql_et('_logs', $CON_logs))
				{
					$_tbldb_created = $this -> sql('
					CREATE TABLE `_logs` (
						`id` Bigint NOT NULL AUTO_INCREMENT,
						`codigo` Varchar (100) NOT NULL, 
						`message` Text, 
						`severity` Varchar(300),
						`code` Varchar(100),
						`filepath` Text,
						`line` Int (10),
						`trace` longtext,
						`meta` longtext,
						`estado` Enum ("Registrado", "Visto", "Analizado", "Solucionado") NOT NULL DEFAULT "Registrado",
						`creado` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
						`cantidad` INT (5) NOT NULL DEFAULT 1,

						PRIMARY KEY (`id`),
						UNIQUE KEY `codigo` (`codigo`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC', FALSE, $CON_logs);

					if ( ! $_tbldb_created)
					{
						$CON_logs = FALSE;
						throw new Exception('No se pudo crear la tabla en la db');
					}
				}

				$_codigo = md5(json_encode([
					$message,
					$severity,
					$code,
					$filepath,
					$line,
					$trace_slim
				]));

				$query = '
				INSERT INTO `_logs` (
					`codigo`, 
					`message`, 
					`severity`, 
					`code`, 
					`filepath`, 
					`line`, 
					`trace`, 
					`meta`
				) 
				VALUES (
					' . $this -> sql_qpesc($_codigo , FALSE, $CON_logs) . ', 
					' . $this -> sql_qpesc($message  , TRUE, $CON_logs) . ', 
					' . $this -> sql_qpesc($severity , TRUE, $CON_logs) . ', 
					' . $this -> sql_qpesc($code     , TRUE, $CON_logs) . ', 
					' . $this -> sql_qpesc($filepath , TRUE, $CON_logs) . ', 
					' . $this -> sql_qpesc($line     , TRUE, $CON_logs) . ', 
					' . $this -> sql_qpesc($trace    , TRUE, $CON_logs) . ', 
					' . $this -> sql_qpesc($meta     , TRUE, $CON_logs) . '
				)
				ON DUPLICATE KEY UPDATE `cantidad` = `cantidad` +1
				';

				$saved = $this -> sql($query, TRUE, $CON_logs);

				if ( ! $saved)
				{
					$CON_logs = FALSE;
					throw new Exception('No se pudo guardar el registro');
				}
			}
			catch (\BasicException $e){}
			catch (\Exception $e){}
			catch (\TypeError $e){}
			catch (\Error $e){}
			finally
			{
				isset($CON_logs) or $CON_logs = FALSE;
			}

			if ($CON_logs === FALSE)
			{
				$_log_path = APPPATH . '/logs';
				$_log_file = date('Ymd') . '.log';

				$this->mkdir2($_log_path);

				$log_file = $_log_path . DS . $_log_file;
				$log_file_exists = file_exists($log_file);

				$msg_file = json_encode([
					'message'	 => $message, 
					'severity'	 => $severity, 
					'code'		 => $code, 
					'filepath'	 => $filepath, 
					'line'		 => $line, 
					'trace'		 => $trace, 
					'meta'		 => $meta,
				]);

				file_put_contents($log_file, $msg_file . PHP_EOL, FILE_APPEND | LOCK_EX);

				if ( ! $log_file_exists)
				{
					chmod($log_file, 0644);
				}
				return;
			}
		}

		/**
		 * mkdir2()
		 * Crea los directorios faltantes desde la carpeta $base
		 *
		 * @param	string 	$folder folder
		 * @param	string 	$base	base a considerar
		 * @return 	string 	ruta del folder creado
		 */
		public function mkdir2($folder, $base = NULL)
		{
			if (is_null($base))
			{
				$_app_directories_list = $this->get_app_directories();

				$base = HOMEPATH;
				foreach($_app_directories_list as $base_dir)
				{
					if ($temp = str_replace($base_dir, '', $folder) and $temp <> $folder)
					{
						$base = $base_dir;
						break;
					}
				}
			}

			$_chars = ['/','.','*','+','?','|','(',')','[',']','{','}','\\','$','^','-'];
			$folder = preg_replace('/^' . preg_replace('/(\\' . implode('|\\', $_chars).')/', "\\\\$1", $base) . '/i', '', $folder);
			$folder = strtr($folder, '/\\', DS . DS);
			$folder = trim($folder);
			$folder = trim($folder, DS);

			$return = realpath($base);

			if (empty($folder))
			{
				return $return;
			}

			$folder = explode(DS, $folder);

			foreach ($folder as $dir)
			{
				$return .= DS . $dir;

				if ( ! file_exists($return))
				{
					mkdir($return);
				}

				if ( ! file_exists($return . DS . 'index.htm'))
				{
					file_put_contents($return . DS . 'index.htm', '');
				}
			}

			return $return;
		}

		/**
		 * is_command()
		 * identifica si la solicitud de procedimiento ha sido por comando
		 * @return Boolean False en caso de que la solicitud ha sido por web.
		 */
		public function is_command ()
		{
			return defined('STDIN');
		}

		/**
		 * is_cli()
		 */
		public function is_cli ()
		{
			return (PHP_SAPI === 'cli' OR defined('STDIN'));
		}

		/**
		 * http_code()
		 * Establece la cabecera del status HTTP
		 *
		 * @param Integer $code El código
		 * @param String $text El texto del estado
		 * @return self
		 */
		public function http_code($code = 200, $text = '')
		{
			if ($this->is_cli())
			{
				return;
			}

			is_int($code) OR $code = (int) $code;

			if (empty($text))
			{
				$def_codes_text = [
					100	=> 'Continue',
					101	=> 'Switching Protocols',

					200	=> 'OK',
					201	=> 'Created',
					202	=> 'Accepted',
					203	=> 'Non-Authoritative Information',
					204	=> 'No Content',
					205	=> 'Reset Content',
					206	=> 'Partial Content',

					300	=> 'Multiple Choices',
					301	=> 'Moved Permanently',
					302	=> 'Found',
					303	=> 'See Other',
					304	=> 'Not Modified',
					305	=> 'Use Proxy',
					307	=> 'Temporary Redirect',

					400	=> 'Bad Request',
					401	=> 'Unauthorized',
					402	=> 'Payment Required',
					403	=> 'Forbidden',
					404	=> 'Not Found',
					405	=> 'Method Not Allowed',
					406	=> 'Not Acceptable',
					407	=> 'Proxy Authentication Required',
					408	=> 'Request Timeout',
					409	=> 'Conflict',
					410	=> 'Gone',
					411	=> 'Length Required',
					412	=> 'Precondition Failed',
					413	=> 'Request Entity Too Large',
					414	=> 'Request-URI Too Long',
					415	=> 'Unsupported Media Type',
					416	=> 'Requested Range Not Satisfiable',
					417	=> 'Expectation Failed',
					422	=> 'Unprocessable Entity',
					426	=> 'Upgrade Required',
					428	=> 'Precondition Required',
					429	=> 'Too Many Requests',
					431	=> 'Request Header Fields Too Large',

					500	=> 'Internal Server Error',
					501	=> 'Not Implemented',
					502	=> 'Bad Gateway',
					503	=> 'Service Unavailable',
					504	=> 'Gateway Timeout',
					505	=> 'HTTP Version Not Supported',
					511	=> 'Network Authentication Required',
				];

				if (isset($def_codes_text[$code]))
				{
					$text = $def_codes_text[$code];
				}
				else
				{
					$text = 'Non Status Text';
				}
			}

			if (strpos(PHP_SAPI, 'cgi') === 0)
			{
				@header('Status: ' . $code . ' ' . $text, TRUE);
				return $this;
			}

			$server_protocol_alloweds = ['HTTP/1.0', 'HTTP/1.1', 'HTTP/2'];
			$server_protocol = (isset($_SERVER['SERVER_PROTOCOL']) && in_array($_SERVER['SERVER_PROTOCOL'], $server_protocol_alloweds, TRUE))
								? $_SERVER['SERVER_PROTOCOL'] 
								: 'HTTP/1.1';

			@header($server_protocol . ' ' . $code . ' ' . $text, TRUE, $code);
			return $this;
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
		 * url()
		 * Obtiene la estructura y datos importantes de la URL
		 *
		 * @param	string	$get
		 * @return	mixed
		 */
		public function &url($get = 'base')
		{
			static $datos = [];

			if (count($datos) === 0)
			{
				$file = __FILE__;

				//Archivo index que se ha leído originalmente
				$script_name = $_SERVER['SCRIPT_NAME'];

				//Este es la ruta desde el /public_html/{...}/APPPATH/index.php
				// y sirve para identificar si la aplicación se ejecuta en una subcarpeta
				// o desde la raiz, con ello podemos añadir esos subdirectorios {...} en el enlace
				$datos['srvpublic_path'] = '';
				$datos['srvpublic_path'] = $this->filter_apply('JApi/url/srvpublic_path', $datos['srvpublic_path'], $_SERVER['HTTP_HOST']);

				//Devuelve si usa https (boolean)
				$datos['https'] = FALSE;
				if (
					( ! empty($_SERVER['HTTPS']) && mb_strtolower($_SERVER['HTTPS']) !== 'off') ||
					(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && mb_strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
					( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && mb_strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') ||
					(isset($_SERVER['REQUEST_SCHEME']) and $_SERVER['REQUEST_SCHEME'] === 'https')
				)
				{
					$datos['https'] = TRUE;
				}

				isset($_SERVER['REQUEST_SCHEME']) or $_SERVER['REQUEST_SCHEME'] = 'http' . ($datos['https'] ? 's' : '');

				$_parsed = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
				$_parsed = parse_url($_parsed);

				//Devuelve 'http' o 'https' (string)
				$datos['scheme'] = $_parsed['scheme'];

				//Devuelve el host (string)
				$datos['host'] = $_parsed['host'];

				//Devuelve el port (int)
				$datos['port'] = $_parsed['port'];

				isset($_parsed['user']) and $datos['user'] = $_parsed['user'];
				isset($_parsed['pass']) and $datos['pass'] = $_parsed['pass'];

				$datos['path'] = isset($_parsed['path']) ? $_parsed['path'] : '/';
				if ($this -> is_command())
				{
					global $argv;
					array_shift($argv); // Archivo SCRIPT
					if (count($argv) > 0)
					{
						$datos['path'] = array_shift($argv);
					}
				}

				empty($datos['srvpublic_path']) or $datos['path'] = str_replace($datos['srvpublic_path'], '', $datos['path']);

				$datos['query'] = isset($_parsed['query']) ? $_parsed['query'] : '';
				$datos['fragment'] = isset($_parsed['fragment']) ? $_parsed['fragment'] : '';

				//Devuelve el port en formato enlace (string)		:8082	para el caso del port 80 o 443 retorna vacío
				$datos['port-link'] = (new class($datos) implements JsonSerializable {
					private $datos;

					public function __construct(&$datos)
					{
						$this->datos =& $datos;
					}

					public function __toString()
					{
						$port_link = '';
						if ($this->datos['port'] <> 80 and $this->datos['port'] <> 443)
						{
							$port_link = ':' . $this->datos['port'];
						}
						return $port_link;
					}

					public function __debugInfo()
					{
						return [
							'port' => $this->datos['port'],
							'port-link' => $this->__toString()
						];
					}

					public function jsonSerialize() {
						return $this->__toString();
					}
				});

				//Devuelve si usa WWW (boolean)
				$datos['www'] = (bool)preg_match('/^www\./', $datos['host']);

				//Devuelve el base host (string)
				$datos['host-base'] = (new class($datos) implements JsonSerializable{
					private $datos;

					public function __construct(&$datos)
					{
						$this->datos =& $datos;
					}

					public function __toString()
					{
						$host_base = explode('.', $this->datos['host']);

						while (count($host_base) > 2)
						{
							array_shift($host_base);
						}

						$host_base = implode('.', $host_base);

						return $host_base;
					}

					public function __debugInfo()
					{
						return [
							'host' => $this->datos['host'],
							'host-base' => $this->__toString()
						];
					}

					public function jsonSerialize() {
						return $this->__toString();
					}
				});

				//Devuelve el base host (string)
				$datos['host-parent'] = (new class($datos) implements JsonSerializable{
					private $datos;

					public function __construct(&$datos)
					{
						$this->datos =& $datos;
					}

					public function __toString()
					{
						$host_parent = explode('.', $this->datos['host']);

						if ($this->datos['www'])
						{
							array_shift($host_parent);
						}

						if (count($host_parent) > 2)
						{
							array_shift($host_parent);
						}

						$host_parent = implode('.', $host_parent);

						return $host_parent;
					}

					public function __debugInfo()
					{
						return [
							'host' => $this->datos['host'],
							'www' => $this->datos['www'],
							'host-parent' => $this->__toString()
						];
					}

					public function jsonSerialize() {
						return $this->__toString();
					}
				});

				//Devuelve el host mas el port (string)			intranet.net:8082
				$datos['host-link'] = (new class($datos) implements JsonSerializable{
					private $datos;

					public function __construct(&$datos)
					{
						$this->datos =& $datos;
					}

					public function __toString()
					{
						$host_link = $this->datos['host'] . $this->datos['port-link'];
						return $host_link;
					}

					public function __debugInfo()
					{
						return [
							'host' => $this->datos['host'],
							'port-link' => (string)$this->datos['port-link'],
							'host-link' => $this->__toString()
						];
					}

					public function jsonSerialize() {
						return $this->__toString();
					}
				});

				//Devuelve el host sin puntos o guiones	(string)	intranetnet
				$datos['host-clean'] = (new class($datos) implements JsonSerializable{
					private $datos;

					public function __construct(&$datos)
					{
						$this->datos =& $datos;
					}

					public function __toString()
					{
						$host_clean = preg_replace('/[^a-z0-9]/i', '', $this->datos['host']);
						return $host_clean;
					}

					public function __debugInfo()
					{
						return [
							'host' => $this->datos['host'],
							'host-clean' => $this->__toString()
						];
					}

					public function jsonSerialize() {
						return $this->__toString();
					}
				});

				//Devuelve el scheme mas el host-link (string)	https://intranet.net:8082
				$datos['host-uri'] = (new class($datos) implements JsonSerializable{
					private $datos;

					public function __construct(&$datos)
					{
						$this->datos =& $datos;
					}

					public function __toString()
					{
						$host_uri = $this->datos['scheme'] . '://' . $this->datos['host-link'];
						return $host_uri;
					}

					public function __debugInfo()
					{
						return [
							'scheme' => $this->datos['scheme'],
							'host-link' => (string)$this->datos['host-link'],
							'host-uri' => $this->__toString()
						];
					}

					public function jsonSerialize() {
						return $this->__toString();
					}
				});

				//Devuelve la URL base hasta la aplicación
				$datos['base'] = (new class($datos) implements JsonSerializable{
					private $datos;

					public function __construct(&$datos)
					{
						$this->datos =& $datos;
					}

					public function __toString()
					{
						$base = $this->datos['host-uri'] . $this->datos['srvpublic_path'];
						return $base;
					}

					public function __debugInfo()
					{
						return [
							'host-uri' => (string)$this->datos['host-uri'],
							'srvpublic_path' => $this->datos['srvpublic_path'],
							'base' => $this->__toString()
						];
					}

					public function jsonSerialize() {
						return $this->__toString();
					}
				});

				//Devuelve la URL base hasta el alojamiento real de la aplicación
				$datos['abs'] = (new class($datos) implements JsonSerializable{
					private $datos;

					public function __construct(&$datos)
					{
						$this->datos =& $datos;
					}

					public function __toString()
					{
						$abs = $this->datos['host-uri'] . $this->datos['srvpublic_path'];
						return $abs;
					}

					public function __debugInfo()
					{
						return [
							'host-uri' => (string)$this->datos['host-uri'],
							'srvpublic_path' => $this->datos['srvpublic_path'],
							'abs' => $this->__toString()
						];
					}

					public function jsonSerialize() {
						return $this->__toString();
					}
				});

				//Devuelve la URL base hasta el alojamiento real de la aplicación
				$datos['host-abs'] = (new class($datos) implements JsonSerializable{
					private $datos;

					public function __construct(&$datos)
					{
						$this->datos =& $datos;
					}

					public function __toString()
					{
						$abs = str_replace('www.', '', $this->datos['host']) . $this->datos['srvpublic_path'];
						return $abs;
					}

					public function __debugInfo()
					{
						return [
							'host' => (string)$this->datos['host'],
							'srvpublic_path' => $this->datos['srvpublic_path'],
							'host-abs' => $this->__toString()
						];
					}

					public function jsonSerialize() {
						return $this->__toString();
					}
				});

				//Devuelve la URL completa incluido el PATH obtenido
				$datos['full'] = (new class($datos) implements JsonSerializable{
					private $datos;

					public function __construct(&$datos)
					{
						$this->datos =& $datos;
					}

					public function __toString()
					{
						$full = $this->datos['base'] . $this->datos['path'];

						return $full;
					}

					public function __debugInfo()
					{
						return [
							'base' => (string)$this->datos['base'],
							'path' => $this->datos['path'],
							'full' => $this->__toString()
						];
					}

					public function jsonSerialize() {
						return $this->__toString();
					}
				});

				//Devuelve la URL completa incluyendo los parametros QUERY si es que hay
				$datos['full-wq'] = (new class($datos) implements JsonSerializable{
					private $datos;

					public function __construct(&$datos)
					{
						$this->datos =& $datos;
					}

					public function __toString()
					{
						$full_wq = $this->datos['full'] . ( ! empty($this->datos['query']) ? '?' : '' ) . $this->datos['query'];

						return $full_wq;
					}

					public function __debugInfo()
					{
						return [
							'full' => (string)$this->datos['full'],
							'query' => $this->datos['query'],
							'full-wq' => $this->__toString()
						];
					}

					public function jsonSerialize() {
						return $this->__toString();
					}
				});

				//Devuelve la ruta de la aplicación como directorio del cookie
				$datos['cookie-base'] = $datos['srvpublic_path'] . '/';

				//Devuelve la ruta de la aplicación como directorio del cookie hasta la carpeta de la ruta actual
				$datos['cookie-full'] = (new class($datos) implements JsonSerializable{
					private $datos;

					public function __construct(&$datos)
					{
						$this->datos =& $datos;
					}

					public function __toString()
					{
						$cookie_full = $this->datos['srvpublic_path'] . rtrim($this->datos['path'], '/') . '/';
						return $cookie_full;
					}

					public function __debugInfo()
					{
						return [
							'srvpublic_path' => $this->datos['srvpublic_path'],
							'path' => $this->datos['path'],
							'cookie-full' => $this->__toString()
						];
					}

					public function jsonSerialize() {
						return $this->__toString();
					}
				});

				//Obtiene todos los datos enviados
				$datos['request'] =& $this->request('array');

				//Request Method
				$datos['request_method'] = mb_strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'cli');

				$this->datos = $this->filter_apply('JApi/url', $this->datos);
			}

			if ($get === 'array')
			{
				return $datos;
			}

			isset($datos[$get]) or $datos[$get] = NULL;
			return $datos[$get];
		}

		/**
		 * request()
		 * Obtiene los request ($_GET $_POST)
		 *
		 * @param	string	$get
		 * @return	mixed
		 */
		public function &request($get = 'array', $default = NULL, $put_default_if_empty = TRUE)
		{
			static $datos = [];

			if (count($datos) === 0)
			{
				$datos = array_merge(
					$_REQUEST,
					$_POST,
					$_GET
				);

				$path = explode('/', $this->url('path'));
				foreach($path as $_p)
				{
					if (preg_match('/(.+)(:|=)(.*)/i', $_p, $matches))
					{
						$datos[$matches[1]] = $matches[3];
					}
				}
			}

			if ($get === 'array')
			{
				return $datos;
			}

			$get = (array)$get;

			$return = $datos;
			foreach($get as $_get)
			{
				if ( ! isset($return[$_get]))
				{
					$return = $default;
					break;
				}

				if ($put_default_if_empty and ((is_array($return[$_get]) and count($return[$_get]) === 0) or empty($return[$_get])))
				{
					$return = $default;
					break;
				}

				$return = $return[$_get];
			}

			return $return;
		}

		/**
		 * ip_address()
		 * Obtiene el IP del cliente
		 *
		 * @param string $get
		 * @return mixed
		 */
		function &ip_address ($get = 'ip_address')
		{
			static $datos = [];

			if (count($datos) === 0)
			{
				$datos = [
					'ip_address' => '',
					'separator' => '',
					'binary' => '',
				];

				extract($datos, EXTR_REFS);

				$ip_address = $_SERVER['REMOTE_ADDR'];

				$spoof = NULL;
				foreach(['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP'] as $ind)
				{
					if ( ! isset($_SERVER[$ind]) OR is_null($_SERVER[$ind]))
					{
						continue;
					}

					$spoof = $_SERVER[$ind];
					sscanf($spoof, '%[^,]', $spoof);

					if ( ! filter_var($spoof, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
					{
						$spoof = NULL;
						continue;
					}

					break;
				}

				is_null($spoof) or $ip_address = $spoof;

				$separator = filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? ':' : '.';

				if ($separator === ':')
				{
					// Make sure we're have the "full" IPv6 format
					$binary = explode(':', str_replace('::', str_repeat(':', 9 - substr_count($ip_address, ':')), $ip_address));

					for ($j = 0; $j < 8; $j++)
					{
						$binary[$j] = intval($binary[$j], 16);
					}
					$sprintf = '%016b%016b%016b%016b%016b%016b%016b%016b';
				}
				else
				{
					$binary = explode('.', $ip_address);
					$sprintf = '%08b%08b%08b%08b';
				}

				$binary = vsprintf($sprintf, $binary);

				if ( ! filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
				{
					$ip_address = '0.0.0.0';
				}
			}

			if ($get === 'array')
			{
				return $datos;
			}

			if ( ! isset($datos[$get]))
			{
				$get = 'ip_address';
			}

			return $datos[$get];
		}

		/**
		 * is_empty()
		 * Validar si $valor está vacío
		 *
		 * Si es ARRAY entonces valida que tenga algún elemento
		 * Si es BOOL entonces retorna FALSO ya que es un valor así sea FALSO
		 * 
		 * @param array|bool|string|null $v
		 * @return bool
		 */
		function is_empty($v)
		{
			$type = gettype($v);

			if ($type === 'NULL')
			{
				return TRUE;
			}
			elseif ($type === 'string')
			{
				if ($v === '0')
				{
					return FALSE;
				}

				return empty($v);
			}
			elseif ($type === 'array')
			{
				return count($v) === 0;
			}

			return FALSE;
		}

		/**
		 * def_empty()
		 * Obtener un valor por defecto en caso se detecte que el primer valor se encuentra vacío
		 *
		 * @param mixed
		 * @param mixed
		 * @return mixed
		 */
		function def_empty($v, $def = NULL)
		{
			if ( ! is_empty($v))
			{
				return $v;
			}

			if (is_callable($def))
			{
				return $def();
			}

			return $def;
		}

		/**
		 * non_empty()
		 * Ejecutar una función si detecta que el valor no está vacío
		 *
		 * @param mixed
		 * @param callable
		 * @return mixed
		 */
		function non_empty($v, callable $callback)
		{
			if ( ! is_empty($v))
			{
				return $callback($v);
			}

			return $v;
		}

		/**
		 * $_CONs
		 * Variable que almacena todas las dbs conectadas
		 */
		protected $_CONs = [];

		/**
		 * $CON
		 * Variable que almacena la db primaria o por defecto
		 */
		protected $CON;
		
		/**
		 * use_CON()
		 * Inicializa la conección primaria
		 *
		 * @return self
		 */
		public function use_CON ()
		{
			if ( ! isset($this -> CON))
			{
				$db =& $this -> config('db');
				$db = (array)$db;

				isset($db['host']) or $db['host'] = 'localhost';
				isset($db['user']) or $db['user'] = 'root';
				isset($db['pasw']) or $db['pasw'] = NULL;
				isset($db['name']) or $db['name'] = NULL;
				isset($db['charset']) or $db['charset'] = 'utf8';

				$this -> CON = $this -> sql_start($db['host'], $db['user'], $db['pasw'], $db['name'], $db['charset']);
			}

			if ( ! isset($this -> CON))
			{
				throw new Exception('No se pudo conectar la base datos');
			}

			return $this;
		}
		
		public function get_CON ()
		{
			return $this -> CON;
		}

		/**
		 * $CON_logs
		 * Variable que almacena la db de logueo
		 */
		protected $CON_logs;
		
		/**
		 * use_CON()
		 * Inicializa la conección primaria
		 *
		 * @return self
		 */
		protected function use_CON_logs ()
		{
			if ( ! isset($this -> CON_logs))
			{
				$db =& $this -> config('db_logs');
				$db = (array)$db;

				isset($db['host']) or $db['host'] = 'localhost';
				isset($db['user']) or $db['user'] = 'root';
				isset($db['pasw']) or $db['pasw'] = NULL;
				isset($db['name']) or $db['name'] = NULL;
				isset($db['charset']) or $db['charset'] = 'utf8';

				if ( ! empty($db['name']))
				{
					$this -> CON_logs = $this -> sql_start($db['host'], $db['user'], $db['pasw'], $db['name'], $db['charset']);
				}
			}

			if ( ! isset($this -> CON_logs))
			{
				try
				{
					$this -> use_CON();
				}
				catch (Exception $e)
				{}

				$this -> CON_logs = $this -> CON;
			}

			if ( ! isset($this -> CON_logs))
			{
				throw new Exception('No se pudo conectar la base datos');
			}

			return $this;
		}

		/**
		 * $MYSQL_history
		 * QUERY ejecutados y errores producidos
		 * **Estructura:**
		 * - QUERY // Query Ejecutado
		 * - Error // Texto de error detectado
		 * - Errno // Número de error detectado
		 */
		protected $_MYSQL_history = [];

		/**
		 * cbd()
		 * Inicia una conección de base datos
		 *
		 * @param string
		 * @param string
		 * @param string
		 * @param string
		 * @param string
		 * @return bool
		 */
		function sql_start ($host = 'localhost', $usuario = 'root', $password = NULL, $base_datos = NULL, $charset = 'utf8')
		{
			$conection = mysqli_connect($host, $usuario, $password);

			if ( ! $conection)
			{
				$this-> _MYSQL_history[] = [
					'query' => '',
					'suphp' => 'mysqli_connect("' . $host . '", "' . $usuario . '", "' . str_repeat('*', mb_strlen($password)) . '")',
					'error' => mysqli_connect_error(), 
					'errno' => mysqli_connect_errno(),
					'hstpr' => 'error',
				];
				return NULL;
			}

			$conection->_host = $host;
			$conection->_usuario = $usuario;
			$conection->_password = $password;

			$this->_CONs[$conection->thread_id] = $conection;

			if ( ! empty($base_datos) and ! mysqli_select_db($conection, $base_datos))
			{
				$this-> _MYSQL_history[] = [
					'query' => '',
					'suphp' => 'mysqli_select_db($conection, "' . $base_datos . '")',
					'error' => mysqli_error($conection), 
					'errno' => mysqli_errno($conection),
					'hstpr' => 'error',
					'conct' => $conection->thread_id,
				];
				return NULL;
			}

			$conection->_base_datos = $base_datos;

			$this-> _MYSQL_history[] = [
				'query' => '',
				'suphp' => 'mysqli_connect("' . $host . '", "' . $usuario . '", "' . str_repeat('*', mb_strlen($password)) . '")',
				'error' => '', 
				'errno' => '',
				'hstpr' => 'success',
				'conct' => $conection->thread_id,
			];

			if ( ! mysqli_set_charset($conection, $charset))
			{
				$this-> _MYSQL_history[] = [
					'query' => '',
					'suphp' => 'mysqli_set_charset($conection, "' . $charset . '")',
					'error' => mysqli_error($conection), 
					'errno' => mysqli_errno($conection),
					'hstpr' => 'warning',
					'conct' => $conection->thread_id,
				];
			}

			$conection->_charset = $charset;

			$utc = $this -> getUTC();

			if ( ! mysqli_query($conection, 'SET time_zone = "' . $utc . '";'))
			{
				$this-> _MYSQL_history[] = [
					'query' => 'SET time_zone = "' . $utc . '";',
					'suphp' => 'mysqli_query($conection, $query)',
					'error' => mysqli_error($conection), 
					'errno' => mysqli_errno($conection),
					'hstpr' => 'warning',
					'conct' => $conection->thread_id,
				];
			}

			$conection->_utc = $utc;

			if ( ! mysqli_query($conection, 'SET SESSION group_concat_max_len = 1000000;'))
			{
				$this-> _MYSQL_history[] = [
					'query' => 'SET SESSION group_concat_max_len = 1000000;',
					'suphp' => 'mysqli_query($conection, $query)',
					'error' => mysqli_error($conection), 
					'errno' => mysqli_errno($conection),
					'hstpr' => 'warning',
					'conct' => $conection->thread_id,
				];
			}

			return $conection;
		}
		
		/**
		 * sql_stop()
		 * Cierra una conección de base datos
		 *
		 * @param mysqli
		 * @return bool
		 */
		public function sql_stop (mysqli $conection)
		{
			$tid = $conection -> thread_id;

			$return = mysqli_close($conection);

			unset($this->_CONs[$tid]);

			$this-> _MYSQL_history[] = [
				'query' => '',
				'suphp' => 'mysqli_close($conection)',
				'error' => '', 
				'errno' => '',
				'hstpr' => 'success',
				'conct' => $tid,
			];

			return $return;
		}

		/**
		 * sql_stop()
		 * Cierra una conección de base datos
		 *
		 * @param mysqli
		 * @return bool
		 */
		public function sql_stop_all ()
		{
			$return = true;

			foreach($this->_CONs as $tid => $conection)
			{
				if ($this -> CON and $tid === $this -> CON -> thread_id)
				{
					$this -> CON = NULL;
				}

				if ($this -> CON_logs and $tid === $this -> CON_logs -> thread_id)
				{
					$this -> CON_logs = NULL;
				}

				$_return = $this->sql_stop($conection);

				$_return or $return = false;
			}

			return $return;
		}
		
		/**
		 * sql_esc()
		 * Ejecuta la función `mysqli_real_escape_string`
		 *
		 * @param string
		 * @param mysqli
		 * @return string
		 */
		function sql_esc ($valor = '', mysqli $conection = NULL)
		{
			is_null($conection) and $conection = $this -> use_CON() -> CON;
			return mysqli_real_escape_string($conection, $valor);
		}
		
		/**
		 * sql_qpesc()
		 * Retorna el parametro correcto para una consulta de base datos
		 *
		 * @param string
		 * @param bool
		 * @param mysqli
		 * @return string
		 */
		function sql_qpesc ($valor = '', $or_null = FALSE, mysqli $conection = NULL, $f_as_f = FALSE)
		{
			static $_functions_alws = [
				'CURDATE', 'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURTIME', 'LOCALTIME', 'LOCALTIMESTAMP', 'NOW', 'SYSDATE'
			];
			static $_functions = [
				'ASCII', 'CHAR_LENGTH', 'CHARACTER_LENGTH', 'CONCAT', 'CONCAT_WS', 'FIELD', 'FIND_IN_SET', 'FORMAT', 'INSERT', 'INSTR', 'LCASE', 'LEFT', 'LENGTH', 'LOCATE', 'LOWER', 'LPAD', 'LTRIM', 'MID', 'POSITION', 'REPEAT', 'REPLACE', 'REVERSE', 'RIGHT', 'RPAD', 'RTRIM', 'SPACE', 'STRCMP', 'SUBSTR', 'SUBSTRING', 'SUBSTRING_INDEX', 'TRIM', 'UCASE', 'UPPER', 'ABS', 'ACOS', 'ASIN', 'ATAN', 'ATAN2', 'AVG', 'CEIL', 'CEILING', 'COS', 'COT', 'COUNT', 'DEGREES', 'DIV', 'EXP', 'FLOOR', 'GREATEST', 'LEAST', 'LN', 'LOG', 'LOG10', 'LOG2', 'MAX', 'MIN', 'MOD', 'PI', 'POW', 'POWER', 'RADIANS', 'RAND', 'ROUND', 'SIGN', 'SIN', 'SQRT', 'SUM', 'TAN', 'TRUNCATE', 'ADDDATE', 'ADDTIME', 'CURDATE', 'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURTIME', 'DATE', 'DATEDIFF', 'DATE_ADD', 'DATE_FORMAT', 'DATE_SUB', 'DAY', 'DAYNAME', 'DAYOFMONTH', 'DAYOFWEEK', 'DAYOFYEAR', 'EXTRACT', 'FROM_DAYS', 'HOUR', 'LAST_DAY', 'LOCALTIME', 'LOCALTIMESTAMP', 'MAKEDATE', 'MAKETIME', 'MICROSECOND', 'MINUTE', 'MONTH', 'MONTHNAME', 'NOW', 'PERIOD_ADD', 'PERIOD_DIFF', 'QUARTER', 'SECOND', 'SEC_TO_TIME', 'STR_TO_DATE', 'SUBDATE', 'SUBTIME', 'SYSDATE', 'TIME', 'TIME_FORMAT', 'TIME_TO_SEC', 'TIMEDIFF', 'TIMESTAMP', 'TO_DAYS', 'WEEK', 'WEEKDAY', 'WEEKOFYEAR', 'YEAR', 'YEARWEEK', 'BIN', 'BINARY', 'CASE', 'CAST', 'COALESCE', 'CONNECTION_ID', 'CONV', 'CONVERT', 'CURRENT_USER', 'DATABASE', 'IF', 'IFNULL', 'ISNULL', 'LAST_INSERT_ID', 'NULLIF', 'SESSION_USER', 'SYSTEM_USER', 'USER', 'VERSION'
			];

			if ($or_null !== FALSE and is_empty($valor))
			{
				$or_null = ($or_null === TRUE ? 'NULL' : $or_null);
				return $or_null;
			}

			$_regex_funcs_alws = '/^(' . implode('|', $_functions_alws) . ')(\(\))?$/i';
			$_regex_funcs = '/\b('.implode('|', $_functions).')\b/i';

			if (is_string($valor) and preg_match($_regex_funcs_alws, $valor))  ## Palabras Reservadas No Peligrosas
			{
				return $valor;
			}
			elseif (is_string($valor) and preg_match($_regex_funcs, $valor) and $f_as_f)  ## Palabras Reservadas
			{
				if (is_string($valor) and preg_match('/^\[MF\]\:/i', $valor))
				{
					$valor = preg_replace('/^\[MF\]\:/i', '', $valor);
				}
				else
				{
					return $valor;
				}
			}
			else
			{
				if (is_string($valor) and preg_match('/^\[MF\]\:/i', $valor))
				{
					$valor = preg_replace('/^\[MF\]\:/i', '', $valor);
				}
			}

			if (is_bool($valor))
			{
				return $valor ? 'TRUE' : 'FALSE';
			}

			if (is_numeric($valor) and ! preg_match('/^0/i', (string)$valor))
			{
				return $this->sql_esc($valor, $conection);
			}

			is_array($valor) and $valor = json_encode($valor);

			return '"' . $this->sql_esc($valor, $conection) . '"';
		}

		/**
		 * sql_et()
		 * Valida la existencia de una tabla en la db
		 *
		 * @param string
		 * @param mysqli
		 * @return bool
		 */
		function sql_et(string $tbl, mysqli $conection = NULL)
		{
			is_null($conection) and $conection = $this -> use_CON() -> CON;
			return (bool)mysqli_query($conection, 'SELECT * FROM `' . $tbl . '` LIMIT 0');
		}

		/**
		 * sql()
		 * Ejecuta una consulta a la Base Datos
		 *
		 * @param string
		 * @param bool
		 * @param mysqli
		 * @return mixed
		 */
		function sql(string $query, $is_insert = FALSE, mysqli $conection = NULL)
		{
			is_null($conection) and $conection = $this -> use_CON() -> CON;

			$result =  mysqli_query($conection, $query);

			if ( ! $result)
			{
				$this-> _MYSQL_history[] = [
					'query' => $query,
					'suphp' => 'mysqli_query($conection, $query)',
					'error' => mysqli_error($conection), 
					'errno' => mysqli_errno($conection),
					'hstpr' => 'error',
					'conct' => $conection->thread_id,
				];

				trigger_error('Error en el query: ' . $query, E_USER_WARNING);
				return FALSE;
			}

			$return = true;

			if ($is_insert)
			{
				$return = mysqli_insert_id($conection);
			}

			$this-> _MYSQL_history[] = [
				'query' => $query,
				'suphp' => 'mysqli_query($conection, $query)',
				'error' => '', 
				'errno' => '',
				'hstpr' => 'success',
				'conct' => $conection->thread_id,
				($is_insert ? 'insert_id' : 'return') => $return,
			];

			return $return;
		}

		/**
		 * sql_data()
		 * Ejecuta una consulta a la Base Datos
		 *
		 * @param string
		 * @param bool
		 * @param string|array|null
		 * @param mysqli
		 * @return mixed
		 */
		function sql_data(string $query, $return_first = FALSE, $fields = NULL, mysqli $conection = NULL)
		{
			static $_executeds = [];

			if (is_a($return_first, 'mysqli'))
			{
				is_null($conection) and $conection = $return_first;
				$return_first = FALSE;
			}

			if (is_a($fields, 'mysqli'))
			{
				is_null($conection) and $conection = $fields;
				$fields = NULL;
			}

			is_null($conection) and $conection = $this -> use_CON() -> CON;

			isset($_executeds[$conection->thread_id]) or $_executeds[$conection->thread_id] = 0;
			$_executeds[$conection->thread_id]++;

			if($_executeds[$conection->thread_id] > 1)
			{
				@mysqli_next_result($conection);
			}

			$result =  mysqli_query($conection, $query);

			if ( ! $result)
			{
				$this-> _MYSQL_history[] = [
					'query' => $query,
					'suphp' => 'mysqli_query($conection, $query)',
					'error' => mysqli_error($conection), 
					'errno' => mysqli_errno($conection),
					'hstpr' => 'error',
					'conct' => $conection->thread_id,
				];

				trigger_error('Error en el query: ' . $query, E_USER_WARNING);

				$sql_data_result = MysqlResultData::fromArray([])
				-> quitar_fields('log');
			}
			else
			{
				$sql_data_result = new MysqlResultData ($result);
			}

			if ( ! is_null($fields))
			{
				$sql_data_result
				-> filter_fields($fields);
			}

			if ($return_first)
			{
				return $sql_data_result
				-> first();
			}

			return $sql_data_result;
		}

		/**
		 * sql_pswd()
		 * Obtiene el password de un texto
		 *
		 * @param string
		 * @param mysqli
		 * @return bool
		 */
		function sql_pswd ($valor, mysqli $conection = NULL)
		{
			return $this -> sql_data('
			SELECT PASSWORD(' . $this->sql_qpesc($valor, FALSE, $conection) . ') as `valor`;
			', TRUE, 'valor', $conection);
		}

		/**
		 * sql_trans()
		 * Procesa transacciones de Base Datos
		 * 
		 * WARNING: Si se abre pero no se cierra no se guarda pero igual incrementa AUTOINCREMENT
		 * WARNING: Se deben cerrar exitosamente la misma cantidad de los que se abren
		 * WARNING: El primero que cierra con error cierra todos los transactions activos 
		 *          (serìa innecesario cerrar exitosamente las demas)
		 *
		 * @param bool|null
		 * @param mysqli
		 * @return bool
		 */
		function sql_trans($do = NULL, mysqli $conection = NULL)
		{
			static $_trans = []; ## levels de transacciones abiertas
			static $_auto_commit_setted = [];

			if (is_a($do, 'mysqli'))
			{
				is_null($conection) and $conection = $do;
				$do = NULL;
			}

			is_null($conection) and $conection = $this -> use_CON() -> CON;

			isset($_trans[$conection->thread_id]) or $_trans[$conection->thread_id] = 0;

			if ($do === 'NUMTRANS')
			{
				return $_trans[$conection->thread_id];
			}

			isset($_auto_commit_setted[$conection->thread_id]) or $_auto_commit_setted[$conection->thread_id] = FALSE;

			if (is_null($do))
			{
				## Se está iniciando una transacción

				## Solo si el level es 0 (aún no se ha abierto una transacción), se ejecuta el sql
				$_trans[$conection->thread_id] === 0 and $this -> sql('START TRANSACTION', FALSE, $conection);

				$_trans[$conection->thread_id]++; ## Incrmentar el level

				if ( ! $_auto_commit_setted[$conection->thread_id])
				{
					$this -> sql('SET autocommit = 0') AND $_auto_commit_setted[$conection->thread_id] = TRUE;
				}

				return TRUE;
			}

			if ($_trans[$conection->thread_id] === 0)
			{
				return FALSE; ## No se ha abierto una transacción
			}

			if ( ! is_bool($do))
			{
				trigger_error('Se está enviando un parametro ' . gettype($do) . ' en vez de un BOOLEAN', E_USER_WARNING);
				$do = (bool)$do;
			}

			if ($do)
			{
				$_trans[$conection->thread_id]--; ## Reducir el level

				## Solo si el level es 0 (ya se han cerrado todas las conecciones), se ejecuta el sql
				if ($_trans[$conection->thread_id] === 0)
				{
					$this -> sql('COMMIT', FALSE, $conection);

					if ($_auto_commit_setted[$conection->thread_id])
					{
						$this -> sql('SET autocommit = 1') AND $_auto_commit_setted[$conection->thread_id] = FALSE;
					}
				}
			}
			else
			{
				$_trans[$conection->thread_id] = 0; ## Finalizar todas los levels abiertos

				$this -> sql('ROLLBACK', FALSE, $conection);

				if ($_auto_commit_setted[$conection->thread_id])
				{
					$this -> sql('SET autocommit = 1') AND $_auto_commit_setted[$conection->thread_id] = FALSE;
				}
			}

			return TRUE;
		}

		function translate ($frase, $n = NULL, ...$sprintf)
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
					if ($file = $base. DS. 'configs' . DS . 'translates'. DS. $_lang . '-noerror.php' and file_exists($file))
					{
						@include $file;
					}

					if ($file = $base. DS. 'configs' . DS . 'translates'. DS. $_lang . '.php' and file_exists($file))
					{
						@include $file;
					}

					if ($file = $path. DS. 'configs' . DS . 'translate'. DS. mb_strtolower($_lang) . '.php' and file_exists($file))
					{
						@include $file;
					}

					$_temp_lang = $_lang;
					$_temp_lang = explode('-', $_temp_lang, 2);
					$_temp_lang = $_temp_lang[0];

					if ($file = $base. DS. 'configs' . DS . 'translates'. DS. $_temp_lang . '.php' and file_exists($file))
					{
						@include $file;
					}

					if ($file = $path. DS. 'configs' . DS . 'translate'. DS. mb_strtolower($_temp_lang) . '.php' and file_exists($file))
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

			if (is_null($n))
			{
				$n = 1;
			}

			$frase_original = $frase;

			$frase_traduccion = $frase;
			if (is_array($frase))
			{
				$frase = array_values($frase);

				switch(count($frase))
				{
					case 2:
							if($n==1) $frase_traduccion = $frase[0];
						else          $frase_traduccion = $frase[1];
						break;
					case 3:
							if($n==1) $frase_traduccion = $frase[0];
						elseif($n==0) $frase_traduccion = $frase[2];
						else          $frase_traduccion = $frase[1];
						break;
					case 4:
							if($n==1) $frase_traduccion = $frase[0];
						elseif($n==0) $frase_traduccion = $frase[2];
						elseif($n <0) $frase_traduccion = $frase[3];
						else          $frase_traduccion = $frase[1];
						break;
					default:
						$frase_traduccion = $frase[0];
						break;
				}

				$frase = $frase[0];
			}

			if ( ! isset($langs[$frase_traduccion]) and ! isset($langs[$frase]))
			{
				if ($this->LANG <> 'ES')
				{
					
					$this->mkdir2('/configs/translates', APPPATH);
					$_file_dest = APPPATH . '/configs/translates/' . $this->LANG . '-noerror.php';

					file_exists($_file_dest) or 
					file_put_contents($_file_dest, '<?php' .PHP_EOL. '/** Generado automáticamente el ' . date('d/m/Y H:i:s') . ' */'.PHP_EOL);

					$trace = debug_backtrace(false);

					$_japi_funcs_file = COREPATH . DS . 'configs' . DS . 'functions' . DS . 'JApi.php';
					while(count($trace) > 0 and isset($trace[0]['file']) and in_array($trace[0]['file'], [__FILE__, $_japi_funcs_file]))
					{
						array_shift($trace);
					}

					$filename = __FILE__ . '#' . __LINE__;
					if (isset($trace[0]))
					{
						$filename = $trace[0]['file'] . '#' . $trace[0]['line'];
					}

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
				}

				$langs[$frase] = $frase_traduccion;
			}

			array_unshift($sprintf, $n);

			$traduccion = isset($langs[$frase_traduccion]) ? $langs[$frase_traduccion] : $langs[$frase];
			$traduccion = (array)$traduccion;

			switch(count($traduccion))
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
					$traduccion = $traduccion[0];
					break;
			}

			$traduccion = $_sprintf($traduccion, $sprintf);
			return $traduccion;
		}

		function obj ($class, ...$pk)
		{
			$class = str_replace('/', '\\', $class);
			$class = explode('\\', $class);
			empty($class[0]) and array_shift($class);
			$class[0] === 'Object' or array_unshift($class, 'Object');
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

		function snippet ($file, $return_content = TRUE, $declared_variables = [])
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

		public function nocache()
		{
			header('Cache-Control: no-cache, must-revalidate'); //HTTP 1.1
			header('Pragma: no-cache'); //HTTP 1.0
			header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past

			return $this;
		}

		public function cache($days = 365, $for = 'private', $rev = 'no-revalidate')
		{
			$time = 60 * 60 * 24 * $days;
			$cache_expire_date = gmdate("D, d M Y H:i:s", time() + $time);

			header('User-Cache-Control: max-age=' . $time. ', ' . $for . ', ' . $rev); //HTTP 1.1
			header('Cache-Control: max-age=' . $time. ', ' . $for . ', ' . $rev); //HTTP 1.1
			header('Pragma: cache'); //HTTP 1.0
			header('Expires: '.$cache_expire_date.' GMT'); // Date in the future

			return $this;
		}

		public function exit($status = NULL)
		{
			exit ($status);
		}
	}
}

if ( ! function_exists('APP'))
{
	/**
	 * APP()
	 * Función que retorna la única instancia del JApi a nivel Global
	 */
	function APP ()
	{
		return JApi::instance();
	}
}

/** Generando la primera instancia del JApi */
APP ();

/** Iniciando todo el proceso */
APP () -> init ();

return APP();