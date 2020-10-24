<?php
/**
 * /JApi/configs/functions/JCore.php
 * funciones del núcleo JCore
 * @filesource
 */

defined('HOMEPATH') or exit('Archivo no se puede llamar directamente');

if ( ! function_exists('cbd'))
{
	function cbd ($host = 'localhost', $usuario = 'root', $password = NULL, $base_datos = NULL, $charset = 'utf8')
	{
		trigger_error('Función ' . __FUNCTION__ . ' es obsoleto, usar "sql_start"', E_USER_DEPRECATED);
		return sql_start($host, $usuario, $password, $base_datos, $charset);
	}
}

if ( ! function_exists('qp_esc'))
{
	function qp_esc ($valor = '', $or_null = FALSE, mysqli $conection = NULL, $f_as_f = FALSE)
	{
		trigger_error('Función ' . __FUNCTION__ . ' es obsoleto, usar "sql_qpesc"', E_USER_DEPRECATED);
		return sql_qpesc($valor, $or_null, $conection, $f_as_f);
	}
}

if ( ! function_exists('esc'))
{
	function esc ($valor = '', mysqli $conection = NULL)
	{
		trigger_error('Función ' . __FUNCTION__ . ' es obsoleto, usar "sql_esc"', E_USER_DEPRECATED);
		return sql_esc($valor, $conection);
	}
}

if ( ! function_exists('add_css'))
{
	function add_css ($codigo, $uri = NULL, $version = NULL, $prioridad = NULL, $attr = NULL, $position = NULL)
	{
		trigger_error('Función ' . __FUNCTION__ . ' es obsoleto, usar "load_css"', E_USER_DEPRECATED);
		return load_css($codigo, $uri, [
			'version' => $version,
			'orden' => $prioridad,
			'attr' => $attr,
			'position' => $position,
		]);
	}
}

if ( ! function_exists('add_inline_css'))
{
	function add_inline_css ($content, $prioridad = 80, $position = 'BODY')
	{
		trigger_error('Función ' . __FUNCTION__ . ' es obsoleto, usar "load_inline_css"', E_USER_DEPRECATED);
		return load_inline_css($content, $prioridad, mb_strtolower($position));
	}
}

if ( ! function_exists('add_js'))
{
	function add_js ($codigo, $uri = NULL, $version = NULL, $prioridad = NULL, $attr = NULL, $position = NULL)
	{
		trigger_error('Función ' . __FUNCTION__ . ' es obsoleto, usar "load_js"', E_USER_DEPRECATED);
		return load_js($codigo, $uri, [
			'version' => $version,
			'orden' => $prioridad,
			'attr' => $attr,
			'position' => $position,
		]);
	}
}

if ( ! function_exists('add_inline_js'))
{
	function add_inline_js ($content, $prioridad = 80, $position = 'BODY')
	{
		trigger_error('Función ' . __FUNCTION__ . ' es obsoleto, usar "load_inline_js"', E_USER_DEPRECATED);
		return load_inline_js($content, $prioridad, mb_strtolower($position));
	}
}

if ( ! function_exists('template'))
{
	function template ($file, $return_content = TRUE, $declared_variables = [])
	{
		trigger_error('Función ' . __FUNCTION__ . ' es obsoleto, usar "snippet"', E_USER_DEPRECATED);
		return snippet($file, $return_content, $declared_variables);
	}
}

if ( ! function_exists('set_status_header'))
{
	/**
	 * set_status_header()
	 * Establece la cabecera del status HTTP
	 *
	 * @param	int		$code	El codigo
	 * @param	string	$text	El texto del estado
	 * @return	void
	 */
	function set_status_header($code = 200, $text = '')
	{
		trigger_error('Función ' . __FUNCTION__ . ' es obsoleto, usar "http_code"', E_USER_DEPRECATED);
		return http_code($code, $text);
	}
}
