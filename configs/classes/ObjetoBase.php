<?php
/**
 * ObjetoBase
 * Manipulador de Objetos Tbl de la db
 * @upddated 22/DIC/2021 03:14 AM
 */

abstract class ObjetoBase extends JArray implements CasterVal_Constants
{
	use CasterVal;

	//////////////////////////////////////
	/// Variables gestión de clase     ///
	//////////////////////////////////////

	/**
	 * @static @var $_tblname
	 * Nombre de la tabla del objeto
	 */
	protected static $_tblname = NULL;

	/**
	 * @static @var $_keys
	 * Listado de los campos claves
	 */
	protected static $_keys = [];

	/**
	 * @static @var $_key
	 * Si solo hay un campo clave, entonces es este
	 */
	protected static $_key = NULL;

	/**
	 * @static @var $_toString
	 * Si hay un campo o función que permita retornar el objeto como string
	 */
	protected static $_toString = NULL;

	/**
	 * @static @var $_columns
	 * Listado de todas las columnas mediante un array:
	 *
	 * Formato de los valores dentro del array
	 * [
	 *   'nombre'      => 'field',             // Nombre del campo
	 *   'tipo'        => self::Texto,         // El tipo del campo, por defecto será asumido como self::Texto
	 *   'largo'       => self::Ilimitado,     // El mb_strlen del valor del campo, por defecto es el máximo posible
	 *   'opciones'    => NULL,                // Si el campo solo tiene autorizado uno de los valores dentro de esta lista, por defecto NULO
	 *   'defecto'     => NULL,                // El valor que tomará por defecto si no se ha asignado algún valor al campo, por defecto NULO
	 *   'attr'        => NULL,                // Alguna atribución especial al campo tal como UNSIGNED ZEROFILL, por defecto NULO
	 *   'nn'          => FALSE,               // Identificador si el campo es NULLABLE o no, por defecto siempre es NULLABLE
	 *   'ne'          => TRUE,                // Si el campo es NOT NULL, se va a permitir campo vacío o no
	 *   'ai'          => FALSE,               // Si el campo es el único KEY identifica si el valor es AUTO_INCREMENT, caso contrario no sirve
	 *   'ag'          => NULL,                // Si el campo es autogenerado, este es el nombre de la función que devolverá el valor del campo
	 *   'dg'          => FALSE,               // Si el campo es autogenerado mediante DATA_BASE, por tanto no se puede hacer un INSERT o UPDATE
	 * ]
	 */
	protected static $_columns = [];

	/**
	 * @static @var $_rxs_hijo
	 * Listado de todas las referencias en las cuales este objeto es el padre
	 * (los valores de las llaves de este objeto son campos del otro objeto)
	 *
	 * Formato de los objetos dentro del array
	 * [
	 *     'tabla'     => 'tabla',             // Nombre de la tabla que relacinó su campo con este objeto
	 *     'clase'     => 'clase',             // Nombre de la clase a la cual llamar cuando se consulte el listado de los objetos hijos
	 *     'columnas'  => [                    // Listado de todos los campos relacionados entre sí de la tabla de este objeto como el del hijo
	 *         'campo' => 'campo_hijo'
	 *     ],
	 *     'field'     => NULL,               // Un valor del cual como se quiere que aparezca como campo en este objeto
	 *     'on_update' => 'CASCADE',          // Acción relacionado con el hijo una vez que este objeto cambie los valores de los campos llaves
	 *     'on_delete' => 'CASCADE',          // Acción relacionado con el hijo una vez que el registro db de este objeto es eliminado
	 *     'r11'       => FALSE,              // La relación con el hijo es 1-1, si ese es el caso se intenta agregar un registro automáticamente
	 *     'is_attr'   => FALSE,              // El listado de hijos son considerados como atributos del objeto por lo que se procurará cachear la data con el select
	 *     'attr_uniq' => NULL,               // El listado de hijos son ÚNICOS basados en este atributo
	 * ]
	 */
	protected static $_rxs_hijo = [];

	/**
	 * @static @var $_rxs_padre
	 * Listado de todas las referencias en las cuales este objeto es el hijo
	 * (algunos de los campos del este objeto son los valores de las llaves de otro objeto)
	 *
	 * Formato de los objetos dentro del array
	 * [
	 *     'tabla'     => 'tabla',            // Nombre de la tabla con el cual esta relacionado este objeto
	 *     'clase'     => 'clase',            // Nombre de la clase a la cual llamar cuando se consulte el objeto padre
	 *     'columnas'  => [                   // Listado de todos los campos relacionados entre sí de la tabla de este objeto como el del padre
	 *         'campo' => 'campo_padre'
	 *     ],
	 *     'field'     => NULL,               // Un valor del cual como se quiere que aparezca como campo en este objeto
	 *     'r11'       => FALSE,              // La relación con el padre es 1-1, si ese es el caso se intenta eliminar el registro automáticamente
	 *     'fus'       => FALSE,              // Force Update Sync, ejecuta un update al padre para que se autocalcule algún parametro o algo
	 * ]
	 */
	protected static $_rxs_padre = [];

	/**
	 * $_extra_functions
	 */
	public static $_static_extra_functions = [];

	//////////////////////////////////////
	/// Funciones gestión de clase     ///
	//////////////////////////////////////

	/**
	 * gcc ()
	 */
	public static function gcc ()
	{
		return get_called_class();
	}

	/**
	 * tblname ()
	 * use $_tblname
	 */
	public static function tblname()
	{
		$that = self::gcc();
		return $that::$_tblname;
	}

	/**
	 * keys ()
	 * use $_keys
	 */
	public static function keys()
	{
		$that = self::gcc();
		return $that::$_keys;
	}

	/**
	 * key ()
	 * use $_key
	 */
	public static function key()
	{
		$that = self::gcc();
		return $that::$_key;
	}

	/**
	 * toString ()
	 * use $_toString
	 */
	public static function toString()
	{
		$that = self::gcc();
		return $that::$_toString;
	}

	/**
	 * add_static_var_item ()
	 */
	public static function add_static_var_item($_var, $_reg)
	{
		$that = self::gcc();
		$that::$$_var[] = $_reg;
		return;
	}

	/**
	 * add_column ()
	 * Agrega mas columnas
	 * use $_columns
	 */
	public static function add_column($_column)
	{
		$that = self::gcc();
		$that::$_columns[] = $_column;
		return;
	}

	/**
	 * columns_real ()
	 * use $_columns
	 */
	public static function columns_real()
	{
		$that = self::gcc();
		return $that::$_columns;
	}

	/**
	 * columns ()
	 * use columns_real ()
	 */
	public static function columns()
	{
		static $_columns = [];
		$that = self::gcc();
		isset($_columns[$that]) or $_columns[$that] = [];

		if ($columns = self::columns_real() and count($_columns[$that]) !== count($columns))
		{
			foreach($columns as $column)
			{
				// Añadiendo posibles atributos faltantes
				$column = array_merge([
					'nombre'   => NULL,
					'tipo'     => self::Texto,
					'largo'    => self::Ilimitado,
					'opciones' => NULL,
					'defecto'  => NULL,
					'attr'     => NULL,
					'nn'       => FALSE,
					'ne'       => TRUE,
					'ai'       => FALSE,
					'ag'       => NULL,
					'dg'       => FALSE,
				], $column);

				// Validando que exista el atributo NOMBRE
				if (is_null($column['nombre']))
				{
					throw new Exception ('Algunos campos no tienen nombre');
				}

				// Corrigiendo posibles atributos dañados
				is_null ($column['tipo'])     and $column['tipo']     = self::Texto;
				is_null ($column['largo'])    and $column['largo']    = self::Ilimitado;
				is_empty($column['opciones']) and $column['opciones'] = NULL;
				is_empty($column['defecto'])  and $column['defecto']  = NULL;
				is_empty($column['attr'])     and $column['attr']     = NULL;
				is_bool ($column['nn'])        or $column['nn']       = FALSE;
				is_bool ($column['ai'])        or $column['ai']       = FALSE;
				is_empty($column['ag'])       and $column['ag']       = NULL;
				is_bool ($column['dg'])        or $column['dg']       = FALSE;

				// Las posibilidades por las cuales sería innecesario que NOT_EMPTY sea TRUE
				if(
					 ! $column['nn'] // Si NOT_NULL = FALSE
					or $column['dg'] // Si DB_GENERATED = TRUE
					or $column['ai'] // Si AUTO_INCREMENTE = TRUE
				)
				{
					$column['ne'] = FALSE;
				}

				// Si NOT_NULL = TRUE entonces el valor por defecto no puede ser NULL
				// Si el atributo es PK BIGINT NOT NULL AUTO_INCREMENT y el valor es CERO este no se pondrá al momento de insertar
				if ($column['nn'] and is_null($column['defecto']))
				{
					switch($column['tipo'])
					{
						case self::Boolean:
							$column['defecto'] = false;
							break;
						case self::Arreglo:
							$column['defecto'] = [];
							break;
						case self::Numero:
							$column['defecto'] = $column['ai'] ? NULL : 0;
							break;
						case self::FechaHora:
							$column['defecto'] = date('Y-m-d H:i:s');
							break;
						case self::Fecha:
							$column['defecto'] = date('Y-m-d');
							break;
						case self::Hora:
							$column['defecto'] = date('H:i:s');
							break;
						case self::Texto: default:
							$column['defecto'] = '';
							break;
					}
				}

				// Añadiendo la columna corregida
				$_columns[$that][$column['nombre']] = $column;
			}
		}

		return $_columns[$that];
	}

	/**
	 * fields ()
	 * use columns ()
	 */
	public static function fields()
	{
		static $_fields = [];
		$that = self::gcc();
		isset($_fields[$that]) or $_fields[$that] = [];

		if ($columns = self::columns() and count($_fields[$that]) !== count($columns))
		{
			$_fields[$that] = array_map(function($o){
				return $o['nombre'];
			}, $columns);
		}

		return $_fields[$that];
	}

	/**
	 * rxs_hijo_real ()
	 * use $_rxs_hijo
	 */
	public static function rxs_hijo_real()
	{
		$that = self::gcc();
		return $that::$_rxs_hijo;
	}

	/**
	 * rxs_hijo ()
	 * use rxs_hijo_real ()
	 * use fields ()
	 */
	public static function rxs_hijo()
	{
		static $_rxs_hijo = [];
		$that = self::gcc();
		isset($_rxs_hijo[$that]) or $_rxs_hijo[$that] = [];

		if ($rxs_hijo = self::rxs_hijo_real() and count($_rxs_hijo[$that]) !== count($rxs_hijo))
		{
			$fields = self::fields();

			foreach($rxs_hijo as $rx)
			{
				// Añadiendo posibles atributos faltantes
				$rx = array_merge([
					'tabla'     => NULL,
					'clase'     => NULL,
					'columnas'  => [],
					'field'     => NULL,
					'on_update' => 'CASCADE',
					'on_delete' => 'CASCADE',
					'r11'       => FALSE,
					'is_attr'   => FALSE,
					'attr_uniq' => null,
				], $rx);

				// Validando que exista el atributo NOMBRE y CLASE
				if (is_null($rx['tabla']) or is_null($rx['clase']))
				{
					throw new BasicException ('Algunas relaciones no tienen nombre o clase', __LINE__, $rx);
				}

				// Validando que exista el atributo COLUMNAS
				if (is_empty($rx['columnas']))
				{
					throw new BasicException ('Algunas relaciones no tienen campos', __LINE__, $rx);
				}

				$rx['c1'] = count($rx['columnas']) === 1;

				// Corrigiendo posibles atributos dañados
				if (is_null ($rx['field']))
				{
					$tbl_subname = preg_replace('/^' . self::tblname() . '(e)?(s)?\_/', '', $rx['tabla']);
					$tbl_subname_idx = '';
					do
					{
						$_temp_field = $tbl_subname . $tbl_subname_idx . '_lista';
						$tbl_subname_idx === '' and $tbl_subname_idx = 1;
						$tbl_subname_idx++;
					}
					while (in_array($_temp_field, $fields));
					
					$rx['field'] = $_temp_field;
				}

				$fields[] = $rx['field'];

				is_null ($rx['on_update']) and $rx['on_update'] = 'CASCADE';
				is_null ($rx['on_delete']) and $rx['on_delete'] = 'CASCADE';
				is_bool ($rx['r11'])        or $rx['r11']       = FALSE;

				$rx['on_update'] = mb_strtoupper($rx['on_update']);
				$rx['on_delete'] = mb_strtoupper($rx['on_delete']);

				// Añadiendo la columna corregida
				$_rxs_hijo[$that][] = $rx;
			}
		}

		return $_rxs_hijo[$that];
	}

	/**
	 * rxs_padre_real ()
	 * use $_rxs_padre
	 */
	public static function rxs_padre_real()
	{
		$that = self::gcc();
		return $that::$_rxs_padre;
	}

	/**
	 * rxs_padre ()
	 * use rxs_padre_real ()
	 * use fields ()
	 */
	public static function rxs_padre()
	{
		static $_rxs_padre = [];
		$that = self::gcc();
		isset($_rxs_padre[$that]) or $_rxs_padre[$that] = [];

		if ($rxs_padre = self::rxs_padre_real() and count($_rxs_padre[$that]) !== count($rxs_padre))
		{
			$fields = self::fields();

			foreach($rxs_padre as $rx)
			{
				// Añadiendo posibles atributos faltantes
				$rx = array_merge([
					'tabla'   => NULL,
					'clase'   => NULL,
					'columnas'=> [],
					'field'   => NULL,
					'r11'     => FALSE,
					'fus'     => FALSE,
				], $rx);

				// Validando que exista el atributo NOMBRE y CLASE
				if (is_null($rx['tabla']) or is_null($rx['clase']))
				{
					throw new Exception ('Algunas relaciones no tienen nombre o clase');
				}

				// Validando que exista el atributo COLUMNAS
				if (is_empty($rx['columnas']))
				{
					throw new Exception ('Algunas relaciones no tienen campos');
				}

				$rx['c1'] = count($rx['columnas']) === 1;
				if ($rx['c1'])
				{
					$rxc1_cam_padre = array_keys  ($rx['columnas']) [0];
					$rxc1_cam_hijo  = array_values($rx['columnas']) [0];
				}

				// Corrigiendo posibles atributos dañados
				if (is_null ($rx['field']))
				{
					$tbl_subname = $rx['c1'] ? preg_replace('/\_(?:id|codigo)$/i', '', $rxc1_cam_hijo) : $rx['tabla'];
					$tbl_subname_idx = '';
					do
					{
						$_temp_field = $tbl_subname . $tbl_subname_idx . '_obj';
						$tbl_subname_idx === '' and $tbl_subname_idx = 1;
						$tbl_subname_idx++;
					}
					while (in_array($_temp_field, $fields));
					
					$rx['field'] = $_temp_field;
				}

				$fields[] = $rx['field'];

				is_bool ($rx['r11']) or $rx['r11'] = FALSE;

				// Añadiendo la columna corregida
				$_rxs_padre[$that][] = $rx;
			}
		}

		return $_rxs_padre[$that];
	}

	/**
	 * rxs_padre_nexto_fields ()
	 * use rxs_padre ()
	 */
	public static function rxs_padre_nexto_fields()
	{
		static $_nexto_fields = [];
		$that = self::gcc();
		isset($_nexto_fields[$that]) or $_nexto_fields[$that] = [];

		if (count($_nexto_fields[$that]) === 0)
		{
			$rxs_padre = self::rxs_padre();

			foreach($rxs_padre as $rx)
			{
				if ( ! $rx['c1']) continue;

				$cam_padre = array_keys  ($rx['columnas']) [0];
				$cam_hijo  = array_values($rx['columnas']) [0];

				$_nexto_fields[$that][$cam_hijo] = $rx['field'];
			}
		}

		return $_nexto_fields[$that];
	}

	/**
	 * rxs_padre_nonexto_fields ()
	 * use rxs_padre ()
	 */
	public static function rxs_padre_nonexto_fields()
	{
		static $_nonexto_fields = [];
		$that = self::gcc();
		isset($_nonexto_fields[$that]) or $_nonexto_fields[$that] = [];

		if (count($_nonexto_fields[$that]) === 0)
		{
			$rxs_padre = self::rxs_padre();

			foreach($rxs_padre as $rx)
			{
				if ($rx['c1']) continue;

				$_nonexto_fields[$that][] = $rx['field'];
			}
		}

		return $_nonexto_fields[$that];
	}

	/**
	 * rxs_padre_castval_objects ()
	 * use rxs_padre ()
	 */
	public static function rxs_padre_castval_objects()
	{
		static $_castval_objects = [];
		$that = self::gcc();
		isset($_castval_objects[$that]) or $_castval_objects[$that] = [];

		if (count($_castval_objects[$that]) === 0)
		{
			$rxs_padre = self::rxs_padre();

			foreach($rxs_padre as $rx)
			{
				$_castval_objects[$that][$rx['field']] = [
					'class'  => 'Objeto\\' . $rx['clase'],
					'campos' => $rx['columnas'],
				];

				foreach($rx['columnas'] as $padre => $hijo)
				{
					$_castval_objects[$that][$hijo] = [
						'class'  => 'Objeto\\' . $rx['clase'],
						'campos' => $rx['columnas'],
					];
				}
			}
		}

		return $_castval_objects[$that];
	}

	//////////////////////////////////////
	/// Funciones Generales de lista   ///
	//////////////////////////////////////

	/**
	 * FromArray ()
	 * Retorna la clase generado desde un array
	 * @param array $data
	 * @return self
	 */
	public static function FromArray($data)
	{
		$that = self::gcc();
		$instance = new $that();

		foreach($data as $k => $v)
		{
			$instance -> _data_original [$k] = $v;
			$instance -> _data_instance [$k] = $v;
		}

		$instance -> _found = TRUE;
		$instance -> _from_array = TRUE;

		return $instance;
	}

	/**
	 * Lista ()
	 * Lista objetos basados en filtros
	 * @param array $filter
	 * @param int $limit
	 * @param string $sortby
	 * @return array[self]
	 */
	public static function Lista ($filter = [], $limit = NULL, $sortby = NULL)
	{
		$columns = self::columns();
		$fields = array_keys($columns);

		$_sql_where = '';
		foreach($filter as $field => $val)
		{
			if ( ! in_array($field, $fields))
			{
				continue;
			}

			$field_dats = $columns[$field];
			$clas = $field_dats['tipo'];
			
			$_where = ' AND `' . $field . '`';

			if (is_array($val))
			{
				if ($clas === self::Numero AND $val[0] === 'IN')
				{
					array_shift($val);
					
					if (count($val) === 0)
					{
						continue;
					}
					
					$_where .= ' IN (' . implode(', ', array_map('qp_esc', $val)) . ')';
				}
				elseif ($clas === self::Numero AND in_array($val[0], ['>', '<', '=']) AND count($val) === 2)
				{
					$_where .= ' ' . $val[0] . ' ' . qp_esc($val[1]);
				}
				elseif (in_array($clas, [self::Numero, self::FechaHora, self::Fecha, self::Hora]) AND count($val) === 2)
				{
					$_where .= ' BETWEEN ' . qp_esc($val[0]) . ' AND ' . qp_esc($val[1]) . '';
				}
				else
				{
					$_where .= ' IN (' . implode(', ', array_map('qp_esc', $val)) . ')';
				}
			}
			elseif (is_null($val) and ! $field_dats['nn'])
			{
				$_where .= ' IS NULL';
			}
			elseif (in_array($clas, [self::FechaHora, self::Fecha, self::Hora]))
			{
				$_where .= ' LIKE "' . esc($val) . '%"';
			}
			else
			{
				$_where .= ' = ' . qp_esc($val);
			}

			$_sql_where .= $_where;
		}

		if ( ! is_null($sortby))
		{
			is_array($sortby) or $sortby = (array)$sortby;
			! isset($sortby[0]) and ! is_empty($sortby) and $sortby = [$sortby];

			$_sql_where .= ' ORDER BY ' . implode(', ', array_map(function($o){
				$o = (array)$o;
				(isset($o[1]) and in_array($o[1], ['ASC', 'DESC'])) or $o[1] = 'DESC';
				return '`' . $o[0] . '` ' . $o[1];
			}, $sortby));
		}

		if ( ! is_null($limit))
		{
			$_sql_where .= ' LIMIT ' . $limit;
		}

		$query = 'SELECT * FROM `' . self::tblname() . '` WHERE TRUE' . $_sql_where;
		$data = (array) sql_data($query, FALSE);

		$_return = [];
		foreach($data as $reg)
		{
			$_return[] = self::FromArray($reg);
		}

		return $_return;
	}

	/**
	 * FindBy ()
	 * Busca un objeto basado en filtros
	 * @param array $filter
	 * @param int $limit
	 * @param string $sortby
	 * @return array[self]
	 */
	public static function FindBy ($filter = [])
	{
		$that = self::gcc();
		$lista = self :: Lista ($filter, 1);
		$lista = (array) $lista;

		$first = array_shift($lista);
		is_null($first) and $first = new $that();

		return $first;
	}

	//////////////////////////////////////
	/// Atributos de objeto creado     ///
	//////////////////////////////////////

	/**
	 * $_found
	 */
	protected $_found = FALSE;

	/**
	 * $_from_array
	 */
	protected $_from_array = FALSE;

	/**
	 * $_data_original
	 * Data original del objeto en la base datos
	 * Permite comparar los cambios realizados en UPDATE y se actualiza después de cada SELECT
	 */
	protected $_data_original;

	/**
	 * $_data_instance
	 */
	protected $_data_instance;

	/**
	 * $_manual_setted
	 * Listado de campos que el usuario estableció manualmente
	 *
	 * Consideraciones:
	 * - Si el nuevo valor es NULL el campo se quita de la lista
	 * - Después de un SELECT se vacía la lista
	 * - Los rxs_hijo son insertados/actualizados despues de cada insert/update del objeto
	 */
	protected $_manual_setted = [];

	/**
	 * $_errors
	 */
	protected $_errors = [];

	/**
	 * $_extra_functions
	 */
	protected $_extra_functions = [];

	//////////////////////////////////////
	/// Funciones del objeto creado    ///
	//////////////////////////////////////

	/**
	 * add_error ()
	 */
	public function add_error ($error)
	{
		if (is_array($error))
		{
			foreach($error as $_error)
			{
				$this->add_error($_error);
			}
			return;
		}

		$this->_errors[] = $error;
	}

	/**
	 * logger ()
	 */
	public function logger ($_error, $_op_level = 1, $_function = null, $_severity = E_USER_WARNING, $filepath = __FILE__, $line = __LINE__)
	{
		$message = $_error . ' [' . self::gcc() . ($_op_level > 1 ? ('#' . $_op_level) : '') . '*' . $_function . ']';
		\logger ($message, $_severity, $_severity, [
			'Objeto' => (array) $this,
		], $filepath, $line);

		return $this;
	}

	/**
	 * get_errors ()
	 */
	public function get_errors ()
	{
		return $this->_errors;
	}

	/**
	 * get_error ()
	 */
	public function get_error ($join_by = '<br>')
	{
		return implode($join_by, array_map(function($o){
			return '- ' . $o;
		}, $this->_errors));
	}

	/**
	 * get_last_error ()
	 */
	public function get_last_error ()
	{
		$_errors = $this->_errors;
		return array_pop($_errors);
	}

	/**
	 * found ()
	 */
	public function found()
	{
		return $this->_found;
	}

	/**
	 * set_found_as ()
	 */
	public function set_found_as ($found)
	{
		$this->_found = $found;
		return $this;
	}

	/**
	 * from_array ()
	 */
	public function from_array()
	{
		return $this->_from_array;
	}

	/**
	 * CastVal
	 * Retorna el valor casteado y reparado basado en el indice
	 */
	public function CastVal ($indice, $valor = null)
	{
		// Alojando las columnas
		$columns   = self::columns();
		$rxs_padre = self::rxs_padre();

		if ( ! isset($columns[$indice]))
			return $valor;

		$column   = $columns[$indice];
		$nullable = ! $column['nn'];
		$tipo     = $column['tipo'];

		if (is_empty($valor))
			return $this -> _cv_onempty ($nullable, $tipo);

		$valor = $this -> _cv_check_tipo      ($valor, $tipo);
		$valor = $this -> _cv_check_largo_max ($valor, $tipo, (int) $column['largo'],    function ($error) use ($indice){
			$this -> logger ('Índice [' . $indice . ']: ' . $error, 0, __FUNCTION__, E_USER_WARNING, __FILE__, __LINE__);
		});
		$valor = $this -> _cv_check_in_array  ($valor, $tipo, (array) $column['opciones'], $nullable, function ($error) use ($indice){
			$this -> logger ('Índice [' . $indice . ']: ' . $error, 0, __FUNCTION__, E_USER_WARNING, __FILE__, __LINE__);
		});

		   isset($this->_callbacks['cast_val']) and 
		$valor = $this->_callbacks['cast_val']($valor, $indice,   $this);

		   isset($this->_callbacks['cast_val_' . $indice]) and 
		$valor = $this->_callbacks['cast_val_' . $indice]($valor, $this);

		return $valor;
	}

	/**
	 * UID
	 * Hasheo único basado en los atributos llaves
	 */
	public function UID ()
	{
		return $this->_uid();
	}

	/**
	 * valid
	 * Permite validar que todos los campos requeridos esten llenos
	 */
	public function valid ()
	{
		return $this -> _verify('validar', 1, false);
	}

	/**
	 * reset
	 * Permite deshacer cualquier cambio realizado hasta el último select realizado
	 * Esto permite que previo a hacer un update o delete, la información regrese a como era el ultimo select
	 */
	public function reset ()
	{
		$this -> _found = false;
		return $this -> select();
	}

	/**
	 * getData()
	 * Obtiene un array con los campos requeridos
	 *
	 * @param Array $fields Campos requeridos
	 * @return Array
	 */
	public function getData ($fields = NULL, $context = 'edit')
	{
		$return = [];
		$this->AssignDefaultContext($context);

		$fields = $this -> _callback_exec ('getData_fields', $fields, $context);
		if (is_empty($fields)) $fields = array_keys((array)$this->_data_instance);
		$fields = (array)$fields;

		foreach($fields as $field)
		{
			$function = [$this, 'get_' . $field] and
			is_callable ($function) and 
			$return[$field] = call_user_func($function);
		}

		$return = (array)$return;
		$return = $this -> _callback_exec ('getData',   $return, $context);
		$return = $this -> _callback_exec ('getData_' . $context, $return);

		return $return;
	}

	/**
	 * FSADD ()
	 */
	public static function FSADD ($key, $callback)
	{
		$_gcc = get_called_class();
		$_extra_functions = $_gcc::$_static_extra_functions;
		isset($_extra_functions[$_gcc]) or $_extra_functions[$_gcc] = [];

		$_extra_functions[$_gcc][$key] = $callback;

		$_gcc::$_static_extra_functions = $_extra_functions;
		return $_gcc;
	}

	/**
	 * _extra_functions_static_add ()
	 */
	protected function _extra_functions_static_add ($key, $callback)
	{
		$_gcc = get_called_class();
		$_extra_functions = self::$_static_extra_functions;
		isset($_extra_functions[$_gcc]) or $_extra_functions[$_gcc] = [];

		$_extra_functions[$_gcc][$key] = $callback;

		self::$_static_extra_functions = $_extra_functions;
		return $this;
	}

	/**
	 * _extra_functions_add ()
	 */
	protected function _extra_functions_add ($key, $callback)
	{
		$_extra_functions = $this -> _extra_functions;
		isset($_extra_functions[$key]) or $_extra_functions[$key] = [];

		$_extra_functions[$key] = $callback;

		$this -> _extra_functions = $_extra_functions;
		return $this;
	}

	//////////////////////////////////////
	/// Funciones de consultas bbdd    ///
	//////////////////////////////////////

	/**
	 * insert_forced
	 * Permite hacer una consulta INSERT aún si existe el registro
	 */
	public function insert_forced (&$_sync = [], &$_changes = [], $_op_level = 1)
	{
		$this -> _found = false;

		$key = self::key();
		$msk = array_search($key, $this->_manual_setted);
		if ($msk !== false) {
			unset($this->_manual_setted[$msk]);
			$this->_manual_setted[$msk] = array_values($this->_manual_setted[$msk]);
		}

		return $this -> insert($_sync, $_changes, $_op_level);
	}

	/**
	 * insert_update
	 * Permite hacer una consulta INSERT si el registro no existe o UPDATE si existe
	 */
	public function insert_update (&$_sync = [], &$_changes = [], $_op_level = 1)
	{
		return $this->_found ? 
		$this->update($_sync, $_changes, $_op_level) : 
		$this->insert($_sync, $_changes, $_op_level);
	}

	/**
	 * select
	 * Permite hacer una consulta SELECT a la DB 
	 */
	public function select (&$_sync = [])
	{
		$_uid  = $this -> _uid();
		$query = $this -> _query_select ();
		$data  = $this -> _sql_data($query, TRUE);

		if (is_null($data) or count($data) === 0)
		{
			$this->_found = FALSE;
		}
		else
		{
			$this->_found = TRUE;
			foreach($data as $k => $v)
			{
				$this->_data_instance[$k] = $v;
				$this->_data_original[$k] = $v;
			}
		}

		$this -> __construct_fields ();
		$this -> _manual_setted = [];

		if ($this->_found)
		{
			$_sync[self::gcc()][$_uid] = $this->__toArray();
		}
		else
		{
			$_sync['eliminar'][self::gcc()][] = $_uid;
		}

		return $this;
	}

	/**
	 * recalc_childs
	 */
	public function recalc_childs ()
	{
		$this->_callback_exec ('recalc_childs');
	}

	/**
	 * sync_childs
	 */
	public function sync_childs ($rxshj_editeds, &$_sync = [], &$_changes = [], $_op_level = 1)
	{
		$rxshj_editeds = $this->_callback_exec ('before_sync_childs', $rxshj_editeds);
		if (count($rxshj_editeds) === 0) return true;

		foreach($rxshj_editeds as $field => $_rx)
		{
			$rx    = $_rx['rx'];
			$data  = $_rx['data'];
			$iattr = $_rx['iattr'];
			$auniq = $_rx['auniq'];
			$class = 'Objeto\\' . $rx['clase'];

			$_UIDs = [];
			foreach($data as $reg)
			{
				$reg_o = $reg;

				if ( ! is_object($reg_o) or ! is_a($reg_o, $class))
				{
					$reg_d = $reg_o;
					$reg_o = new $class();
					$reg_k = $reg_o :: keys();

					foreach($reg_k as $k) 
						if (isset($reg_d[$k]))
							$reg_o[$k] = $reg_d[$k];

					// Comprobar que el objeto existe en base datos habiendo añadido las posibles llaves existentes
					$reg_o -> select();

					foreach($reg_d as $k => $v) 
						$reg_o[$k] = $v; // Se actualiza la información exista o no el objeto
				}

				if ($reg_o -> found())
				{
					// Ya que el objeto existe, comprobar que esté asociado al objeto padre
					$all_correct = true;
					foreach($rx['columnas'] as $_padre => $_hijo)
					{
						if ($reg_o[$_hijo] <> $this->_data_instance[$_padre])
						{
							$all_correct = false;
							break;
						}
					}

					if ( ! $all_correct) 
						$reg_o -> set_found_as (false); // Esto ingresará un nuevo registro en ves de actualizar uno que no le corresponde
				}
				else
				{
					// Ya que el objeto existe, se asocia al objeto padre
					foreach($rx['columnas'] as $_padre => $_hijo) 
						$reg_o[$_hijo] = $this->_data_instance[$_padre];
				}

				$_exec = $reg_o->insert_update($_sync, $_changes, ($_op_level + 1));
				if ( ! $_exec)
				{
					$_errors = $reg_o->get_errors();
					foreach($_errors as $_error)
					{
						$this -> add_error ('[' . $reg_o::gcc() . ($_op_level > 1 ? ('#' . $_op_level) : '') . '] ' . $_error);
					}
					return false;
				}
				$_UIDs[] = $reg_o -> UID ();
			}

			$this->select();
			$actuales = $this->offsetGet ($field);

			// Si es attr
			if ($iattr)
			{
				// borrar todos los que no se encuentran en la lista enviada
				$actuales_temp = $actuales;
				$actuales      = [];
				while(count($actuales_temp) > 0)
				{
					$obj = array_shift($actuales_temp);
					$UID = $obj->UID ();

					if ( ! in_array($UID, $_UIDs))
					{
						$obj -> delete();
						continue;
					}

					$actuales[] = $obj;
				}

				// eliminar los no unicos
				if ( ! is_empty($auniq))
				{
					$actuales_temp = $actuales;
					$actuales      = [];
					$valores       = [];
					while(count($actuales_temp) > 0)
					{
						$obj   = array_shift($actuales_temp);
						$valor = $obj[$auniq];

						if (in_array($valor, $valores))
						{
							$obj -> delete();
							continue;
						}

						$valores[]  = $valor;
						$actuales[] = $obj;
					}
				}

				$this->_callback_exec ('sync_childs_attr',        $field, $actuales, $_rx, $auniq, $class);
				$this->_callback_exec ('sync_childs_' . $field . '_attr', $actuales, $_rx, $auniq, $class);
			}

			$this->_callback_exec ('sync_childs',   $field, $actuales, $iattr, $_rx, $auniq, $class);
			$this->_callback_exec ('sync_childs_' . $field, $actuales, $iattr, $_rx, $auniq, $class);
		}

		$this->_callback_exec ('sync_childs', $rxshj_editeds);
		return true;
	}

	/**
	 * sync_rx_padres
	 */
	public function sync_rx_padres ($rxs_padre, &$_sync = [], &$_changes = [], $_op_level = 1)
	{
		$rxs_padre = $this->_callback_exec ('before_sync_rx_padres', $rxs_padre);
		if (count($rxs_padre) === 0) return true;

		// Actualizar los RX_PADRES que requieren actualización
		foreach($rxs_padre as $rx)
		{
			if ( ! $rx['fus']) continue;

			$field = $rx['field'];
			$reg_o = $this[$field];

			$reg_o -> recalc_childs ();
			$_exec = $reg_o -> update($_sync, $_changes, ($_op_level + 1));

			if ( ! $_exec)
			{
				$_errors = $reg_o->get_errors();
				foreach($_errors as $_error)
				{
					$this->add_error('[' . $reg_o::gcc() . ($_op_level > 1 ? ('#' . $_op_level) : '') . '] ' . $_error);
				}
				return false;
			}
		}

		$this->_callback_exec ('sync_rx_padres', $rxs_padre);
		return true;
	}

	/**
	 * insert
	 * Permite hacer una consulta INSERT a la DB 
	 */
	public function insert (&$_sync = [], &$_changes = [], $_op_level = 1)
	{
		$columns   = self::columns();
		$_key      = self::key();
		$rxs_hijo  = self::rxs_hijo();
		$rxs_padre = self::rxs_padre();

		if ($this->_found)
		{
			$_error = 'El objeto a ingresar ya existe en la base datos.';
			$this->add_error($_error);
			$this -> logger ($_error, $_op_level, __FUNCTION__, E_USER_WARNING, __FILE__, __LINE__);
			return false;
		}

		$this -> _callback_exec ('before_insert');
		$this -> _callback_exec ('before_insert_update');

		$_valid = $this->_verify('insertar', $_op_level);
		if ( ! $_valid) return false;

		$_ai_key = NULL;
		if ( ! is_empty($_key) and $columns[$_key]['ai'] and ! in_array($_key, $this->_manual_setted))
		{
			$_ai_key = $_key;
			unset($columns[$_key]);
		}

		$_data_instance = $this->_data_instance;
		$_insert_data   = [];
		$rxshj_editeds  = [];

		foreach($columns as $column)
		{
			if ($column['dg']) continue;

			$field = $column['nombre'];
			$value = isset($_data_instance[$field]) ? $_data_instance[$field] : NULL;
			$value = $this -> _qp_esc($value, ! $column['nn']);
			$_insert_data[$field] = $value;
		}

		foreach($rxs_hijo as $rx)
		{
			$field = $rx['field'];
			$iattr = $rx['is_attr'];
			$auniq = $rx['attr_uniq'];

			if ( ! in_array($field, $this->_manual_setted)) continue;
			$rxshj_editeds[$field] = [
				'iattr' => $iattr,
				'auniq' => $auniq,
				'data'  => $_data_instance[$field],
				'rx'    => $rx,
			];
		}

		$query = '';
		$query.= 'INSERT INTO `' . self::tblname() . '` ' . PHP_EOL;
		$query.= '(';
		$query.= implode(', ', array_map(function($o){
			return PHP_EOL . '  `' . $o . '`';
		}, array_keys($_insert_data))) . PHP_EOL;
		$query.= ')' . PHP_EOL;
		$query.= 'VALUES' . PHP_EOL;
		$query.= '(';
		$query.= implode(', ', array_map(function($o){
			return PHP_EOL . '  ' . $o;
		}, array_values($_insert_data))) . PHP_EOL;
		$query.= ')' . PHP_EOL;

		$gcc = self::gcc();
		$query = filter_apply ('ObjetoBase::Insert', $query, $gcc, $this);
		$query = filter_apply ('ObjTbl::Insert',	 $query, $gcc, $this);

		sql_trans();
		$_exec = $this -> _sql($query,  ! is_null($_ai_key));

		if ( ! $_exec)
		{
			sql_trans(false);
			global $_MYSQL_errno;

			switch ($_MYSQL_errno)
			{
				case 1062:
					$_error = 'Se encontró un registro duplicado en la base datos.';
					break;
				default:
					$_error = 'Se produjo un error al ingresar el registro en la base datos.';
					break;
			}
			$this->add_error($_error);
			$this -> logger ($_error, $_op_level, __FUNCTION__, E_USER_WARNING, __FILE__, __LINE__);
			return false;
		}

		if ( ! is_null($_ai_key))
		{
			$this->_data_instance[$_ai_key] = $_exec;
			$this->_data_original[$_ai_key] = $_exec;
		}

		$this -> select($_sync);
		$_changes[] = [
			'accion'    => 'insert',
			'clase'     => self::gcc(),
			'tabla'     => self::tblname(),
			'tabla_key' => $this->_uid(),
			'anterior'  => [],
			'nuevo'     => $this->__toArray(),
		];

		$this -> _callback_exec ('insert');
		$this -> _callback_exec ('insert_update');

		$sync_childs = $this -> sync_childs($rxshj_editeds, $_sync, $_changes, $_op_level);
		if ( ! $sync_childs) return false;

		$sync_rx_padres = $this -> sync_rx_padres ($rxs_padre , $_sync, $_changes, $_op_level);
		if ( ! $sync_rx_padres) return false;

		
		$this -> _callback_exec ('after_insert');
		$this -> _callback_exec ('after_insert_update');

		sql_trans(true);
		$this -> select();

		if ($_op_level === 1)
		{
			$gcc = self::gcc();
			action_apply('ObjetoBase::Changes', $_changes, $gcc, $this);
			action_apply('ObjTbl::Changes',	 $_changes, $gcc, $this);
		}

		return true;
	}

	/**
	 * update
	 * Permite hacer una consulta UPDATE a la DB 
	 */
	public function update (&$_sync = [], &$_changes = [], $_op_level = 1)
	{
		$columns   = self::columns();
		$rxs_hijo  = self::rxs_hijo();
		$rxs_padre = self::rxs_padre();
		$_keys     = self::keys();
		$_key      = self::key();

		if ( ! $this->_found)
		{
			$_error = 'El objeto a actualizar aún no existe en la base datos.';
			$this->add_error($_error);
			$this -> logger ($_error, $_op_level, __FUNCTION__, E_USER_WARNING, __FILE__, __LINE__);
			return false;
		}

		$this -> _callback_exec ('before_update');
		$this -> _callback_exec ('before_insert_update');

		$_valid = $this->_verify('actualizar', $_op_level);
		if ( ! $_valid) return false;

		$_data_instance      = $this->_data_instance;
		$_update_data_before = [];
		$_update_data_after  = [];
		$_update_data        = [];
		$_tabla_key          = $this->_uid();
		$_keys_edited        = [];
		$rxshj_editeds       = [];

		foreach($columns as $column)
		{
			if ($column['dg']) continue;

			$field = $column['nombre'];

			if (mb_strtolower($column['attr']) === mb_strtolower('on update CURRENT_TIMESTAMP') and ! in_array($field, $this->_manual_setted)) continue;

			$value_after  = isset($_data_instance[$field])       ? $_data_instance[$field]       : NULL;
			$value_before = isset($this->_data_original[$field]) ? $this->_data_original[$field] : NULL;

			if ($value_before == $value_after) continue;

			$_update_data_before[$field] = $value_before;
			$_update_data_after [$field] = $value_after;

			$value_after = $this -> _qp_esc($value_after, ! $column['nn']);
			$_update_data[$field] = $value_after;

			if (in_array($field, $_keys)) $_keys_edited[] = $field;
		}

		foreach($rxs_hijo as $rx)
		{
			$field = $rx['field'];
			$iattr = $rx['is_attr'];
			$auniq = $rx['attr_uniq'];

			if ( ! in_array($field, $this->_manual_setted)) continue;
			$rxshj_editeds[$field] = [
				'iattr' => $iattr,
				'auniq' => $auniq,
				'data'  => $_data_instance[$field],
				'rx'    => $rx,
			];
		}

		if (count($_update_data) === 0)
		{
			$this->add_error('No se han realizado cambios');

			$sync_childs = $this -> sync_childs($rxshj_editeds, $_sync, $_changes, $_op_level);
			if ( ! $sync_childs) return false;

			$sync_rx_padres = $this -> sync_rx_padres ($rxs_padre, $_sync, $_changes, $_op_level);
			if ( ! $sync_rx_padres) return false;

			$this -> select ();
			return true;
		}

		if (count($_keys_edited) > 0) // Se han actualizado algún KEY
		{
			$posible_error = grouping($_keys_edited, [
				'prefix' => ['El campo ', 'Los campos '],
				'suffix' => [' no puede ser actualizado', ' no pueden ser actualizados'],
			]);

			// validar que no hayan hijos con on_update = 'NO ACTION' or 'RESTRICT'
			foreach($rxs_hijo as $rx)
			{
				if ( ! in_array($rx['on_update'], ['NO ACTION', 'RESTRICT'])) continue;
				$_error = $posible_error;
				$this->add_error($_error);
				$this -> logger ($_error, $_op_level, __FUNCTION__, E_USER_WARNING, __FILE__, __LINE__);
				return false;
			}
		}

		sql_trans();

		$query = '';
		$query.= 'UPDATE `' . self::tblname() . '` ' . PHP_EOL;
		$query.= 'SET';
		$query.= implode(', ', array_map(function($o, $p){
			return PHP_EOL . '  `' . $o . '` = ' . $p;
		}, array_keys($_update_data), array_values($_update_data))) . PHP_EOL;
		$query.= 'WHERE TRUE' . PHP_EOL;
		$query.= $this->_query_where();
		$query.= 'LIMIT 1' . PHP_EOL;

		$gcc = self::gcc();
		$query = filter_apply ('ObjetoBase::Update', $query, $gcc, $this);
		$query = filter_apply ('ObjTbl::Update',     $query, $gcc, $this);

		$_exec = $this -> _sql($query);

		if ( ! $_exec)
		{
			sql_trans(false);

			$_error = 'Se produjo un error al actualizar el registro de la base datos.';
			$this->add_error($_error);
			$this -> logger ($_error, $_op_level, __FUNCTION__, E_USER_WARNING, __FILE__, __LINE__);
			return false;
		}

		foreach($_keys_edited as $_key)
		{
			// En algunas ocasiones se actualizan los IDs y cuando se ejecuta el SELECT retorna como no encontrado
			$this->_data_original[$_key] = $this->_data_instance[$_key];
		}

		$this->select($_sync);
		$_changes[] = [
			'accion'    => 'update',
			'clase'     => self::gcc(),
			'tabla'     => self::tblname(),
			'tabla_key' => $_tabla_key,
			'anterior'  => $_update_data_before,
			'nuevo'     => $_update_data_after,
		];

		$this -> _callback_exec ('update');
		$this -> _callback_exec ('insert_update');

		$sync_childs = $this -> sync_childs($rxshj_editeds, $_sync, $_changes, $_op_level);
		if ( ! $sync_childs) return false;

		$sync_rx_padres = $this -> sync_rx_padres ($rxs_padre, $_sync, $_changes, $_op_level);
		if ( ! $sync_rx_padres) return false;

		// Actualizar los KEYs de los objetos que no se han actualizado mediante base de datos
		if (count($_keys_edited) > 0)
		{
			foreach($rxs_hijo as $rx)
			{
				if ( ! (
					in_array($rx['on_update'], ['CASCADE', 'SET NULL']) // Se debe actualizar o poner NULO
					and $rx['on_delete'] === 'NOTHING DO' // Pero no se generó la RX en la db porque el DELETE es NOTHING DO
				)) continue;

				$field = $rx['field'];
				$data  = (array)$this->offsetGet($field);
				$vnulo = $rx['on_update'] === 'SET NULL';

				foreach($data as $reg_o)
				{
					foreach($rx['columnas'] as $_padre => $_hijo)
					{
						if (isset($_update_data[$_padre]))
						{
							$_updated = true;
							$reg_o[$_hijo] = $vnulo ? NULL : $_update_data[$_padre];
						}
					}

					$reg_o -> update(); // No importa si genera error
				}
			}
		}

		$this -> _callback_exec ('after_update');
		$this -> _callback_exec ('after_insert_update');

		sql_trans(true);
		$this -> select();

		if ($_op_level === 1)
		{
			$gcc = self::gcc();
			action_apply('ObjetoBase::Changes', $_changes, $gcc, $this);
			action_apply('ObjTbl::Changes',	 $_changes, $gcc, $this);
		}

		return TRUE;
	}

	/**
	 * delete
	 * Permite hacer una consulta DELETE a la DB 
	 */
	public function delete (&$_sync = [], &$_changes = [], $_op_level = 1)
	{
		return $this -> delete_wr ([], $_sync, $_changes, $_op_level);
	}

	/**
	 * delete_wr
	 * Permite hacer una consulta DELETE a la DB omitiendo la validación de los hijos enviados
	 */
	public function delete_wr ($_omitir_hijos = [], &$_sync = [], &$_changes = [], $_op_level = 1)
	{
		$_tabla_key = $this->_uid();
		$rxs_hijo   = self::rxs_hijo();
		$rxs_padre  = self::rxs_padre();

		$_omitir_hijos_bool  = count($_omitir_hijos) > 0;
		$_delete_data_before = (array)$this->_data_instance;

		if ( ! $this->_found)
		{
			$this->add_error('El objeto no existe aún en la base datos');
			return true;
		}

		$this -> _callback_exec ('before_delete');

		// validar los rx_hijos con on_delete = 'NO ACTION' or 'RESTRICT'
		foreach($rxs_hijo as $rx)
		{
			$field = $rx['field'];
			if ( ! in_array($rx['on_delete'], ['NO ACTION', 'RESTRICT'])) continue;
			if (in_array($field, $_omitir_hijos) or in_array($rx['clase'], $_omitir_hijos)) continue;

			$data = $this->offsetGet($field);
			if (count($data) === 0) continue;

			$_error = 'No se puede eliminar el registro `' . self::gcc() . '` hasta que se eliminen los registros `' . $rx['clase'] . '`';
			$this->add_error('Se produjo un error al eliminar el registro de la base datos');
			$this -> logger ($_error, $_op_level, __FUNCTION__, E_USER_WARNING, __FILE__, __LINE__);
			return false;
		}

		sql_trans();

		$query = '';
		$query.= 'DELETE FROM `' . self::tblname() . '` ' . PHP_EOL;
		$query.= 'WHERE TRUE' . PHP_EOL;
		$query.= $this->_query_where();
		$query.= 'LIMIT 1' . PHP_EOL;

		$gcc = self::gcc();
		$query = filter_apply ('ObjetoBase::Delete', $query, $gcc, $this);
		$query = filter_apply ('ObjTbl::Delete',     $query, $gcc, $this);

		$_omitir_hijos_bool and $this -> _sql('SET FOREIGN_KEY_CHECKS=0;');
		$_exec = 				$this -> _sql($query);
		$_omitir_hijos_bool and $this -> _sql('SET FOREIGN_KEY_CHECKS=1;');

		if ( ! $_exec)
		{
			sql_trans(false);

			$_error = 'Se produjo un error al eliminar el registro de la base datos.';
			$this->add_error($_error);
			$this -> logger ($_error, $_op_level, __FUNCTION__, E_USER_WARNING, __FILE__, __LINE__);
			return false;
		}

		$this->select($_sync);
		$_changes[] = [
			'accion'    => 'delete',
			'clase'     => self::gcc(),
			'tabla'     => self::tblname(),
			'tabla_key' => $_tabla_key,
			'anterior'  => $_delete_data_before,
			'nuevo'     => [],
		];

		$this -> _callback_exec ('delete');

		// Eliminar los objetos hijos o Actualizar los KEYs que relaciona a este objeto que no se ha actualizado mediante base de datos
		foreach($rxs_hijo as $rx)
		{
			if ( ! (
				in_array($rx['on_delete'], ['CASCADE', 'SET NULL']) // Se debe actualizar o poner NULO
				and $rx['on_update'] === 'NOTHING DO' // Pero no se generó la RX en la db porque el DELETE es NOTHING DO
			)) continue;

			$field = $rx['field'];
			$data  = (array)$this->offsetGet($field);
			$vnulo = $rx['on_delete'] === 'SET NULL';

			foreach($data as $reg_o)
			{
				if ($vnulo)
				{
					foreach($rx['columnas'] as $_padre => $_hijo)
					{
						$reg_o[$_hijo] = NULL;
					}
					continue;
				}

				$reg_o -> delete(); // No importa si genera error
			}
		}

		$sync_rx_padres = $this -> sync_rx_padres ($rxs_padre, $_sync, $_changes, $_op_level);
		if ( ! $sync_rx_padres) return false;

		$this -> _callback_exec ('after_delete');

		sql_trans(true);
		$this -> select();

		if ($_op_level === 1)
		{
			$gcc = self::gcc();
			action_apply('ObjetoBase::Changes', $_changes, $gcc, $this);
			action_apply('ObjTbl::Changes',     $_changes, $gcc, $this);
		}

		return TRUE;
	}

	/**
	 * insert_odk_update
	 * Permite hacer una consulta INSERT ON DUPLICATE KEY UPDATE  a la DB 
	 */
	public function insert_odk_update (&$_sync = [], &$_changes = [], $_op_level = 1)
	{
		$columns   = self::columns();
		$_key      = self::key();
		$rxs_hijo  = self::rxs_hijo();
		$rxs_padre = self::rxs_padre();

		if ($this->_found)
		{
			$_error = 'El objeto a ingresar ya existe en la base datos.';
			$this->add_error($_error);
			$this -> logger ($_error, $_op_level, __FUNCTION__, E_USER_WARNING, __FILE__, __LINE__);
			return false;
		}

		$this -> _callback_exec ('before_insert');
		$this -> _callback_exec ('before_insert_update');

		$_valid = $this->_verify('insertar', $_op_level);
		if ( ! $_valid) return false;

		$_ai_key = NULL;
		if ( ! is_empty($_key) and $columns[$_key]['ai'] and ! in_array($_key, $this->_manual_setted))
		{
			$_ai_key = $_key;
			unset($columns[$_key]);
		}

		$_data_instance = $this->_data_instance;
		$_insert_data   = [];
		$rxshj_editeds  = [];

		foreach($columns as $column)
		{
			if ($column['dg']) continue;

			$field = $column['nombre'];
			$value = isset($_data_instance[$field]) ? $_data_instance[$field] : NULL;
			$value = $this -> _qp_esc($value, ! $column['nn']);
			$_insert_data[$field] = $value;
		}

		foreach($rxs_hijo as $rx)
		{
			$field = $rx['field'];
			$iattr = $rx['is_attr'];
			$auniq = $rx['attr_uniq'];

			if ( ! in_array($field, $this->_manual_setted)) continue;
			$rxshj_editeds[$field] = [
				'iattr' => $iattr,
				'auniq' => $auniq,
				'data'  => $_data_instance[$field],
				'rx'    => $rx,
			];
		}

		$query = '';
		$query.= 'INSERT INTO `' . self::tblname() . '` ' . PHP_EOL;
		$query.= '(';
		$query.= implode(', ', array_map(function($o){
			return PHP_EOL . '  `' . $o . '`';
		}, array_keys($_insert_data))) . PHP_EOL;
		$query.= ')' . PHP_EOL;
		$query.= 'VALUES' . PHP_EOL;
		$query.= '(';
		$query.= implode(', ', array_map(function($o){
			return PHP_EOL . '  ' . $o;
		}, array_values($_insert_data))) . PHP_EOL;
		$query.= ')' . PHP_EOL;
		$query.= 'ON DUPLICATE KEY UPDATE' . PHP_EOL;
		$query.= implode(', ', array_map(function($o, $p){
			return PHP_EOL . '  `' . $o . '` = ' . $p;
		}, array_keys($_insert_data), array_values($_insert_data))) . PHP_EOL;

		$gcc = self::gcc();
		$query = filter_apply ('ObjetoBase::InsertODKU', $query, $gcc, $this);
		$query = filter_apply ('ObjTbl::InsertODKU',	 $query, $gcc, $this);

		sql_trans();
		$_exec = $this -> _sql($query,  ! is_null($_ai_key));

		if ( ! $_exec)
		{
			sql_trans(false);
			global $_MYSQL_errno;

			switch ($_MYSQL_errno)
			{
				case 1062:
					$_error = 'Se encontró un registro duplicado en la base datos.';
					break;
				default:
					$_error = 'Se produjo un error al ingresar el registro en la base datos.';
					break;
			}
			$this->add_error($_error);
			$this -> logger ($_error, $_op_level, __FUNCTION__, E_USER_WARNING, __FILE__, __LINE__);
			return false;
		}

		if ( ! is_null($_ai_key))
		{
			$this->_data_instance[$_ai_key] = $_exec;
			$this->_data_original[$_ai_key] = $_exec;
		}

		$this -> select($_sync);
		$_changes[] = [
			'accion'    => 'insert',
			'clase'     => self::gcc(),
			'tabla'     => self::tblname(),
			'tabla_key' => $this->_uid(),
			'anterior'  => [],
			'nuevo'     => $this->__toArray(),
		];

		$this -> _callback_exec ('insert_odku');
		$this -> _callback_exec ('insert_update');

		$sync_childs = $this -> sync_childs($rxshj_editeds, $_sync, $_changes, $_op_level);
		if ( ! $sync_childs) return false;

		$sync_rx_padres = $this -> sync_rx_padres ($rxs_padre , $_sync, $_changes, $_op_level);
		if ( ! $sync_rx_padres) return false;

		
		$this -> _callback_exec ('after_insert_odku');
		$this -> _callback_exec ('after_insert_update');

		sql_trans(true);
		$this -> select();

		if ($_op_level === 1)
		{
			$gcc = self::gcc();
			action_apply('ObjetoBase::Changes', $_changes, $gcc, $this);
			action_apply('ObjTbl::Changes',	 $_changes, $gcc, $this);
		}

		return true;
	}

	//////////////////////////////////////
	/// Constructor del objeto         ///
	//////////////////////////////////////
	public function __construct (...$data_keys)
	{
		$gcc = get_called_class();
		$gcc :: establecer_CON($this);

		/** Habilitando los atributos que contendrán la data del objeto */
		$this->_data_original = new JArray();
		$this->_data_instance = new JArray();

		/** Paso al objeto como array */
		parent::__construct($this->_data_instance);
		$this-> __construct_callbacks();
		$this-> __construct_fields ();

		$this -> _callback_exec ('construct');

		while(count($data_keys) > 0)
		{
			$last = array_pop($data_keys);
			if (is_null($last)) continue;

			$data_keys[] = $last;
			break;
		}

		/** Si no se ha enviado las llaves entonces es un posible objeto nuevo */
		if (count($data_keys) === 0) return;

		/** Seteando la información de las llaves */
		$_keys = self::keys();
		foreach($_keys as $_ind => $_field)
		{
			if ( ! isset($data_keys[$_ind]) or is_null($data_keys[$_ind])) continue;

			// Agregando a la data de la instancia
			$this->_data_instance[$_field] = $data_keys[$_ind];
			$this->_data_original[$_field] = $data_keys[$_ind];
		}

		/** Buscar información del objeto basado en los campos laves */
		$this -> select();
	}

	public static function establecer_CON ($instance)
	{}

	//////////////////////////////////////
	/// Apoyos del objeto y su info    ///
	//////////////////////////////////////

	/**
	 * __construct_callbacks ()
	 */
	protected function __construct_callbacks()
	{
		$this->_callback_add('offsetSet', function ($newval, $index, $that) {
			$this -> _calc_ag ();
		});

		$this->_callback_add('before_set', function ($newval, $index, $that) {
			$newval = $this -> CastVal($index, $newval);
			return $newval;
		});
	}

	/**
	 * __construct_fields
	 * Permite añadir o corregir atributos faltantes
	 */
	protected function __construct_fields ()
	{
		$columns                  = self::columns();                  // Alojando las columnas
		$rxs_padre_nexto_fields   = self::rxs_padre_nexto_fields();   // Alojando todos los objetos de las cuales alguno de estos campos dependen
		$rxs_padre_nonexto_fields = self::rxs_padre_nonexto_fields(); // Alojando todos los objetos de las cuales alguno de estos campos dependen
		$rxs_hijo                 = self::rxs_hijo();                 // Alojando las relaciones de objetos que dependen de este objeto

		foreach($columns as $column)
		{
			$field   = $column['nombre'];
			$default = $column['defecto'];

			if (isset($this->_data_instance[$field]) and ! is_empty($this->_data_instance[$field])) continue;

			$this->_data_instance[$field] = $default;
		}

		foreach($rxs_hijo as $rx)
		{
			$field   = $rx['field'];
			$is_attr = $rx['is_attr'];

			$is_attr and 
			$this->_data_instance[$field] = (array) $this -> offsetGet ($field);
		}

		// Calculando los AG
		$this->_calc_ag();

		foreach($columns as $column)
		{
			$field = $column['nombre'];
			if (isset($this->_data_original[$field]) and ! is_empty($this->_data_original[$field])) continue;
			$this->_data_original[$field] = $this->_data_instance[$field];
		}

		$this->_fields_cast_val();
	}

	/**
	 * _calc_ag ()
	 * Calcula todos los autogenerados de código
	 */
	protected function _calc_ag ()
	{
		$columns = self::columns();
		foreach($columns as $column)
		{
			if (is_empty($column['ag'])) continue;

			$method = $column['ag'];
			is_callable($method) or $method = [$this, $method];
			if ( ! is_callable($method)) continue;

			$field = $column['nombre'];
			if (in_array($field, $this->_manual_setted)) continue; // Ha sido manipulado manualmente así que no se continuará calculando

			try
			{
				$actual = $this[$field];
				$valor  = call_user_func_array($method, [
					$this,
					$field,
					$actual,
				]);

				if ($actual <> $valor)
				{
					$this->offsetSet($field, $valor, false);
				}
			}
			catch (Exception $e)
			{}
		}
	}

	/**
	 * _fields_cast_val ()
	 */
	protected function _fields_cast_val ()
	{
		$fields = self::fields();
		foreach($fields as $field)
		{
			if ( ! isset($this->_data_instance[$field])) continue;
			$this->_data_instance[$field] = $this -> CastVal ($field, $this->_data_instance[$field]);
		}
	}

	/**
	 * _uid
	 * Hasheo único basado en los atributos llaves
	 */
	protected function _uid ()
	{
		$_key  = self::key();
		$_keys = self::keys();

		if ( ! is_empty($_key)) return $this->_data_original[$_key];

		$_llaves = [ self::gcc() ];
		foreach($_keys as $_key)
			$_llaves[] = $this->_data_original[$_key];

		return md5(json_encode($_llaves));
	}

	/**
	 * _query_select
	 */
	protected function _query_select ()
	{
		$query = '';
		$query.= 'SELECT *' . PHP_EOL;
		$query.= 'FROM `' . self::tblname() . '` tbl' . PHP_EOL;
		$query.= 'WHERE TRUE' . PHP_EOL;
		$query.= $this->_query_where('tbl');
		$query.= 'LIMIT 1' . PHP_EOL;

		$gcc   = self::gcc();
		$query = filter_apply ('ObjetoBase::Select', $query, $gcc, $this);
		$query = filter_apply ('ObjTbl::Select',     $query, $gcc, $this);
		$query = $this -> _callback_exec ('query_select',   $query, $this -> _default_context);

		return $query;
	}

	/**
	 * _query_where
	 */
	protected function _query_where ($_as_tbl = '')
	{
		is_empty($_as_tbl) or $_as_tbl = '`' . $_as_tbl . '`.';

		$query   = '';
		$keys    = self::keys();
		$columns = self::columns();
		$_data   = $this->_found ? $this->_data_original : $this->_data_instance;

		foreach($keys as $key)
		{
			$column   = $columns[$key];
			$nullable = ! $column['nn'];

			if (is_null($_data[$key]) and $nullable)
			{
				$query.= ' AND ' . $_as_tbl . '`'.$key.'` IS NULL' . PHP_EOL;
				continue;
			}

			$query.= ' AND ' . $_as_tbl . '`'.$key.'` = ' . $this -> _qp_esc($_data[$key]) . PHP_EOL;
		}

		return $query;
	}

	/**
	 * _verify
	 * Permite validar que todos los campos requeridos esten llenos
	 */
	protected function _verify ($from = null, $_op_level = 1, $_logger_error = true)
	{
		// Validar No vacíos
		$not_valids = [];
		$columns    = self::columns();

		$columns_ne = array_keys(array_filter($columns, function($o){
			return $o['ne'];
		}));
		foreach($columns_ne as $column)
		{
			if (is_empty($this->_data_instance[$column]))
				$not_valids[] = $column;
		}

		if (count($not_valids) > 0)
		{
			$_error = grouping($not_valids, [
				'prefix' => ['El campo ', 'Los campos '],
				'suffix' => [' es requerido', ' son requeridos'],
			]);
			$this->add_error($_error);

			$_logger_error and $this -> logger ($_error, $_op_level, __FUNCTION__, E_USER_WARNING, __FILE__, __LINE__);

			return false;
		}

		// Validar campos hijos
		$rxs_padre = self::rxs_padre();
		foreach($rxs_padre as $rx)
		{
			$a_null = true;
			$_pks   = [];
			$_pkn   = '';

			foreach($rx['columnas'] as $_padre => $_hijo)
			{
				$_valor_hijo = $this->_data_instance[$_hijo];
				if ( ! is_empty($_valor_hijo)) $a_null = false;
				$_pks[] = $_valor_hijo;

				if ($rx['c1']) $pkn = ' con `' . $_padre . '` = "' . $_valor_hijo . '"';
			}

			if ($a_null) continue; // Todos son NULL

			array_unshift($_pks, $rx['clase']);
			$obj_padre = call_user_func_array('obj', $_pks);

			if ( ! $obj_padre->found())
			{
				$_error = 'No existe objeto `' . $rx['clase'] . '`' . $pkn . ' (' . $rx['field'] . ') #FKE';
				$this->add_error('Se produjo un error al ' . $from .  ' el registro.');

				$_logger_error and $this -> logger ($_error, $_op_level, __FUNCTION__, E_USER_WARNING, __FILE__, __LINE__);

				return false;
			}
		}

		return $this -> _callback_exec ('verify', true, $from, $_op_level, $_logger_error);
	}

	//////////////////////////////////////
	/// Magic Functions                ///
	//////////////////////////////////////

	/**
	 * offsetGet
	 */
	public function offsetGet ($index)
	{
		$_founded     = NULL;
		$_founded_obj = NULL;

		$rxs_padre = self::rxs_padre();
		$rxs_hijo  = self::rxs_hijo();

		if (is_null($_founded))
		{
			foreach($rxs_padre as $rx)
			{
				if ($rx['field'] === $index)
				{
					$_founded     = 'rxs_padre';
					$_founded_obj = $rx;
					break;
				}
			}
		}

		if (is_null($_founded))
		{
			foreach($rxs_hijo as $rx)
			{
				if ($rx['field'] === $index)
				{
					$_founded     = 'rxs_hijo';
					$_founded_obj = $rx;
					break;
				}
			}
		}

		if ( ! is_null($_founded_obj) and in_array($_founded_obj['field'], $this->_manual_setted)) $_founded = NULL;

		if (is_null($_founded)) return parent::offsetGet($index);

		isset($this->_callbacks['before_get']) and
		$this->_callbacks['before_get']($index, $this);

		isset($this->_callbacks['before_get_' . $index]) and
		$this->_callbacks['before_get_' . $index]($index, $this);

		switch($_founded)
		{
			case 'rxs_padre':
				$_obj_params   = [];
				$_obj_params[] = $_founded_obj['clase'];

				foreach($_founded_obj['columnas'] as $_hijo => $_padre)
				{
					$_obj_params[] = $this->_data_instance[$_padre];
				}

				$obj = call_user_func_array('obj', $_obj_params);
				$return = $obj;
				break;

			case 'rxs_hijo':
				$class = 'Objeto\\' . $_founded_obj['clase'];

				$query = '';
				$query.= 'SELECT *' . PHP_EOL;
				$query.= 'FROM `' . $_founded_obj['tabla'] . '`' . PHP_EOL;
				$query.= 'WHERE TRUE' . PHP_EOL;

				foreach($_founded_obj['columnas'] as $_padre => $_hijo)
				{
					if (is_null($this->_data_instance[$_padre]))
					{
						$query.= ' AND `'.$_hijo.'` IS NULL' . PHP_EOL;
					}
					else
					{
						$query.= ' AND `'.$_hijo.'` = ' . $this -> _qp_esc($this->_data_original[$_padre]) . PHP_EOL;
					}
				}

				$gcc = $class::gcc();
				$query = filter_apply ('ObjetoBase::Select', $query, $gcc, $this);
				$query = filter_apply ('ObjTbl::Select',	 $query, $gcc, $this);
				$query = $this -> _callback_exec('query_select_childs', $query, $index, $gcc, $_founded_obj);

				$data = $this -> _sql_data($query);

				$return = [];
				foreach($data as $reg)
				{
					$return[] = $class::FromArray ($reg);
				}
				break;
		}

		isset($this->_callbacks['get']) and
		$this->_callbacks['get']($return, $index, $this);

		isset($this->_callbacks['get_' . $index]) and
		$this->_callbacks['get_' . $index]($return, $index, $this);

		isset($this->_callbacks[__FUNCTION__]) and
		$this->_callbacks[__FUNCTION__]($return, $index, $this);

		return $return;
	}

	/**
	 * offsetSet
	 */
	public function offsetSet ($index, $newval, $manual = TRUE)
	{
		$_rxs_padre_castval_objects = self::rxs_padre_castval_objects();
		$_rxs_padre_castval_objects_fields = array_keys($_rxs_padre_castval_objects);
		if (in_array($index, $_rxs_padre_castval_objects_fields) and is_a($newval, $_rxs_padre_castval_objects[$index]['class']))
		{
			$campos = $_rxs_padre_castval_objects[$index]['campos'];
			foreach($campos as $padre => $hijo)
			{
				$this->offsetSet($hijo, $newval[$padre], $manual);
			}
			return;
		}

		if ($manual)
		{
			if (is_null($newval))
			{
				$this->_manual_setted = array_diff($this->_manual_setted, [$index]);
			}
			else
			{
				$this->_manual_setted[] = $index;
				$this->_manual_setted = array_unique($this->_manual_setted);
			}
		}

		return parent::offsetSet($index, $newval);
	}

	public function __toArray()
	{
		isset($this->_callbacks['before_toarray']) and
		      $this->_callbacks['before_toarray']($this);

		$return = [];
		$fields = self::fields();
		foreach($fields as $field)
		{
			$return[$field] = $this->_data_instance[$field];
		}

		isset($this->_callbacks['toarray']) and
		      $this->_callbacks['toarray']($return, $this);

		isset($this->_callbacks[__FUNCTION__]) and
		      $this->_callbacks[__FUNCTION__]($return, $this);

		return $return;
	}

	/**
	 * count ()
	 */
	public function count ()
	{
		return $this->_found ? 1 : 0;
	}

	/**
	 * __toString  ()
	 */
	public function __toString  ()
	{
		$field = self::toString();
		$key   = self::key();
		$keys  = self::keys();

		is_null($field) and $field = $key;
		is_null($field) and $field = array_shift($keys);

		return $this->$field;
	}

	/**
	 * __call ()
	 */
	public function __call ($name, $args)
	{
		try
		{
			$undefined_method = false;
			$return = parent :: __call ($name, $args);
		}
		catch (Exception $e)
		{
			if ( ! preg_match('/^Función requerida no existe/i', $e->getMessage()))
				throw $e;
			$undefined_method = true;
		}

		$params = $args;
		$params[] = $this;
		$params[] = $name;

		$_gcc = get_called_class();
		$_extra_functions = self::$_static_extra_functions;
		isset($_extra_functions[$_gcc]) or $_extra_functions[$_gcc] = [];
		if (isset($_extra_functions[$_gcc][$name]))
		{
			try
			{
				$temp = call_user_func_array($_extra_functions[$_gcc][$name], $params);
				return $temp;
			}
			catch(Exception $e)
			{}
		}

		$_extra_functions = $this -> _extra_functions;
		if (isset($_extra_functions[$name]))
		{
			try
			{
				$temp = call_user_func_array($_extra_functions[$name], $params);
				return $temp;
			}
			catch(Exception $e)
			{}
		}

		if ($undefined_method) throw $e;
		return $return;
	}

	/** DB */
	protected $_CON;
	protected function CON ()
	{
		isset($this -> _CON) or $this -> _CON = use_CON ();
		return $this -> _CON;
	}
	public function set_CON ($CON)
	{
		$this -> _CON = $CON;
		return $this;
	}
	protected function _sql(string $query, $is_insert = FALSE)
	{
			return sql ($query, $is_insert, $this -> CON());
	}
	protected function _sql_data(string $query, $return_first = FALSE, $fields = NULL)
	{
			return sql_data ($query, $return_first, $fields, $this -> CON());
	}
	protected function _qp_esc ($valor = '', $or_null = FALSE)
	{
			return qp_esc ($valor, $or_null, $this -> CON());
	}
	
}