<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class V3s3 extends CI_Controller {

	function put() {
		$this->load->helper('v3s3_exception');

		$name = $this->uri->uri_string;

		try {
			if (empty($name) || ($name == '/')) {
				throw new v3s3_exception($this->lang->line('V3S3_EXCEPTION_PUT_EMPTY_OBJECT_NAME'), v3s3_exception::PUT_EMPTY_OBJECT_NAME);
			} else if (strlen($name) > 1024) {
				throw new v3s3_exception($this->lang->line('V3S3_EXCEPTION_OBJECT_NAME_TOO_LONG'), v3s3_exception::OBJECT_NAME_TOO_LONG);
			}
		} catch(v3s3_exception $e) {
			return $this->output
				->set_header('Content-type: application/json; charset=utf-8', true)
				->set_output(
					json_encode(
						[
							'status'=>0,
							'code'=>$e->getCode(),
							'message'=>$e->getMessage()
						]
					)
				);
		}

		$data = $this->input->raw_input_stream;
		$content_type = $this->input->get_request_header('Content-type');
		$mime_type = (is_null($content_type)?(new finfo(FILEINFO_MIME))->buffer($data):$content_type);
		$this->config->load('v3s3');
		$this->load->model('v3s3_model', '', $this->config->item('db')['v3s3']);
		$row = $this->v3s3_model->put(
			[
				'ip'=>$this->input->ip_address(),
				'name'=>$name,
				'data'=>$data,
				'mime_type'=>$mime_type,
			]
		);

		return $this->output
			->set_header('Content-type: application/json; charset=utf-8', true)
			->set_header('v3s3-object-id: '.$row['id'], true)
			->set_output(
				json_encode(
					[
						'status'=>1,
						'message'=>$this->lang->line('V3S3_MESSAGE_PUT_OBJECT_ADDED_SUCCESSFULLY')
					]
				)
			);
	}

	function get() {
		$this->load->helper('v3s3_exception');

		$name = $this->uri->uri_string;

		try {
			if (strlen($name) > 1024) {
				throw new v3s3_exception($this->lang->line('V3S3_EXCEPTION_OBJECT_NAME_TOO_LONG'), v3s3_exception::OBJECT_NAME_TOO_LONG);
			}
		} catch(v3s3_exception $e) {
			return $this->output
				->set_header('Content-type: application/json; charset=utf-8', true)
				->set_output(
					json_encode(
						[
							'status'=>0,
							'code'=>$e->getCode(),
							'message'=>$e->getMessage()
						]
					)
				);
		}

		$input = $_GET;
		unset($input['download']);
		$this->config->load('v3s3');
		$this->load->model('v3s3_model', '', $this->config->item('db')['v3s3']);
		$row = $this->v3s3_model->get(
			array_replace(
				$input,
				[
					'name'=>$name,
				]
			)
		);

		if(!empty($row['status'])) {
			if(empty($row['mime_type'])) {
				$row['mime_type'] = (new finfo(FILEINFO_MIME))->buffer($row['data']);
			}
			if(!empty($_GET['download'])) {
				$filename = basename($name);
				$this->output->set_header('Content-Disposition: attachment; filename="'.$filename.'"', true);
			}
			return $this->output
				->set_header('Content-type: ' . $row['mime_type'], true)
				->set_header('Content-length: ' . strlen($row['data']), true)
				->set_output($row['data']);
		} else {
			return $this->output
				->set_status_header(404)
				->set_header('Content-type: application/json; charset=utf-8', true)
				->set_output(
					json_encode(
						[
							'status'=>1,
							'results'=>0,
							'message'=>$this->lang->line('V3S3_MESSAGE_404')
						]
					)
				);
		}
	}

	function delete() {
		$this->load->helper('v3s3_exception');

		$name = $this->uri->uri_string;

		try {
			if (empty($name) || ($name == '/')) {
				throw new v3s3_exception($this->lang->line('V3S3_EXCEPTION_DELETE_EMPTY_OBJECT_NAME'), v3s3_exception::DELETE_EMPTY_OBJECT_NAME);
			} else if (strlen($name) > 1024) {
				throw new v3s3_exception($this->lang->line('V3S3_EXCEPTION_OBJECT_NAME_TOO_LONG'), v3s3_exception::OBJECT_NAME_TOO_LONG);
			}
		} catch(v3s3_exception $e) {
			return $this->output
				->set_header('Content-type: application/json; charset=utf-8', true)
				->set_output(
					json_encode(
						[
							'status'=>0,
							'code'=>$e->getCode(),
							'message'=>$e->getMessage()
						]
					)
				);
		}

		$input = $_GET;
		$this->config->load('v3s3');
		$this->load->model('v3s3_model', '', $this->config->item('db')['v3s3']);
		$row = $this->v3s3_model->api_delete(
			array_replace(
				$input,
				[
					'name'=>$name,
					'ip_deleted_from'=>$this->input->ip_address()
				]
			)
		);

		if(empty($row)) {
			return $this->output
				->set_status_header(404)
				->set_header('Content-type: application/json; charset=utf-8', true)
				->set_output(
					json_encode(
						[
							'status'=>1,
							'results'=>0,
							'message'=>$this->lang->line('V3S3_MESSAGE_404')
						]
					)
				);
		} else {
			return $this->output
				->set_header('Content-type: application/json; charset=utf-8', true)
				->set_output(
					json_encode(
						[
							'status'=>1,
							'results'=>1,
							'message'=>$this->lang->line('V3S3_MESSAGE_DELETE_OBJECT_DELETED_SUCCESSFULLY')
						]
					)
				);
		}
	}

	function post() {
		$this->load->helper('v3s3_exception');
		$this->load->helper('v3s3_html');
		$this->load->helper('v3s3_xml');

		$name = $this->uri->uri_string;

		$input = $this->input->raw_input_stream;
		$parsed_input = (!empty($input)?json_decode($input, true):[]);
		if(!empty($input) && empty($parsed_input)) {
			try {
				throw new v3s3_exception($this->lang->line('v3s3_Translation.V3S3_EXCEPTION_POST_INVALID_REQUEST'), v3s3_exception::POST_INVALID_REQUEST);
			} catch(v3s3_exception $e) {
				return $this->output
					->set_header('Content-type: application/json; charset=utf-8', true)
					->set_output(
						json_encode(
							[
								'status'=>0,
								'code'=>$e->getCode(),
								'message'=>$e->getMessage()
							]
						)
					);
			}
		}

		$attr = (!empty($parsed_input['filter'])?$parsed_input['filter']:[]);
		if((!empty($name) && ($name != '/')) || ($name === '0')) {
			$attr['name'] = $name;
		}

		$this->config->load('v3s3');
		$this->load->model('v3s3_model', '', $this->config->item('db')['v3s3']);
		$rows = $this->v3s3_model->post(
			$attr
		);


		if(!empty($rows)) {
			foreach ($rows as &$_row) {
				unset($_row['id']);
				unset($_row['timestamp']);
				unset($_row['hash_name']);
				unset($_row['timestamp_deleted']);
				if(empty($_row['mime_type'])) {
					$_row['mime_type'] = (new finfo(FILEINFO_MIME))->buffer($_row['data']).' (determined using PHP finfo)';
				}
				unset($_row['data']);
			}

			$format = ((!empty($parsed_input['format'])&&in_array(strtolower($parsed_input['format']), ['json', 'xml', 'html']))?strtolower($parsed_input['format']):'json');
			switch($format) {
				case 'xml':
					$rows = v3s3_xml::simple_xml($rows);
					return $this->output
						->set_header('Content-type: text/xml; charset=utf-8', true)
						->set_output($rows);
					break;
				case 'html':
					$rows = v3s3_html::simple_table($rows);
					return $this->output
						->set_header('Content-type: text/html; charset=utf-8', true)
						->set_output($rows);
					break;
				case 'json':
				default:
					$rows = json_encode($rows, JSON_PRETTY_PRINT);
					return $this->output
						->set_header('Content-type: application/json; charset=utf-8', true)
						->set_output($rows);
					break;
			}
		} else {
			return $this->output
				->set_header('Content-type: application/json; charset=utf-8', true)
				->set_output(
					json_encode(
						[
							'status'=>1,
							'results'=>0,
							'message'=>$this->lang->line('V3S3_MESSAGE_NO_MATCHING_RESOURCES')
						]
					)
				);
		}
	}
}