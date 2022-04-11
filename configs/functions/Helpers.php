<?php
/**
 * /JApi/configs/functions/Helpers.php
 * Funciones de apoyo
 * @filesource
 */

defined('HOMEPATH') or exit('Archivo no se puede llamar directamente');

if ( ! function_exists('APP')) exit('Función `APP()` es requerida');

use MatthiasMullie\Minify\JS  as MinifyJS;
use MatthiasMullie\Minify\CSS as MinifyCSS;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\String\Slugger\AsciiSlugger;

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
		$type = gettype($v);

		if ($type === 'NULL')
		{
			return TRUE;
		}
		elseif ($type === 'string')
		{
			if ($v === '0')
			{
				return FALSE;
			}

			return empty($v);
		}
		elseif ($type === 'array')
		{
			return count($v) === 0;
		}


		return FALSE;
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
	function def_empty($valor, ...$valores)
	{
		array_unshift($valores, $valor);
		foreach($valores as $valor)
		{
			is_callable($valor) and 
			$valor = $valor ();

			if ( ! is_empty($valor))
			{
				return $valor;
			}
		}

		return null;
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
	function non_empty($v, callable $callback, $def_empty = null)
	{
		if ( ! is_empty($v))
		{
			return $callback($v);
		}

		return def_empty($v, $def_empty);
	}
}

if ( ! function_exists('with'))
{
	/**
	 * with()
	 */
	function with(...$params)
	{
		$args = [];
		$result = null;

		foreach($params as $param)
		{
			if (is_callable($param))
			{
				$result = call_user_func_array($param, $args);
				is_null($result) or 
				$args = (array)$result;
				continue;
			}

			$args[] = $param;
		}

		return $result; //retorna el último result
	}
}

if ( ! function_exists('html_esc'))
{
	/**
	 * html_esc
	 */
	function html_esc($str){
		return htmlspecialchars($str);
	}
}

if ( ! function_exists('extracto'))
{
	/**
	 * extracto
	 * Retorna un resumen del texto, basado en el tamaño de caracteres indicado pero soportando tags html
	 * y ubica los puntos de separación en donde se desee
	 * @param String $str
	 * @param Integer $lenght
	 * @param Integer|Decimal $position Valor decimal entre el 0 y el 1
	 * @param String $dots Separador del texto
	 * @param String $tags_allowed Tags html soportado, Eg: '<a><p>'
	 * @return String
	 */
	function extracto($str, $lenght = 50, $position = 1, $dots = '&hellip;', $tags_allowed = ''){
		// Strip tags
		$html = trim(strip_tags($str, $tags_allowed));
		$strn = trim(strip_tags($str));
		$inc_tag = FALSE;
		
		if (mb_strlen($html) > mb_strlen($strn))
		{
			$inc_tag = TRUE;
			$o = 0;
			$v = [];
			for($i=0; $i<=mb_strlen($html); $i++)
			{
				$html_char = mb_substr($html, $i, 1);
				$strn_char = mb_substr($strn, $i, 1);

				if ($html_char == '<')
				{
					$tag = '';
					$c = 0;
					
					do
					{
						$html_char = mb_substr($html, $i + $c, 1);
						$tag .= $html_char;
						
						$c++;
					}
					while($html_char <> '>');
					
					$v[$o] = $tag;
					$i+=$c - 1;
				}
				else
				{
					$o++;
				}
			}
		}
		
		// Is the string long enough to ellipsize?
		if (mb_strlen($strn) <= $lenght)
		{
			return $html;
		}
		
		$position = $position > 1 ? 1 : ($position < 0 ? 0 : $position);
		
		$beg = mb_substr($strn, 0, floor($lenght * $position));
		if ($position === 1)
		{
			$end = mb_substr($strn, 0, -($lenght - mb_strlen($beg)));
		}
		else
		{
			$end = mb_substr($strn, -($lenght - mb_strlen($beg)));
		}
		
		if ($inc_tag)
		{
			$beg_e = mb_strlen($beg);
			$end_s = mb_strlen($end);
			$spc_l = mb_strlen($strn) - $end_s - $beg_e;
			$end_s = $beg_e + $spc_l;

			$return = '';
			$opened_lvl = 0;
			for($i=0; $i<=mb_strlen($strn); $i++)
			{
				if ($i>=$beg_e and $i<$end_s)
				{
					while($opened_lvl > 0)
					{
						for($ti = $beg_e; $ti <= $end_s; $ti++)
						{
							if (isset($v[$ti]))
							{
								if ($v[$ti][1] == '/')
								{
									$opened_lvl--;
								}
								else
								{
									$opened_lvl++;
								}

								$is_br = preg_match('#<br( )*(/){0,1}>#', $v[$ti]);
								if ($is_br)
								{
									$opened_lvl--;
									continue;
								}
					
								$return .= $v[$ti];
							}
						}
					}
					
					$return .= $dots;
					$i += $spc_l - 1;
					continue;
				}
				
				$char = mb_substr($strn, $i, 1);

				if (isset($v[$i]))
				{
					if ($v[$i][1] == '/')
					{
						$opened_lvl--;
					}
					else
					{
						$opened_lvl++;
					}
					
					$is_br = preg_match('#<br( )*(/){0,1}>#', $v[$i]);
					if ($is_br)
					{
						$opened_lvl--;
					}
					
					$return .= $v[$i];
				}
				
				if ($i < $beg_e or $i >= $end_s)
				{
					$return .= $char;
				}
			}

			return $return;
		}
		else
		{
			return $beg . $dots . $end;
		}
	}
}

if ( ! function_exists('youtube'))
{
	/**
	 * youtube
	 * Obtener el código de YouTube
	 */
	function youtube($link)
	{
		static $_regex = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';

		if ( ! preg_match($_regex, $link, $v))
		{
			return preg_match('youtu', $link) ? NULL : $link;
		}
		
		return $v[1];
	}
}

if ( ! function_exists('compare'))
{
	/**
	 * compare
	 */
	function compare($str, $txt, $success = 'selected="selected"', $echo = TRUE)
	{
		$equal = $str == $txt;
		
		if ($equal)
		{
			if (is_callable($success))
			{
				$success = $success($str, $txt, $echo);
			}
			
			$success = (string)$success;
			
			if ($echo)
			{
				echo  $success;
				return TRUE;
			}
			else
			{
				return $success;
			}
		}
		
		if ($echo)
		{
			echo  '';
			return FALSE;
		}
		else
		{
			return '';
		}
	}
}

if ( ! function_exists('html_compressor'))
{
	function html_compressor($buffer)
	{
		$search = [
			'/\>[^\S ]+/s',     // strip whitespaces after tags, except space
			'/[^\S ]+\</s',     // strip whitespaces before tags, except space
			'/(\s)+/s',         // shorten multiple whitespace sequences
			'/<!--(.|\s)*?-->/' // Remove HTML comments
		];

		$replace = [
			'>',
			'<',
			'\\1',
			''
		];

		$buffer = preg_replace($search, $replace, $buffer);
		return $buffer;
	}
}

if ( ! function_exists('js_compressor'))
{
	function js_compressor (string $content = '', $arr = [])
	{
		$arr = array_merge([
			'cache' => FALSE,
			'cachetime' => NULL, 
			'use_apiminifier' => FALSE,
		], $arr);
		extract($arr);

		if (empty($content))
		{
			return $content;
		}

		if ($cache !== FALSE)
		{
			$app = APP();
			$key = ($cache !== TRUE ? $cache : md5($content)) . '.js';
			$Cache = $app->Cache($app::$_cache_nmsp_paginaassets);
			$CacheItem = $Cache->getItem($key);

			if ($CacheItem->isHit())
			{
				return $CacheItem->get();
			}
		}

		try
		{
			$temp = (new MinifyJS($content))->minify();
			$content = $temp;
		}
		catch (Exception $e)
		{}

		try
		{
			if ($use_apiminifier)
			{
				static $uri = 'https://www.toptal.com/developers/javascript-minifier/raw';

				$temp = file_get_contents($uri, false, stream_context_create([
					'http' => [
						'method'  => 'POST',
						'header'  => 'Content-type: application/x-www-form-urlencoded',
						'content' => http_build_query([
							'input' => $content
						])
					],
				]));

				if (preg_match('/^\/\/ Error/i', $temp))
				{
					throw new Exception($temp);
				}

				if ($temp === FALSE or empty($temp))
				{
					throw new Exception('Error: No Content');
				}

				$content = $temp;
			}
		}
		catch (Exception $e)
		{
			trigger_error('Se intentó Minificar el contenido JS: ' . PHP_EOL . PHP_EOL . 
						  $content . PHP_EOL . PHP_EOL . 
						  'Error Obtenido: ' . $e->getMessage(), E_USER_WARNING);
		}

		if (isset($Cache) and isset($CacheItem))
		{
			$CacheItem->set($content);
			$Cache->save($CacheItem);
		}
		return $content;
	}
}

if ( ! function_exists('css_compressor'))
{
	function css_compressor (string $content = '', $arr = [])
	{
		$arr = array_merge([
			'cache' => FALSE,
			'cachetime' => NULL, 
			'use_apiminifier' => FALSE,
		], $arr);
		extract($arr);

		if (empty($content))
		{
			return $content;
		}

		if ($cache !== FALSE)
		{
			$app = APP();
			$key = ($cache !== TRUE ? $cache : md5($content)) . '.css';
			$Cache = $app->Cache($app::$_cache_nmsp_paginaassets);
			$CacheItem = $Cache->getItem($key);

			if ($CacheItem->isHit())
			{
				return $CacheItem->get();
			}
		}

		try
		{
			$temp = (new MinifyCSS($content))->minify();
			$content = $temp;
		}
		catch (Exception $e)
		{}

		try
		{
			if ($use_apiminifier)
			{
				static $uri = 'https://cssminifier.com/raw';

				$temp = file_get_contents($uri, false, stream_context_create([
					'http' => [
						'method'  => 'POST',
						'header'  => 'Content-type: application/x-www-form-urlencoded',
						'content' => http_build_query([
							'input' => $content
						])
					],
				]));

				if (preg_match('/^\/\/ Error/i', $temp))
				{
					throw new Exception($temp);
				}

				if ($temp === FALSE or empty($temp))
				{
					throw new Exception('Error: No Content');
				}

				$content = $temp;
			}
		}
		catch (Exception $e)
		{
			trigger_error('Se intentó Minificar el contenido CSS: ' . PHP_EOL . PHP_EOL . 
						  $content . PHP_EOL . PHP_EOL . 
						  'Error Obtenido: ' . $e->getMessage(), E_USER_WARNING);
		}

		if (isset($Cache) and isset($CacheItem))
		{
			$CacheItem->set($content);
			$Cache->save($CacheItem);
		}
		return $content;
	}

}

if ( ! function_exists('json_compressor'))
{
	function json_compressor (string $content = '')
	{
		if (empty($content))
		{
			return $content;
		}

		try
		{
			$temp = json_decode($content);
			$content = json_encode($temp);
		}
		catch (Exception $e)
		{
			return $content;
		}

		return $content;
	}
}

if ( ! function_exists('grouping'))
{
	function grouping($array, $opts=[]){
		$opts = array_merge([
			'prefix' => [NULL, NULL, NULL],//Singular, Plural, Zero
			'suffix' => [NULL, NULL, NULL],//Singular, Plural, Zero
			'union' => [', ', ' y '],//normal, last
		], $opts);
		if(is_string($array)){$array=[$array];}
		$array = array_unique($array);

		$r = '';
		$c = count($array);
		$t = 2;//Zero
			if($c==0){$t=2;}
		elseif($c==1){$t=0;}
		elseif($c>=2){$t=1;}

		if(is_string($opts['prefix'])) $opts['prefix'] = [$opts['prefix']];
		if(!isset($opts['prefix'][2]) or is_null($opts['prefix'][2])){$opts['prefix'][2] = $opts['prefix'][0];}
		if(!isset($opts['prefix'][1]) or is_null($opts['prefix'][1])){$opts['prefix'][1] = $opts['prefix'][0];}

		if(is_string($opts['suffix'])) $opts['suffix'] = [$opts['suffix']];
		if(!isset($opts['suffix'][2]) or is_null($opts['suffix'][2])){$opts['suffix'][2] = $opts['suffix'][0];}
		if(!isset($opts['suffix'][1]) or is_null($opts['suffix'][1])){$opts['suffix'][1] = $opts['suffix'][0];}

		if(is_string($opts['union'])) $opts['union'] = [$opts['union']];
		if(is_null($opts['union'][0])) $opts['union'][0] = ' ';
		if(!isset($opts['union'][1]) or is_null($opts['union'][1])){$opts['union'][1] = $opts['union'][0];}

		$r.=$opts['prefix'][$t];

			if($c==0){}
		elseif($c==1){$r.=$array[0];}
		elseif($c>=2){
			$last = array_pop($array);
			$r.=implode($opts['union'][0], $array);
			$r.=$opts['union'][1].$last;
		}

		$r.=$opts['suffix'][$t];
		return $r;
	}
}

if ( ! function_exists('array_search2'))
{
	function array_search2($array, $filter_val, $filter_field = NULL, $return_field = NULL){
		$obj = [];

		if (is_null($filter_field))
		{
			$obj = array_search($filter_val, $array);
		}
		else
		{
			foreach($array as $arr)
			{
				if ($arr[$filter_field] == $filter_val)
				{
					$obj = $arr;
				}
			}
		}

		if (is_null($return_field))
		{
			return $obj;
		}

		isset($obj[$return_field]) or $obj[$return_field] = NULL;
		return $obj[$return_field];
	}
}

if ( ! function_exists('reduce_double_slashes'))
{
	/**
	 * Reduce Double Slashes
	 *
	 * Converts double slashes in a string to a single slash,
	 * except those found in http://
	 *
	 * http://www.some-site.com//index.php
	 *
	 * becomes:
	 *
	 * http://www.some-site.com/index.php
	 *
	 * @param	string
	 * @return	string
	 */
	function reduce_double_slashes($str)
	{
		return preg_replace('#(^|[^:])//+#', '\\1/', $str);
	}
}

if ( ! function_exists('reduce_multiples'))
{
	/**
	 * Reduce Multiples
	 *
	 * Reduces multiple instances of a particular character.  Example:
	 *
	 * Fred, Bill,, Joe, Jimmy
	 *
	 * becomes:
	 *
	 * Fred, Bill, Joe, Jimmy
	 *
	 * @param	string
	 * @param	string	the character you wish to reduce
	 * @param	bool	TRUE/FALSE - whether to trim the character from the beginning/end
	 * @return	string
	 */
	function reduce_multiples($str, $character = ',', $trim = FALSE)
	{
		$str = preg_replace('#' . preg_quote($character, '#') . '{2,}#', $character, $str);
		return ($trim === TRUE) ? trim($str, $character) : $str;
	}
}

if ( ! function_exists('strtoslug'))
{
	function strtoslug (string $string, string $separator = '-') : string
	{
		static $slugger;
		if ( ! isset($slugger))
		{
			$symbolsMap = [
				'es' => ['&' => 'y', '%' => 'por-ciento'],
			];
			$symbolsMap = filter_apply('strtoslug/symbols', $symbolsMap);
			$slugger = new AsciiSlugger(null, $symbolsMap);
		}

		return $slugger
		-> slug($string, $separator, APP()->get_LANG())
		-> lower()
		-> slice(0, 100);
	}
}

if ( ! function_exists('strtobool'))
{
	function strtobool ($str = '', $empty = FALSE)
	{
		if (is_empty($str))
		{
			return $empty;
		}

		if (is_bool($str))
		{
			return $str;
		}

		$str = (string)$str;

		if (preg_match('/^(s|y|v|t|1)/i', $str))
		{
			return TRUE;
		}

		if (preg_match('/^(n|f|0)/i', $str))
		{
			return FALSE;
		}

		return !$empty;
	}
}

if ( ! function_exists('strtonumber'))
{
	function strtonumber ($str = '')
	{
		$str = (string)$str;
		$str = preg_replace('/[^0-9\.]/i', '', $str);

		$str = (double)$str;
		return $str;
	}
}

if ( ! function_exists('date_str'))
{
	function date_str ($str, $timestamp = NULL, $Force = TRUE)
	{
		is_bool($timestamp) and $Force = $timestamp and $timestamp = NULL;

		if (is_array($str))
		{
			return array_map(function($v) use ($timestamp, $Force){
				return date_str($v, $timestamp, $Force);
			}, $str);
		}
		
		is_null($timestamp) and $timestamp = time();

		is_numeric($timestamp) or $timestamp = strtotime($timestamp);

		$return = $str;

		switch($str)
		{
			//Palabras de StrToTime
			case 'this week':
				$return = strtotime('this week');
				break;

			//Force date as now
			case 'now':
			case 'ahora':
			case 'today':
			case 'hoy':
				$return = time();
				break;
			
			case 'tomorrow':
			case 'mañana':
				$return = strtotime(date('Y-m-d H:i:s').' + 1 Day');
				break;
			
			case 'yesterday':
			case 'ayer':
				$return = strtotime(date('Y-m-d H:i:s').' - 1 Day');
				break;
			
			case 'now start':
			case 'now_start':
			case 'now-start':
				$return = strtotime(date('Y-m-d 00:00:00'));
				break;
			
			case 'now end':
			case 'now_end':
			case 'now-end':
				$return = strtotime(date('Y-m-d 23:59:59'));
				break;

			case 'this_week':
			case 'esta_semana':
				$d = date('w');
				$fis = ($d==0?'':($d==1?' - 1 Day':(' - '.$d.' Days')));
				$ffs = ($d==6?'':($d==5?' + 1 Day':(' + '.(6-$d).' Days')));
				
				$return = [
					strtotime(date('Y-m-d 00:00:00').$fis),
					strtotime(date('Y-m-d 23:59:59').$ffs)
				];
				break;
			
			case 'this_week_time':                             
				$d = date('w');
				$fis = ($d==0?'':($d==1?' - 1 Day':(' - '.$d.' Days')));
				$ffs = ($d==6?'':($d==5?' + 1 Day':(' + '.(6-$d).' Days')));
				
				$return = [
					strtotime(date('Y-m-d H:i:s').$fis),
					strtotime(date('Y-m-d H:i:s').$ffs)
				];
				break;
			
			case 'this_week_str':                              
				$ini = strtotime(date('Y-m-d 00:00:00', strtotime('this week')));
				
				$return = [
					$ini,
					strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s', $ini).' + 7 Days')).' - 1 Second')
				];
				break;
			
			case 'this_week_str_time':                         
				$ini = strtotime('this week');
				
				$return = [
					$ini,
					strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s', $ini).' + 7 Days')).' - 1 Second')
				];
				break;
			
			case 'this_month':
			case 'este_mes':                 
				$return = [
					strtotime(date('Y-m-01 00:00:00')),
					strtotime(date('Y-m-'.date('t').' 23:59:59'))
				];
				break;
			
			case 'last_month':
			case 'mes_pasado':
				$mon = strtotime(date('Y-m-d').' - 1 Month');
				
				$return = [
					strtotime(date('Y-m-01 00:00:00', $mon)),
					strtotime(date('Y-m-'.date('t', $mon).' 23:59:59', $mon))
				];
				break;
			
			case 'this_year':
			case 'este_año':                  
				$return = [
					strtotime(date('Y-01-01 00:00:00')),
					strtotime(date('Y-12-31 23:59:59'))
				];
				break;
			
			case 'last_year':
			case 'año_pasado':
				$yrs = strtotime(date('Y-m-d').' - 1 Year');
				
				$return = [
					strtotime(date('Y-01-01 00:00:00', $yrs)),
					strtotime(date('Y-12-31 23:59:59', $yrs))
				];
				break;

			//The dateFrom
			case 'timestamp':
			case 'hora':
				$return = $timestamp;
				break;
			
			case 'day_start':
				$return = strtotime(date('Y-m-d 00:00:00', $timestamp));
				break;

			case 'day_end':
				$return = strtotime(date('Y-m-d 23:59:59', $timestamp));
				break;
				
			case 'that_week':                                  
				$d = date('w', $timestamp);
				$fis = ($d==0?'':($d==1?' - 1 Day':(' - '.$d.' Days')));
				$ffs = ($d==6?'':($d==5?' + 1 Day':(' + '.(6-$d).' Days')));
				
				$return = [
					strtotime(date('Y-m-d 00:00:00', $timestamp).$fis),
					strtotime(date('Y-m-d 23:59:59', $timestamp).$ffs)
				];
				break;
				
			case 'that_week_time':                             
				$d = date('w', $timestamp);
				$fis = ($d==0?'':($d==1?' - 1 Day':(' - '.$d.' Days')));
				$ffs = ($d==6?'':($d==5?' + 1 Day':(' + '.(6-$d).' Days')));
				
				$return = [
					strtotime(date('Y-m-d H:i:s', $timestamp).$fis),
					strtotime(date('Y-m-d H:i:s', $timestamp).$ffs)
				];
				break;
				
			case 'that_week_str':                              
				$ini = strtotime(date('Y-m-d 00:00:00', strtotime('this week', $timestamp)));
				
				$return = [
					$ini,
					strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s', $ini).' + 7 Days')).' - 1 Second')
				];
				break;
				
			case 'that_week_str_time':                         
				$ini = strtotime('this week', $timestamp);
				
				$return = [
					$ini,
					strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s', $ini).' + 7 Days')).' - 1 Second')
				];
				break;
				
			case 'that_month':                                 
				$return = [
					strtotime(date('Y-m-01 00:00:00', $timestamp)),
					strtotime(date('Y-m-'.date('t', $timestamp).' 23:59:59', $timestamp))
				];
				break;
				
			case 'that_year':                                  
				$return = [
					strtotime(date('Y-01-01 00:00:00', $timestamp)),
					strtotime(date('Y-12-31 23:59:59', $timestamp))
				];
				break;

			default:
				$nms = 'Second|Minute|Hour|Day|Week|Month|Year';
				
				if(preg_match('/^(\-|\+)\=([\ ]*)([0-9]*)([\ ]*)(' . $nms . ')(s){0,1}/i', $str, $matchs))
				{
					if($matchs[3]*1===1)
					{
						$matchs[6] = '';
					}
					else
					{
						$matchs[6] = 's';
					}
					
					$return = strtotime(date('Y-m-d H:i:s', $timestamp) . ' ' . $matchs[1] . ' ' . strtocapitalize($matchs[3]) . ' ' . $matchs[5] . $matchs[6]);
				}
				else
				if(preg_match('/^last([\ \_]+)([0-9]*)([\ \_]+)(' . $nms . ')(s){0,1}([\ \_]*)(wt)*/i', $str, $matchs))
				{
					if(trim($matchs[7])==='')
					{
						$timestamp = strtotime(date('Y-m-d 23:59:59', $timestamp));
					}
					
					if($matchs[2]*1===1)
					{
						$matchs[5] = '';
					}
					else
					{
						$matchs[5] = 's';
					}
					
					$return = [
						strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s', $timestamp).' - '.$matchs[2].' '.strtocapitalize($matchs[4]).$matchs[5])).' + 1 Second'),
						$timestamp
					];
				}
				else
				if(is_numeric($str))
				{
					$return = $str;
				}
				else
				{
					$_str = $str;
					
					if ( ! $Force and in_array($str, ['d.vmm']))
					{
						$str = 'PreReValid';
					}
					
					$return = strtotime($str, $timestamp);
					
					$return === FALSE and ! $Force and $return = $_str;
				}
				break;
		}

		$return === FALSE and $Force and $return = time();
		return $return;
	}
}

if ( ! function_exists('date2'))
{
	function date2 ($formato = 'Y-m-d H:i:s', ...$timestamps)
	{
		if (count($timestamps) === 0 or ! is_int(end($timestamps)))
		{
			$timestamps[] = time();
		}

		if (count($timestamps) > 1)
		{
			while(count($timestamps) > 1)
			{
				$timestamp = array_pop($timestamps);
				$x = count($timestamps) - 1;
				
				$timestamps[$x] = date2($timestamps[$x], $timestamp);
				
				is_int($timestamps[$x]) or $timestamps[$x] = strtotime($timestamps[$x]);
				$timestamps[$x] === false and $timestamps[$x] = time();
			}
		}
		
		$timestamp = array_pop($timestamps);
		
		if (is_int($formato))
		{
			$nt = $formato;
			
			switch($timestamp)
			{
				case 'this week':
				case 'now':
				case 'ahora':
				case 'now start':
				case 'now_start':
				case 'now-start':
				case 'now end':
				case 'now_end':
				case 'now-end':
				case 'this_week_time':
				case 'this_week_str_time':
				case 'day_start':
				case 'day_end':
				case 'that_week_time':
				case 'that_week_str_time':
					$formato = 'Y-m-d H:i:s';
					break;
				
				case 'today':
				case 'hoy':
				case 'tomorrow':
				case 'mañana':
				case 'yesterday':
				case 'ayer':
				case 'this_week':
				case 'esta_semana':
				case 'this_week_str':
				case 'this_month':
				case 'este_mes':
				case 'this_year':
				case 'este_año':
				case 'that_week':
				case 'that_week_str':
				case 'that_month':
				case 'that_year':
				case 'last_month':
				case 'mes_pasado':
				case 'last_year':
				case 'año_pasado':
					$formato = 'Y-m-d';
					break;
				
				case 'timestamp':
					$formato = 'timestamp';
					break;
				
				case 'hora':
					$formato = 'H:i:s';
					break;

				default:
					$formato = 'Y-m-d H:i:s';
					break;
			}
			
			$timestamp = $nt;
			unset($nt);
		}
		
		if (mb_strtolower($formato) === 'iso8601')
		{
			// El formato iso8601 no requiere que convierta el timestamp a numero
			return date_iso8601($timestamp);
		}

		$timestamp = date_str($timestamp, FALSE);

		is_int($timestamp) or $timestamp = date_str($timestamp);

		$nformato = date_str($formato, $timestamp, false);

		if ($nformato !== $formato)
		{
			return $nformato;
		}

		if (mb_strtolower($formato) === 'timestamp')
		{
			return $timestamp;
		}

		$return = '';
		$split = str_split($formato);
		
		$dgt = '';
		
		for($x = 0; $x < count($split); $x++)
		{
			$c = $split[$x];
			
			if($c === '\\')
			{
				$return .= $split[$x+1];
				$x++;
			}
			elseif ($c === '"' or $c === '\'')
			{
				$x_ =1;
				$t = '';
				
				while($split[$x+$x_]<>$c)
				{
					$t.=$split[$x+$x_];
					$x_++;
				}
				
				$return.=_t($t);
				$x+=$x_;
			}
			elseif(preg_match('/[a-zA-Z]/', $c))
			{
				$dgt.=$c;
				
				if( ! ((count($split)-1) === $x) and preg_match('/[a-zA-Z]/', $split[$x+1]))
				{
					continue;
				}
				
				switch($dgt)
				{
					case 'de' :
						$return.=_t('de'); 
						break;
						
					case 'del':
						$return.=_t('del');
						break;
						
					case 'vmn':
						$return.=mes(date('m', $timestamp), 'normal');
						break;
						
					case 'vmm':
						$return.=mes(date('m', $timestamp), 'min');
						break;
						
					case 'vdn':
						$return.=dia(date('w', $timestamp), 'normal');
						break;
						
					case 'vdm':
						$return.=dia(date('w', $timestamp), 'min');
						break;
						
					case 'vdmn':
						$return.=dia(date('w', $timestamp), 'vmin');
						break;
						
					case 'LL':
						$return.=date2('d "de" vmn "de" Y', $timestamp);
						break;
						
					default:
						$return.=date($dgt, $timestamp);
						break;
				}

				$dgt='';
			}
			else
			{
				$return.=$c;
			}
		}

		return $return;
	}
}

if ( ! function_exists('date_iso8601'))
{
	/**
	 * date_iso8601 ()
	 * -Obtener el formato ISO8601 de una fecha
	 *
	 * @param int|string|null $time Fecha a formatear, si es NULL entonces se asume este momento
	 * @return string
	 */
	function date_iso8601 ($time = NULL)
	{
		static $_regex = '/(([0-9]{2,4})\-([0-9]{1,2})\-([0-9]{1,2}))*(\ )*(([0-9]{1,2})\:([0-9]{1,2})\:([0-9]{1,2}))*/';
		
		/** Convertimos NULL a momento actual */
		is_null($time) and $time = time();
		
		if ( ! preg_match($_regex, $time))
		{
			/** Convertimos STRING a TIME */
			is_string($time) and $time = date2($time);

			/** TIME to DATE */
			$time = date2('Y-m-d H:i:s', $time);
		}

		/** Obteniendo las partes del DATE */
		preg_match($_regex, $time, $matches);

		$R = [];
		
		$n = 2 and isset($matches[$n]) and $v = (string)$matches[$n] and ! is_empty($v) and $R['P']['Y'] = $v;
		$n = 3 and isset($matches[$n]) and $v = (string)$matches[$n] and ! is_empty($v) and $R['P']['M'] = $v;
		$n = 4 and isset($matches[$n]) and $v = (string)$matches[$n] and ! is_empty($v) and $R['P']['D'] = $v;
		
		$n = 7 and isset($matches[$n]) and $v = (string)$matches[$n] and ! is_empty($v) and $R['T']['H'] = $v;
		$n = 8 and isset($matches[$n]) and $v = (string)$matches[$n] and ! is_empty($v) and $R['T']['M'] = $v;
		$n = 9 and isset($matches[$n]) and $v = (string)$matches[$n] and ! is_empty($v) and $R['T']['S'] = $v;

		isset($R['P']['Y']) and mb_strlen($R['P']['Y']) === 2 and $R['P']['Y'] = '19' . $R['P']['Y'];

		return implode('', array_map(function($k, $v){
			return $k . implode('', array_map(function($x, $y){
				return $y . $x;
			}, array_keys($v), array_values($v)));
		}, array_keys($R), array_values($R)));
	}
}

if ( ! function_exists('moment'))
{
	/**
	 * moment ()
	 * Obtener un texto de relatividad de momentos
	 *
	 * @param int|string|null $from Fecha desde el cual ejecutar la relatividad del momento, si es NULL entonces se asume este momento
	 * @param int|string|null $to Fecha hacia el cual ejecutar la relatividad del momento, si es NULL entonces se asume este momento, este valor debe ser mayor o igual a $from
	 * @param bool $min Si la relatividad debe ser devuelta en texto corto o largo
	 *
	 * @return string
	 */
	function moment ($from = NULL, $to = NULL, $min = FALSE)
	{
		is_bool($to) and $min = $to and $to = NULL;
		
		/** Convertimos NULL a momento actual */
		is_null($to) and $to = time();
		is_null($from) and $from = time();
		
		/** Convertimos STRING a TIME */
		is_string($to) and $to = date2($to, $from, time()) and $to = date2('timestamp', $to);
		is_string($from) and $from = date2($from) and $from = date2('timestamp', $from);
		
		/** Nos aseguramos que $to sea mayor o igual a $from */
		$to < $from and $to = $from;
		
		/** Obtenemos la diferencia en Segundos */
		$_seg = $to - $from;
		
		if ($_seg < 30)
		{
			return _t($min ? 'Instante' : 'Hace un momento');
		}
		
		$_min = (int) floor ($_seg / 60);
		
		if ($_min === 0)
		{
			return _t ($min ? '%d Seg(s)' : 'Hace %d segundo(s)', $_seg);
		}
		elseif ($_min === 1 and ! $min)
		{
			return _t ('Hace un minuto');
		}
		elseif ($_min === 30 and ! $min)
		{
			return _t ('Hace media hora');
		}
		
		$_hor = (int) floor ($_min / 60);
		
		if ($_hor === 0)
		{
			return _t ($min ? '%d Min(s)' : 'Hace %d minuto(s)', $_min);
		}
		elseif ($_hor === 1 and ! $min)
		{
			return _t ('Hace una hora');
		}
		
		$_dia = (int) floor ($_hor / 24);
		
		if ($_dia === 0)
		{
			return _t ($min ? date2('H:i', $from) : ($_hor <= date('H') ? 'Hoy' : 'Ayer') . ' a las ' . date2('H:i:s', $from));
		}
		elseif ($_dia === 1 and ! $min)
		{
			return _t ('Hace un día');
		}
		
		$_sem = (int) floor ($_dia / 7);

		if ($_sem === 0)
		{
			return _t ($min ? date2('d.vmm', $from) : date2('LL', $from));
		}
		elseif ($_sem === 1 and $_dia === 7 and ! $min)
		{
			return _t ('Hace una semana');
		}
		elseif ($_sem === 2 and $_dia === 14 and ! $min)
		{
			return _t ('Hace dos semanas');
		}
		
		$_mes = (int) floor ($_sem / 4);

		if ($_mes === 0)
		{
			return _t ($min ? date2('d.vmm', $from) : date2('LL', $from));
		}
		elseif ($_mes === 1 and $_sem === 4 and ! $min)
		{
			return _t ('Hace un més');
		}
		
		$_ano = (int) floor ($_mes / 12);
		
		if ($_ano === 0)
		{
			return _t ($min ? date2('vmm.Y', $from) : date2('vmn "del" Y', $from));
		}
		elseif ($_ano === 1 and ! $min)
		{
			return _t ('Hace un año');
		}
		
		return _t ($min ? date2('d.vmm.Y', $from) : date2('LL', $from));
	}
}

if ( ! function_exists('remove_invisible_characters'))
{
	/**
	 * Remove Invisible Characters
	 *
	 * This prevents sandwiching null characters
	 * between ascii characters, like Java\0script.
	 *
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	function remove_invisible_characters($str, $url_encoded = TRUE)
	{
		$non_displayables = array();

		// every control character except newline (dec 10),
		// carriage return (dec 13) and horizontal tab (dec 09)
		if ($url_encoded)
		{
			$non_displayables[] = '/%0[0-8bcef]/i';	// url encoded 00-08, 11, 12, 14, 15
			$non_displayables[] = '/%1[0-9a-f]/i';	// url encoded 16-31
			$non_displayables[] = '/%7f/i';	// url encoded 127
		}

		$non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

		do
		{
			$str = preg_replace($non_displayables, '', $str, -1, $count);
		}
		while ($count);

		return $str;
	}
}

if ( ! function_exists('csvstr'))
{
	function csvstr(...$params)
	{
		$f = fopen('php://memory', 'r+');
		array_unshift($params, $f);

		if (call_user_func_array('fputcsv', $params) === false) {
			return false;
		}

		rewind($f);
		$csv_line = stream_get_contents($f);
		return ltrim($csv_line);
	}
}

if ( ! function_exists('download'))
{
	/**
	 * Force Download
	 *
	 * Generates headers that force a download to happen
	 *
	 * @param	string	filename
	 * @param	mixed	the data to be downloaded
	 * @param	bool	whether to try and send the actual file MIME type
	 * @return	void
	 */
	function download($filename = '', $data = null, $set_mime = FALSE, $unlink = false)
	{
		if ($filename === '' OR $data === '')
		{
			return;
		}
		elseif ($data === NULL)
		{
			if ( ! @is_file($filename) OR ($filesize = @filesize($filename)) === FALSE)
			{
				return;
			}

			$filepath = $filename;
			$filename = explode('/', str_replace(DS, '/', $filename));
			$filename = end($filename);
		}
		else
		{
			$filesize = strlen($data);
			$unlink = false;
		}

		// Set the default MIME type to send
		$mime = 'application/octet-stream';

		$x = explode('.', $filename);
		$extension = end($x);

		if ($set_mime === TRUE)
		{
			if (count($x) === 1 OR $extension === '')
			{
				/* If we're going to detect the MIME type,
				 * we'll need a file extension.
				 */
				return;
			}

			// Only change the default MIME if we can find one
			$mimeTypes = new MimeTypes();
			$types = $mimeTypes->getMimeTypes($extension);
			$mime = array_shift($types);
		}

		/* It was reported that browsers on Android 2.1 (and possibly older as well)
		 * need to have the filename extension upper-cased in order to be able to
		 * download it.
		 *
		 * Reference: http://digiblog.de/2011/04/19/android-and-the-download-file-headers/
		 */
		if (count($x) !== 1 && isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Android\s(1|2\.[01])/', $_SERVER['HTTP_USER_AGENT']))
		{
			$x[count($x) - 1] = strtoupper($extension);
			$filename = implode('.', $x);
		}

		if ($data === NULL && ($fp = @fopen($filepath, 'rb')) === FALSE)
		{
			return;
		}

		APP()->ResponseAs('FILE');

		header('Content-Type: '.$mime);
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Expires: 0');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '.$filesize);
		header('Cache-Control: private, no-transform, no-store, must-revalidate');

		// If we have raw data - just dump it
		if ($data !== NULL)
		{
			exit($data);
		}

		// Flush 1MB chunks of data
		while ( ! feof($fp) && ($data = fread($fp, 1048576)) !== FALSE)
		{
			echo $data;
			flush();
		}

		fclose($fp);

		if ($unlink)
		{
			unlink($filepath);
		}
		exit;
	}
}

if ( ! function_exists('directory_map'))
{
	/**
	 * Create a Directory Map
	 *
	 * Reads the specified directory and builds an array
	 * representation of it. Sub-folders contained with the
	 * directory will be mapped as well.
	 *
	 * @param	string	$source_dir		Path to source
	 * @param	int	$directory_depth	Depth of directories to traverse
	 *						(0 = fully recursive, 1 = current dir, etc)
	 * @param	bool	$hidden			Whether to show hidden files
	 * @return	array
	 */
	function directory_map($source_dir, $directory_depth = 0, $hidden = FALSE)
	{
		if ($fp = @opendir($source_dir))
		{
			$filedata	= array();
			$new_depth	= $directory_depth - 1;

			while (FALSE !== ($file = readdir($fp)))
			{
				// Remove '.', '..', and hidden files [optional]
				if ($file === 'index.htm' OR $file === '.' OR $file === '..' OR ($hidden === FALSE && $file[0] === '.'))
				{
					continue;
				}

				if (($directory_depth < 1 OR $new_depth > 0) && is_dir($source_dir . DS . $file))
				{
					$filedata[DS . $file] = directory_map($source_dir . DS . $file, $new_depth, $hidden);
				}
				else
				{
					$filedata[] = DS . $file;
				}
			}

			closedir($fp);
			return $filedata;
		}

		return [];
	}
}

if ( ! function_exists('unlink_directory'))
{
	function unlink_directory($source_dir, $directory_depth = 0)
	{
		if ( ! file_exists($source_dir) or ! is_dir($source_dir))
		{
			return TRUE;
		}
		
		$source_dir = realpath($source_dir);
		
		if ($fp = @opendir($source_dir))
		{
			$new_depth	= $directory_depth - 1;

			while (FALSE !== ($file = readdir($fp)))
			{
				// Remove '.', '..', and hidden files [optional]
				if ($file === '.' OR $file === '..')
				{
					continue;
				}

				if (($directory_depth < 1 OR $new_depth > 0) && is_dir($source_dir . DS . $file))
				{
					unlink_directory($source_dir . DS . $file, $new_depth);
				}
				else
				{
					@unlink($source_dir . DS . $file);
				}
			}

			closedir($fp);
			@rmdir($source_dir);

			return TRUE;
		}
	}
}

if ( ! function_exists('encrypt'))
{
	/**
	 * Encrypt a message
	 * 
	 * @param string $message - message to encrypt
	 * @param string $key - encryption key
	 * @return string
	 */
	function encrypt(string $message, string $key = 'JApi')
	{
		return (string) Crypter::instance() 
		-> encrypt($message, $key);
	}
}

if ( ! function_exists('decrypt'))
{
	/**
	 * Decrypt a message
	 * 
	 * @param string $encrypted - message encrypted with safeEncrypt()
	 * @param string $key - encryption key
	 * @return string
	 */
	function decrypt(string $encrypted, string $key = 'JApi')
	{
		return (string) Crypter::instance() 
		-> decrypt($encrypted, $key);
	}
}

if ( ! function_exists('_o'))
{
	/**
	 * _o()
	 * Obtiene el ob_content de una función
	 *
	 * @param callable
	 * @return string
	 */
	function _o (callable ...$callbacks)
	{
		ob_start();
		foreach($callbacks as $callback)
		{
			call_user_func($callback);
		}
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}
}

if ( ! function_exists('date_recognize'))
{
	function date_recognize($date, $returnFormat = NULL){
		if(is_empty($date)){
			return NULL;
		}

		if(preg_match('/^\d{4}[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$/', $date)){
			$this_format = 'Y-m-d';
		}
		elseif(preg_match('/^\d{4}[-](0[1-9]|1[012])[-]([1-9]|[12][0-9]|3[01])$/', $date)){
			$this_format = 'Y-m-j';
		}
		elseif(preg_match('/^\d{4}[-]([1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$/', $date)){
			$this_format = 'Y-n-d';
		}
		elseif(preg_match('/^\d{4}[-]([1-9]|1[012])[-]([1-9]|[12][0-9]|3[01])$/', $date)){
			$this_format = 'Y-n-j';
		}
		elseif(preg_match('/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[012])\/\d{4}$/', $date)){
			$this_format = 'd/m/Y';
		}
		elseif(preg_match('/^(0[1-9]|[12][0-9]|3[01])\/(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\/\d{4}$/', $date)){
			$this_format = 'd/F/Y';
		}
		elseif(preg_match('/^(0[1-9]|[12][0-9]|3[01])\/(January|February|March|April|May|June|July|August|September|October|November|December)\/\d{4}$/', $date)){
			$this_format = 'd/M/Y';
		}
		else
		{
			return NULL;//Formato no reconocido
		}

		if(is_null($returnFormat))
		{
			return $this_format;
		}

		$date = date_create_from_format($this_format, $date);
		return date2($returnFormat, strtotime($date->format('Y-m-d H:i:s')));
	}
}

if ( ! function_exists('diffdates'))
{
	function diffdates($fecha_mayor='now_end', $fecha_menor='now', $possitive=true){
		$fecha_mayor = date2('timestamp', $fecha_mayor);
		$fecha_menor = date2('timestamp', $fecha_menor);

		if($possitive and $fecha_menor>$fecha_mayor){
			$fecha_temp = $fecha_mayor;
			$fecha_mayor = $fecha_menor;
			$fecha_menor = $fecha_temp;
			unset($fecha_temp);
		}

		$diff = $fecha_mayor - $fecha_menor;
		return $diff;
	}
}

if ( ! function_exists('convertir_tiempo'))
{
	//Convertir Tiempo
	//$return = array|completo|reducido
	function  convertir_tiempo($seg, $return = 'reducido', $inverted = true)
	{
		static $txtplu  = ['segundos', 'minutos', 'horas', 'dias', 'semanas', 'meses', 'años'];
		static $txtsing = ['segundo',  'minuto',  'hora',  'dia',  'semana',  'mes',   'año' ];

		$r = ['sg' => $seg < 1 ? (round($seg * 1000) / 1000) : round($seg)];

		$r['mi'] = floor($r['sg']/60); $r['sg'] -= $r['mi']*60;
		$r['ho'] = floor($r['mi']/60); $r['mi'] -= $r['ho']*60;
		$r['di'] = floor($r['ho']/24); $r['ho'] -= $r['di']*24;
		$r['se'] = floor($r['di']/7 ); $r['di'] -= $r['se']*7 ;
		$r['me'] = floor($r['se']/4 ); $r['se'] -= $r['me']*4 ;
		$r['añ'] = floor($r['me']/12); $r['me'] -= $r['añ']*12;

		$obl = false;

		if ($r['añ']<>0 or $obl) $obl = true;
		$r['añ'] = [$r['añ'], $r['añ']==1?$txtsing[6]:$txtplu[6], $obl];

		if ($r['me']<>0 or $obl) $obl = true;
		$r['me'] = [$r['me'], $r['me']==1?$txtsing[5]:$txtplu[5], $obl];

		if ($r['se']<>0 or $obl) $obl = true;
		$r['se'] = [$r['se'], $r['se']==1?$txtsing[4]:$txtplu[4], $obl];

		if ($r['di']<>0 or $obl) $obl = true;
		$r['di'] = [$r['di'], $r['di']==1?$txtsing[3]:$txtplu[3], $obl];

		if ($r['ho']<>0 or $obl) $obl = true;
		$r['ho'] = [$r['ho'], $r['ho']==1?$txtsing[2]:$txtplu[2], $obl];

		if ($r['mi']<>0 or $obl) $obl = true;
		$r['mi'] = [$r['mi'], $r['mi']==1?$txtsing[1]:$txtplu[1], $obl];

		if ($r['sg']<>0 or $obl) $obl = true;    
		$r['sg'] = [$r['sg'], $r['sg']==1?$txtsing[0]:$txtplu[0], $obl];

		if($inverted){
			$r = array_merge(array('añ'=>[], 'me'=>[], 'se'=>[], 'di'=>[], 'ho'=>[], 'mi'=>[], 'sg'=>[] ), $r);
		}

		if($return=='array'){
			return $r;
		}

		$s = '';
		foreach($r as $x=>$y){
			if(!$y[2] and $return=='reducido') continue;
			$s .= ($s==''?'':' ').$y[0].' '.$y[1];
		}

		return $s;
	}
}

if ( ! function_exists('transform_size'))
{
	//Transformar Tamaño
	function transform_size($tam)
	{
		static $units = ['b','kb','mb','gb','tb','pb'];
		$tam = (double)$tam;
		if ($tam < 1) 
		{
			return $tam . 'b';
		}

		$tam  = @round($tam / pow(1024, ($i = floor(log($tam, 1024)))), 4) . ' ' . $units[$i];
		return $tam;
	}
}

if ( ! function_exists('get_image'))
{
	/**
	 * get_image()
	 * Obtiene la ruta convertida de la imagen
	 *
	 * @param string Ruta de la imagen
	 * @param array  Opciones de la imagen
	 * @param string Ruta de la imagen por defecto en caso de que no se encuentre la primera imagen
	 * @return string
	 */
	function get_image($src, $opt = [], $def = NULL)
	{
		$url = parse_url($src);

		isset($url['scheme']) or $url['scheme']= url('scheme');
		isset($url['host'])   or $url['host']  = url('host');
		isset($url['query'])  or $url['query'] = '';
		isset($url['path'])   or $url['path']  = '';

		$url['path'] = '/' . ltrim($url['path'], '/');

		$src = build_url($url);
		$externo = ! preg_match('#'.regex(url('host-abs')).'#i', $src);

		/**
		 * DESCARGAR LA IMAGEN SI ES EXTERNO AL SERVER
		 */
		if ($externo)
		{
			$url = parse_url($src);

			isset($url['query']) or $url['query'] = '';
			isset($url['fragment']) or $url['fragment'] = '';

			extract($url, EXTR_PREFIX_ALL, 'src');

			$directorio = explode('/', $src_path);
			count($directorio) and empty($directorio[0]) and array_shift($directorio);

			array_unshift($directorio, $src_host);
			array_unshift($directorio, 'externo');
			array_unshift($directorio, '');

			$file_name = array_pop($directorio);

			$file_name = explode('.', $file_name);
			$file_ext  = count($file_name) > 1 ? ('.' . array_pop($file_name)) : '';
			$file_name = implode('.', $file_name);

			$directorio = implode(DS, $directorio);

			$_path = config('upload_path');
			$_path_img = config('upload_path_for_img');
			$_path_img_extra = config('upload_path_for_img_is_extra');

			is_null($_path_img) or $_path_img = '';
			$_path = (is_null($_path_img) or $_path_img_extra) ? ($_path . $_path_img) : $_path_img;
			$_yearmonth = config('upload_yearmonth');
			$_path = $_path . ($_yearmonth ? (DS . date('Y') . DS . date('m')) : '');
			$directorio = $_path . $directorio;

			if ( ! empty($src_query) or ! empty($src_fragment))
			{
				$file_name = md5(json_encode([
					$file_name,
					$src_query,
					$src_fragment
				]));
			}

			$file_saved = $directorio . DS . $file_name . $file_ext;

			mkdir2(dirname($file_saved), HOMEPATH);

			if ( ! file_exists(HOMEPATH . $file_saved))
			{
				try
				{
					$contents = file_get_contents($src);

					if (empty($contents))
					{
						throw new Exception('Contenido Vacío');
					}
				}
				catch(Exception $e)
				{
					if ( ! is_empty($def))
					{
						return get_image($def, $opt);
					}
					return $src;
				}

				file_put_contents(HOMEPATH . $file_saved, $contents);
			}

			$url['scheme'] = url('scheme');
			$url['host'] = url('host');
			$url['path'] = strtr($file_saved, '\\/', '//');

			$url['query'] = '';
			$url['fragment'] = '';

			$src = build_url($url);
		}
		unset($externo);

		/**
		 * PARSEANDO LA SRC
		 */
		$url = parse_url($src);

		isset($url['query']) or $url['query'] = '';
		isset($url['fragment']) or $url['fragment'] = '';

		/**
		 * EXTRAYENDO LOS DATOS DE LA URL
		 */
		extract($url, EXTR_PREFIX_ALL, 'src');

		// Obtener los parametros de la SRC
		parse_str($src_query, $src_params);

		$directorio = explode('/', $src_path);
		count($directorio) and empty($directorio[0]) and array_shift($directorio);

		$file_name = array_pop($directorio);

		$file_name = explode('.', $file_name);
		$file_ext  = count($file_name) > 1 ? ('.' . array_pop($file_name)) : '';
		$file_name = implode('.', $file_name);

		if (empty($file_name))
		{
			// No hay ningun nombre de archivo en la $src
			return $src;
		}

		// Eliminar carpetas del slug
		if (count($directorio) > 0 and $directorio[0] === 'img')
		{
			array_shift($directorio);
		}

		isset($directorio[0]) or array_unshift($directorio, ''); ## Agrega el espacio inicial
		empty($directorio[0]) or array_unshift($directorio, ''); ## Agrega el espacio inicial

		$directorio = implode(DS, $directorio);

		$opts_in_name = ImgManager :: GetParamsFromName ($file_name);

		if ( ! is_array($opt))
		{
			if (is_callable($opt))
			{
				$opt = $opt($src, $url, $src_params);
			}

			if (is_string($opt) or ! is_array($opt))
			{
				$opt = (string)$opt;
				$opt = ['size' => $opt];
			}
		}
	
		$opt = array_merge([
			'size'    => NULL,
			'crop'    => NULL,
			'offset'  => NULL,

			'quality' => NULL,
		], $opts_in_name, (array)$opt);

		$real_file = HOMEPATH . $directorio . DS .$file_name . $file_ext;
		$real_file = strtr($real_file, '/\\', DS.DS);

		$opt_uri = ImgManager :: GetParamsUri($opt, $real_file);

		$the_file = '/img' . $directorio . '/' . $file_name . $opt_uri . $file_ext;
		$the_file = strtr($the_file, '/\\', '//');

		$the_file_path = HOMEPATH . str_replace('/', DS, $the_file);

		$time = file_exists($real_file) ? filemtime($real_file) : 404;
		if (file_exists($the_file_path))
		{
			$_time = filemtime($the_file_path);
			if ($time > $_time)
			{
				unlink($the_file_path);
			}
		}

		$url['path'] = $the_file;
		$url['query'] = $time;

		return build_url($url);
	}
}


//--------------------------------------------------------------------
// Variables Fijos
//--------------------------------------------------------------------
if ( ! function_exists('numeros'))
{
	/**
	 * numeros()
	 * Obtener los números
	 *
	 * @param int	$n
	 * @return int|array	 si $n es nulo entonces retorna la lista
	 */
	function numeros($n = NULL)
	{
		$return = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
		if ( ! is_null($n))
		{
			$return = $return[$n];
		}
		return $return;
	}	
}

if ( ! function_exists('letras'))
{
	/**
	 * letras()
	 * Obtener las letras en minúsculas
	 *
	 * @param int	$n
	 * @return string|array	 si $n es nulo entonces retorna la lista
	 */
	function letras($n = NULL)
	{
		$return = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
		if ( ! is_null($n))
		{
			$return = $return[$n];
		}
		return $return;
	}
}

if ( ! function_exists('letras_may'))
{
	/**
	 * letras_may()
	 * Obtener las letras en mayúscula
	 *
	 * @param int
	 * @return mixed
	 */
	function letras_may($n = NULL)
	{
		$return = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
		if ( ! is_null($n))
		{
			$return = $return[$n];
		}
		return $return;
	}
}

if ( ! function_exists('vocales'))
{
	/**
	 * vocales()
	 * Obtener las vocales
	 *
	 * @param int
	 * @return mixed
	 */
	function vocales($n = NULL)
	{
		$return = ['a', 'e', 'i', 'o', 'u'];
		if ( ! is_null($n))
		{
			$return = $return[$n];
		}
		return $return;
	}
}

if ( ! function_exists('tildes'))
{
	/**
	 * tildes()
	 * Obtener las tildes (letras) en minúsculas
	 *
	 * @param int
	 * @return mixed
	 */
	function tildes($n = NULL)
	{
		$return = ['á', 'é', 'í', 'ó', 'ú', 'ñ'];
		if ( ! is_null($n))
		{
			$return = $return[$n];
		}
		return $return;
	}	
}

if ( ! function_exists('tildes_may'))
{
	/**
	 * tildes_may()
	 * Obtener las tildes (letras) en minúsculas
	 *
	 * @param int
	 * @return mixed
	 */
	function tildes_may($n = NULL)
	{
		$return = ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'];
		if ( ! is_null($n))
		{
			$return = $return[$n];
		}
		return $return;
	}	
}

if ( ! function_exists('simbolos'))
{
	/**
	 * simbolos()
	 * Obtener las tildes (letras) en minúsculas
	 *
	 * @param int
	 * @param bool
	 * @return mixed
	 */
	function simbolos($n = NULL, $inc_hard = FALSE)
	{
		$return = ['#', '@', '$', '&', '¿', '?', '¡', '!', '%', '=', '+', '-', '*', '_', '/', '\\', '.', ',', ';', ':', '(', ')', '{', '}', '[', ']', '"', '\'', '<', '>'];
		
		if ($inc_hard)
		{
			$return = array_merge($return, ['°', '~', '|']);
		}
		
		if ( ! is_null($n))
		{
			$return = $return[$n];
		}
		return $return;
	}	
}

if ( ! function_exists('meses'))
{
	/**
	 * meses()
	 * Obtener los meses
	 *
	 * @param string
	 * @param string
	 * @param int
	 * @return mixed
	 */
	function meses($mode = NULL, $n = NULL)
	{
		$return = [NULL, 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Setiembre', 'Octubre', 'Noviembre', 'Diciembre'];

		if (is_null($mode))
		{
			$mode = 'normal';
		}
		
		foreach ($return as &$mes)
		{
			$mes = _t ($mes);
		}
		
		if ($mode == 'min.')
		{
			foreach ($return as &$mes)
			{
				$mes = substr($mes, 0, 3) . '.';
			}
		}
		elseif ($mode == 'min')
		{
			foreach ($return as &$mes)
			{
				$mes = substr($mes, 0, 3);
			}
		}
		
		unset($mes);
		
		if ( ! is_null($n))
		{
			$return = $return[(int)$n];
		}
		return $return;
	}
}

if ( ! function_exists('mes'))
{
	/**
	 * mes()
	 * Obtener un mes
	 *
	 * @param int
	 * @param string
	 * @param string
	 * @return mixed
	 */
	function mes($n, $mode = NULL)
	{
		return meses($mode, $n);
	}	
}

if ( ! function_exists('dias'))
{
	/**
	 * dias()
	 * Obtener los días
	 *
	 * @param string
	 * @param string
	 * @param int
	 * @return mixed
	 */
	function dias($mode = NULL, $n = NULL)
	{
		$return = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
		
		if (is_null($mode))
		{
			$mode = 'normal';
		}
		
		foreach ($return as &$dia)
		{
			$dia = _t ($dia);
		}
		
		if ($mode == 'min.')
		{
			foreach ($return as &$dia)
			{
				$dia = substr($dia, 0, 3) . '.';
			}
		}
		elseif ($mode == 'min')
		{
			foreach ($return as &$dia)
			{
				$dia = substr($dia, 0, 3);
			}
		}
		else
		{
			$return[3] = _t('Miércoles');
		}
		
		unset($dia);
		
		if ( ! is_null($n))
		{
			$return = $return[(int)$n];
		}
		return $return;
	}	
}

if ( ! function_exists('dia'))
{
	/**
	 * dia()
	 * Obtener un día
	 *
	 * @param int
	 * @param string
	 * @param string
	 * @return mixed
	 */
	function dia(int $n, $mode = NULL)
	{
		return dias($mode, $n);
	}	
}

if ( ! function_exists('vTab'))
{
	/**
	 * vTab()
	 * Obtener los caracteres de Tabulación cuantas veces se requiera
	 *
	 * @param int
	 * @return string
	 */
	function vTab (int $n = 1)
	{
		if ($n <= 0)
		{
			return '';
		}
		
		$return = str_repeat("\t", $n);
		return $return;
	}
}
defined('vTab') OR define('vTab', vTab());

if ( ! function_exists('vEnter'))
{
	/**
	 * vEnter()
	 * Obtener los caracteres de Salto de Linea cuantas veces se requiera
	 *
	 * @param int
	 * @return string
	 */
	function vEnter (int $n = 1)
	{
		if ($n <= 0)
		{
			return '';
		}
		
		$return = str_repeat("\n", $n);
		return $return;
	}
}
defined('vEnter') OR define('vEnter',vEnter());

//--------------------------------------------------------------------
// Aleatorios
//--------------------------------------------------------------------
if ( ! function_exists('na'))
{
	/**
	 * na()
	 * Obtener un numero aleatorio
	 *
	 * @param int Cantidad de dígitos
	 * @return string
	 */
	function na($digitos = 1)
	{
		static $int_max_digits = 9;
		
		if ($digitos <= 0)
		{
			return '';
		}
		
		$return = '';
		if ($digitos > $int_max_digits)
		{
			$digitos_extras = $digitos - $int_max_digits;
			$return = na($digitos_extras);
			$digitos -= $digitos_extras;
		}
		
		$rand_min = 1;
		if ($digitos > 1)
		{
			$rand_min = pow(10, $digitos - 1);
		}
		$rand_max = pow(10, $digitos) - 1;
		
		return rand($rand_min, $rand_max) . $return;
	}
}

if ( ! function_exists('la'))
{
	/**
	 * la()
	 * Obtener una letra aleatoria
	 *
	 * @param int Cantidad de dígitos
	 * @param bool $min Incluír mínimos
	 * @param bool $may Incluír mayúsculas
	 * @param bool $tilde Incluír Tildes
	 * @return string
	 */
	function la($digitos = 1, bool $min = TRUE, bool $may = TRUE, bool $tilde = FALSE)
	{
		if ($digitos <= 0)
		{
			return '';
		}
		
		$return = '';
		
		if ( ! $min and ! $may and ! $tilde)
		{
			$min = TRUE;
			$may = TRUE;
		}
		
		$letras = [];
		
		if ($min)
		{
			$letras = array_merge($letras, (array)letras());
		}
		
		if ($may)
		{
			$letras = array_merge($letras, (array)letras_may());
		}
		
		if ($tilde)
		{
			if ($min and ! $may)
			{
				$letras = array_merge($letras, (array)tildes());
			}
			elseif ( ! $min and $may)
			{
				$letras = array_merge($letras, (array)tildes_may());
			}
			else
			{
				$letras = array_merge($letras, (array)tildes(), (array)tildes_may());
			}
		}
		
		for($digito = 1; $digito <= $digitos; $digito++)
		{
			$return .= $letras[rand(0, count($letras) - 1)];
		}
		return $return;
	}
}

if ( ! function_exists('sa'))
{
	/**
	 * sa()
	 * Obtener un símbolo aleatorio
	 *
	 * @param int Cantidad de dígitos
	 * @return string
	 */
	function sa($digitos = 1)
	{
		if ($digitos <= 0)
		{
			return '';
		}
		
		$return = '';
		
		$simbolos = (array)simbolos();
		
		for($digito = 1; $digito <= $digitos; $digito++)
		{
			$return .= $simbolos[rand(0, count($simbolos) - 1)];
		}
		return $return;
	}
}

if ( ! function_exists('cs'))
{
	/**
	 * cs()
	 * Obtener un código seguro aleatorio
	 *
	 * @param int Cantidad de dígitos
	 * @param bool $min Incluír mínimos
	 * @param bool $may Incluír mayúsculas
	 * @param bool $tilde Incluír Tildes
	 * @param bool $num Incluír Números
	 * @param bool $sym Incluír Símbolos
	 * @return string
	 */
	function cs($digitos    = 60, 
				bool $min   = TRUE, 
				bool $may   = TRUE, 
				bool $tilde = FALSE, 
				bool $num   = TRUE, 
				bool $sym   = FALSE, 
				bool $spc   = FALSE)
	{
		if ($digitos <= 0)
		{
			return '';
		}
		
		$return = '';
		
		if ( ! $min AND ! $may AND ! $tilde AND ! $num AND ! $sym)
		{
			$min = TRUE;
			$may = TRUE;
			$num = TRUE;
		}
		
		for($digito = 1; $digito <= $digitos; $digito++)
		{
			switch(rand(1,10))
			{
				case 1:case 4:case 7://Letra
					if ( ! $min AND ! $may AND ! $tilde)
					{
						$digito--;
						break;
					}
					
					$return .= la(1, $min, $may, $tilde);
					break;

				case 2:case 5:case 8://Número
					if ( ! $num)
					{
						$digito--;
						break;
					}
					
					$return .= na(1);
					break;

				case 3:case 6:case 9://Símbolo
					if ( ! $sym)
					{
						$digito--;
						break;
					}
					
					$return .= sa(1);
					break;
				
				case 10://Space
					if ( ! $spc)
					{
						$digito--;
						break;
					}
					
					$return .= ' ';
					break;
					
			}
		}
		
		return $return;
	}
}

if ( ! function_exists('licencia'))
{
	/**
	 * licencia()
	 * Obtener un licencia aleatoria
	 *
	 * @param bool Modo genérico
	 * @return string
	 */
	function licencia($generic = FALSE)
	{
		$licencia = array(
			cs( $generic ? 5 : 6, FALSE, TRUE, FALSE, TRUE, FALSE),
			cs(5, FALSE, TRUE, FALSE, TRUE, FALSE),
			cs(5, FALSE, TRUE, FALSE, TRUE, FALSE),
			cs(5, FALSE, TRUE, FALSE, TRUE, FALSE),
			cs( $generic ? 5 : 9, FALSE, TRUE, FALSE, TRUE, FALSE)
		);
		
		return implode('-', $licencia);
	}
}

if ( ! function_exists('pswd'))
{
	/**
	 * pswd()
	 * Obtener una clave aleatoria
	 *
	 * @param int Cantidad de dígitos
	 * @return string
	 */
	function pswd($digitos = NULL)
	{
		if (is_null($digitos))
		{
			$digitos = rand(10, 15);
		}
		elseif ($digitos > 16)
		{
			$digitos = 16;
		}
		elseif ($digitos < 5)
		{
			$digitos = 5;
		}

		return cs($digitos, TRUE, TRUE, TRUE, TRUE, TRUE);
	}
}

if ( ! function_exists('pswd_percent'))
{
	/**
	 * pswd_percent()
	 * Obtener el porcentaje de seguridad de una clave
	 *
	 * Función pswd -> estadísticas de seguridad buena
	 *  -----------------------	    -----------------------	    -----------------------	    -----------------------
	 *  Dígitos	16	                Dígitos	15	                Dígitos	14					Dígitos	13
	 *  Percent	100.00	            Percent	100.00	            Percent	100.00	            Percent	100.00
	 *  Securit	99.99	            Securit	99.98	            Securit	99.74	            Securit	99.49
	 *  Hacking	16.32	            Hacking	15.95	            Hacking	15.19	            Hacking	14.15
	 *  -----------------------	    -----------------------	    -----------------------	    -----------------------
	 *  Dígitos	12	                Dígitos	11	                Dígitos	10	                Dígitos	9
	 *  Percent	100.00	            Percent	99.90	            Percent	99.85	            Percent	99.35
	 *  Securit	98.02	            Securit	95.33	            Securit	90.21	            Securit	82.89
	 *  Hacking	13.32	            Hacking	12.17	            Hacking	11.91	            Hacking	11.48
	 *  -----------------------	    -----------------------	    -----------------------	    -----------------------
	 *  Dígitos	8	                Dígitos	7					Dígitos	6					Dígitos	5
	 *  Percent	93.80	            Percent	82.65	            Percent	65.65	            Percent	49.90
	 *  Securit	74.06	            Securit	63.24	            Securit	54.09	            Securit	45.71
	 *  Hacking	20.68	            Hacking	20.90	            Hacking	19.75	            Hacking	17.97
	 *  -----------------------	    -----------------------	    -----------------------	    -----------------------
	 */
	function pswd_percent($pswd)
	{
		$pswd = utf8($pswd);
		$pswd_wo_tildes = replace_tildes($pswd);
		
		$calcs = [];
		$calcs['largo']  = mb_strlen($pswd);
		$calcs['mayus']  = mb_strlen(preg_replace('/[^A-Z]/', '', $pswd));
		$calcs['minus']  = mb_strlen(preg_replace('/[^a-z]/', '', $pswd));
		$calcs['numes']  = mb_strlen(preg_replace('/[^0-9]/', '', $pswd));
		$calcs['simbs']  = mb_strlen(preg_replace('/[a-z0-9 ]/i', '', $pswd_wo_tildes));
		
		$calcs['only_letters'] = only_letters($pswd);
		$calcs['only_numbers'] = only_numbers($pswd);
		
		$calcs['num_symb'] = 0;
		$calcs['reqs'] = 0;
		
		$calcs['repetidos'] = 0;//caracteres repetidos
		$calcs['consecutivo_may'] = 0;
		$calcs['consecutivo_min'] = 0;
		$calcs['consecutivo_num'] = 0;
		$calcs['secuencia_let'] = 0;
		$calcs['secuencia_num'] = 0;
		$calcs['palabras'] = 0;//Palabras comunes
		
		extract($calcs, EXTR_REFS);
		
		$num_symb = $numes + $simbs - 1;
		if ($only_letters OR $only_numbers)
		{
			$num_symb--;
		}
		if ($num_symb < 0)
		{
			$num_symb = 0;
		}
		
		if($largo >= 8)
		{
			$reqs++;
		}
        if($mayus>=1)
		{
			$reqs++;
		}
        if($minus>=1)
		{
			$reqs++;
		}
        if($numes>=1)
		{
			$reqs++;
		}
        if($simbs>=1)
		{
			$reqs++;
		}
		
		$letras = (array)letras();
		
		$char_h = ['', '', ''];//Historial
		$char_c = [];//Cantidad
		for ($i=0; $i<=$largo; $i++)
		{
			$char   = mb_substr($pswd, $i, 1);
			$char_l = mb_strtolower($char);
			
			$char_h[0] = $char_h[1];
			$char_h[1] = $char_h[2];
			$char_h[2] = $char;
			
			isset($char_c[$char]) or $char_c[$char_l] = 0;
			$char_c[$char_l]++;
			
			//letras_mayusculas_consecutivas
			if (preg_match('/^[A-Z]$/', $char_h[2]) AND preg_match('/^[A-Z]$/', $char_h[1]))
			{
				$consecutivo_may++;
			}
			
			//letras_minusculas_consecutivas
			if (preg_match('/^[a-z]$/', $char_h[2]) AND preg_match('/^[a-z]$/', $char_h[1]))
			{
				$consecutivo_min++;
			}
			
			//numeros_consecutivos
			if (preg_match('/^[0-9]$/', $char_h[2]) AND preg_match('/^[0-9]$/', $char_h[1]))
			{
				$consecutivo_num++;
			}
			
			
			//secuencia_letras
			if ( ! empty($char_h[0]) AND ! empty($char_h[1]))
			{
				if (preg_match('/^[a-z]$/', mb_strtolower($char_h[2])) AND 
					preg_match('/^[a-z]$/', mb_strtolower($char_h[1])) AND 
					preg_match('/^[a-z]$/', mb_strtolower($char_h[0])))
				{
					$i2 = array_keys($letras, mb_strtolower($char_h[2]), true);
					$i2 = $i2[0]; 
					
					$i1 = array_keys($letras, mb_strtolower($char_h[1]), true);
					$i1 = $i1[0];
					
					$i0 = array_keys($letras, mb_strtolower($char_h[0]), true);
					$i0 = $i0[0];
					
					if( (($i2 - 2) == ($i1 - 1) and ($i1 - 1) == ($i0 - 0)) or (($i2 - 0) == ($i1 - 1) and ($i1 - 1) == ($i0 - 2)) )
					{
						$secuencia_let++;
					}
				}
				
				if (preg_match('/^[0-9]$/', $char_h[2]) AND 
					preg_match('/^[0-9]$/', $char_h[1]) AND 
					preg_match('/^[0-9]$/', $char_h[0]))
				{
					$i2 = (int)$char_h[2];
					$i1 = (int)$char_h[1];
					$i0 = (int)$char_h[0];
					
					if( (($i2 - 2) == ($i1 - 1) and ($i1 - 1) == ($i0 - 0)) or (($i2 - 0) == ($i1 - 1) and ($i1 - 1) == ($i0 - 2)) )
					{
						$secuencia_num++;
					}
				}
			}
		}

		foreach (array_values($char_c) as $cantidad)
		{
			$repetidos += $cantidad * ($cantidad - 1) * 1;
		}

		$pswd_wo_tildes_lower = mb_strtolower($pswd_wo_tildes);
		$pswd_wo_tildes_nonum = mb_strtolower(replace_tildes($pswd, TRUE));

		static $common_words = [
			'password', '123456', '12345678', '2580', '0258', '123456789', '111111', 'iloveyou', '123123', 'adobe123', 'admin', 'root', '1234', 'photoshop', 'shadow', '12345', 'princess', '00000', 'azerty', 'qwerty', 'abc123', 'letmein', 'monkey', 'myspace1', 'password1', 'blink182', 'clave', 'link182', 'dios', 'love', 'god', 'amor'
		];
		$_regex = '/(' . implode('|', $common_words).')/';
		
		preg_match_all($_regex, $pswd_wo_tildes_lower, $matches);
		$palabras += count($matches[0]);
		
		preg_match_all($_regex, $pswd_wo_tildes_nonum, $matches);
		$palabras += count($matches[0]);
		
		$sumas  = 0;
		$restas = 0;
		
		$sumas += ($largo)            * 1;
		$sumas += ($largo - $mayus)   * 2;
		$sumas += ($largo - $minus)   * 2;
		$sumas += ($numes)            * 3;
		$sumas += ($simbs)            * 4;
		$sumas += ($num_symb)         * 3;
		$sumas += ($reqs)             * 2;
		
		$restas += ($only_letters)    * 2;
		$restas += ($only_numbers)    * 2;
		$restas += ($repetidos)       * 3;
		$restas += ($consecutivo_may) * 4;
		$restas += ($consecutivo_min) * 4;
		$restas += ($consecutivo_num) * 5;
		$restas += ($secuencia_let)   * 6;
		$restas += ($secuencia_num)   * 6;
		$restas += ($palabras)        * 8;
		
		$chars_q = (int)$largo;
			if ($chars_q ==  1)
		{
			$chars_q = 16;
		}
		elseif ($chars_q <=  2)
		{
			$chars_q = 8;
		}
		elseif ($chars_q <=  4)
		{
			$chars_q = 4;
		}
		elseif ($chars_q <=  8)
		{
			$chars_q = 2;
		}
		elseif ($chars_q <= 16)
		{
			$chars_q = 1;
		}
		elseif ($chars_q <= 32)
		{
			$chars_q = .5;
		}
		elseif ($chars_q <= 64)
		{
			$chars_q = .25;
		}
		else
		{
			$chars_q = .1;
		}
		
		$kacking = (($chars_q * $restas) / $sumas) * 100;
		if ($kacking > 100)
		{
			$kacking = 100;
		}
		
		$result = 0;
		$result_dats = [
			'seguridad' => number_format($sumas > 100 ? 100 : $sumas, 2),
			'hacking'   => number_format($kacking, 2)
		];
		
		if($result_dats['seguridad'] >= 75)
		{
			if($result_dats['hacking'] <= 35)
			{
				$result = 3;
			}
			elseif($result_dats['hacking'] <= 60)
			{
				$result = 2;
			}
			elseif($result_dats['hacking'] <= 75)
			{
				$result = 1;
			}
			else
			{
				$result = 0;
			}
		}
		elseif($result_dats['seguridad'] >= 60)
		{
			if($result_dats['hacking'] <= 25)
			{
				$result = 2;
			}elseif($result_dats['hacking'] <= 50)
			{
				$result = 1;
			}elseif($result_dats['hacking'] <= 65)
			{
				$result = 0;
			}else
			{
				$result = -1;
			}
		}
		elseif($result_dats['seguridad'] >= 45)
		{
			if($result_dats['hacking'] <= 15)
			{
				$result = 1;
			}elseif($result_dats['hacking'] <= 40)
			{
				$result = 0;
			}elseif($result_dats['hacking'] <= 55)
			{
				$result = -1;
			}else{
				$result = -2;
			}
		}
		elseif($result_dats['seguridad'] >= 35)
		{
			if($result_dats['hacking'] <= 5)
			{
				$result = 0;
			}elseif($result_dats['hacking'] <= 30)
			{
				$result = -1;
			}elseif($result_dats['hacking'] <= 45)
			{
				$result = -2;
			}else{
				$result = -3;
			}
		}
		else
		{
			$result = -3;
		}
		
		$return = [
			'result' => $result,
			'result_dats' => $result_dats,
//			'detalle' => [
//				'sumas' => $sumas,
//				'restas' => $restas,
//				'calcs' => $calcs,
//			],
//			'pswd' => $pswd
		];
		
		return $return;
	}
}
