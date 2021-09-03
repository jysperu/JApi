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
