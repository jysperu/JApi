<?php

class ImgManager
{
	public static function GetParamsFromName (&$file_name)
	{
		$opt = [
			'size'    => NULL,
			'crop'    => NULL,
			'offset'  => NULL,

			'quality' => NULL,
		];

		extract($opt, EXTR_REFS);

		// Buscar parametro de Quality en el nombre
		$opts_in_name = explode('@', $file_name);
		$opts_in_name_base = array_shift($opts_in_name);//Elimino el primero porque corresponde al nombre

		if (count($opts_in_name) > 0)
		{
			foreach($opts_in_name as $ind => $par)
			{
				if ( ! preg_match('#^[0-4]X$#i', $par))
				{
					// Valores autorizados:
					// @0X  ~ verylow
					// @1X  ~ low
					// @2X  ~ normal
					// @3X  ~ hight
					// @4X  ~ veryhight
					continue;
				}
				
				if (is_null($opt['quality']))
				{
					$opt['quality'] = $par;
				}
				
				unset($opts_in_name[$ind]);
			}
			
			$file_name = $opts_in_name_base;
			if (count($opts_in_name) > 0)
			{
				$file_name .= '@' . implode('@', $opts_in_name);
			}
		}

		// Buscar parametro no Quality en el nombre
		$opts_in_name = explode('.', $file_name);
		$opts_in_name_base = array_shift($opts_in_name);//Elimino el primero porque corresponde al nombre

		if (count($opts_in_name) > 0)
		{
			foreach($opts_in_name as $ind => $par)
			{
				if ( ! preg_match('#^(is([0-9]+)X([0-9]+)|is([0-9]+)|ic[0-1]|io([0-9]+)X([0-9]+))$#i', $par))
				{
					// Valores autorizados:
					// is1234X4321
					// ic1	ic0
					// io12X0
					continue;
				}
				
				switch(mb_substr($par, 0, 2))
				{
					case 'is':
						if (is_null($opt['size']))
						{
							$opt['size'] = mb_substr($par, 2);
						}
						break;
					case 'ic':
						if (is_null($opt['crop']))
						{
							$opt['crop'] = (bool)(int)mb_substr($par, 2, 1);
						}
						break;
					case 'io':
						if (is_null($opt['offset']))
						{
							$opt['offset'] = mb_substr($par, 2);
						}
						break;
				}
				
				unset($opts_in_name[$ind]);
			}
			
			$file_name = $opts_in_name_base;
			if (count($opts_in_name) > 0)
			{
				$file_name .= '.' . implode('.', $opts_in_name);
			}
		}

		foreach(array_keys($opt) as $opt_name)
		{
			if (isset($src_params[$opt_name]))
			{
				if (is_null($opt[$opt_name]))
				{
					$opt[$opt_name] = $src_params[$opt_name];
				}
			}
		}

		return $opt;
	}

	public static function GetParamsUri (&$opt, $real_file = null)
	{
		$opt = array_merge([
			'size'    => NULL,
			'crop'    => NULL,
			'offset'  => NULL,

			'quality' => NULL,
		], $opt);

		extract($opt, EXTR_REFS);

		$file_size = [1, 1];
		try
		{
			! is_null($real_file) and file_exists($real_file) and $file_size = getimagesize($real_file);
		}
		catch(Exception $e)
		{}

		/**
		 * Obtener datos de las opciones
		 */
		// Verificar las OPCIONES
		IF (is_null($size))
		{
			//Obteners el tamaño del archivo original
			$size = [$file_size[0], $file_size[1]];
		}
		
		IF (is_string($size))
		{
			//Obteners el tamaño del archivo original
			$size = preg_split('/x/i', $size, 2);
		}
		
		IF (is_numeric($size))
		{
			//Obteners el tamaño del archivo original
			$size = [$size, $size];
		}

		$size = (array)$size;

		if ( ! isset($size[1]))
		{
			$size[1] = $file_size[1];
		}

		if ((int)$size[0] === 0)
		{
			$file_size_1 = $file_size[1];
			$file_size_1 === 0 and $file_size_1 = 1;
			$size[0] = $file_size[0] * $size[1] / $file_size[1];
		}

		if ((int)$size[1] === 0)
		{
			$file_size_0 = $file_size[0];
			$file_size_0 === 0 and $file_size_0 = 1;
			$size[1] = $file_size[1] * $size[0] / $file_size_0;
		}

		if (preg_match('#^(\*{0,1})([0-9\.]+)\%$#', $size[0], $matches))
		{
			//obtener el porcentaje del width original
			$percent = (double)$matches[2];
			
			$width = $file_size[0];
			if ($matches[1] == '*')
			{
				if (preg_match('#^(\*{0,1})([0-9\.]+)\%$#', $size[1], $matches_temp))
				{
					//obtener el porcentaje del width original
					$percent_temp = (double)$matches_temp[2];

					$height = $file_size[1];
					if ($matches_temp[1] == '*')
					{
						throw new Exception('No pueden haber dos * en los valores del Tamaño de imagen');
					}

					$size[1] = $height * $percent_temp / 100;
				}
				
				$size[1] = (int) $size[1];

				if ($size[1] == 0)
				{
					$size[1] = $file_size[1];
				}
				
				$width = $size[1] * $file_size[1] / $width;
			}
			
			$size[0] = $width * $percent / 100;
		}
		
		$size[0] = (int) $size[0];
		
		if ($size[0] == 0)
		{
			$size[0] = $file_size[0];
		}
		
		if (preg_match('#^(\*{0,1})([0-9\.]+)\%$#', $size[1], $matches))
		{
			//obtener el porcentaje del width original
			$percent = (double)$matches[2];
			
			$height = $file_size[1];
			if ($matches[1] == '*')
			{
				$height = $size[0] * $file_size[0] / $height;
			}
			
			$size[1] = $height * $percent / 100;
		}
		
		$size[1] = (int) $size[1];
		
		if ($size[1] == 0)
		{
			$size[1] = $file_size[1];
		}

		if (is_null($crop))
		{
			$crop = FALSE;
		}
		
		if (is_null($offset))
		{
			$offset = [0, 0];
		}
		
		IF (is_string($offset))
		{
			//Obteners el tamaño del archivo original
			$offset = preg_split('/x/i', $offset, 2);
		}
		
		IF (is_numeric($offset))
		{
			//Obteners el tamaño del archivo original
			$offset = [$offset, $offset];
		}
		
		$offset = (array)$offset;
		
		if ( ! isset($offset[1]))
		{
			$offset[1] = 0;
		}
		
		if (preg_match('#^([0-9\.]+)\%$#', $offset[0], $matches))
		{
			//obtener el porcentaje del width original
			$percent = (double)$matches[1];
			
			$width = $size[0];
			
			$offset[0] = $width * $percent / 100;
		}
		
		$offset[0] = (int) $offset[0];
		
		if (preg_match('#^([0-9\.]+)\%$#', $offset[1], $matches))
		{
			//obtener el porcentaje del width original
			$percent = (double)$matches[1];
			
			$height = $size[1];
			
			$offset[1] = $height * $percent / 100;
		}
		
		$offset[1] = (int) $offset[1];
		
		IF (is_null($quality))
		{
			$quality = '1X';
		}

		$quality = mb_strtoupper($quality);

		/**
		 * Formando la ruta del nuevo archivo
		 */
		$opt_uri = '';
		$opt_uri.= '.is' . implode('x', $size);
		
		if ($crop)
		{
			$opt_uri .= '.ic1';
		}
		
		if ($offset[0] > 0 and $offset[1] > 0)
		{
			$opt_uri .= '.io' . implode('x', $offset);
		}
		
		if ($quality <> '1X')
		{
			$opt_uri .= '@' . floatval($quality) .'X';
		}

		return $opt_uri;
	}

	public static function GenerateImage ($real_file, $output_file, $opt = [])
	{
		$opt = array_merge([
			'size'    => NULL,
			'crop'    => NULL,
			'offset'  => NULL,

			'quality' => NULL,
		], $opt);

		mkdir2(dirname($output_file));

		$file_name = basename($real_file);
		$file_name = explode('.', $file_name);
		$file_ext  = count($file_name) > 1 ? array_pop($file_name) : '';

		$extension = FileMimes::instance() -> consulta($file_ext);

		try
		{
			$mime = filemime($real_file);
			$extension_tmp = FileMimes::instance() -> getExtensionByMime($mime);

			if ($extension_tmp === NULL)
			{
				throw new Exception('Extensión no encontrada');
			}

			$extension = $extension_tmp();
		}
		catch(Exception $e)
		{}

		$file_ext = $extension['ext'];

		$file_size = [1, 1];
		try
		{
			$file_size = getimagesize($real_file);
		}
		catch(Exception $e)
		{}

		$color_trans = 127;
		switch($file_ext)
		{
			case "jpeg":
			case "jpg": $src_image = imagecreatefromjpeg($real_file);$color_trans=0;break;
			case "png": $src_image = imagecreatefrompng($real_file);break;
			case "bmp": $src_image = imagecreatefrombmp($real_file);break;
			case "gif": $src_image = imagecreatefromgif($real_file);break;
			default   : $src_image = imagecreatefromgd($real_file);break;
		}

		$size_s   = [$opt['size'][0], $opt['size'][1]];
		$size     = ['width' => $opt['size'][0], 'heigth' => $opt['size'][1]];
		$size_src = ['width' => $file_size[0], 'heigth' => $file_size[1], 'x' => 0, 'y' => 0];

		$width    = $opt['size'][0];
		
		$esc      = $size['width'] / $width;
		$prop     = $size['width'] / $size['heigth'];

		$size_dst = ['width' => $size['width'], 'heigth' => $size['heigth'], 'x' => 0, 'y' => 0];

		if ($opt['crop'])
		{
			if ($size_src['width'] / $size_src['heigth'] > $prop)
			{
				$size_dst['width'] = ceil($size_src['width'] * $size_dst['heigth'] / $size_src['heigth']);
				$size_dst['x']     = floor(($size['width'] - $size_dst['width']) / 2);
			}
			else
			{
				$size_dst['heigth']= ceil($size_src['heigth'] * $size_dst['width'] / $size_src['width']);
				$size_dst['y']     = floor(($size['heigth'] - $size_dst['heigth']) / 2);
			}
		}
		else
		{
			if ($size_src['width'] / $size_src['heigth'] > $prop)
			{
				$size_dst['heigth']= ceil($size_src['heigth'] * $size_dst['width'] / $size_src['width']);
				$size_dst['y']     = floor(($size['heigth'] - $size_dst['heigth']) / 2);
			}
			else
			{
				$size_dst['width'] = ceil($size_src['width'] * $size_dst['heigth'] / $size_src['heigth']);
				$size_dst['x']     = floor(($size['width'] - $size_dst['width']) / 2);
			}
		}

		$heigth = $size['heigth'] / $esc;

		$dst_image  = imagecreatetruecolor($size['width'], $size['heigth']);
		imagealphablending($dst_image, false);
		imagesavealpha($dst_image, true);

		$color = imagecolorallocatealpha($dst_image, 255, 255, 255, $color_trans);
		$trans = imagecolortransparent($dst_image, $color);
		imagefill($dst_image, 0, 0, $trans);

		imagecopyresampled($dst_image, $src_image, $size_dst['x'], $size_dst['y'], $size_src['x'], $size_src['y'], $size_dst['width'], $size_dst['heigth'], $size_src['width'], $size_src['heigth']);

		$dst_image2 = imagecreatetruecolor($width, $heigth);
		imagealphablending($dst_image2, false);
		imagesavealpha($dst_image2, true);

		$color      = imagecolorallocatealpha($dst_image2, 255, 255, 255, $color_trans);
		$trans = imagecolortransparent($dst_image2, $color);
		imagefill($dst_image2, 0, 0, $trans);

		imagecopyresampled($dst_image2, $dst_image, 0, 0, 0, 0, $width, $heigth, $size['width'], $size['heigth']);

		switch($file_ext){
			case "png": imagepng($dst_image2, $output_file, 9, PNG_NO_FILTER);break;
			case "bmp": imagewbmp($dst_image2, $output_file);break;
			case "gif": imagegif($dst_image2, $output_file);break;
			case "jpeg":
			case "jpg":
			default:    imagejpeg($dst_image2, $output_file, 80); break;
		}

		return file_exists($output_file);
	}

	public static function GetLocalProcessed ($uri, $slug = null)
	{
		$file = explode('/', $uri);
		empty($file[0]) and array_shift($file);

		$_uri_slug = array_shift($file); // _URI slug
		if ( ! is_null($slug) and mb_strtolower($_uri_slug) !== mb_strtolower($slug))
		{
			array_unshift($file, $_uri_slug);
			$_uri_slug = $slug;
		}

		$file = implode(DS, $file);

		$directorio = explode(DS, $file);
		count($directorio) > 0 and empty($directorio[0]) and array_shift($directorio);

		$file_name = array_pop($directorio);

		$file_name = explode('.', $file_name);
		$file_ext  = count($file_name) > 1 ? array_pop($file_name) : '';
		$file_name = implode('.', $file_name);
		
		isset($directorio[0]) or array_unshift($directorio, ''); ## Agrega el espacio inicial
		empty($directorio[0]) or array_unshift($directorio, ''); ## Agrega el espacio inicial
		$directorio = implode(DS, $directorio);

		$opt = self::GetParamsFromName($file_name);

		$real_file = HOMEPATH . $directorio . DS .$file_name . '.' . $file_ext;

		if ( ! file_exists($real_file))
		{
			return NULL;
		}

		$extension = FileMimes::instance() -> consulta($file_ext);

		try
		{
			$mime = filemime($real_file);
			$extension_tmp = FileMimes::instance() -> getExtensionByMime($mime);

			if ($extension_tmp === NULL)
			{
				throw new Exception('Extensión no encontrada');
			}

			$extension = $extension_tmp();
		}
		catch(Exception $e)
		{}

		empty($file_ext) and $file_ext = $extension['ext'];
		$tipo = $extension['type'];

		if ($tipo !== 'IMAGEN')
		{
			trigger_error('Archivo es `' . $real_file . '` es de tipo: ' . $tipo . PHP_EOL . 'Error al procesarlo como imagen', E_USER_WARNING);
			return null;
		}

		$file_ext = '.' . $file_ext;
		$opt_uri = self :: GetParamsUri($opt, $real_file);

		$the_file = DS . $_uri_slug . $directorio . DS . $file_name . $opt_uri . $file_ext;
		$the_file_path = HOMEPATH . $the_file;

		if ( ! self::GenerateImage($real_file, $the_file_path, $opt))
		{
			return $real_file;
		}

		return $the_file_path;
	}
}