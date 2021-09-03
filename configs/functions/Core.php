<?php
/**
 * Archivo de requerimientos
 * @filesource
 */

defined('HOMEPATH') or exit('Archivo no se puede llamar directamente');

defined('ISCOMMAND') or define('ISCOMMAND', false);

if ( ! function_exists('APP')) exit('Función `APP()` es requerida');

if ( ! class_exists('url_part'))
{
	class url_part implements JsonSerializable
	{
		public static function create (&$datos, $string_callback, $parts)
		{
			return new self($datos, $string_callback, $parts);
		}

		private $datos;
		private $string_callback;
		private $parts;

		protected function __construct(&$datos, $string_callback, $parts)
		{
			$this->datos           =& $datos;
			$this->string_callback =  $string_callback;
			$this->parts        =  $parts;
		}

		public function __toString()
		{
			return call_user_func($this->string_callback, $this->datos, $this);
		}

		public function __debugInfo()
		{
			$data = [];

			$data['result'] = (string)$this->__toString();
			foreach($this->parts as $part)
			{
				$data[$part] = (string)$this->datos[$part];
			}

			return $data;
		}

		public function jsonSerialize() 
		{
			return $this->__toString();
		}
	}
}

if ( ! function_exists('url'))
{
	/**
	 * url()
	 * Obtiene la estructura y datos importantes de la URL
	 *
	 * @param	string	$get
	 * @return	mixed
	 */
	function &url($get = 'base')
	{
		static $datos = [];

		if (count($datos) === 0)
		{
			$file = __FILE__;

			isset($_SERVER['REQUEST_SCHEME']) or $_SERVER['REQUEST_SCHEME'] = 'http' . ($datos['https'] ? 's' : '');
			isset($_SERVER['SERVER_PORT'])    or $_SERVER['SERVER_PORT']    = ISCOMMAND ? 8080 : 80;
			isset($_SERVER['REQUEST_URI'])    or $_SERVER['REQUEST_URI']    = '/';
			isset($_SERVER['HTTP_HOST'])      or $_SERVER['HTTP_HOST']      = (ISCOMMAND ? 'coman' : 'desconoci') .'.do';

			$_SERVER_HTTP_HOST = $_SERVER['HTTP_HOST'];

			//Archivo index que se ha leído originalmente
			$script_name = $_SERVER['SCRIPT_NAME'];

			//Este es la ruta desde el /public_html/{...}/APPPATH/index.php
			// y sirve para identificar si la aplicación se ejecuta en una subcarpeta
			// o desde la raiz, con ello podemos añadir esos subdirectorios {...} en el enlace
			$datos['srvpublic_path'] = '';
			$datos['srvpublic_path'] = APP()->filter_apply('JApi/url/srvpublic_path', $datos['srvpublic_path'], $_SERVER_HTTP_HOST);

			//Devuelve si usa https (boolean)
			$datos['https'] = FALSE;
			if (
				( ! empty($_SERVER['HTTPS'])                  and mb_strtolower($_SERVER['HTTPS']) !== 'off') ||
				(   isset($_SERVER['HTTP_X_FORWARDED_PROTO']) and mb_strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
				( ! empty($_SERVER['HTTP_FRONT_END_HTTPS'])   and mb_strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') ||
				(   isset($_SERVER['REQUEST_SCHEME'])         and $_SERVER['REQUEST_SCHEME'] === 'https')
			)
			{
				$datos['https'] = TRUE;
			}

			$_parsed = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER_HTTP_HOST . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
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
			if (ISCOMMAND)
			{
				global $argv;
				array_shift($argv); // Archivo SCRIPT
				if (count($argv) > 0)
				{
					$datos['path'] = '/' . array_shift($argv);
				}
			}

			empty($datos['srvpublic_path']) or $datos['path'] = str_replace($datos['srvpublic_path'], '', $datos['path']);

			if (mb_strlen($datos['path']) > 1)
			{
				$datos['path'] = rtrim($datos['path'], '/');
			}

			$datos['query'] = isset($_parsed['query']) ? $_parsed['query'] : '';
			$datos['fragment'] = isset($_parsed['fragment']) ? $_parsed['fragment'] : '';

			//Devuelve el port en formato enlace (string)		:8082	para el caso del port 80 o 443 retorna vacío
			$datos['port-link'] = url_part::create($datos, function($datos){
				$port_link = '';
				if ($datos['port'] <> 80 and $datos['port'] <> 443)
				{
					$port_link = ':' . $datos['port'];
				}
				return $port_link;
			}, [
				'port'
			]);

			//Devuelve si usa WWW (boolean)
			$datos['www'] = (bool)preg_match('/^www\./', $datos['host']);

			//Devuelve el base host (string)
			$datos['host-base'] = url_part::create($datos, function($datos){
				$host_base = explode('.', $datos['host']);

				while (count($host_base) > 2)
				{
					array_shift($host_base);
				}

				$host_base = implode('.', $host_base);
				return $host_base;
			}, [
				'host'
			]);

			//Devuelve el base host (string)
			$datos['host-parent'] = url_part::create($datos, function($datos){
				$host_parent = explode('.', $datos['host']);

				if ($datos['www'])
				{
					array_shift($host_parent);
				}

				if (count($host_parent) > 2)
				{
					array_shift($host_parent);
				}

				$host_parent = implode('.', $host_parent);
				return $host_parent;
			}, [
				'host',
				'www'
			]);

			//Devuelve el host mas el port (string)			intranet.net:8082
			$datos['host-link'] = url_part::create($datos, function($datos){
				$host_link = $datos['host'] . $datos['port-link'];
				return $host_link;
			}, [
				'host',
				'port-link'
			]);

			//Devuelve el host sin puntos o guiones	(string)	intranetnet
			$datos['host-clean'] = url_part::create($datos, function($datos){
				$host_clean = preg_replace('/[^a-z0-9]/i', '', $datos['host']);
				return $host_clean;
			}, [
				'host'
			]);

			//Devuelve el scheme mas el host-link (string)	https://intranet.net:8082
			$datos['host-uri'] = url_part::create($datos, function($datos){
				$host_uri = $datos['scheme'] . '://' . $datos['host-link'];
				return $host_uri;
			}, [
				'scheme',
				'host-link'
			]);

			//Devuelve la URL base hasta la aplicación
			$datos['base'] = url_part::create($datos, function($datos){
				$base = $datos['host-uri'] . $datos['srvpublic_path'];
				return $base;
			}, [
				'host-uri',
				'srvpublic_path'
			]);

			//Devuelve la URL base hasta el alojamiento real de la aplicación
			$datos['abs'] = url_part::create($datos, function($datos){
				$abs = $datos['host-uri'] . $datos['srvpublic_path'];
				return $abs;
			}, [
				'host-uri',
				'srvpublic_path'
			]);

			//Devuelve la URL base hasta el alojamiento real de la aplicación
			$datos['host-abs'] = url_part::create($datos, function($datos){
				$abs = str_replace('www.', '', $datos['host']) . $datos['srvpublic_path'];
				return $abs;
			}, [
				'host',
				'srvpublic_path'
			]);

			//Devuelve la URL completa incluido el PATH obtenido
			$datos['full'] = url_part::create($datos, function($datos){
				$full = $datos['base'] . $datos['path'];
				return $full;
			}, [
				'base',
				'path'
			]);

			//Devuelve la URL completa incluyendo los parametros QUERY si es que hay
			$datos['full-wq'] = url_part::create($datos, function($datos){
				$full_wq = $datos['full'] . ( ! empty($datos['query']) ? '?' : '' ) . $datos['query'];
				return $full_wq;
			}, [
				'full',
				'query'
			]);

			//Devuelve la ruta de la aplicación como directorio del cookie
			$datos['cookie-base'] = $datos['srvpublic_path'] . '/';

			//Devuelve la ruta de la aplicación como directorio del cookie hasta la carpeta de la ruta actual
			$datos['cookie-full'] = url_part::create($datos, function($datos){
				$cookie_full = $datos['srvpublic_path'] . rtrim($datos['path'], '/') . '/';
				return $cookie_full;
			}, [
				'srvpublic_path',
				'path'
			]);

			//Obtiene todos los datos enviados
			$datos['request'] =& request('array');

			//Request Method
			$datos['request_method'] = mb_strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'cli');

			$datos = APP()->filter_apply('JApi/url', $datos);
		}

		if ($get === 'array')
		{
			return $datos;
		}

		isset($datos[$get]) or $datos[$get] = NULL;
		return $datos[$get];
	}
}

if ( ! function_exists('request'))
{
	/**
	 * request()
	 * Obtiene los request ($_GET $_POST)
	 *
	 * @param	string	$get
	 * @return	mixed
	 */

	function &request($get = 'array', $default = NULL, $put_default_if_empty = TRUE)
	{
		static $datos = [];

		if (count($datos) === 0)
		{
			$PhpInput = (array)json_decode(file_get_contents('php://input'), true);
			$_POST = array_merge($_POST, [], $PhpInput, $_POST);

			$datos = array_merge(
				$_REQUEST,
				$_POST,
				$_GET
			);

			$path = explode('/', url('path'));
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
}

if ( ! function_exists('ip'))
{
	/**
	 * ip()
	 * Obtiene el IP del cliente
	 *
	 * @param string $get
	 * @return mixed
	 */
	function &ip ($get = 'ip')
	{
		$result = ip_address($get);
		return $result;
	}
}

if ( ! function_exists('ip_address'))
{
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

			$ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';

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

		if ($get === 'ip' or ! isset($datos[$get]))
		{
			$get = 'ip_address';
		}

		return $datos[$get];
	}
}
