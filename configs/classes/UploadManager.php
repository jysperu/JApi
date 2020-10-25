<?php

class UploadManager extends ArrayObject
{
	protected function sql ($query, $insert = FALSE)
	{
		static $_db;
		
		if ( ! isset($_db))
		{
			try
			{
				APP() -> use_CON();
				$_db = true;
			}
			catch (Exception $e)
			{
				$_db = FALSE;
			}
		}

		if ( ! $_db) return null;

		if ( ! sql_et('_uploads'))
		{
			sql('
CREATE TABLE `_uploads` (
  `id` Bigint NOT NULL AUTO_INCREMENT,
  
  `name` Text, 
  `type` Text,
  `error` Text,
  `size` Bigint,
  
  `id_usuario` BIGINT NOT NULL,
  
  `estado` Enum ("Registrado", "Error", "PorCargar", "Cargado") NOT NULL DEFAULT "Registrado",
  `estado_log` Text,
  
  `imagen` Boolean NOT NULL DEFAULT FALSE,
  `dir` Text,
  `fname` Text,
  `fext` Text,
  `fpath` Text,
  
  `href` Text,
  
  `creado` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP(),

  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC
			');
		}

		return sql($query, $insert);
	}
	
	private $_id = NULL;
	
	private $_name = NULL;
	private $_type = NULL;
	private $_error= NULL;
	private $_size = NULL;
	
	private $_estado     = 'Registrado';
	private $_estado_log = NULL;
	
	private $_imagen = FALSE;
	
	private $_dir     = NULL;
	
	private $_fname = NULL;
	private $_fext  = NULL;
	private $_fpath = NULL;
	private $_href  = NULL;
	
	public function __construct ($_FILE)
	{
		$this->_name = $_FILE['name'];
		$this->_type = $_FILE['type'];
		$this->_error= $_FILE['error'];
		$this->_size = $_FILE['size'];
		
		$id_usuario = (isset(APP()->Usuario) and isset(APP()->Usuario->id)) ? APP()->Usuario->id : 0;

		$this->_id = self::sql('
INSERT INTO `_uploads` (
	`name`, 
	`type`, 
	`error`, 
	`size`, 
	`estado`, 
	`id_usuario`
) 
VALUES (
	' . qp_esc($this->_name) . ', 
	' . qp_esc($this->_type) . ', 
	' . qp_esc($this->_error) . ', 
	' . qp_esc($this->_size) . ', 
	' . qp_esc($this->_estado) . ', 
	' . qp_esc($id_usuario) . '
)', TRUE);

		if ($this->_error > 0)
		{
			switch($this->_error)
			{
				case 1:
					$this->_estado_log = 'UPLOAD_ERR_INI_SIZE';
					break;
				case 2:
					$this->_estado_log = 'UPLOAD_ERR_FORM_SIZE';
					break;
				case 3:
					$this->_estado_log = 'UPLOAD_ERR_PARTIAL';
					break;
				case 6:
					$this->_estado_log = 'UPLOAD_ERR_NO_TMP_DIR';
					break;
				case 7:
					$this->_estado_log = 'UPLOAD_ERR_CANT_WRITE';
					break;
				case 8:
					$this->_estado_log = 'UPLOAD_ERR_EXTENSION';
					break;
				default:
					$this->_estado_log = 'NOT_IDENTIFIED';
					break;
			}

			$this->_estado = 'Error';

			self::sql('
UPDATE `_uploads` 
SET `estado_log` = ' . qp_esc($this->_estado_log) . ', 
	`estado` = ' . qp_esc($this->_estado) . ' 
WHERE `id` = ' . qp_esc($this->_id)
			);

			throw new Exception ('Error al cargar Archivo (' . $this->_estado_log . ')');
		}

		$this->_imagen = preg_match('/^image\/(.*)/i', $this->_type);

		$_path = config('upload_path');

		if ($this->_imagen)
		{
			$_path_img = config('upload_path_for_img');
			$_path_img_extra = config('upload_path_for_img_is_extra');
			
			is_null($_path_img) or $_path_img = '';
			$_path = (is_null($_path_img) or $_path_img_extra) ? ($_path . $_path_img) : $_path_img;
		}

		$_path = filter_apply('Manager/Upload/path', $_path, $this->_imagen, $_FILE);

		$_yearmonth = config('upload_yearmonth');

		$this->_dir = $_path . ($_yearmonth ? (DS . date('Y') . DS . date('m')) : '');
		
		mkdir2($this->_dir, HOMEPATH);
		
		$this->_fname = $this->_name;
		$this->_fname = mb_strtolower($this->_fname);
		
		$this->_fname = explode('.', $this->_fname);
		$this->_fext = count($this->_fname) === 1 ? NULL : array_pop($this->_fname);
		$this->_fname = implode('.', $this->_fname);

		self::sql('
UPDATE `_uploads` 
SET `imagen` = ' . qp_esc($this->_imagen) . ', 
	`dir` = ' . qp_esc($this->_dir) . ', 
	`fname` = ' . qp_esc($this->_fname) . ', 
	`fext` = ' . qp_esc($this->_fext) . ' 
WHERE `id` = ' . qp_esc($this->_id)
		);

		if (is_null($this->_fext))
		{
			$this->_estado_log = 'Archivo no tiene extensión';
			$this->_estado = 'Error';
			
			self::sql('
UPDATE `_uploads` 
SET `estado_log` = ' . qp_esc($this->_estado_log) . ', 
	`estado` = ' . qp_esc($this->_estado) . ' 
WHERE `id` = ' . qp_esc($this->_id)
			);

			throw new Exception ($this->_estado_log);
		}

		$this->_fname = uniqid(strtoslug($this->_fname) . '_');
		if( preg_match('/^php/i', $this->_fext))
		{
			$this->_fext = 'html'; // Seguridad
		}

		$this->_fpath = $this->_dir . DS . $this->_fname . '.' . $this->_fext;

		if (file_exists(HOMEPATH . $this->_fpath))
		{
			$this->_fname = na(5) . "_" . $this->_fname;
			$this->_fpath = $this->_dir . DS . $this->_fname . '.' . $this->_fext;
		}

		$this->_estado = 'PorCargar';

		self::sql('
UPDATE `_uploads` 
SET `fname` = ' . qp_esc($this->_fname) . ', 
	`fext` = ' . qp_esc($this->_fext) . ', 
	`fpath` = ' . qp_esc($this->_fpath) . ', 
	`estado` = ' . qp_esc($this->_estado) . ' 
WHERE `id` = ' . qp_esc($this->_id)
		);

		if( ! move_uploaded_file($_FILE['tmp_name'], HOMEPATH . $this->_fpath))
		{
			$this->_estado_log = 'Error al realizar upload de file - Origen o Destino no leible.';
			$this->_estado = 'Error';

			self::sql('
UPDATE `_uploads` 
SET `estado_log` = ' . qp_esc($this->_estado_log) . ', 
	`estado` = ' . qp_esc($this->_estado) . ' 
WHERE `id` = ' . qp_esc($this->_id)
			);

			throw new Exception ($this->_estado_log);
		}

		$href = url('array');
		$href['path'] = '/' .ltrim(str_replace(DS, '/', $this->_fpath), '/');

		$this->_href = build_url($href);

		$this->_estado = 'Cargado';

		self::sql('
UPDATE `_uploads` 
SET `href` = ' . qp_esc($this->_href) . ', 
	`estado` = ' . qp_esc($this->_estado) . ' 
WHERE `id` = ' . qp_esc($this->_id)
		);

		parent::__construct([
			'name'       => $this->_name,
			'type'       => $this->_type,
			'size'       => $this->_size,
			'imagen'     => $this->_imagen,
			'ext'        => $this->_fext,
			'path'       => $this->_fpath,
			'href'       => $this->_href,

			'preview'    => $this->_imagen ? get_image($this->_href, ['size' => '300x300']) : NULL,
			'favicon'    => $this->_imagen ? get_image($this->_href, ['size' => '50x50']) : NULL,
		]);
	}
}
