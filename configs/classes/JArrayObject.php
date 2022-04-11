<?php
/**
 * JArrayObject.php
 * Archivo de clase JArray
 *
 * @filesource
 */
class JArrayObject extends JArray implements CasterVal_Constants
{
	use CasterVal;

	/**
	 * $ATTRS
	 */
	protected static $ATTRS = [];

	/**
	 * $DATA
	 */
	protected $JArray;

	//===================================================================================//
	//=== PARAMETROS GENERALES ==========================================================//
	//===================================================================================//

	/**
	 * FromArray ()
	 */
	public static function FromArray (array $data)
	{
		$gcc = get_called_class();
		return new $gcc ($data);
	}

	/**
	 * __construct ()
	 */
	public function __construct (array $data = [])
	{
		$gcc = get_called_class();
		$attrs = $gcc :: $ATTRS;
		$attrs = array_keys($attrs);
		$initial = array_combine($attrs, array_map(function($o){return null;}, $attrs));

		$this -> JArray = new JArray($initial); // Objeto vacío
		parent :: __construct ($this -> JArray);

		$this -> SYNC ($data); // Sincroniza la data enviada
	}

	/**
	 * _get_real_attr ()
	 */
	protected function _get_real_attr ($attr)
	{
		$gcc = get_called_class();
		$attrs = $gcc :: ATTRS;
		$attrs = array_keys($attrs);

		if (in_array($attr, $attrs)) return $attr;
		$attr = preg_replace('/^\_/', '', $attr);
		if (in_array($attr, $attrs)) return $attr;
		$attr_lower = mb_strtolower($attr);

		foreach ($attrs as $attr_temp)
			if (mb_strtolower($attr_temp) === $attr_lower)
				return $attr_temp;

		return null;
	}

	/**
	 * SYNC ()
	 */
	public function SYNC (array $data = [])
	{
		foreach ($data as $k => $v)
		{
			$this -> offsetSet ($k, $v);
		}

		return $this;
	}

	/**
	 * DATA ()
	 */
	public function DATA ()
	{
		$gcc = get_called_class();
		$attrs = $gcc :: $ATTRS;
		$attrs = array_keys($attrs);

		$data = array_combine($attrs, array_map(function($attr){
			return $this -> offsetGet ($attr);
		}, $attrs));

		return $data;
	}

	/**
	 * ToArray ()
	 */
	public function ToArray ()
	{
		return $this -> DATA ();
	}

	//===================================================================================//
	//=== MAGIC METHODS  —  ArrayAccess =================================================//
	//===================================================================================//

	/**
	 * offsetSet ()
	 */
	public function offsetSet ($index, $newval)
	{
		// $attrs
		$gcc = get_called_class();
		$attrs = $gcc :: $ATTRS;

		// Detectando si es alias
		while (isset($attrs[$index]) and is_string($attrs[$index]))
		{
			$index = $attrs[$index];
		}

		is_string($newval) and $newval = trim($newval);
		if (isset($attrs[$index]) and ! is_null($attrs[$index]))
		{
			$attr = $attrs[$index];

			isset($attr['tipo'])     and $newval = $this -> _cv_check_tipo      ($newval, $attr['tipo']);
			isset($attr['largo'])    and $newval = $this -> _cv_check_largo_max ($newval, $attr['tipo'], (int) $attr['largo']);
			isset($attr['opciones']) and $newval = $this -> _cv_check_in_array  ($newval, $attr['tipo'], (array) $attr['opciones'], false);
			is_string($newval) and $newval = trim($newval);
		}
	
		// Estableciendo el valor
		$return = parent :: offsetSet ($index, $newval); // void

		// Estableciendo el valor en alias
		$this -> CloneIndexToAlias($index);

		return $return;
	}
	
	/**
	 * CloneIndexToAlias ()
	 */
	public function CloneIndexToAlias ($index)
	{
		// $attrs
		$gcc = get_called_class();
		$attrs = $gcc :: $ATTRS;

		// Estableciendo el valor en alias
		$newval = $this -> offsetGet ($index);
		foreach ($attrs as $attr => $config)
		{
			if (is_string($config) and $config === $index)
			{
				$this -> JArray [$attr] = $newval;
			}
		}

		return $this;
	}

	//===================================================================================//
	//=== MAGIC METHODS  —  String ======================================================//
	//===================================================================================//

	/**
	 * __toString ()
	 */
	public function __toString ()
	{
		return json_encode($this -> DATA ());
	}

	//===================================================================================//
	//=== CasterVal =====================================================================//
	//===================================================================================//

	/**
	 * CastVal ()
	 */
	public function CastVal ($indice, $valor = null)
	{
		return $valor;
	}
}