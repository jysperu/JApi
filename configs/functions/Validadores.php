<?php
/**
 * /JApi/configs/functions/Validadores.php
 * Funciones de apoyo
 * @filesource
 */

defined('HOMEPATH') or exit('Archivo no se puede llamar directamente');

defined('MAIL_DISPOSABLES_JSON') or define('MAIL_DISPOSABLES_JSON', __DIR__ . '/disposables.json');

if ( ! function_exists('APP')) exit('Función `APP()` es requerida');

use Symfony\Component\HttpClient\HttplugClient as Client;

//---------------------------------------------
// Otros
//---------------------------------------------
if ( ! function_exists('is_mail'))
{
	/**
	 * is_mail()
	 * Valida que el valor sea un correo válido
	 *
	 * @param mixed
	 * @return mixed
	 */
	function is_mail($v)
	{
		$v = trim($v);
		if (is_empty($v))
		{
			return FALSE;
		}

		$def_valid = (bool)filter_var($v, FILTER_VALIDATE_EMAIL);
		if ( ! $def_valid)
		{
			return FALSE;
		}

		//A little bit more validations
		$at_pos = strrpos($v, '@');
		$dot_pos = strrpos($v, '.');
		if ($at_pos < 1 || $dot_pos < $at_pos + 2 || $dot_pos + 2 >= mb_strlen($v))
		{
			// No hay un arroba o este esta al comenzar
			// El punto esta antes del arroba
			// El arroba y el punto estan juntos
			// El punto solo tiene un caracter despues
			return FALSE;
		}

		$name = mb_substr($v, 0, $at_pos);
		// Really Valid Chars
		// @link	https://www.jochentopf.com/email/chars.html
		// OK: 		a-zA-Z0-9+-._
		// MAYBE: 	&'*/=?^{}~
		if ( ! preg_match('/^[a-zA-Z0-9\+\-\.\_\&\'\*\/\=\?\^\{\}\~]*$/', $name))
		{
			return FALSE;
		}

		$domain = mb_substr($v, $at_pos + 1);

		static $temporary_mail_domains = [];
		if(count($temporary_mail_domains) === 0)
		{
			_descargar_mail_disposables ();
			file_exists(MAIL_DISPOSABLES_JSON) and
			$temporary_mail_domains = (array)json_decode(file_get_contents(MAIL_DISPOSABLES_JSON), true);
		}

		if (in_array($domain, $temporary_mail_domains))
		{
			//Is a Temporary Mail
			return FALSE;
		}

		return TRUE;
	}
}

if ( ! function_exists('is_ip'))
{
	/**
	 * is_ip()
	 * Valida que el valor sea una IP válida
	 *
	 * @param mixed
	 * @param string $which (ipv4, ipv6, FILTER_FLAG_IPV4, FILTER_FLAG_IPV6)
	 * @return bool
	 */
	function is_ip($ip, $which = NULL)
	{
		switch (mb_strtolower((string)$which))
		{
			case 'ipv4':case 'FILTER_FLAG_IPV4':
				$which = FILTER_FLAG_IPV4;
				break;
			case 'ipv6':case 'FILTER_FLAG_IPV6':
				$which = FILTER_FLAG_IPV6;
				break;
		}

		return filter_var($ip, FILTER_VALIDATE_IP, $which);
	}
}

if ( ! function_exists('_match'))
{
	/**
	 * _match()
	 * Ejecuta de manera ordenada la función preg_match
	 *
	 * @param string Valor a validar el REGEXP
	 * @param string El REGEXP
	 * @param string El delimitador de la función
	 * @param string Las banderas de la función
	 * @return int
	 */
	function _match ($v, $regex, $delimiter = '/', $flags = '')
	{
		$delimiter = (string)$delimiter;
		
		try
		{
			$v = (string)$v;
		}
		catch (Exception $e)
		{
			return FALSE;
		}
		
		return preg_match($delimiter . $regex . $delimiter . $flags, $v);
	}
}

if ( ! function_exists('is_ascii'))
{
	/**
	 * is_ascii()
	 * Valida si el valor es código ASCII
	 *
	 * @param mixed
	 * @return mixed
	 */
	function is_ascii($v)
	{
		static $_regex = '[^\x00-\x7F]';
		return ! _match($v, $_regex, '/', 'S');
	}
}

if ( ! function_exists('is_date'))
{
	/**
	 * is_date()
	 * Valida si el valor es fecha
	 *
	 * @param mixed
	 * @return mixed
	 */
	function is_date($v)
	{
		static $_regex = '^\d{4}[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$';
		
		if (is_empty($v) OR $v === '0000-00-00')
		{
			return FALSE;
		}
		
		return _match($v, $_regex);
	}
}

if ( ! function_exists('is_time'))
{
	/**
	 * is_time()
	 * Valida si el valor es hora
	 *
	 * @param mixed
	 * @return mixed
	 */
	function is_time($v)
	{
		static $_regex = '^([01][0-9]|2[0123])[:]([0-5][0-9])([:]([0-5][0-9])){0,1}$';
		
		if (is_empty($v) OR $v === '00:00:00' OR $v === '00:00')
		{
			return FALSE;
		}
		
		return _match($v, $_regex);
	}
}

if ( ! function_exists('is_datetime'))
{
	/**
	 * is_datetime()
	 * Valida si el valor es fecha y hora
	 *
	 * @param mixed
	 * @return mixed
	 */
	function is_datetime($v)
	{
		static $_regex = '^\d{4}[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01]) '.
						 '([01][0-9]|2[0123])[:]([0-5][0-9])([:]([0-5][0-9])){0,1}$';
		
		if (is_empty($v) OR $v === '0000-00-00 00:00:00' OR $v === '0000-00-00 00:00')
		{
			return FALSE;
		}
		
		return _match($v, $_regex);
	}
}

if ( ! function_exists('_descargar_mail_disposables'))
{
	function _descargar_mail_disposables ()
	{
		if (file_exists(MAIL_DISPOSABLES_JSON))
		{
			// Descargar archivo con 7 días de diferencia
			$diff = time() - filemtime(MAIL_DISPOSABLES_JSON);
			$diff /= (60 * 60 * 24 * 7);
			$diff = floor($diff);

			if ($diff < 1)
			{
				return;
			}
		}

		$client = new Client();
		$request = $client->createRequest('GET', 'https://raw.githubusercontent.com/ivolo/disposable-email-domains/master/index.json');
		$promise = $client->sendAsyncRequest($request) 
		->then(function ($response) {
			$body = (string)$response->getBody();

			$data = json_decode($body, true);
			if (is_null($data)) return;
			$data = json_encode($data);
			file_put_contents(MAIL_DISPOSABLES_JSON, $data);
		});
	}
}

if ( ! file_exists(MAIL_DISPOSABLES_JSON))
{
	/** Descargar por primera vez si no se detecta el archivo indistinto a si se ha pedido la validación */
	APP()->action_add('JApi/Config/Complete', '_descargar_mail_disposables');
}
