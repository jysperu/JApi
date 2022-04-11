<?php
/**
 * JArray.php
 * Archivo de clase JArray
 *
 * @filesource
 */
class JArray extends ArrayObject
{
	//////////////////////////////////////
	/// Variables gestión de clase     ///
	//////////////////////////////////////

	/**
	 * $_callbacks
	 */
	public static $_static_callbacks = [];

	/**
	 * $_callbacks
	 */
	protected $_callbacks = [];

	/**
	 * $_default_context
	 */
	protected $_default_context = 'edit';

	//////////////////////////////////////
	/// Funciones del objeto creado    ///
	//////////////////////////////////////

	/**
	 * WhatIsTheDefaultContext ()
	 */
	public function WhatIsTheDefaultContext()
	{
		return $this->_default_context;
	}

	/**
	 * AssignDefaultContext ()
	 */
	public function AssignDefaultContext($context = 'edit')
	{
		$this->_default_context = $context;
		return $this;
	}

	/**
	 * first ()
	 */
	public function first()
	{
		if (isset($this[0])) return $this[0];

		$return = NULL;
		foreach($this as $v)
		{
			$return = $v;
			break;
		}

		return $return;
	}

	/**
	 * clear ()
	 */
	public function clear()
	{
		$keys = array_keys((array) $this);
		while (count($keys) > 0)
		{
			$key = array_shift   ($keys);
			$this -> offsetUnset ($key );
		}

		return $return;
	}

	//////////////////////////////////////
	/// Constructor del objeto         ///
	//////////////////////////////////////
	public function __construct($data = [], $callbacks = [])
	{
		parent::__construct ($data);
		$this->_callbacks = $callbacks;
	}

	//////////////////////////////////////
	/// Apoyos del objeto y su info    ///
	//////////////////////////////////////

	/**
	 * _detect_index ()
	 */
	protected function _detect_index ($index)
	{
		if ($this->__isset($index)) return $index;

		$indices = [ $index ];
		$temp    = $index . 's' and $indices[] = $temp;
		$temp    = $index . 'es' and $indices[] = $temp;
		$temp    = preg_replace('/s$/i', '', $index) and $indices[] = $temp;
		$temp    = preg_replace('/es$/i', '', $index) and $indices[] = $temp;
		$indices = array_unique($indices);
		$indices = array_filter($indices, function($o){
			return $this->__isset($o);
		});
		count($indices) > 0 and $index   = array_shift ($indices);

		return $index;
	}

	/**
	 * CSADD ()
	 */
	public static function CSADD ($key, $callback)
	{
		$_gcc = get_called_class();
		$_callbacks = $_gcc::$_static_callbacks;
		isset($_callbacks[$_gcc])       or $_callbacks[$_gcc] = [];
		isset($_callbacks[$_gcc][$key]) or $_callbacks[$_gcc][$key] = [];

		$_callbacks[$_gcc][$key]   = (array)$_callbacks[$_gcc][$key];
		$_callbacks[$_gcc][$key][] = $callback;

		$_gcc::$_static_callbacks = $_callbacks;
		return $_gcc;
	}

	/**
	 * _callback_static_add ()
	 */
	protected function _callback_static_add ($key, $callback)
	{
		$_gcc = get_called_class();
		$_callbacks = self::$_static_callbacks;
		isset($_callbacks[$_gcc])       or $_callbacks[$_gcc] = [];
		isset($_callbacks[$_gcc][$key]) or $_callbacks[$_gcc][$key] = [];

		$_callbacks[$_gcc][$key]   = (array)$_callbacks[$_gcc][$key];
		$_callbacks[$_gcc][$key][] = $callback;

		self::$_static_callbacks = $_callbacks;
		return $this;
	}

	/**
	 * _callback_add ()
	 */
	protected function _callback_add ($key, $callback)
	{
		$_callbacks = $this -> _callbacks;
		isset($_callbacks[$key]) or $_callbacks[$key] = [];

		$_callbacks[$key]   = (array)$_callbacks[$key];
		$_callbacks[$key][] = $callback;

		$this -> _callbacks = $_callbacks;
		return $this;
	}

	/**
	 * _callback_exec ()
	 */
	protected function _callback_exec ($key, $return = null, ...$params)
	{
		array_unshift ($params, $return);
		$params[] = $this;
		$params[] = $key;

		$_callbacks = $this -> _callbacks;
		isset($_callbacks[$key]) or $_callbacks[$key] = [];
		$_callbacks = $_callbacks[$key];
		$_callbacks = (array)$_callbacks;
		foreach($_callbacks as $_callback)
		{
			try
			{
				$temp = call_user_func_array($_callback, $params);
				$params[0] = $temp;
			}
			catch(Exception $e)
			{}
		}

		$_gcc = get_called_class();
		$_callbacks = self::$_static_callbacks;
		isset($_callbacks[$_gcc])       or $_callbacks[$_gcc] = [];
		isset($_callbacks[$_gcc][$key]) or $_callbacks[$_gcc][$key] = [];
		$_callbacks = $_callbacks[$_gcc][$key];
		$_callbacks = (array)$_callbacks;
		foreach($_callbacks as $_callback)
		{
			try
			{
				$temp = call_user_func_array($_callback, $params);
				$params[0] = $temp;
			}
			catch(Exception $e)
			{}
		}

		return $params[0];
	}

	/**
	 * _callback_exec_refs ()
	 */
	protected function _callback_exec_refs ($key, &...$params)
	{
		$_callbacks = $this -> _callbacks;
		if ( ! isset($_callbacks[$key])) return;
		$_callback = $_callbacks[$key];

		array_shift ($params, $this);
		array_shift ($params, $key);

		return call_user_func_array($_callback, $params);
	}

	//////////////////////////////////////
	/// Magic Functions / General      ///
	//////////////////////////////////////

	/**
	 * __call ()
	 */
	public function __call ($name, $args)
	{
		if (preg_match('#^set_(.+)#', $name))
		{
			$index = preg_replace('#^set_#', '', $name);
			$index = $this->_detect_index($index);
			$valor = array_shift ($args);
			return $this->__set($index, $valor);
		}

		if (preg_match('#^get_(.+)#', $name))
		{
			$index = preg_replace('#^get_#', '', $name);
			$index = $this->_detect_index($index);
			return $this->__get($index);
		}

		if (preg_match('#^(add|agregar|push)_(.+)#', $name))
		{
			$index = preg_replace('#^(add|agregar|push)_#', '', $name);
			$index = $this->_detect_index($index);
			$valor = array_shift ($args);
			$vkey  = array_shift ($args);

			$actual = $this->__isset($index) ? $this->__get($index) : [];
			$actual = (array)$actual;

			if (is_null($vkey)) $actual[] = $valor;
			else           $actual[$vkey] = $valor;

			return $this->__set($index, $actual);
		}

		if (preg_match('#^(array_shift|shift)_(.+)#', $name))
		{
			$index = preg_replace('#^(array_shift|shift)_#', '', $name);
			$index = $this->_detect_index($index);

			$actual = $this->__isset($index) ? $this->__get($index) : [];
			$actual = (array)$actual;
			$return = array_shift($actual);
			$this->__set($index, $actual);

			return $return;
		}

		if (preg_match('#^(array_pop|pop)_(.+)#', $name))
		{
			$index = preg_replace('#^(array_pop|pop)_#', '', $name);
			$index = $this->_detect_index($index);

			$actual = $this->__isset($index) ? $this->__get($index) : [];
			$actual = (array)$actual;
			$return = array_pop($actual);
			$this->__set($index, $actual);

			return $return;
		}

		if (preg_match('#^(array_diff|diff)_(.+)#', $name))
		{
			$index = preg_replace('#^(array_diff|diff)_#', '', $name);
			$index = $this->_detect_index($index);

			$actual = $this->__isset($index) ? $this->__get($index) : [];
			$actual = (array)$actual;
			$args   = (array)$args;
			array_unshift($args, $actual);

			return call_user_func_array('(array_diff|diff)', $args);
		}

		throw new Exception('Función requerida no existe `' . get_called_class() . '::' . $name . '()`');
		return $this;
	}

	/**
	 * __invoke ()
	 */
	public function __invoke()
	{
		$_args  = func_get_args();
		$index  = array_shift($_args);
		$newval = array_shift($_args);

			if (is_null($index) ) return $this->__toArray();
		elseif (is_null($newval)) return $this->__get($index);
		else                      return $this->__set($index, $newval);
	}

	/**
	 * __toString ()
	 */
	public function __toString()
	{
		return json_encode($this);
	}

	//////////////////////////////////////
	/// Magic Functions / Array Access ///
	//////////////////////////////////////

	/**
	 * offsetExists ()
	 */
	public function offsetExists ($index)
	{
		$this->_callback_exec('before_exists', $index);

		$return = parent::offsetExists($index);

		$return = $this->_callback_exec('exists',     $return, $index);
		$return = $this->_callback_exec(__FUNCTION__, $return, $index);

		return $return;
	}

	/**
	 * offsetExists ()
	 */
	public function offsetGet ($index)
	{
		$context = $this->_default_context;

		$gcc = get_called_class();

		if ($method = '_before_get_' . $index and method_exists($this, $method))
			$this -> $method ($index, $context);

		$this->_callback_exec('before_get',           $index, $context);
		$this->_callback_exec('before_get_' . $index, $index, $context);
		$this->_callback_exec('before_get_' . $index . '_' . $context, $index, $context);

		$return = parent::offsetGet($index);

		$return = $this->_callback_exec('get',           $return, $index, $context);
		$return = $this->_callback_exec('get_' . $index, $return, $index, $context);
		$return = $this->_callback_exec('get_' . $index . '_' . $context, $return, $index, $context);
		$return = $this->_callback_exec(__FUNCTION__,    $return, $index, $context);

		if ($method = '_after_get_' . $index and method_exists($this, $method))
			$return = $this -> $method ($return, $index, $context);

		return $return;
	}

	/**
	 * offsetSet ()
	 */
	public function offsetSet ($index, $newval)
	{
		$gcc = get_called_class();

		if ($method = '_before_set_' . $index and method_exists($this, $method))
			$newval = $this -> $method ($newval, $index);

		$newval = $this->_callback_exec('before_set',           $newval, $index);
		$newval = $this->_callback_exec('before_set_' . $index, $newval, $index);

		$return = parent::offsetSet($index, $newval);

		$this->_callback_exec('set',           $newval, $index);
		$this->_callback_exec('set_' . $index, $newval, $index);
		$this->_callback_exec(__FUNCTION__,    $newval, $index);

		if ($method = '_after_set_' . $index and method_exists($this, $method))
			$this -> $method ($newval, $index);

		if ($method = '_after_set' and method_exists($this, $method))
			$this -> $method ($newval, $index);

		return $return; // void
	}

	/**
	 * offsetUnset ()
	 */
	public function offsetUnset ($index)
	{
		$this->_callback_exec('before_unset',           $index);
		$this->_callback_exec('before_unset_' . $index, $index);

		$return = parent::offsetUnset($index);

		$this->_callback_exec('unset',           $index);
		$this->_callback_exec('unset_' . $index, $index);
		$this->_callback_exec(__FUNCTION__,      $index);

		return $return; // void
	}

	//////////////////////////////////////
	/// Magic Functions / Object Access///
	//////////////////////////////////////

	/**
	 * __isset ()
	 */
	public function __isset ($index)
	{
		$return = $this->offsetExists($index);
		$this->_callback_exec(__FUNCTION__, $return, $index);
		return $return;
	}

	/**
	 * __get ()
	 */
	public function __get ($index)
	{
		$return = $this->offsetGet($index);
		$this->_callback_exec(__FUNCTION__, $return, $index);
		return $return;
	}

	/**
	 * __set ()
	 */
	public function __set ($index, $newval)
	{
		$this->offsetSet($index, $newval);
		$this->_callback_exec(__FUNCTION__, $newval, $index);
		return $this;
	}

	/**
	 * __unset ()
	 */
	public function __unset ($index)
	{
		$this->offsetUnset($index);
		$this->_callback_exec(__FUNCTION__, $index);
		return $this;
	}

	/**
	 * __toArray ()
	 */
	public function __toArray()
	{
		$this->_callback_exec('before_toarray');

		$return = (array)$this;

		$return = $this->_callback_exec('toarray',    $return);
		$return = $this->_callback_exec(__FUNCTION__, $return);

		return $return;
	}
}