<?php
/**
 * /JApi/configs/functions/Helpers.php
 * Funciones de apoyo
 * @filesource
 */

defined('HOMEPATH') or exit('Archivo no se puede llamar directamente');

if ( ! function_exists('print_array'))
{
	/**
	 * print_array()
	 * Muestra los contenidos enviados en el parametro para mostrarlos en HTML
	 *
	 * @param	...array
	 * @return	void
	 */
	function print_array(...$array)
	{
		$r = '';

		$trace = debug_backtrace(false);
		while(count($trace) > 0 and isset($trace[0]['file']) and $trace[0]['file'] === __FILE__)
		{
			array_shift($trace);
		}

		$file_line = '';
		isset($trace[0]) and 
		$file_line = '<small style="color: #ccc;display: block;margin: 0;">' . $trace[0]['file'] . ' #' . $trace[0]['line'] . '</small><br>';

		if (count($array) === 0)
		{
			$r.= '<small style="color: #888">[SIN PARAMETROS]</small>';
		}
		else
		foreach ($array as $ind => $_arr)
		{
			if (is_null($_arr))
			{
				$_arr = '<small style="color: #888">[NULO]</small>';
			}
			elseif (is_string($_arr) and empty($_arr))
			{
				$_arr = '<small style="color: #888">[VACÍO]</small>';
			}
			elseif (is_bool($_arr))
			{
				$_arr = '<small style="color: #888">['.($_arr?'TRUE':'FALSE').']</small>';
			}
			elseif (is_array($_arr) and function_exists('array_html'))
			{
				$_arr = array_html($_arr);
			}
			else
			{
				$_arr = htmlentities(print_r($_arr, true));
			}

			$r.= ($ind > 0 ? '<hr style="border: none;border-top: dashed #ebebeb .5px;margin: 12px 0;">' : '') . $_arr;
		}

		load_inline_css('.debug-info-print-array { display: block;text-align: left;color: #444;background: white;position: relative;z-index: 99999999999;margin: 5px 5px 15px;padding: 0px 10px 10px;border: solid 1px #ebebeb;box-shadow: 4px 4px 4px rgba(235, 235, 235, .5); }');

		echo '<pre class="debug-info-print-array">' . $file_line . $r . '</pre>' . PHP_EOL;
	}
}

if ( ! function_exists('print_r2'))
{
	/**
	 * print_r2()
	 * @see print_array
	 */
	function print_r2(...$array)
	{
		return call_user_func_array('print_array', $array);
	}
}

if ( ! function_exists('die_array'))
{
	/**
	 * die_array()
	 * Muestra los contenidos enviados en el parametro para mostrarlos en HTML y finaliza los segmentos
	 *
	 * @param	...array
	 * @return	void
	 */
	function die_array(...$array)
	{
		call_user_func_array('print_array', $array);
		die();
	}
}

if ( ! function_exists('array_html'))
{
	/**
	 * array_html()
	 * Convierte un Array en un formato nestable para HTML
	 *
	 * @param array $arr Array a mostrar
	 * @return string
	 */
	function array_html (array $arr, $lvl = 0)
	{
		static $_instances = 0;

		$lvl = (int)$lvl;

		$lvl_child = $lvl + 1 ;
		$str = [];

		$lvl===0 and $str[] = '<div class="array_html" id="array_html_'.(++$_instances).'">';

		$str[] = '<ol data-lvl="'.($lvl).'" class="array'.($lvl>0?' child':'').'">';

		foreach ($arr as $key => $val)
		{
			$hash = md5(json_encode([$lvl, $key]));
			$ctype = gettype($val);
			$class = $ctype ==='object' ? get_class($val) : $ctype;

			$_str = '';

			$_str.= '<li class="detail" data-hash="' . htmlspecialchars($hash) . '">';
			$_str.= '<span class="key'.(is_numeric($key)?' num':'').(is_integer($key)?' int':'').'">';
			$_str.= $key;
			$_str.= '<small class="info">'.$class.'</small>';
			$_str.= '</span>';
			
			if ( $ctype === 'object')
			{
				$asarr = NULL;
				foreach(['getArrayCopy', 'toArray', '__toArray'] as $f)
				{
					if (method_exists($val, $f))
					{
						try
						{
							$t = call_user_func([$val, $f]);
							if( ! is_array($t))
							{
								throw new Exception('No es Array');
							}
							$asarr = $t;
						}
						catch(Exception $e)
						{}
					}
				}
				is_null($asarr) or $val = $asarr;
			}
			
			if (is_array($val))
			{
				$_str .= array_html($val, $lvl_child);
			}
			
			elseif ( $ctype === 'object')
			{
				$_str.= '<pre data-lvl="'.$lvl_child.'" class="'.$ctype.' child'.($ctype === 'object' ? (' ' . $class) : '').'">';
				$_str.= htmlentities(print_r($val, true));
				$_str.= '</pre>';
			}
			else
			{
				$_str.= '<pre data-lvl="'.$lvl_child.'" class="'.$ctype.' child-inline">';
				if (is_null($val))
				{
					$_str.= '<small style="color: #888">[NULO]</small>';
				}
				elseif (is_string($val) and empty($val))
				{
					$_str.= '<small style="color: #888">[VACÍO]</small>';
				}
				elseif (is_bool($val))
				{
					$_str.= '<small style="color: #888">['.($val?'TRUE':'FALSE').']</small>';
				}
				else
				{
					$_str.= htmlentities(print_r($val, true));
				}
				$_str.= '</pre>';
			}

			$str[] = $_str;
		}

		$str[] = '</ol>';

		$lvl===0 and $str[] = '</div>';

		$str = implode('', $str);

		$_instances === 1 and $lvl === 0 and 
		load_inline_css(
			'.array_html {display: block;text-align: left;color: #444;background: white;position:relative}'.
			'.array_html * {margin:0;padding:0}'.
			'.array_html .array {list-style: none;margin: 0;padding: 0;}'.
			'.array_html .array .array {margin: 10px 0 10px 10px;}'.
			'.array_html .key {padding: 5px 10px;display:block;border-bottom: solid 1px #ebebeb}'.
			'.array_html .detail {display: block;border: solid 1px #ebebeb;margin: 0 0 0;}'.
			'.array_html .detail + .detail {margin-top: 10px}'.
			'.array_html .array .array .detail {border-right: none}'.
			'.array_html .child:not(.array), .array_html .child-inline {padding:10px}'.
			'.array_html .info {color: #ccc;float: right;margin: 4px 0 4px 4px;user-select:none}'.
			'.array_html.js .detail.has-child:not(.open)>.child {display:none}'.
			'.array_html.js .detail.has-child:not(.open)>.key {border-bottom:none}'.
			'.array_html.js .detail.has-child>.key {cursor:pointer}'.
			'.array_html.js .detail.has-child:before {content: "▼";float: left;padding: 5px;color: #ccc;}'.
			'.array_html.js .detail.has-child.open:before {content: "▲";}'
		);

		$lvl === 0 and 
		load_inline_js(';(function(){'.
			'var div = document.getElementById("array_html_'.$_instances.'");'.
			'var open = function(e){if(e.defaultPrevented){return;};var t = e.target;if(/info/.test(t.classList)){t = t.parentElement;};if(!(/key/.test(t.classList))){return;};t.parentElement.classList.toggle("open");e.preventDefault()};'.
			'div.classList.add("js");'.
			'div.querySelectorAll(".child").forEach(function(d){var p = d.parentElement, c = p.classList;c.add("has-child");c.add("open");p.onclick = open;});'.
		'}());');

		return $str;
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

if ( ! function_exists('regex'))
{
	/**
	 * regex()
	 * Permite convertir un string para ser analizado como REGEXP
	 *
	 * @param string $str String a convertir en REGEXable
	 * @return string
	 */
	function regex ($str)
	{
		/** Caractéres que son usables */
		static $chars = ['/','.','*','+','?','|','(',')','[',']','{','}','\\','$','^','-'];
		$_regex = '/(\\' . implode('|\\', $chars).')/';
		return preg_replace($_regex, "\\\\$1", $str);
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

if ( ! function_exists('url_post'))
{
	function url_post ($url, $data = [])
	{
		$data = http_build_query($data);
		$opts = [
			'http' => [
				'method' => 'POST',
				'header' => 'Content-type: application/x-www-form-urlencoded',
				'content' => $data,
			],
		];
		
		$context = stream_context_create($opts);
		return file_get_contents($url, FALSE, $context);
	}
}

if ( ! function_exists('url_get'))
{
	function url_get ($url, $data = [])
	{
		$data = http_build_query($data);
		$opts = [
			'http' => [
				'method' => 'GET',
				'content' => $data,
			],
		];
		
		$context = stream_context_create($opts);
		return file_get_contents($url, FALSE, $context);
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
	function js_compressor ($content = '', $arr = [])
	{
		$arr = array_merge([
			'cache' => FALSE,
			'cachetime' => NULL, 
			'use_apiminifier' => FALSE,
		], $arr);
		extract($arr);

		if (is_empty($content))
		{
			return $content;
		}

		if ($cache !== FALSE)
		{
			$cache_file = APPPATH . '/.cache/js/' . ($cache !== TRUE ? $cache : md5($content)) . '.js';
			mkdir2(dirname($cache_file), APPPATH);
			
			if($cache !== TRUE and ! is_null($cachetime) and (time() - filemtime($cache_file)) >= $cachetime)
			{
				unlink($cache_file);
			}

			if (file_exists($cache_file))
			{
				return file_get_contents($cache_file);
			}
		}

		try
		{
			$temp = (new MatthiasMullie\Minify\JS($content))->minify();
			$content = $temp;
		}
		catch (Exception $e)
		{}

		try
		{
			if ($use_apiminifier)
			{
				$temp = url_post('https://javascript-minifier.com/raw', array('input' => (string)$content));

				if (preg_match('/^\/\/ Error/i', $temp))
				{
					throw new Exception($temp, 0, ['code' => (string)$content]);
				}

				if ($temp === FALSE or is_empty($temp))
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

		isset($cache_file) and file_put_contents($cache_file, $content);
		return $content;
	}
}

if ( ! function_exists('css_compressor'))
{
	function css_compressor ($content = '', $arr = [])
	{
		$arr = array_merge([
			'cache' => FALSE,
			'cachetime' => NULL, 
			'use_apiminifier' => FALSE,
		], $arr);
		extract($arr);

		if (is_empty($content))
		{
			return $content;
		}

		if ($cache !== FALSE)
		{
			$cache_file = APPPATH . '/.cache/css/' . ($cache !== TRUE ? $cache : md5($content)) . '.css';
			mkdir2(dirname($cache_file), APPPATH);
			
			if($cache !== TRUE and ! is_null($cachetime) and (time() - filemtime($cache_file)) >= $cachetime)
			{
				unlink($cache_file);
			}

			if (file_exists($cache_file))
			{
				return file_get_contents($cache_file);
			}
		}

		try
		{
			$temp = (new MatthiasMullie\Minify\CSS($content))->minify();
			$content = $temp;
		}
		catch (Exception $e)
		{}

		try
		{
			if ($use_apiminifier)
			{
				$temp = url_post('https://cssminifier.com/raw', array('input' => (string)$content));
			
				if (preg_match('/^\/\/ Error/', $temp))
				{
					throw new Exception($temp, 0, ['code' => (string)$content]);
				}

				if ($temp === FALSE or is_empty($temp))
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

		isset($cache_file) and file_put_contents($cache_file, $content);
		return $content;
	}

}

if ( ! function_exists('json_compressor'))
{
	function json_compressor ($content = '')
	{
		if (is_empty($content))
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

if ( ! function_exists('replace_tildes'))
{
	function replace_tildes($str, $numbers_to_letters = FALSE)
	{
		$foreign_characters = [
			'/ä|æ|ǽ/' => 'ae',
			'/ö|œ/' => 'oe',
			'/ü/' => 'ue',
			'/Ä/' => 'Ae',
			'/Ü/' => 'Ue',
			'/Ö/' => 'Oe',
			'/À|Á|Â|Ã|Ä|Å|Ǻ|Ā|Ă|Ą|Ǎ|Α|Ά|Ả|Ạ|Ầ|Ẫ|Ẩ|Ậ|Ằ|Ắ|Ẵ|Ẳ|Ặ|А/' => 'A',
			'/à|á|â|ã|å|ǻ|ā|ă|ą|ǎ|ª|α|ά|ả|ạ|ầ|ấ|ẫ|ẩ|ậ|ằ|ắ|ẵ|ẳ|ặ|а/' => 'a',
			'/Б/' => 'B',
			'/б/' => 'b',
			'/Ç|Ć|Ĉ|Ċ|Č/' => 'C',
			'/ç|ć|ĉ|ċ|č/' => 'c',
			'/Д/' => 'D',
			'/д/' => 'd',
			'/Ð|Ď|Đ|Δ/' => 'Dj',
			'/ð|ď|đ|δ/' => 'dj',
			'/È|É|Ê|Ë|Ē|Ĕ|Ė|Ę|Ě|Ε|Έ|Ẽ|Ẻ|Ẹ|Ề|Ế|Ễ|Ể|Ệ|Е|Э/' => 'E',
			'/è|é|ê|ë|ē|ĕ|ė|ę|ě|έ|ε|ẽ|ẻ|ẹ|ề|ế|ễ|ể|ệ|е|э/' => 'e',
			'/Ф/' => 'F',
			'/ф/' => 'f',
			'/Ĝ|Ğ|Ġ|Ģ|Γ|Г|Ґ/' => 'G',
			'/ĝ|ğ|ġ|ģ|γ|г|ґ/' => 'g',
			'/Ĥ|Ħ/' => 'H',
			'/ĥ|ħ/' => 'h',
			'/Ì|Í|Î|Ï|Ĩ|Ī|Ĭ|Ǐ|Į|İ|Η|Ή|Ί|Ι|Ϊ|Ỉ|Ị|И|Ы/' => 'I',
			'/ì|í|î|ï|ĩ|ī|ĭ|ǐ|į|ı|η|ή|ί|ι|ϊ|ỉ|ị|и|ы|ї/' => 'i',
			'/Ĵ/' => 'J',
			'/ĵ/' => 'j',
			'/Ķ|Κ|К/' => 'K',
			'/ķ|κ|к/' => 'k',
			'/Ĺ|Ļ|Ľ|Ŀ|Ł|Λ|Л/' => 'L',
			'/ĺ|ļ|ľ|ŀ|ł|λ|л/' => 'l',
			'/М/' => 'M',
			'/м/' => 'm',
			'/Ñ|Ń|Ņ|Ň|Ν|Н/' => 'N',
			'/ñ|ń|ņ|ň|ŉ|ν|н/' => 'n',
			'/Ò|Ó|Ô|Õ|Ō|Ŏ|Ǒ|Ő|Ơ|Ø|Ǿ|Ο|Ό|Ω|Ώ|Ỏ|Ọ|Ồ|Ố|Ỗ|Ổ|Ộ|Ờ|Ớ|Ỡ|Ở|Ợ|О/' => 'O',
			'/ò|ó|ô|õ|ō|ŏ|ǒ|ő|ơ|ø|ǿ|º|ο|ό|ω|ώ|ỏ|ọ|ồ|ố|ỗ|ổ|ộ|ờ|ớ|ỡ|ở|ợ|о/' => 'o',
			'/П/' => 'P',
			'/п/' => 'p',
			'/Ŕ|Ŗ|Ř|Ρ|Р/' => 'R',
			'/ŕ|ŗ|ř|ρ|р/' => 'r',
			'/Ś|Ŝ|Ş|Ș|Š|Σ|С/' => 'S',
			'/ś|ŝ|ş|ș|š|ſ|σ|ς|с/' => 's',
			'/Ț|Ţ|Ť|Ŧ|τ|Т/' => 'T',
			'/ț|ţ|ť|ŧ|т/' => 't',
			'/Þ|þ/' => 'th',
			'/Ù|Ú|Û|Ũ|Ū|Ŭ|Ů|Ű|Ų|Ư|Ǔ|Ǖ|Ǘ|Ǚ|Ǜ|Ũ|Ủ|Ụ|Ừ|Ứ|Ữ|Ử|Ự|У/' => 'U',
			'/ù|ú|û|ũ|ū|ŭ|ů|ű|ų|ư|ǔ|ǖ|ǘ|ǚ|ǜ|υ|ύ|ϋ|ủ|ụ|ừ|ứ|ữ|ử|ự|у/' => 'u',
			'/Ƴ|Ɏ|Ỵ|Ẏ|Ӳ|Ӯ|Ў|Ý|Ÿ|Ŷ|Υ|Ύ|Ϋ|Ỳ|Ỹ|Ỷ|Ỵ|Й/' => 'Y',
			'/ẙ|ʏ|ƴ|ɏ|ỵ|ẏ|ӳ|ӯ|ў|ý|ÿ|ŷ|ỳ|ỹ|ỷ|ỵ|й/' => 'y',
			'/В/' => 'V',
			'/в/' => 'v',
			'/Ŵ/' => 'W',
			'/ŵ/' => 'w',
			'/×/' => 'x',
			'/Ź|Ż|Ž|Ζ|З/' => 'Z',
			'/ź|ż|ž|ζ|з/' => 'z',
			'/Æ|Ǽ/' => 'AE',
			'/ß/' => 'ss',
			'/Ĳ/' => 'IJ',
			'/ĳ/' => 'ij',
			'/Œ/' => 'OE',
			'/ƒ/' => 'f',
			'/ξ/' => 'ks',
			'/π/' => 'p',
			'/β/' => 'v',
			'/μ/' => 'm',
			'/ψ/' => 'ps',
			'/Ё/' => 'Yo',
			'/ё/' => 'yo',
			'/Є/' => 'Ye',
			'/є/' => 'ye',
			'/Ї/' => 'Yi',
			'/Ж/' => 'Zh',
			'/ж/' => 'zh',
			'/Х/' => 'Kh',
			'/х/' => 'kh',
			'/Ц/' => 'Ts',
			'/ц/' => 'ts',
			'/Ч/' => 'Ch',
			'/ч/' => 'ch',
			'/Ш/' => 'Sh',
			'/ш/' => 'sh',
			'/Щ/' => 'Shch',
			'/щ/' => 'shch',
			'/Ъ|ъ|Ь|ь/' => '',
			'/Ю/' => 'Yu',
			'/ю/' => 'yu',
			'/Я/' => 'Ya',
			'/я/' => 'ya',
			
			'/@/' => 'a',
			'/¢|©/' => 'c',
			'/€|£/' => 'E',
			'/ⁿ/' => 'n',
			'/°/' => 'o',
			'/¶|₧/' => 'P',
			'/®/' => 'R',
			'/\$/' => 'S',
			'/§/' => 's',
			'/¥/' => 'Y',
			'/&/' => 'y',
			
			'/¹/' => $numbers_to_letters ? 'I' : '1',
			'/²/' => $numbers_to_letters ? 'S' : '2',
			'/³/' => $numbers_to_letters ? 'E' : '3'
		];

		if ($numbers_to_letters)
		{
			$foreign_characters['/1/'] = 'I';
			$foreign_characters['/2/'] = 'S';
			$foreign_characters['/3/'] = 'E';
			$foreign_characters['/4/'] = 'A';
			$foreign_characters['/5/'] = 'S';
			$foreign_characters['/6/'] = 'G';
			$foreign_characters['/7/'] = 'T';
			$foreign_characters['/8/'] = 'B';
			$foreign_characters['/9/'] = 'g';
			$foreign_characters['/0/'] = 'O';
		}

		$array_from = array_keys($foreign_characters);
		$array_to   = array_values($foreign_characters);

		return preg_replace($array_from, $array_to, $str);
	}
}

if ( ! function_exists('strtoslug'))
{
	function strtoslug ($str, $separator = '-', $_allows = ['.', '-', '_']){
		$slug = $str;

		if(is_empty($_allows))
		{
			$_allows = [];
		}

		$_allows = (array)$_allows;
		$_allows[] = $separator;

		$slug = replace_tildes($slug);
		foreach((array)simbolos(NULL, TRUE) as $char)
		{
			$slug = reduce_multiples($slug, $char);
		}

		if (UTF8_ENABLED)
		{
			$slug = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
		}

		$slug = mb_strtolower($slug);

		$_regex = '[^a-z0-9' . implode('', array_map('regex', $_allows)) . ']';

		$slug = preg_replace('#' . $_regex . '#', $separator, $slug);

		foreach($_allows as $char)
		{
			$slug = reduce_multiples($slug, $char);
			$slug = trim($slug, $char);
		}

		return $slug;
	}
}

if ( ! function_exists('strtocapitalize'))
{
	function strtocapitalize ($str = '')
	{
		return ucwords(mb_strtolower($str));
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

if ( ! function_exists('get_mime'))
{
	/**
	 * get_mime()
	 * Obtiene la información de una extensión
	 *
	 * @return Mixed
	 */
	function get_mime ($ext)
	{
		return FileMimes::instance() 
		-> consulta($ext);
	}
}

if ( ! function_exists('filemime'))
{
	/**
	 * filemime()
	 * Obtiene el mime de un archivo indistinto a la extensión que tenga
	 *
	 * @param string
	 * @return string
	 */
	function filemime ($file)
	{
		static $_use_finfo = FALSE;
		
		if ( ! $_use_finfo OR ! file_exists('/usr/share/misc/magic'))
		{
			return mime_content_type($file);
		}
		
		try
		{
			$finfo = finfo_open(FILEINFO_MIME, '/usr/share/misc/magic');
			if ( ! $finfo)
			{
				throw new Exception('! FINFO');
			}
			
			$mime = finfo_file($finfo, $file);
			finfo_close($finfo);
			return $mime;
		}
		catch(Exception $e)
		{
			$_use_finfo = FALSE;
			return mime_content_type($file);
		}
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
	function download($filename = '', $data = null, $set_mime = FALSE)
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
			$mime = get_mime($extension);
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

		ResponseAs('file');

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
		}

		fclose($fp);
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
	function encrypt(string $message, string $key)
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
	function decrypt(string $encrypted, string $key)
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
	function  convertir_tiempo($seg, $return = 'array', $inverted = true, 
							   $txtplu = array('segundos', 'minutos', 'horas', 'dias', 'semanas', 'meses', 'años'), 
							   $txtsing = array('segundo', 'minuto', 'hora', 'dia', 'semana', 'mes', 'año') ){
		$r = array('sg'=>round($seg));

		$r['mi'] = floor($r['sg']/60); $r['sg'] -= $r['mi']*60;
		$r['ho'] = floor($r['mi']/60); $r['mi'] -= $r['ho']*60;
		$r['di'] = floor($r['ho']/24); $r['ho'] -= $r['di']*24;
		$r['se'] = floor($r['di']/7 ); $r['di'] -= $r['se']*7 ;
		$r['me'] = floor($r['se']/4 ); $r['se'] -= $r['me']*4 ;
		$r['añ'] = floor($r['me']/12); $r['me'] -= $r['añ']*12;

		$obl = false;

		if ($r['añ']<>0 or $obl) $obl = true;
		$r['añ'] = array($r['añ'], $r['añ']==1?$txtsing[6]:$txtplu[6], $obl);

		if ($r['me']<>0 or $obl) $obl = true;
		$r['me'] = array($r['me'], $r['me']==1?$txtsing[5]:$txtplu[5], $obl);

		if ($r['se']<>0 or $obl) $obl = true;
		$r['se'] = array($r['se'], $r['se']==1?$txtsing[4]:$txtplu[4], $obl);

		if ($r['di']<>0 or $obl) $obl = true;
		$r['di'] = array($r['di'], $r['di']==1?$txtsing[3]:$txtplu[3], $obl);

		if ($r['ho']<>0 or $obl) $obl = true;
		$r['ho'] = array($r['ho'], $r['ho']==1?$txtsing[2]:$txtplu[2], $obl);

		if ($r['mi']<>0 or $obl) $obl = true;
		$r['mi'] = array($r['mi'], $r['mi']==1?$txtsing[1]:$txtplu[1], $obl);

		if ($r['sg']<>0 or $obl) $obl = true;    
		$r['sg'] = array($r['sg'], $r['sg']==1?$txtsing[0]:$txtplu[0], $obl);

		if($inverted){
			$r = array_merge(array('añ'=>array(), 'me'=>array(), 'se'=>array(), 'di'=>array(), 'ho'=>array(), 'mi'=>array(), 'sg'=>array() ), $r);
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
	function transform_size( $tam ){
		$tam = round($tam);
		$tb  = floor($tam/(1024*1024*1024*1024)); $tam-=($tb*(1024*1024*1024*1024));
		$gb  = floor($tam/(1024*1024*1024) ); $tam-=($gb*(1024*1024*1024));
		$mb  = floor($tam/(1024*1024) ); $tam-=($mb*(1024*1024));
		$kb  = floor($tam/(1024) ); $tam-=($kb*(1024));
		$b   = $tam;

		$r   = '';
		if ($tb<>0) $r .= ($r<>''?' ':'').$tb.' TB';
		if ($gb<>0 or $tb<>0) $r .= ($r<>''?' ':'').$gb.' GB';
		if ($mb<>0 or $gb<>0 or $tb<>0) $r .= ($r<>''?' ':'').$mb.' MB';
		if ($kb<>0 or $mb<>0 or $gb<>0 or $tb<>0) $r .= ($r<>''?' ':'').$kb.' KB';
		if ($b <>0 or $kb<>0 or $mb<>0 or $gb<>0 or $tb<>0) $r .= ($r<>''?' ':''). $b.' B';

		return $r;
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
		$externo = ! preg_match('#'.regex(url('host-base')).'#i', $src);

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
