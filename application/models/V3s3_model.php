<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class V3s3_model extends CI_Model {
	public $id;
	public $timestamp;
	public $date_time;
	public $ip;
	public $hash_name;
	public $name;
	public $data;
	public $mime_type;
	public $status;
	public $timestamp_deleted;
	public $date_time_deleted;
	public $ip_deleted_from;

	public function __construct() {
		parent::__construct();
	}

	public function fromArray(Array $attr) {
		if(!is_array($attr)) {
			return;
		}

		foreach($attr as $key=>$value) {
			if(property_exists($this, $key)) {
				$this->{$key} = $value;
			}
		}
	}

	public function put(Array $attr) {
		$table = get_instance()->config->item('v3s3')['table'];
		$columns = $this->db->list_fields($table);
		$columns = array_combine($columns, $columns);

		$attr = array_intersect_key($attr, $columns);
		$attr['timestamp'] = (isset($attr['timestamp'])?$attr['timestamp']:time());
		$attr['date_time'] = date('Y-m-d H:i:s O', $attr['timestamp']);
		if(isset($attr['name'])) {
			$attr['hash_name'] = sha1($attr['name']);
		} else {
			unset($attr['hash_name']);
		}
		$attr['status'] = (isset($attr['status'])?$attr['status']:1);
		unset($attr['id']);

		$this->fromArray($attr);
		$this->db->insert($table, $this);
		$row = array_replace($attr, ['id'=>$this->db->insert_id()]);

		return $row;
	}

	public function get(Array $attr) {
		$table = get_instance()->config->item('v3s3')['table'];
		$columns = $this->db->list_fields($table);
		$columns = array_combine($columns, $columns);

		$attr = array_intersect_key($attr, $columns);
		if(isset($attr['name'])) {
			$attr['hash_name'] = sha1($attr['name']);
		} else {
			unset($attr['hash_name']);
		}
		unset($attr['name']);

		$this->db->from($table);
		$this->db->where($attr);
		$this->db->order_by('id', 'DESC');
		$this->db->limit(1);
		$query = $this->db->get();

		$rows_count = $query->num_rows();
		if(empty($rows_count)) {
			return false;
		}

		return $query->row_array();
	}

	public function api_delete(Array $attr) {
		$table = get_instance()->config->item('v3s3')['table'];
		$columns = $this->db->list_fields($table);
		$columns = array_combine($columns, $columns);

		$attr = array_intersect_key($attr, $columns);
		$attr['timestamp_deleted'] = (isset($attr['timestamp_deleted'])?$attr['timestamp_deleted']:time());
		$attr['date_time_deleted'] = date('Y-m-d H:i:s O', $attr['timestamp_deleted']);
		if(isset($attr['name'])) {
			$attr['hash_name'] = sha1($attr['name']);
		} else {
			unset($attr['hash_name']);
		}
		$attr['status'] = (isset($attr['status'])?$attr['status']:0);
		unset($attr['name']);

		$where = $attr;
		unset($where['status']);
		unset($where['timestamp_deleted']);
		unset($where['date_time_deleted']);
		unset($where['ip_deleted_from']);
		$this->db->where($where);
		$this->db->order_by('id', 'DESC');
		$this->db->limit(1);
		$query = $this->db->get($table);

		$rows_count = $query->num_rows();
		if(empty($rows_count)) {
			return false;
		}

		$row = array_replace($query->row_array(), $attr);
		$this->db->where(['id'=>$row['id']]);
		$this->db->set($attr);
		$this->db->update($table);

		return $row;
	}

	public function post(Array $attr) {
		$table = get_instance()->config->item('v3s3')['table'];
		$columns = $this->db->list_fields($table);
		$columns = array_combine($columns, $columns);

		$attr = array_intersect_key($attr, $columns);
		if(isset($attr['name'])) {
			$attr['hash_name'] = sha1($attr['name']);
		} else {
			unset($attr['hash_name']);
		}
		unset($attr['name']);

		$this->db->from($table);
		$this->db->where($attr);
		$rows = $this->db->get();

		return (!empty($rows)?$rows->result_array():[]);
	}
}