<?php
/**
 * /JApi/configs/functions/JCore.php
 * funciones obsoletas del núcleo JCore
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
