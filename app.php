<?php
/**
 * /JApi/app.php
 * @filesource
 */

defined('HOMEPATH') or exit('Archivo no se puede llamar directamente');

if (function_exists('APP'))
{
	return APP(); // previene no ser leido doble vez
}

if ( ! class_exists('JApi'))
{
	require_once __DIR__ . '/JApi.class.php';
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