<?php
/*
 * DataTables SPP
 */

class SSP
{
	public static function simple ( $request, $table_name, $table_pk, $columns )
	{
		$instance = new self ($table_name, $table_pk, $columns);

		return $instance
		-> ProcessRequest ( $request );
	}

	public static function complex ( $request, $table_name, $table_pk, $columns, $where_result = null, $where_all = null )
	{
		$instance = new self ($table_name, $table_pk, $columns);

		$instance
		-> set_where_result ( $where_result )
		-> set_where_all    ( $where_all )
		;

		return $instance
		-> ProcessRequest ( $request );
	}

	protected $_table_name;
	public function set_table_name ($table_name)
	{
		$this -> _table_name = $table_name;
		return $this;
	}
	public function get_table_name ()
	{
		return $this -> _table_name;
	}

	protected $_table_pk;
	public function set_table_pk ($table_pk)
	{
		$this -> _table_pk = $table_pk;
		return $this;
	}
	public function get_table_pk ()
	{
		return $this -> _table_pk;
	}

	protected $_columns;
	public function set_columns ($columns)
	{
		$this -> _columns = $columns;
		return $this;
	}
	public function get_columns ()
	{
		return $this -> _columns;
	}

	protected $_where_result = null;
	public function set_where_result ($where_result)
	{
		$this -> _where_result = $where_result;
		return $this;
	}
	public function get_where_result ()
	{
		return $this -> _where_result;
	}

	protected $_where_all = null;
	public function set_where_all ($where_all)
	{
		$this -> _where_all = $where_all;
		return $this;
	}
	public function get_where_all ()
	{
		return $this -> _where_all;
	}

	protected $_CON = null;
	public function set_CON ($CON)
	{
		$this -> _CON = $CON;
		return $this;
	}

	public function __construct ( $table_name, $table_pk, $columns )
	{
		$this
		-> set_table_name ( $table_name )
		-> set_table_pk   ( $table_pk   )
		-> set_columns    ( $columns    )
		-> set_CON        ( use_CON()   )
		;
	}

	protected $_columns_dt = [];
	protected function _cache_columns_dt ()
	{
		$this -> _columns_dt = self :: pluck ( (array) $this -> _columns, 'dt' );
	}

	protected $_columns_db = [];
	protected function _cache_columns_db ()
	{
		$this -> _columns_db = self :: pluck ( (array) $this -> _columns, 'db' );
	}

	public function ProcessRequest ( $request )
	{
		$gcc = get_called_class();

		$this -> _cache_columns_dt ();
		$this -> _cache_columns_db ();

		$columns_db  = $this -> _columns_db;

		// Build the SQL query string from the request
		$_where = $this -> _where ( $request );
		$_where_all = $this -> _where_all (  );
		$_order = $this -> _order ( $request );
		$_limit = $this -> _limit ( $request );

		$columns_db = array_map(function($o){
			return '`' . $o . '`';
		}, array_values ($columns_db));
		$select = implode(', ', $columns_db);

		$table  = $this -> _table_name;
		$primaryKey  = $this -> _table_pk;

		// Main query to actually get the data
		$query = 'SELECT ' . $select . ' FROM `' . $table . '` ' . $_where . ' ' . $_order . ' ' . $_limit;
		addJSON('$query', $query);
		$data = $this -> sql ($query);
		$data = $this -> parse_data ($data);

		// Data set length after filtering
		$query = 'SELECT COUNT(`'. $primaryKey . '`) total FROM `' . $table . '` ' . $_where;
		$recordsFiltered = (int) $this -> sql ($query, true, 'total');

		// Total data set length
		$query = 'SELECT COUNT(`'. $primaryKey . '`) total FROM `' . $table . '`' . $_where_all;
		$recordsTotal = (int) $this -> sql ($query, true, 'total');

		$draw = isset ( $request['draw'] ) ? intval( $request['draw'] ) : 0;

		return [
			'draw'            => $draw,
			'recordsTotal'    => $recordsTotal,
			'recordsFiltered' => $recordsFiltered,
			'data'            => $data,
		];
	}

	public function __invoke( $request )
	{
		return $this -> ProcessRequest ( $request );
	}

	protected function sql ( $query, $return_first = false, $return_first_field = null )
	{
		return sql_data( $query, $return_first, $return_first_field );
	}

	protected function parse_data ( $data )
	{
		$return = [];
		$columns = $this -> _columns;

		foreach ( $data as $reg )
		{
			$idx = -1;
			$row = [];
			foreach ($reg as $column => $valor)
			{
				$idx++;
				$column = $columns[$idx];

				if ( isset( $column['formatter'] ) ) // Is there a formatter?
				{
					if(empty($column['db']))
					{
						$row[ $column['dt'] ] = $column['formatter']( $reg );
					}
					else
					{
						$row[ $column['dt'] ] = $column['formatter']( $valor, $reg );
					}
				}
				else
				{
					if(empty($column['db'])){
						$row[ $column['dt'] ] = '';
					}
					else{
						$row[ $column['dt'] ] = $valor;
					}
				}
			}
			$return[] = $row;
		}

		return $return;
	}

	protected function _where_global ( $request )
	{
		if ( ! (isset($request['search']) && isset($request['search']['value']) && $request['search']['value'] != '') ) return [];
		if ( ! (isset($request['columns'])) ) return [];

		$columns_len = count ($request['columns']);
		$columns     = $this -> _columns;
		$columns_dt  = $this -> _columns_dt;
		$str         = $request['search']['value'];

		$globalSearch = [];
		for ( $i = 0 ; $i < $columns_len ; $i++ )
		{
			$column_request = $request['columns'][$i];
			$column_idx     = array_search( $column_request['data'], $columns_dt );
			$column         = $columns[ $column_idx ];

			$searchable = strtobool( $column_request['searchable'] );
			if ( ! $searchable) continue;
			if (is_empty($column['db'])) continue;

			$globalSearch[] = '`' . $column['db'] . '` LIKE "%' . sql_esc($str) . '%"';
		}

		return $globalSearch;
	}

	protected function _where_column ( $request )
	{
		if ( ! (isset( $request['columns'])) ) return [];

		$columns_len = count ($request['columns']);
		$columns     = $this -> _columns;
		$columns_dt  = $this -> _columns_dt;

		$columnSearch = [];
		for ( $i = 0 ; $i < $columns_len ; $i++ )
		{
			$column_request = $request['columns'][$i];
			$column_idx     = array_search( $column_request['data'], $columns_dt );
			$column         = $columns[ $column_idx ];

			$searchable = strtobool( $column_request['searchable'] );
			if ( ! $searchable) continue;
			if (is_empty($column['db'])) continue;

			$str = $column_request['search']['value'];
			if (is_empty($str)) continue;

			$columnSearch[] = '`' . $column['db'] . '` LIKE "%' . sql_esc($str) . '%"';
		}

		return $columnSearch;
	}

	protected function _where_all ()
	{
		$where = '';

		$where_all = self :: flattern ($this ->_where_all);
		if ( ! is_empty ( $where_all ) )
			$where = $where === '' ? $where_all : ($where . ' AND ' . $where_all);

		is_empty($where) or $where = 'WHERE ' . $where;
		return $where;
	}

	/**
	 * Searching / Filtering
	 *
	 * Construct the WHERE clause for server-side processing SQL query.
	 *
	 * NOTE this does not match the built-in DataTables filtering which does it
	 * word by word on any field. It's possible to do here performance on large
	 * databases would be very poor
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @return string SQL where clause
	 */
	protected function _where ( $request )
	{
		$where = '';

		$where_global = $this -> _where_global ( $request );
		$where_global = implode(' OR ', $where_global);
		if ( ! is_empty ( $where_global ) )
			$where = '(' . $where_global . ')';

		$where_column = $this -> _where_column ( $request );
		$where_column = implode(' AND ', $where_column);
		if ( ! is_empty ( $where_column ) )
			$where = $where === '' ? $where_column : ($where . ' AND ' . $where_column);

		$where_result = self :: flattern ($this ->_where_result);
		if ( ! is_empty ( $where_result ) )
			$where = $where === '' ? $where_result : ($where . ' AND ' . $where_result);

		is_empty($where) or $where = 'WHERE ' . $where;
		return $where;
	}

	/**
	 * Ordering
	 *
	 * Construct the ORDER BY clause for server-side processing SQL query
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @return string SQL order by clause
	 */
	protected function _order ( $request )
	{
		if ( ! (isset($request['order']) && count($request['order'])) )
			return '';

		$columns    = $this -> _columns;
		$columns_dt = $this -> _columns_dt;
		$order_len  = count($request['order']);

		$order    = '';
		$order_by = [];

		for ( $i = 0 ; $i < $order_len ; $i++ )
		{
			// Convert the column index into the column data property
			$column_idx = intval($request['order'][$i]['column']);
			if ( ! isset($request['columns'][$column_idx])) continue;
			$column_request = $request['columns'][$column_idx];
			$column_idx = array_search( $column_request['data'], $columns_dt );

			$column = $columns[ $column_idx ];
			$orderable = strtobool( $column_request['orderable'] );

			if ( ! $orderable) continue;

			isset($request['order'][$i]['dir']) or $request['order'][$i]['dir'] = 'asc';
			$dir = $request['order'][$i]['dir'] === 'asc' ? 'ASC' : 'DESC';

			$order_by[] = '`' . $column['db'] . '` ' . $dir;
		}

		if ( count( $order_by ) === 0 ) return '';
		return 'ORDER BY ' . implode(', ', $order_by);
	}

	/**
	 * Paging
	 *
	 * Construct the LIMIT clause for server-side processing SQL query
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @return string SQL limit clause
	 */
	protected function _limit ( $request )
	{
		if ( ! (isset($request['start']) && isset($request['length']) && $request['length'] > 0) )
			return '';

		return 'LIMIT ' . intval($request['start']) . ', ' . intval($request['length']);
	}

	/**
	 * Pull a particular property from each assoc. array in a numeric array, 
	 * returning and array of the property values from each item.
	 *
	 *  @param  array  $array Array to get data from
	 *  @param  string $prop  Property to read
	 *  @return array         Array of property values
	 */
	protected static function pluck ( array $array, string $prop )
	{
		$out = [];

		for ( $i = 0, $len = count($array) ; $i < $len ; $i++ )
		{
			if (is_empty($array[$i][$prop])) continue;

			//removing the $out array index confuses the filter method in doing proper binding,
			//adding it ensures that the array data are mapped correctly
			$out[$i] = $array[$i][$prop];
		}

		return $out;
	}

	/**
	 * Return a string from an array or a string
	 *
	 * @param  array|string $a Array to join
	 * @param  string $join Glue for the concatenation
	 * @return string Joined string
	 */
	protected static function flattern ( $data, string $join = ' AND ' )
	{
		if ( ! $data)
			return '';

		if ( $data and is_array($data) )
			return implode( $join, $data );

		return $data;
	}
}