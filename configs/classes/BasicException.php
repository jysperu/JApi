<?php
defined('APPPATH') or exit('Acceso directo al archivo no autorizado.');

/**
 * Clase BasicException
 * Extensión de la clase Exception con funciones extras
 *
 * @package		JCore\classes
 * @author		YisusCore
 * @link		https://jcore.jys.pe/guia/classes/basicexception
 * @version		1.0.0
 * @copyright	Copyright (c) 2018 - 2022, JYS Perú (https://www.jys.pe/)
 * @filesource
 */

class BasicException extends Exception {
	
	protected $meta = [];
	
	public function __construct(string $message = '', int $code = 0, array $meta = [], Throwable $previous = NULL)
	{
		$this->meta = $meta;
		parent::__construct($message, $code, $previous);
	}
	
	public function getMeta()
	{
		return $this->meta;
	}
	
	public function setMeta($meta)
	{
		$this->meta = $meta;
	}
}
