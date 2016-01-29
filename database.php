<?php

class Database extends PDO {

	private static $pkey_cache = array();

	public function get_pkey ($table) {
	
		if (!empty(self::$pkey_cache[$table]))
			return self::$pkey_cache[$table];
	
		$sth = $this->prepare("
			SELECT k.COLUMN_NAME
			FROM information_schema.table_constraints t
			LEFT JOIN information_schema.key_column_usage k USING(constraint_name,table_schema,table_name)
			WHERE t.constraint_type='PRIMARY KEY'
			AND t.table_schema=DATABASE()
			AND t.table_name=?
			ORDER BY k.ordinal_position;
		");
		$sth->setFetchMode(PDO::FETCH_NUM);
		$sth->execute(array($table));
		
		$rv = array();
		while ($row = $sth->fetch()) {
			$rv[] = $row[0];
		}
		self::$pkey_cache[$table] = $rv;
		return $rv;
	}

	public function get_label_cols ($table) {
		if ($table==='user') {
			return array('name', 'staff_id');
		}
		if ($table==='client') {
			return array('name');
		}
		if ($table==='permission') {
			return array('name');
		}
		if ($table==='form') {
			return array('title');
		}
		if ($table==='field') {
			return array('form_id', 'label');
		}
		if ($table==='note') {
			return array('client_id', 'user_id', 'form_id', 'created');
		}
		if ($table==='submission') {
			return array('client_id', 'user_id', 'form_id', 'created');
		}
		return false;
	}

}

