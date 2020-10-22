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

			/**
			 * Procesando todos los app.php
			 * El orden a recorrer es de menor a mayor para que los directoreios prioritarios puedan procesar sus requerimientos primero
			 */
			-> map_app_directories ([$this, '_init_load_app'])
			;
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

			$directories = $this->get_app_directories(true);

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
		 * _init_load_app()
		 */
		protected function _init_load_app ($dir = NULL)
		{
			$_app_file = $dir . '/app.php';
			if ( ! file_exists($_app_file)) return;

			$this->action_apply('JApi/app.php', $dir, $_app_file);

			@include_once ($_app_file);
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

			if (isset($trace[0]))
			{
				$filepath === __FILE__ and $line = NULL;
				$filepath === __FILE__ and $filepath = NULL;

				is_null($filepath) and $filepath = $trace[0]['file'];
				is_null($line) and $line = $trace[0]['line'];

				isset($trace[0]['class']) and ! isset($meta['class']) and $meta['class'] = $trace[0]['class'];
				isset($trace[0]['function']) and ! isset($meta['function']) and $meta['function'] = $trace[0]['function'];
			}

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

			$meta['MYSQL_history'] = $this->_MYSQL_history;

			$trace_slim = array_map(function($arr){
				return $arr['file'] . '#' . $arr['line'];
			}, $trace);
			$meta['trace_slim'] = $trace_slim;

			try
			{
				$CON_logs = $this->use_CON_logs()->CON_logs; // Conecta la DB de logs en caso de que no esté conectada

				if ($this -> sql_trans('NUMTRANS', $CON_logs) !== 0)
				{
					$this -> sql_trans(false, $CON_logs);
				}

				if ( ! (bool)mysqli_query($CON_logs, 'SELECT * FROM `_logs` LIMIT 0'))
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
				$_app_directories_list = $this->get_app_directories(true);

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

			$_utc_dtz = new DateTimeZone(date_default_timezone_get());
			$_utc_dt  = new DateTime('now', $_utc_dtz);
			$_utc_offset = $_utc_dtz->getOffset($_utc_dt);

			$utc = sprintf( "%s%02d:%02d", ( $_utc_offset >= 0 ) ? '+' : '-', abs( $_utc_offset / 3600 ), abs( $_utc_offset % 3600 ) );

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