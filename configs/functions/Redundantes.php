<?php
/**
 * Archivo de funciones redundantes
 * @filesource
 */

defined('HOMEPATH') or exit('Archivo no se puede llamar directamente');

if ( ! function_exists('exec_app'))
{
	/**
	 * exec_app()
	 *
	 * @param String $function Función a ejecutar
	 * @param Mixed $parameters
	 * @return Mixed
	 */
	function &exec_app ($function, &...$parameters)
	{
		$_func = [APP(), $function];
		$result = (count($parameters) === 0) ? call_user_func($_func) : call_user_func_array($_func, $parameters);
		return $result;
	}
}

if ( ! function_exists('exec_app_nkp'))
{
	/**
	 * exec_app_nkp()
	 *
	 * @param String $function Función a ejecutar
	 * @param Mixed $parameters
	 * @return Mixed
	 */
	function &exec_app_nkp ($function, &$parameters = [])
	{
		$_func = [APP(), $function];
		$result = (count($parameters) === 0) ? call_user_func($_func) : call_user_func_array($_func, $parameters);
		return $result;
	}
}

if ( ! function_exists('filter_add'))
{
	/**
	 * filter_add()
	 * Agrega funciones programadas para filtrar variables
	 *
	 * @param String $key Hook
	 * @param Callable $function Función a ejecutar
	 * @param Integer $priority Prioridad (Orden) a ejecutar la función cuando es llamado el Hook
	 * @return Boolean
	 */
	function filter_add ($key, $function, $priority = 50)
	{
		return exec_app(__FUNCTION__, $key, $function, $priority);
	}
}

if ( ! function_exists('non_filtered'))
{
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
	function non_filtered ($key, $function, $priority = 50)
	{
		return exec_app(__FUNCTION__, $key, $function, $priority);
	}
}

if ( ! function_exists('filter_apply'))
{
	/**
	 * filter_apply()
	 * Ejecuta funciones para validar o cambiar una variable
	 *
	 * @param String $key Hook
	 * @param Mixed	&...$params Parametros a enviar en las funciones del Hook (Referenced)
	 * @return Mixed $params[0] || NULL
	 */
	function filter_apply ($key, &...$params)
	{
		$parameters = [];
		$parameters[] =& $key;

		foreach($params as &$param)
		{
			$parameters[] =& $param;
		}

		return exec_app_nkp(__FUNCTION__, $parameters);
	}
}

if ( ! function_exists('action_add'))
{
	/**
	 * action_add()
	 * Agrega funciones programadas
	 *
	 * @param String $key Hook
	 * @param Callable $function Función a ejecutar
	 * @param Integer $priority Prioridad (orden) a ejecutar la función
	 * @return Boolean
	 */
	function action_add ($key, $function, $priority = 50)
	{
		return exec_app(__FUNCTION__, $key, $function, $priority);
	}
}

if ( ! function_exists('non_actioned'))
{
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
		return exec_app(__FUNCTION__, $key, $function, $priority);
	}
}

if ( ! function_exists('action_apply'))
{
	/**
	 * action_apply()
	 * Ejecuta las funciones programadas
	 *
	 * @param String $key Hook
	 * @param Mixed &...$params Parametros a enviar en las funciones del Hook (Referenced)
	 * @return Boolean || NULL
	 */
	function action_apply ($key, &...$params)
	{
		$parameters = [
			$key,
		];

		foreach($params as &$param)
		{
			$parameters[] =& $param;
		}

		return exec_app_nkp(__FUNCTION__, $parameters);
	}
}

if ( ! function_exists('config'))
{
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
	function &config ($get = NULL, Array $replace = [], bool $force = FALSE)
	{
		return exec_app(__FUNCTION__, $get, $replace, $force);
	}
}

if ( ! function_exists('map_app_directories'))
{
	/**
	 * map_app_directories ()
	 * Función que ejecuta una función establecida con todos los directorios de aplicación como parametro
	 *
	 * @param $callback Callable Función a ejecutar
	 * @param $reverse Boolean Indica si la función a ejecutar se hará a la lista invertida
	 * @return self
	 */
	function map_app_directories(callable $callback, $reverse = FALSE)
	{
		return exec_app(__FUNCTION__, $callback, $reverse);
	}
}

if ( ! function_exists('ResponseAs'))
{
	/**
	 * ResponseAs ()
	 */
	function ResponseAs($type, $mime = NULL, $charset = NULL)
	{
		return exec_app(__FUNCTION__, $type, $mime, $charset);
	}
}

if ( ! function_exists('process_result_message'))
{
	/**
	 * process_result_message ()
	 */
	function process_result_message($return_html = false, $clear = TRUE)
	{
		return exec_app(__FUNCTION__, $return_html, $clear);
	}
}

if ( ! function_exists('response_success'))
{
	/**
	 * response_success ()
	 */
	function response_success($message = NULL, $code = NULL)
	{
		return exec_app('success', $message, $code);
	}
}

if ( ! function_exists('response_error'))
{
	/**
	 * response_error ()
	 */
	function response_error($error = NULL, $code = NULL)
	{
		return exec_app('error', $error, $code);
	}
}

if ( ! function_exists('response_notice'))
{
	/**
	 * response_notice ()
	 */
	function response_notice($message = NULL, $code = NULL)
	{
		return exec_app('notice', $message, $code);
	}
}

if ( ! function_exists('exit_iftype'))
{
	/**
	 * exit_iftype ()
	 */
	function exit_iftype($types, $status = NULL)
	{
		return exec_app(__FUNCTION__, $types, $status);
	}
}

if ( ! function_exists('exit_ifhtml'))
{
	/**
	 * exit_ifhtml ()
	 */
	function exit_ifhtml($status = NULL)
	{
		return exec_app(__FUNCTION__, $status);
	}
}

if ( ! function_exists('exit_ifjson'))
{
	/**
	 * exit_ifjson ()
	 */
	function exit_ifjson($status = NULL)
	{
		return exec_app(__FUNCTION__, $status);
	}
}

if ( ! function_exists('redirect_iftype'))
{
	/**
	 * redirect_iftype ()
	 */
	function redirect_iftype($type, $link)
	{
		return exec_app(__FUNCTION__, $type, $link);
	}
}

if ( ! function_exists('redirect_ifhtml'))
{
	/**
	 * redirect_ifhtml ()
	 */
	function redirect_ifhtml($link)
	{
		return exec_app(__FUNCTION__, $link);
	}
}

if ( ! function_exists('redirect_ifjson'))
{
	/**
	 * redirect_ifjson ()
	 */
	function redirect_ifjson($link)
	{
		return exec_app(__FUNCTION__, $link);
	}
}

if ( ! function_exists('addJSON'))
{
	/**
	 * addJSON ()
	 */
	function addJSON($key, $val = null)
	{
		return exec_app(__FUNCTION__, $key, $val);
	}
}

if ( ! function_exists('addHTML'))
{
	/**
	 * addHTML ()
	 */
	function addHTML($content)
	{
		return exec_app(__FUNCTION__, $content);
	}
}

if ( ! function_exists('force_uri'))
{
	/**
	 * force_uri ()
	 */
	function force_uri($uri = null)
	{
		return exec_app(__FUNCTION__, $uri);
	}
}

if ( ! function_exists('register_css'))
{
	/**
	 * register_css ()
	 */
	function register_css($codigo, $uri = NULL, $arr = [])
	{
		return exec_app(__FUNCTION__, $codigo, $uri, $arr);
	}
}

if ( ! function_exists('load_css'))
{
	/**
	 * load_css ()
	 */
	function load_css($codigo, $uri = NULL, $arr = [])
	{
		return exec_app(__FUNCTION__, $codigo, $uri, $arr);
	}
}

if ( ! function_exists('load_inline_css'))
{
	/**
	 * load_inline_css ()
	 */
	function load_inline_css($content, $orden = 80, $position = 'body')
	{
		return exec_app(__FUNCTION__, $content, $orden, $position);
	}
}

if ( ! function_exists('register_js'))
{
	/**
	 * register_js ()
	 */
	function register_js($codigo, $uri = NULL, $arr = [])
	{
		return exec_app(__FUNCTION__, $codigo, $uri, $arr);
	}
}

if ( ! function_exists('load_js'))
{
	/**
	 * load_js ()
	 */
	function load_js($codigo, $uri = NULL, $arr = [])
	{
		return exec_app(__FUNCTION__, $codigo, $uri, $arr);
	}
}

if ( ! function_exists('load_inline_js'))
{
	/**
	 * load_inline_js ()
	 */
	function load_inline_js($content, $orden = 80, $position = 'body')
	{
		return exec_app(__FUNCTION__, $content, $orden, $position);
	}
}

if ( ! function_exists('localize_js'))
{
	/**
	 * localize_js ()
	 */
	function localize_js($codigo, $content, $when = 'after')
	{
		return exec_app(__FUNCTION__, $codigo, $content, $when);
	}
}

if ( ! function_exists('snippet'))
{
	/**
	 * snippet ()
	 */
	function snippet($file, $return_content = TRUE, $declared_variables = [])
	{
		return exec_app(__FUNCTION__, $file, $return_content, $declared_variables);
	}
}

if ( ! function_exists('obj'))
{
	/**
	 * obj ()
	 */
	function obj($class, ...$pk)
	{
		$parameters = [
			$class,
		];

		foreach($pk as &$param)
		{
			$parameters[] =& $param;
		}

		return exec_app_nkp(__FUNCTION__, $parameters);
	}
}

if ( ! function_exists('response_cache'))
{
	/**
	 * response_cache ()
	 */
	function response_cache($days = 365, $for = 'private', $rev = 'no-revalidate')
	{
		return exec_app(__FUNCTION__, $days, $for, $rev);
	}
}

if ( ! function_exists('response_nocache'))
{
	/**
	 * response_nocache ()
	 */
	function response_nocache()
	{
		return exec_app(__FUNCTION__);
	}
}

if ( ! function_exists('translate'))
{
	/**
	 * translate ()
	 */
	function translate($frase, $n = NULL, $lang = NULL, ...$sprintf)
	{
		$parameters = [
			$frase,
			$n,
			$lang
		];

		foreach($sprintf as &$param)
		{
			$parameters[] =& $param;
		}

		return exec_app_nkp(__FUNCTION__, $parameters);
	}
}

if ( ! function_exists('_t'))
{
	/**
	 * _t ()
	 */
	function _t($frase, $n = NULL, ...$sprintf)
	{
		$parameters = [
			$frase,
			$n
		];

		foreach($sprintf as &$param)
		{
			$parameters[] =& $param;
		}

		return exec_app_nkp('translate', $parameters);
	}
}
