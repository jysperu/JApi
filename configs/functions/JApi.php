<?php
/**
 * /JApi/configs/functions/JApi.php
 * funciones simplificadas de la app por defecto
 * eg: APP()->action_add() equivale a action_add()
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
		if (count($parameters) === 0)
		{
			return call_user_func($_func);
		}
		return call_user_func_array($_func, $parameters);
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
		if (count($parameters) === 0)
		{
			return call_user_func($_func);
		}
		return call_user_func_array($_func, $parameters);
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
	function action_apply ($key, ...$params)
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
	function logger ($message, $code = NULL, $severity = NULL, $meta = NULL, $filepath = NULL, $line = NULL, $trace = NULL, $show = TRUE)
	{
		return exec_app(__FUNCTION__, $message, $code, $severity, $meta, $filepath, $line, $trace, $show);
	}
}

if ( ! function_exists('is_command'))
{
	/**
	 * is_command()
	 * identifica si la solicitud de procedimiento ha sido por comando
	 * @return Boolean False en caso de que la solicitud ha sido por web.
	 */
	function is_command ()
	{
		return exec_app(__FUNCTION__);
	}
}

if ( ! function_exists('is_cli'))
{
	/**
	 * is_cli()
	 */
	function is_cli ()
	{
		return exec_app(__FUNCTION__);
	}
}

if ( ! function_exists('http_code'))
{
	/**
	 * http_code()
	 * Establece la cabecera del status HTTP
	 *
	 * @param Integer $code El código
	 * @param String $text El texto del estado
	 * @return self
	 */
	function http_code($code = 200, $text = '')
	{
		return exec_app(__FUNCTION__, $code, $text);
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
	function mkdir2($folder, $base = NULL)
	{
		return exec_app(__FUNCTION__, $folder, $base);
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
			return exec_app(__FUNCTION__, $get);
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
			return exec_app(__FUNCTION__, $get, $default, $put_default_if_empty);
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
			return exec_app(__FUNCTION__, $get);
		}
}

if ( ! function_exists('is_empty'))
{
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
			return exec_app(__FUNCTION__, $v);
		}
}

if ( ! function_exists('def_empty'))
{
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
			return exec_app(__FUNCTION__, $v, $def);
		}
}

if ( ! function_exists('non_empty'))
{
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
			return exec_app(__FUNCTION__, $v, $callback);
		}
}

if ( ! function_exists('use_CON'))
{
		function use_CON ()
		{
			return exec_app(__FUNCTION__);
		}
}

if ( ! function_exists('get_CON'))
{
		function get_CON ()
		{
			return exec_app(__FUNCTION__);
		}
}

if ( ! function_exists('sql_start'))
{
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
			return exec_app(__FUNCTION__, $host, $usuario, $password, $base_datos, $charset);
		}
}

if ( ! function_exists('sql_stop'))
{
		/**
		 * sql_stop()
		 * Cierra una conección de base datos
		 *
		 * @param mysqli
		 * @return bool
		 */
		function sql_stop (mysqli $conection)
		{
			return exec_app(__FUNCTION__, $conection);
		}
}

if ( ! function_exists('sql_stop_all'))
{
		/**
		 * sql_stop()
		 * Cierra una conección de base datos
		 *
		 * @param mysqli
		 * @return bool
		 */
		function sql_stop_all ()
		{
			return exec_app(__FUNCTION__);
		}
}

if ( ! function_exists('sql_esc'))
{
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
			return exec_app(__FUNCTION__, $valor, $conection);
		}
}

if ( ! function_exists('sql_qpesc'))
{
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
			return exec_app(__FUNCTION__, $valor, $or_null, $conection, $f_as_f);
		}
}

if ( ! function_exists('sql'))
{
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
			return exec_app(__FUNCTION__, $query, $is_insert, $conection);
		}
}

if ( ! function_exists('sql_data'))
{
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
			return exec_app(__FUNCTION__, $query, $return_first, $fields, $conection);
		}
}

if ( ! function_exists('sql_pswd'))
{
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
			return exec_app(__FUNCTION__, $valor, $conection);
		}
}

if ( ! function_exists('sql_trans'))
{
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
			return exec_app(__FUNCTION__, $do, $conection);
		}
}
