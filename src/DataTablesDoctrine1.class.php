<?php
/**
 * A simple way to use DataTables (The JQuery Plugin) + PHP + Doctrine 1.2.4
 * You can use this class in order to implement a server side DataTable
 * For more information go to https://datatables.net/examples/data_sources/server_side.html
 *
 * @author      Oscar Villarraga <dvillarraga@gmail.com>
 */
class DataTablesDoctrine1 {

    private $query;
    private $columns;
    private $request;

    /**
     * Constructor
     *
     * @param Doctrine_Query $q
     * @param array $request array of requests, it could be $_GET
     * @param array $columns map of colums to
     * $columns = array(
     *      array('db' => 'c.id', 'dt' => 0),
     *      array('db' => 'c.name', 'dt' => 1),
     *      array('db' => 'c.phone', 'dt' => 2),
     *      );
     *
     */
    public function __construct(Doctrine_Query $q, array $request, array $columns) {
        $this->query = $q;
        $this->request = $request;
        $this->columns = $columns;
    }
    
    /**
     * Proccess the query and returns the information
     * @return array That must be parsed to JSON and shall be used by DataTables
     */
    public function getData() {
        self::filter();
        self::order();
        self::limit();
        $current_page = (intval(intval($this->request['start']) / intval($this->request['length']))) + 1;
        $pager = new Doctrine_Pager($this->query, $current_page, intval($this->request['length']));
        $data = $pager->execute();
        
        $recordsFiltered = $pager->getNumResults();
        
        return array(
            "draw" => intval($this->request['draw']),
            "recordsTotal" => intval($recordsFiltered),
            "recordsFiltered" => intval($recordsFiltered),
            "data" => self::data_output($data->getData())
        );
    }
    
    /**
     * Gets the data output
     *  @param  array $data From Doctrine_Query
     *  @return array data ouput required for Datatables
     */
    private function data_output(array $data) {
        $out = array();
        for ($i = 0, $ien = count($data); $i < $ien; $i++) {
            $row = array();
            for ($j = 0, $jen = count($this->columns); $j < $jen; $j++) {
                $column = $this->columns[$j];
                // Is there a formatter?
                if (isset($column['formatter'])) {
                    $row[$column['dt']] = $column['formatter']($data[$i][$column['db']], $data[$i]);
                } else {
                    $row[$column['dt']] = $data[$i][$this->columns[$j]['db']];
                }
            }
            $out[] = $row;
        }
        return $out;
    }

    /**
     * Adds limit for pager to the Doctrine_Query
     */
    private function limit() {
        if (isset($this->request['start']) && $this->request['length'] != -1) {
            $this->query->limit(intval($this->request['start']))->offset(intval($this->request['length']));
        }
    }

    /**
     * Adds Order in Doctrine_Query based on the request
     */
    private function order() {
        if (isset($this->request['order']) && count($this->request['order'])) {
            $orderBy = array();
            $dtColumns = self::pluck($this->columns, 'dt');

            for ($i = 0, $ien = count($this->request['order']); $i < $ien; $i++) {
                // Convert the column index into the column data property
                $columnIdx = intval($this->request['order'][$i]['column']);
                $requestColumn = $this->request['columns'][$columnIdx];
                $columnIdx = array_search($requestColumn['data'], $dtColumns);
                $column = $this->columns[$columnIdx];

                if ($requestColumn['orderable'] == 'true') {
                    $dir = $this->request['order'][$i]['dir'] === 'asc' ?
                            ' ASC' :
                            ' DESC';

                    $orderBy[] = $column['db'] . $dir;
                }
            }
            $this->query->orderBy(implode(', ', $orderBy));
        }
    }

    /**
     * Adds Filters in Doctrine_Query based on the request
     * using LIKE %% on each column
     */
    private function filter() {
        $globalSearch = array();
        $havingSearch = array();
        $columnSearch = array();
        $dtColumns = self::pluck($this->columns, 'dt');

        if (isset($this->request['search']) && $this->request['search']['value'] != '') {
            $str = $this->request['search']['value'];

            for ($i = 0, $ien = count($this->request['columns']); $i < $ien; $i++) {
                $requestColumn = $this->request['columns'][$i];
                $columnIdx = array_search($requestColumn['data'], $dtColumns);
                $column = $this->columns[$columnIdx];

                if ($requestColumn['searchable'] == 'true') {
                    if(strpos($column['db'], "GROUP_CONCAT") !== false){
                        $havingSearch[] = $column['db'] . " LIKE '%$str%'";
                    }else{
                        $globalSearch[] = $column['db'] . " LIKE '%$str%'";
                    }
                }
            }
        }

        // Individual column filtering
        for ($i = 0, $ien = count($this->request['columns']); $i < $ien; $i++) {
            $requestColumn = $this->request['columns'][$i];
            $columnIdx = array_search($requestColumn['data'], $dtColumns);
            $column = $this->columns[$columnIdx];

            $str = $requestColumn['search']['value'];

            if ($requestColumn['searchable'] == 'true' && $str != '') {
                if(  !(strpos($column['db'], "GROUP_CONCAT") !== false)  ){
                    $columnSearch[] = $column['db'] . " LIKE '%$str%'";
                }
            }
        }

        // Combine the filters into a single string
        $where = '';
        $having = '';
        
        if (count($globalSearch)) {
            $where = '(' . implode(' OR ', $globalSearch) . ')';
        }
        if (count($havingSearch)) {
            $having = '(' . implode(' OR ', $havingSearch) . ')';
        }

        if (count($columnSearch)) {
            $where = $where === '' ?
                    implode(' AND ', $columnSearch) :
                    $where . ' AND ' . implode(' AND ', $columnSearch);
        }

        if ($where !== '') {
            $this->query->andWhere($where);
        }
        if ($having !== '') {
            /*
             * @ToDo 
             * There is some bugs when you use GROUP_CONCAT function
             **/
            //$this->query->having($having);
        }
    }

    /**
     * Generates a new array giving just one column.
     *  @param  array  $source_array    Source Array
     *  @param  string $column          Column required
     *  @return array
     */
    private function pluck(array $source_array, string $column) {
        $out = array();

        for ($i = 0, $len = count($source_array); $i < $len; $i++) {
            $out[] = $source_array[$i][$column];
        }

        return $out;
    }

}
