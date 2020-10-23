<?php
/**
 * /JApi/configs/config.php
 * @filesource
 */

defined('HOMEPATH') or exit('Archivo no se puede llamar directamente');

/**
 * charset
 * Charset por Defecto
 *
 * @global
 */
$config['charset'] = 'UTF-8';

/**
 * timezone
 * TimeZone por Defecto
 *
 * @global
 */
$config['timezone'] = 'America/Lima';

/**
 * lang
 * Lenguaje por Defecto
 * WARNING: Si es NULO se detectará el lenguaje del usuario
 *
 * @global
 */
$config['lang'] = NULL;

/**
 * db - bd
 * Datos de la primera conección de Base Datos
 *
 * HOST:	Host del servidor mysql
 * USER:	Usuario para conectar en el servidor
 * PASW:	Clave de la conección. (Si es NULO entonces el usuario no requiere de clave)
 * NAME:	Nombre de la base datos autorizado
 *
 * @global
 */
$config['db'] = [];
$config['bd'] =& $config['db'];

//$config['db']['host'] = 'localhost';
//$config['db']['user'] = 'root';
//$config['db']['pasw'] = 'mysql';
//$config['db']['name'] = 'intranet';

/**
 * db_logs
 * Datos de la conección para alojar los errores
 *
 * @global
 */
$config['db_logs'] = [];

/**
 * www
 * WWW por Defecto
 *
 * Si el valor es NULO entonces no redireccionará en caso de no corresponder el WWW
 * El valor debe ser boleano y si no corresponde con url('www') redireccionará al que corresponda
 *
 * @global
 */
$config['www'] = NULL;

/**
 * https
 * HTTPS por Defecto
 *
 * Si el valor es NULO entonces no redireccionará en caso de no corresponder el HTTPS
 * El valor debe ser boleano y si no corresponde con url('https') redireccionará al que corresponda
 *
 * @global
 */
$config['https'] = NULL;
