<?php
/**
 * Archivo de funciones principal
 * @filesource
 */

defined('HOMEPATH') or exit('Archivo no se puede llamar directamente');

defined('ISCOMMAND') or define('ISCOMMAND', false);
defined('JAPIPATH')  or define('JAPIPATH', __DIR__);
defined('cdkdsp')    or define('cdkdsp', 'cdkdsp');

if ( ! function_exists('APP')) exit('Función `APP()` es requerida');

//=================================================================================//
//==== Helpers                                                                =====//
//=================================================================================//

if ( ! function_exists('print_array'))
{
	/**
	 * print_array()
	 * Muestra los contenidos enviados en el parametro para mostrarlos en HTML
	 *
	 * @param	...array
	 * @return	void
	 */
	function print_array(...$array)
	{
		$r = '';

		$trace = debug_backtrace(false);
		while(count($trace) > 0 and isset($trace[0]['file']) and $trace[0]['file'] === __FILE__)
		{
			array_shift($trace);
		}

		$file_line = '';
		isset($trace[0]) and 
		$file_line = '<small style="color: #ccc;display: block;margin: 0;">' . $trace[0]['file'] . ' #' . $trace[0]['line'] . '</small><br>';

		if (count($array) === 0)
		{
			$r.= '<small style="color: #888">[SIN PARAMETROS]</small>';
		}
		else
		foreach ($array as $ind => $_arr)
		{
			if (is_null($_arr))
			{
				$_arr = '<small style="color: #888">[NULO]</small>';
			}
			elseif (is_string($_arr) and empty($_arr))
			{
				$_arr = '<small style="color: #888">[VACÍO]</small>';
			}
			elseif (is_bool($_arr))
			{
				$_arr = '<small style="color: #888">['.($_arr?'TRUE':'FALSE').']</small>';
			}
			elseif (is_array($_arr) and function_exists('array_html'))
			{
				$_arr = array_html($_arr);
			}
			else
			{
				$_arr = htmlentities(print_r($_arr, true));
			}

			$r.= ($ind > 0 ? '<hr style="border: none;border-top: dashed #ebebeb .5px;margin: 12px 0;">' : '') . $_arr;
		}

		echo '<pre class="dipa">' . 
				'<style>.dipa{' . 
					'display:block;text-align:left;color:#444;background:#fff;position:relative;z-index:99999999999;' . 
					'margin:5px 5px 15px;padding:0 10px 10px;border:solid 1px #ebebeb;box-shadow:4px 4px 4px rgba(235,235,235,.5)' . 
				'}</style>' . 
				$file_line . 
				$r . 
			 '</pre>' . 
			 PHP_EOL;
	}
}

if ( ! function_exists('print_r2'))
{
	/**
	 * print_r2()
	 * @see print_array
	 */
	function print_r2(...$array)
	{
		return call_user_func_array('print_array', $array);
	}
}

if ( ! function_exists('die_array'))
{
	/**
	 * die_array()
	 * Muestra los contenidos enviados en el parametro para mostrarlos en HTML y finaliza los segmentos
	 *
	 * @param	...array
	 * @return	void
	 */
	function die_array(...$array)
	{
		call_user_func_array('print_array', $array);
		die();
	}
}

if ( ! function_exists('mkdir2'))
{
	/**
	 * mkdir2()
	 * Crea los directorios faltantes desde la carpeta $base
	 *
	 * @param	string 	$folder folder
	 * @param	string 	$base	base a considerar
	 * @return 	string 	ruta del folder creado
	 */
	function mkdir2 ($folder, $base = NULL)
	{
		if (is_null($base))
		{
			$_app_directories_list = APP()->get_app_directories();

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
				file_put_contents($return . DS . 'index.htm', ''); // Silence is golden
			}
		}

		return $return;
	}
}

if ( ! function_exists('array_html'))
{
	/**
	 * array_html()
	 * Convierte un Array en un formato nestable para HTML
	 *
	 * @param array $arr Array a mostrar
	 * @return string
	 */
	function array_html (array $arr, $lvl = 0)
	{
		static $_instances = 0;

		$lvl = (int)$lvl;

		$lvl_child = $lvl + 1 ;
		$str = [];

		$lvl===0 and $str[] = '<div class="array_html" id="array_html_' . (++$_instances) . '">';

		$str[] = '<ol data-lvl="' . ($lvl) . '" class="array' . ($lvl > 0 ? ' child' : '') . '">';

		if (count($arr) === 0)
		{
			$_str = '';
			$_str.= '<li class="detail">';
			$_str.= '<pre class="child-inline">';
			$_str.= '<small style="color: #888">[Array vacío]</small>';
			$_str.= '</pre>';

			$str[] = $_str;
		}

		foreach ($arr as $key => $val)
		{
			$hash = md5(json_encode([$lvl, $key]));
			$ctype = gettype($val);
			$class = $ctype ==='object' ? get_class($val) : $ctype;

			$_str = '';

			$_str.= '<li class="detail" data-hash="' . htmlspecialchars($hash) . '">';
			$_str.= '<span class="key'.(is_numeric($key)?' num':'').(is_integer($key)?' int':'').'">';
			$_str.= $key;
			$_str.= '<small class="info">'.$class.'</small>';
			$_str.= '</span>';
			
			if ( $ctype === 'object')
			{
				$asarr = NULL;
				foreach(['getArrayCopy', 'toArray', '__toArray'] as $f)
				{
					if (method_exists($val, $f))
					{
						try
						{
							$t = call_user_func([$val, $f]);
							if( ! is_array($t))
							{
								throw new Exception('No es Array');
							}
							$asarr = $t;
						}
						catch(Exception $e)
						{}
					}
				}
				is_null($asarr) or $val = $asarr;
			}
			
			if (is_array($val))
			{
				$_str .= array_html($val, $lvl_child);
			}
			
			elseif ( $ctype === 'object')
			{
				$_str.= '<pre data-lvl="'.$lvl_child.'" class="'.$ctype.' child'.($ctype === 'object' ? (' ' . $class) : '').'">';
				$_str.= htmlentities(print_r($val, true));
				$_str.= '</pre>';
			}
			else
			{
				$_str.= '<pre data-lvl="'.$lvl_child.'" class="'.$ctype.' child-inline">';
				if (is_null($val))
				{
					$_str.= '<small style="color: #888">[NULO]</small>';
				}
				elseif (is_string($val) and empty($val))
				{
					$_str.= '<small style="color: #888">[VACÍO]</small>';
				}
				elseif (is_bool($val))
				{
					$_str.= '<small style="color: #888">['.($val?'TRUE':'FALSE').']</small>';
				}
				else
				{
					$_str.= htmlentities(print_r($val, true));
				}
				$_str.= '</pre>';
			}

			$str[] = $_str;
		}

		$str[] = '</ol>';

		if ($lvl === 0)
		{
			$str[] = 
				'<style>'.
					'.array_html {display: block;text-align: left;color: #444;background: white;position:relative}'.
					'.array_html * {margin:0;padding:0}'.
					'.array_html .array {list-style: none;margin: 0;padding: 0;}'.
					'.array_html .array .array {margin: 10px 0 10px 10px;}'.
					'.array_html .key {padding: 5px 10px;display:block;border-bottom: solid 1px #ebebeb}'.
					'.array_html .detail {display: block;border: solid 1px #ebebeb;margin: 0 0 0;}'.
					'.array_html .detail + .detail {margin-top: 10px}'.
					'.array_html .array .array .detail {border-right: none}'.
					'.array_html .child:not(.array), .array_html .child-inline {padding:10px}'.
					'.array_html .info {color: #ccc;float: right;margin: 4px 0 4px 4px;user-select:none}'.
					'.array_html.js .detail.has-child:not(.open)>.child {display:none}'.
					'.array_html.js .detail.has-child:not(.open)>.key {border-bottom:none}'.
					'.array_html.js .detail.has-child>.key {cursor:pointer}'.
					'.array_html.js .detail.has-child:before {content: "▼";float: left;padding: 5px;color: #ccc;}'.
					'.array_html.js .detail.has-child.open:before {content: "▲";}' . 
				'</style>'
			;

			$str[] = 
				'<script>'.
					';(function(){'.
						'var div = document.getElementById("array_html_'.$_instances.'");'.
						'var open = function(e){if(e.defaultPrevented){return;};var t = e.target;if(/info/.test(t.classList)){t = t.parentElement;};if(!(/key/.test(t.classList))){return;};t.parentElement.classList.toggle("open");e.preventDefault()};'.
						'div.classList.add("js");'.
						'div.querySelectorAll(".child").forEach(function(d){var p = d.parentElement, c = p.classList;c.add("has-child");c.add("open");p.onclick = open;});'.
					'}());' .
				'</script>'
			;
		}

		$lvl===0 and $str[] = '</div>';
		$str = implode('', $str);
		return $str;
	}

}

if ( ! function_exists('build_url'))
{
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
}

if ( ! function_exists('redirect'))
{
	/**
	 * Establecer una redirección en caso el Tipo sea
	 * @param	string	$link
	 * @return	self
	 */
	function redirect($url, $query = NULL)
	{
		error_reporting(0);

		is_array($url) and $url = build_url($url);
		$parsed_url = parse_url($url);

		isset($parsed_url['scheme']) or $parsed_url['scheme'] = url('scheme');
		if ( ! isset($parsed_url['host']))
		{
			$parsed_url['host'] = url('host');
			$parsed_url['path'] = url('srvpublic_path') . '/' . ltrim($parsed_url['path'], '/');
		}

		if ( ! is_null($query))
		{
			isset($parsed_url['query'])    or $parsed_url['query']  = [];
			is_array($parsed_url['query']) or $parsed_url['query']  = parse_str($parsed_url['query']);

			$parsed_url['query'] = array_merge($parsed_url['query'], $query);
		}

		$url = build_url ($parsed_url);

		APP() -> _getandclear_buffer_content(); // El contenido no será reportado como error

		header('Location: ' . $url) or die('<script>location.replace("' . $url . '");</script>');
		die();
	}
}

//=================================================================================//
//==== Respuestas                                                             =====//
//=================================================================================//

if ( ! function_exists('http_code_message'))
{
	/**
	 * http_code_message()
	 * Resuelve el valor por defecto de las respuestas del HTTP status
	 *
	 * @param Integer $code El código
	 * @return string
	 */
	function http_code_message (int $code = 200)
	{
		static $messages = [
			100 => 'Continue',
			101 => 'Switching Protocols',

			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',

			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			307 => 'Temporary Redirect',

			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			422 => 'Unprocessable Entity',
			426 => 'Upgrade Required',
			428 => 'Precondition Required',
			429 => 'Too Many Requests',
			431 => 'Request Header Fields Too Large',

			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported',
			511 => 'Network Authentication Required',
		];
		return isset($messages[$code]) ? $messages[$code] : 'Non Status Text';
	}
}

if ( ! function_exists('http_code'))
{
	/**
	 * http_code()
	 * Establece la cabecera del status HTTP
	 *
	 * @param Integer $code El código
	 * @param String $message El texto del estado
	 * @return void
	 */
	function http_code ($code = 200, $message = '')
	{
		static $server_protocol_alloweds = [
			'HTTP/1.0', 
			'HTTP/1.1', 
			'HTTP/2'
		];

		if (defined('STDIN')) return;

		is_int($code) or 
		$code = (int) $code;

		empty($message) and 
		$message = http_code_message($code);

		if (ISCOMMAND)
		{
			@header('Status: ' . $code . ' ' . $message, TRUE);
			return;
		}

		
		$server_protocol = (isset($_SERVER['SERVER_PROTOCOL']) && in_array($_SERVER['SERVER_PROTOCOL'], $server_protocol_alloweds, TRUE)) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
		@header($server_protocol . ' ' . $code . ' ' . $message, TRUE, $code);
		return;
	}
}

if ( ! function_exists('force_exit'))
{
	/**
	 * force_exit()
	 */
	function force_exit ($status = null)
	{
		exit($status);
	}
}

//=================================================================================//
//==== Control de errores                                                     =====//
//=================================================================================//

if ( ! function_exists('logger'))
{
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
	function logger ($message, $code = NULL, $severity = NULL, $meta = NULL, $filepath = NULL, $line = NULL, $trace = NULL)
	{
		static $_alertas_omitidas = [
//			'Trying to access array offset on value of type null',
		];

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
		$_directories = APP()->get_app_directories_labels();

		(is_array($severity) and is_null($meta)) and $meta = $severity and $severity = NULL;

		is_null($code) and $code = 0;
		is_null($meta) and $meta = [];
		is_array($meta) or $meta = (array)$meta;

		$meta['datetime']        = date('l d/m/Y H:i:s');
		$meta['time']            = time();
		$meta['microtime']       = microtime();
		$meta['microtime_float'] = microtime(true);

		if ($message instanceof BasicException)
		{
			$exception = $message;

			$meta = array_merge($exception->getMeta(), $meta);
			is_null($severity) and $severity = 'BasicException';
			$meta['class'] = get_class($exception);
			$meta['class_base'] = 'BasicException';
		}
		elseif ($message instanceof Exception)
		{
			$exception = $message;

			is_null($severity) and $severity = 'Exception';
			$meta['class'] = get_class($exception);
			$meta['class_base'] = 'Exception';
		}
		elseif ($message instanceof TypeError)
		{
			$exception = $message;

			is_null($severity) and $severity = 'Error';
			$meta['class'] = get_class($exception);
			$meta['class_base'] = 'TypeError';
		}
		elseif ($message instanceof Error)
		{
			$exception = $message;

			is_null($severity) and $severity = 'Error';
			$meta['class'] = get_class($exception);
			$meta['class_base'] = 'Error';
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

		if (in_array($message, $_alertas_omitidas))
		{
			return;
		}

		if (is_null($trace))
		{
			$trace = debug_backtrace(false);
		}

		$trace = (array)$trace;
		$trace = array_values($trace);

		$trace = array_map(function($arr) use ($_directories) {
			if (isset($arr['file']))
			{
				foreach($_directories as $_directory => $label)
				{
					$arr['file'] = str_replace($_directory, $label, $arr['file']);
				}
			}

			return $arr;
		}, $trace);

		$trace_original = $trace;

		while(count($trace) > 0 and (
			( ! isset($trace[0]['file']))    or 
			(   isset($trace[0]['file'])     and str_replace(JAPIPATH, '', $trace[0]['file']) <> $trace[0]['file']) or 
			(   isset($trace[0]['function']) and in_array   ($trace[0]['function'], ['logger', '_handler_exception', '_handler_error', 'trigger_error']))
		))
		{
			array_shift($trace);
		}

		if (isset($trace[0]))
		{
			if (str_replace(JAPIPATH, '', $filepath) <> $filepath)
			{
				$line = $trace[0]['line'];
				$filepath = $trace[0]['file'];
			}

			isset($trace[0]['class'])    and ! isset($meta['class'])    and $meta['class']    = $trace[0]['class'];
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
			$meta['url'] = $url;
		}
		catch (\BasicException $e){}
		catch (\Exception      $e){}
		catch (\TypeError      $e){}
		catch (\Error          $e){}
		finally
		{
			$meta['URL_loadable'] = isset($url);
		}

		try
		{
			$ip_address = ip_address('array');
			$meta['ip_address'] = $ip_address;
		}
		catch (\BasicException $e){}
		catch (\Exception      $e){}
		catch (\TypeError      $e){}
		catch (\Error          $e){}
		finally
		{
			$meta['IPADRESS_loadable'] = isset($url);
		}

		$meta[cdkdsp] = isset($_COOKIE[cdkdsp])  ? $_COOKIE[cdkdsp]  : NULL; // Código de Dispositivo

		$trace_slim = $trace;
		$trace_slim = array_filter($trace_slim, function($arr){
			return isset($arr['file']) and isset($arr['line']);
		});
		$trace_slim = array_map(function($arr) use ($_directories) {
			return $arr['file'] . '#' . $arr['line'];
		}, $trace_slim);
		$meta['trace_slim'] = $trace_slim;
		$meta['trace_original'] = $trace_original;
		$meta['instant_buffer'] = ob_get_contents();

		$_codigo = md5(json_encode([
			$message,
			$severity,
			$code,
			$filepath,
			$line,
			$trace_slim
		]));

		$data = [
			'codigo'   => $_codigo,
			'message'  => $message,
			'severity' => $severity,
			'code'     => $code,
			'filepath' => $filepath,
			'line'     => $line,
			'trace'    => $trace,
			'meta'     => $meta,
		];
		APP() -> action_apply('SaveLogger', $data);
	}
}

if ( ! function_exists('_handler_error'))
{
	function _handler_error ($severity, $message, $filepath, $line)
	{
		static $error_reporting = E_ALL;
//		static $error_reporting = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED; // Recomendado para producción

		if (($severity & $error_reporting) !== $severity)
		{
			return;
		}

		$is_error = (((E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR | E_USER_ERROR) & $severity) === $severity);

		$is_error and
		http_code(500);

		logger($message, $severity, $severity, [], $filepath, $line);

		$is_error and 
		force_exit(1);
	}
}

if ( ! function_exists('_handler_exception'))
{
	function _handler_exception ($exception)
	{
		logger($exception);

		ISCOMMAND or
		http_code(500);

		force_exit(1);
	}
}
