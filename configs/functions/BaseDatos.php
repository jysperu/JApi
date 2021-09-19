<?php
/**
 * /JApi/configs/functions/Helpers.php
 * Funciones de apoyo
 * @filesource
 */

defined('HOMEPATH') or exit('Archivo no se puede llamar directamente');
defined('sql_pswd_blowfish') or define('sql_pswd_blowfish', 'JApi@2021');

if ( ! function_exists('APP')) exit('Función `APP()` es requerida');

/**
 * $_CONs
 * Variable que almacena todas las dbs conectadas
 */
$_CONs = [];

/**
 * $CON
 * Variable que almacena la db primaria o por defecto
 */
$CON = null;

/**
 * $MYSQL_history
 * QUERY ejecutados y errores producidos
 * **Estructura:**
 * - QUERY // Query Ejecutado
 * - Error // Texto de error detectado
 * - Errno // Número de error detectado
 */
$_MYSQL_history = [];

if ( ! function_exists('use_CON'))
{
	/**
	 * use_CON()
	 * Inicializa la conección primaria
	 *
	 * @return self
	 */
	function use_CON ($identify = null, $ordefault = true)
	{
		global $_CONs;

		empty($identify) and $identify = '@'; // Principal

		if ( ! isset($_CONs[$identify]))
		{
			$key = $identify === '@' ? 'db' : ('db_' . $identify);
			$db =& APP() -> config($key);
			$db = (array)$db;

			isset($db['host']) or $db['host'] = 'localhost';
			isset($db['user']) or $db['user'] = 'root';
			isset($db['pasw']) or $db['pasw'] = NULL;
			isset($db['name']) or $db['name'] = 'test';
			isset($db['charset']) or $db['charset'] = 'utf8';

			$_CONs[$identify] = sql_start($db['host'], $db['user'], $db['pasw'], $db['name'], $db['charset']);
			isset($_CONs[$identify]) and $_CONs[$identify]->identify = $identify;
		}

		if ( ! isset($_CONs[$identify]) and $identify !== '@' and $ordefault)
		{
			return use_CON(null, false);
		}

		if ( ! isset($_CONs[$identify]))
		{
			throw new Exception('No se pudo conectar la base datos `'. $identify .'`' . PHP_EOL . 'sql_start("' . $db['host'].'","'. $db['user'].'","'. (empty($db['pasw']) ? 'Sin' : 'Con') .' clave","'. $db['name'].'","'. $db['charset'] . '")');
		}

		return $_CONs[$identify];
	}
}

if ( ! function_exists('with_CON'))
{
	function with_CON ($identify = null, $ordefault = true)
	{
		return use_CON($identify, $ordefault);
	}
}

if ( ! function_exists('get_CON'))
{
	function get_CON ($identify = null, $ordefault = true)
	{
		return use_CON($identify, $ordefault);
	}
}

if ( ! function_exists('sql_start'))
{
	/**
	 * sql_start()
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
		global $CON;

		$conection = mysqli_connect($host, $usuario, $password);
		if ( ! $conection) return NULL;

		$conection->_host = $host;
		$conection->_usuario = $usuario;
		$conection->_password = $password;

		if ( ! empty($base_datos) and ! mysqli_select_db($conection, $base_datos)) return NULL;
		$conection->_base_datos = $base_datos;

		mysqli_set_charset($conection, $charset);
		$conection->_charset = $charset;

		$utc = APP() -> get_utc();
		mysqli_query($conection, 'SET time_zone = "' . $utc . '";');
		$conection->_utc = $utc;

		isset($CON) or
		$CON = $conection; // Primera coneccion

		return $conection;
	}
}

if ( ! function_exists('cbd'))
{
	function cbd ($host = 'localhost', $usuario = 'root', $password = NULL, $base_datos = NULL, $charset = 'utf8')
	{
		return sql_start($host, $usuario, $password, $base_datos, $charset);
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
		global $_CONs, $CON;

		$identify = $conection -> identify;
		$tid = $conection -> thread_id;
		$ati = (isset($CON) and isset($CON->thread_id)) ? $CON->thread_id : 0;

		$return = mysqli_close($conection);

		unset($_CONs[$identify]);
		if ($ati == $tid)
		{
			$CON = null;
			if (count($_CONs) > 0)
			{
				$CON = array_keys($_CONs);
				$CON = array_shift($CON);
				$CON = $_CONs[$CON];
			}
		}
		return $return;
	}
}

if ( ! function_exists('_sql_update_utc_all'))
{
	/**
	 * _sql_update_utc_all()
	 *
	 * @param mysqli
	 * @return bool
	 */
	function _sql_update_utc_all ($utc)
	{
		global $_CONs;
		$_CONs = (array)$_CONs;
		foreach($_CONs as $conection)
		{
			mysqli_query($conection, 'SET time_zone = "' . $utc . '";');
		}
		return true;
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
		global $_CONs;
		$_CONs = (array)$_CONs;
		foreach($_CONs as $conection)
		{
			if ( ! is_a($conection, 'mysqli')) continue;
			sql_stop($conection);
		}
		return true;
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
		is_a($conection, 'mysqli') or $conection = use_CON($conection);
		return mysqli_real_escape_string($conection, $valor);
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
		static $_functions_alws = [
			'CURDATE', 'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURTIME', 'LOCALTIME', 'LOCALTIMESTAMP', 'NOW', 'SYSDATE'
		];
		static $_functions = [
			'ASCII', 'CHAR_LENGTH', 'CHARACTER_LENGTH', 'CONCAT', 'CONCAT_WS', 'FIELD', 'FIND_IN_SET', 'FORMAT', 'INSERT', 'INSTR', 'LCASE', 'LEFT', 'LENGTH', 'LOCATE', 'LOWER', 'LPAD', 'LTRIM', 'MID', 'POSITION', 'REPEAT', 'REPLACE', 'REVERSE', 'RIGHT', 'RPAD', 'RTRIM', 'SPACE', 'STRCMP', 'SUBSTR', 'SUBSTRING', 'SUBSTRING_INDEX', 'TRIM', 'UCASE', 'UPPER', 'ABS', 'ACOS', 'ASIN', 'ATAN', 'ATAN2', 'AVG', 'CEIL', 'CEILING', 'COS', 'COT', 'COUNT', 'DEGREES', 'DIV', 'EXP', 'FLOOR', 'GREATEST', 'LEAST', 'LN', 'LOG', 'LOG10', 'LOG2', 'MAX', 'MIN', 'MOD', 'PI', 'POW', 'POWER', 'RADIANS', 'RAND', 'ROUND', 'SIGN', 'SIN', 'SQRT', 'SUM', 'TAN', 'TRUNCATE', 'ADDDATE', 'ADDTIME', 'CURDATE', 'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURTIME', 'DATE', 'DATEDIFF', 'DATE_ADD', 'DATE_FORMAT', 'DATE_SUB', 'DAY', 'DAYNAME', 'DAYOFMONTH', 'DAYOFWEEK', 'DAYOFYEAR', 'EXTRACT', 'FROM_DAYS', 'HOUR', 'LAST_DAY', 'LOCALTIME', 'LOCALTIMESTAMP', 'MAKEDATE', 'MAKETIME', 'MICROSECOND', 'MINUTE', 'MONTH', 'MONTHNAME', 'NOW', 'PERIOD_ADD', 'PERIOD_DIFF', 'QUARTER', 'SECOND', 'SEC_TO_TIME', 'STR_TO_DATE', 'SUBDATE', 'SUBTIME', 'SYSDATE', 'TIME', 'TIME_FORMAT', 'TIME_TO_SEC', 'TIMEDIFF', 'TIMESTAMP', 'TO_DAYS', 'WEEK', 'WEEKDAY', 'WEEKOFYEAR', 'YEAR', 'YEARWEEK', 'BIN', 'BINARY', 'CASE', 'CAST', 'COALESCE', 'CONNECTION_ID', 'CONV', 'CONVERT', 'CURRENT_USER', 'DATABASE', 'IF', 'IFNULL', 'ISNULL', 'LAST_INSERT_ID', 'NULLIF', 'SESSION_USER', 'SYSTEM_USER', 'USER', 'VERSION'
		];

		if ($or_null !== FALSE and is_empty($valor))
		{
			$or_null = ($or_null === TRUE ? 'NULL' : $or_null);
			return $or_null;
		}

		$_regex_funcs_alws = '/^(' . implode('|', $_functions_alws) . ')(\(\))?$/i';
		$_regex_funcs = '/\b('.implode('|', $_functions).')\b/i';

		if (is_string($valor) and preg_match($_regex_funcs_alws, $valor))  ## Palabras Reservadas No Peligrosas
		{
			return $valor;
		}
		elseif (is_string($valor) and preg_match($_regex_funcs, $valor) and $f_as_f)  ## Palabras Reservadas
		{
			if (is_string($valor) and preg_match('/^\[MF\]\:/i', $valor))
			{
				$valor = preg_replace('/^\[MF\]\:/i', '', $valor);
			}
			else
			{
				return $valor;
			}
		}
		else
		{
			if (is_string($valor) and preg_match('/^\[MF\]\:/i', $valor))
			{
				$valor = preg_replace('/^\[MF\]\:/i', '', $valor);
			}
		}

		if (is_bool($valor))
		{
			return $valor ? 'TRUE' : 'FALSE';
		}

		if (is_numeric($valor) and ! preg_match('/^0/i', (string)$valor))
		{
			return sql_esc($valor, $conection);
		}

		is_array($valor) and $valor = json_encode($valor);

		return '"' . sql_esc($valor, $conection) . '"';
	}
}

if ( ! function_exists('qp_esc'))
{
	function qp_esc ($valor = '', $or_null = FALSE, mysqli $conection = NULL, $f_as_f = FALSE)
	{
		return sql_qpesc($valor, $or_null, $conection, $f_as_f);
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
	function sql(string $query, $is_insert = FALSE, mysqli $conection = NULL, $modulo = null)
	{
		global $_MYSQL_history;

		$trace = debug_backtrace(false);
		while(count($trace) > 0 and (
			( ! isset($trace[0]['file']))    or 
			(   isset($trace[0]['file'])     and str_replace(JAPIPATH, '', $trace[0]['file']) <> $trace[0]['file']) or 
			(   isset($trace[0]['function']) and preg_match ('/^sql/i', $trace[0]['function']))
		))
		{
			array_shift($trace);
		}

		$trace = array_shift($trace);
		is_null($trace) or $trace = $trace['file'] . '#' . $trace['line'];

		is_a($is_insert, 'mysqli') and
		$conection = $is_insert and
		$is_insert = false;

		is_a($conection, 'mysqli') or $conection = use_CON($conection);

		$_consulta_inicio = microtime(true);
		$result =  mysqli_query($conection, $query);
		$_consulta_fin = microtime(true);

		if ( ! $result)
		{
			$_ERRNO = mysqli_errno($conection);
			$_ERROR = mysqli_error($conection);

			$_stat = [
				'query' => $query,
				'suphp' => 'mysqli_query($conection, $query)',
				'error' => $_ERROR, 
				'errno' => $_ERRNO,
				'hstpr' => 'error',
				'start' => $_consulta_inicio,
				'endin' => $_consulta_fin,
				'conct' => $conection->identify,
				'funct' => 'sql',
				'filen' => $trace,
				'modul' => $modulo,
			];
			$_MYSQL_history[] = $_stat;
			APP() -> action_apply('SQL/Stat', $_stat, $conection);

			trigger_error('Error en el query: ' . PHP_EOL . $query . PHP_EOL . $_ERRNO . ': ' . $_ERROR, E_USER_WARNING);
			return FALSE;
		}

		$return = true;

		$is_insert and
		$return = mysqli_insert_id($conection);

		$_stat = [
			'query' => $query,
			'suphp' => 'mysqli_query($conection, $query)',
			'error' => '', 
			'errno' => '',
			'hstpr' => 'success',
			'start' => $_consulta_inicio,
			'endin' => $_consulta_fin,
			'conct' => $conection->identify,
			'funct' => 'sql',
			'afrow' => $conection->affected_rows,
			($is_insert ? 'insert_id' : 'return') => $return,
			'filen' => $trace,
			'modul' => $modulo,
		];
		$_MYSQL_history[] = $_stat;

		APP() -> action_apply('SQL/Stat', $_stat, $conection);
		return $return;
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

	function sql_data(string $query, $return_first = FALSE, $fields = NULL, mysqli $conection = NULL, $modulo = null)
	{
		global $_MYSQL_history;
		static $_executeds = [];

		$trace = debug_backtrace(false);
		while(count($trace) > 0 and (
			( ! isset($trace[0]['file']))    or 
			(   isset($trace[0]['file'])     and str_replace(JAPIPATH, '', $trace[0]['file']) <> $trace[0]['file']) or 
			(   isset($trace[0]['function']) and preg_match ('/^sql/i', $trace[0]['function']))
		))
		{
			array_shift($trace);
		}

		$trace = array_shift($trace);
		is_null($trace) or $trace = $trace['file'] . '#' . $trace['line'];

		is_a($return_first, 'mysqli') and
		$conection = $return_first and
		$return_first = false;

		is_a($fields, 'mysqli') and
		$conection = $fields and
		$fields = null;

		is_a($conection, 'mysqli') or $conection = use_CON($conection);

		isset($_executeds[$conection->identify]) or $_executeds[$conection->identify] = 0;
		$_executeds[$conection->identify]++;

		$_executeds[$conection->identify] > 1 and
		@mysqli_next_result($conection);

		$_consulta_inicio = microtime(true);
		$result =  mysqli_query($conection, $query);
		$_consulta_fin = microtime(true);

		if ( ! $result)
		{
			$_ERRNO = mysqli_errno($conection);
			$_ERROR = mysqli_error($conection);

			$_stat = [
				'query' => $query,
				'suphp' => 'mysqli_query($conection, $query)',
				'error' => $_ERROR, 
				'errno' => $_ERRNO,
				'hstpr' => 'error',
				'start' => $_consulta_inicio,
				'endin' => $_consulta_fin,
				'conct' => $conection->identify,
				'funct' => 'sql_data',
				'filen' => $trace,
				'modul' => $modulo,
			];
			$_MYSQL_history[] = $_stat;
			APP() -> action_apply('SQL/Stat', $_stat, $conection);
			trigger_error('Error en el query: ' . PHP_EOL . $query . PHP_EOL . $_ERRNO . ': ' . $_ERROR, E_USER_WARNING);

			$sql_data_result = MysqlResultData::fromArray([])
			-> quitar_fields('log');
		}
		else
		{
			$_stat = [
				'query' => $query,
				'suphp' => 'mysqli_query($conection, $query)',
				'error' => '', 
				'errno' => '',
				'hstpr' => 'success',
				'start' => $_consulta_inicio,
				'endin' => $_consulta_fin,
				'conct' => $conection->identify,
				'funct' => 'sql_data',
				'total' => $result->num_rows,
				'filen' => $trace,
				'modul' => $modulo,
			];
			$_MYSQL_history[] = $_stat;
			APP() -> action_apply('SQL/Stat', $_stat, $conection);

			$sql_data_result = new MysqlResultData ($result);
		}

		if ( ! is_null($fields))
		{
			$sql_data_result
			-> filter_fields($fields);
		}

		if ($return_first)
		{
			return $sql_data_result
			-> first();
		}

		return $sql_data_result;
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
		if (function_exists('encrypt'))
		{
			return encrypt ($valor, sql_pswd_blowfish);
		}

		return sql_data('
		SELECT PASSWORD(' . sql_qpesc($valor, FALSE, $conection) . ') as `valor`;
		', TRUE, 'valor', $conection);
	}
}

if ( ! function_exists('sql_maxgroupconcat'))
{
	/**
	 * sql_maxgroupconcat()
	 *
	 * @param mysqli
	 * @return bool
	 */
	function sql_maxgroupconcat (mysqli $conection = NULL)
	{
		return sql('
		SET SESSION group_concat_max_len = 1000000;
		', false, $conection);
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
		static $_trans = []; ## levels de transacciones abiertas
		static $_auto_commit_setted = [];

		is_a($do, 'mysqli') and
		$conection = $do and
		$do = null;

		is_a($conection, 'mysqli') or $conection = use_CON($conection);

		isset($_trans[$conection->identify]) or $_trans[$conection->identify] = 0;

		if ($do === 'NUMTRANS')
		{
			return $_trans[$conection->identify];
		}

		isset($_auto_commit_setted[$conection->identify]) or $_auto_commit_setted[$conection->identify] = FALSE;

		if (is_null($do))
		{
			## Se está iniciando una transacción

			## Solo si el level es 0 (aún no se ha abierto una transacción), se ejecuta el sql
			$_trans[$conection->identify] === 0 and mysqli_begin_transaction($conection);
			$_trans[$conection->identify]++; ## Incrmentar el level

			if ( ! $_auto_commit_setted[$conection->identify])
			{
				mysqli_autocommit($conection, false) AND $_auto_commit_setted[$conection->identify] = TRUE;
			}

			return TRUE;
		}

		if ($_trans[$conection->identify] === 0)
		{
			return FALSE; ## No se ha abierto una transacción
		}

		if ( ! is_bool($do))
		{
			trigger_error('Se está enviando un parametro ' . gettype($do) . ' en vez de un BOOLEAN', E_USER_WARNING);
			$do = (bool)$do;
		}

		if ($do)
		{
			$_trans[$conection->identify]--; ## Reducir el level

			## Solo si el level es 0 (ya se han cerrado todas las conecciones), se ejecuta el sql
			if ($_trans[$conection->identify] === 0)
			{
				mysqli_commit($conection);

				if ($_auto_commit_setted[$conection->identify])
				{
					mysqli_autocommit($conection, true) AND $_auto_commit_setted[$conection->identify] = FALSE;
				}
			}
		}
		else
		{
			$_trans[$conection->identify] = 0; ## Finalizar todas los levels abiertos

			mysqli_rollback($conection);

			if ($_auto_commit_setted[$conection->identify])
			{
				mysqli_autocommit($conection, true) AND $_auto_commit_setted[$conection->identify] = FALSE;
			}
		}

		return TRUE;
	}
}

/** Validaciones de Base Datos */
if ( ! function_exists('sql_e_global'))
{
	function sql_e_global (string $buscado, string $_is_key, mysqli $conection = null)
	{
		static $_data = [], $_consultados = [];
		is_a($conection, 'mysqli') or $conection = use_CON($conection);

		isset($_data[$_is_key]) or $_data[$_is_key] = [];
		isset($_consultados[$_is_key]) or $_consultados[$_is_key] = [];
		isset($_consultados[$_is_key][$conection->_base_datos]) or $_consultados[$_is_key][$conection->_base_datos] = [];

		$data =& $_data[$_is_key];
		$consultados =& $_consultados[$_is_key][$conection->_base_datos];

		if (in_array($buscado, $_consultados)) 
		{
			unset($data[$conection->_base_datos]);
			$_consultados = [];
		}
		$_consultados[] = $buscado;

		list($_is_CAMPO, $_is_TABLA, $_is_CAMPO_SHEMA, $_is_WHERE) = explode('|', $_is_key . '|||||', 5);

		isset($data[$conection->_base_datos]) or 
		$data[$conection->_base_datos] = (array) sql_data('
		SELECT ' . $_is_CAMPO . '
		FROM   information_schema.'. $_is_TABLA . '
		WHERE  ' . $_is_CAMPO_SHEMA . ' = ' . sql_qpesc($conection->_base_datos) . $_is_WHERE . '
		', false, $_is_CAMPO, $conection);

		return in_array($buscado, $data[$conection->_base_datos]);
	}
}

if ( ! function_exists('sql_e_tabla'))
{
	function sql_e_tabla (string $tabla, string $buscado, string $_is_key, mysqli $conection = null)
	{
		static $_data = [], $_consultados = [];
		is_a($conection, 'mysqli') or $conection = use_CON($conection);

		isset($_data[$_is_key]) or $_data[$_is_key] = [];
		isset($_consultados[$_is_key]) or $_consultados[$_is_key] = [];

		isset($_data[$_is_key][$conection->_base_datos]) or $_data[$_is_key][$conection->_base_datos] = [];
		isset($_consultados[$_is_key][$conection->_base_datos]) or $_consultados[$_is_key][$conection->_base_datos] = [];
		isset($_consultados[$_is_key][$conection->_base_datos][$tabla]) or $_consultados[$_is_key][$conection->_base_datos][$tabla] = [];

		$data =& $_data[$_is_key][$conection->_base_datos];
		$consultados =& $_consultados[$_is_key][$conection->_base_datos][$tabla];

		if (in_array($buscado, $_consultados)) 
		{
			unset($data[$tabla]);
			$_consultados = [];
		}
		$_consultados[] = $buscado;

		list($_is_CAMPO, $_is_TABLA, $_is_WHERE) = explode('|', $_is_key . '|||||', 5);

		isset($data[$tabla]) or 
		$data[$tabla] = (array) sql_data('
		SELECT ' . $_is_CAMPO . '
		FROM   information_schema.'. $_is_TABLA . '
		WHERE  TABLE_SCHEMA = ' . sql_qpesc($conection->_base_datos) . ' AND 
			   TABLE_NAME   = ' . sql_qpesc($tabla) . $_is_WHERE . '
		', false, $_is_CAMPO, $conection);

		return in_array($buscado, $data[$tabla]);
	}
}

if ( ! function_exists('sql_et'))
{
	/**
	 * sql_et()
	 * Valida la existencia de una tabla en la db
	 *
	 * @param string
	 * @param mysqli
	 * @return bool
	 */
	function sql_et(string $tabla, mysqli $conection = NULL)
	{
		return sql_e_global($tabla, 'TABLE_NAME|TABLES|TABLE_SCHEMA', $conection);
	}
}

if ( ! function_exists('sql_etc'))
{
	/**
	 * sql_etc()
	 * Valida la existencia de un campo dentro de una tabla de la db
	 *
	 * @param string
	 * @param string
	 * @param mysqli
	 * @return bool
	 */
	function sql_etc(string $campo, string $tabla, mysqli $conection = NULL)
	{
		return sql_e_tabla($tabla, $campo, 'COLUMN_NAME|COLUMNS', $conection);
	}
}

if ( ! function_exists('sql_ev'))
{
	/**
	 * sql_ev()
	 * Valida la existencia de una vista en la db
	 *
	 * @param string
	 * @param mysqli
	 * @return bool
	 */
	function sql_ev(string $tabla, mysqli $conection = NULL)
	{
		return sql_e_global($tabla, 'TABLE_NAME|TABLES|TABLE_SCHEMA', $conection);
	}
}

if ( ! function_exists('sql_efk'))
{
	/**
	 * sql_efk()
	 * Valida la existencia de una relación foránea
	 *
	 * @param string
	 * @param mysqli
	 * @return bool
	 */
	function sql_efk(string $constraint, mysqli $conection = NULL)
	{
		return sql_e_global($constraint, 'CONSTRAINT_NAME|TABLE_CONSTRAINTS|CONSTRAINT_SCHEMA| AND CONSTRAINT_TYPE = "FOREIGN KEY"', $conection);
	}
}

if ( ! function_exists('sql_euk'))
{
	/**
	 * sql_euk()
	 * Valida la existencia de una constante única dentro de una tabla
	 *
	 * @param string
	 * @param string
	 * @param mysqli
	 * @return bool
	 */
	function sql_euk(string $constraint, string $tabla, mysqli $conection = NULL)
	{
		return sql_e_tabla($tabla, $constraint, 'CONSTRAINT_NAME|TABLE_CONSTRAINTS| AND CONSTRAINT_TYPE = "UNIQUE"', $conection);
	}
}

if ( ! function_exists('sql_eix'))
{
	/**
	 * sql_eix()
	 * Valida la existencia de un indice
	 *
	 * @param string
	 * @param string
	 * @param mysqli
	 * @return bool
	 */
	function sql_eix(string $constraint, string $tabla, mysqli $conection = NULL)
	{
		return sql_e_tabla($tabla, $constraint, 'INDEX_NAME|STATISTICS', $conection);
	}
}

if ( ! function_exists('sql_ee'))
{
	/**
	 * sql_ee()
	 * Valida la existencia de una evento en la db
	 *
	 * @param string
	 * @param mysqli
	 * @return bool
	 */
	function sql_ee(string $evento, mysqli $conection = NULL)
	{
		return sql_e_global($evento, 'EVENT_NAME|EVENTS|EVENT_SCHEMA', $conection);
	}
}

if ( ! function_exists('sql_ef'))
{
	/**
	 * sql_ef()
	 * Valida la existencia de una función en la db
	 *
	 * @param string
	 * @param mysqli
	 * @return bool
	 */
	function sql_ef(string $funcion, mysqli $conection = NULL)
	{
		return sql_e_global($funcion, 'ROUTINE_NAME|ROUTINES|ROUTINE_SCHEMA| AND ROUTINE_TYPE = "FUNCTION"', $conection);
	}
}

if ( ! function_exists('sql_ep'))
{
	/**
	 * sql_ep()
	 * Valida la existencia de un procedimiento en la db
	 *
	 * @param string
	 * @param mysqli
	 * @return bool
	 */
	function sql_ep(string $proceso, mysqli $conection = NULL)

	{
		return sql_e_global($proceso, 'ROUTINE_NAME|ROUTINES|ROUTINE_SCHEMA| AND ROUTINE_TYPE = "PROCEDURE"', $conection);
	}
}

if ( ! function_exists('sql_ed'))
{
	/**
	 * sql_ed()
	 * Valida la existencia de una disparador (trigger)
	 *
	 * @param string
	 * @param mysqli
	 * @return bool
	 */
	function sql_ed(string $disparador, mysqli $conection = NULL)
	{
		return sql_e_global($disparador, 'TRIGGER_NAME|TRIGGERS|TRIGGER_SCHEMA', $conection);
	}
}

APP() -> action_add('shutdown', 'sql_stop_all');
APP() -> action_add('JApi/utc', '_sql_update_utc_all');
