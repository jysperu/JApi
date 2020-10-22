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
				@trigger_error('Directorio `' . $_directory . '` no existe', E_USER_WARNING);
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

			foreach($this->_app_directories as $orden => $directories)
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
			//ob_start();

			/** Iniciando las variables _SESSION */
			//session_start();

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

			/** Iniciando autoload */
			spl_autoload_register([$this, '_init_autoload']);

			$this

			/**
			 * Cargando todos los archivos de funciones
			 * El orden a recorrer es de mayor a menor para que los directorios de mayor orden puedan crear primero las funciones actualizadas
			 */
			-> map_app_directories ([$this, '_init_load_functions'], true)

			/**
			 * Cargando el autoload de la carpeta vendor
			 * El orden a recorrer es de mayor a menor para que los directoreios de mayor orden puedan cargar sus librerías actualizadas
			 */
			-> map_app_directories ([$this, '_init_load_vendor'], true)

			/**
			 * Cargando la configuración de la aplicación
			 * El orden a recorrer es de menor a mayor para que los directorios de mayor orden puedan sobreescribir los de menor orden
			 */
			-> map_app_directories ([$this, '_init_load_config'], false)

			/**
			 * Procesando todos los app.php
			 * El orden a recorrer es de mayor a menor para que los directoreios de mayor orden puedan procesar sus requerimientos primero
			 */
			-> map_app_directories ([$this, '_init_load_app'], true)
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
			if (($severity & error_reporting()) !== $severity)
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

			@include_once ($_config_file);
		}

		/**
		 * _init_load_app()
		 */
		protected function _init_load_app ($dir = NULL)
		{
			$_app_file = $dir . '/app.php';
			if ( ! file_exists($_app_file)) return;

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
				@trigger_error('Hook es requerido', E_USER_ERROR);
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
				@trigger_error('Hook es requerido', E_USER_ERROR);
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
		public function logger ($message, $code = NULL, $severity = NULL, $meta = NULL, $filepath = NULL, $line = NULL, $trace = NULL, $show = TRUE)
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

			is_bool($code) and $show = $code and $code = NULL;

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
				if (in_array($trace[0]['function'], ['_handler_exception', '_handler_error']))
				{
					array_shift($trace);
				}
			}

			if (isset($trace[0]))
			{
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
				$url = url('array');
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
				$ip_address = ip_address('array');
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

			try
			{
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