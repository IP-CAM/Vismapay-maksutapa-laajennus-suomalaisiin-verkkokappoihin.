<?php
class ControllerPaymentVismapay extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('payment/Vismapay');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('Vismapay', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_clear'] = $this->language->get('text_clear');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');

		$data['entry_failed_status'] = $this->language->get('entry_failed_status');
		$data['entry_order_status'] = $this->language->get('entry_order_status');
		$data['entry_total'] = $this->language->get('entry_total');
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_api_key'] = $this->language->get('entry_api_key');
		$data['entry_private_key'] = $this->language->get('entry_private_key');
		$data['entry_banks'] = $this->language->get('entry_banks');
		$data['entry_cards'] = $this->language->get('entry_cards');
		$data['entry_invoice'] = $this->language->get('entry_invoice');
		$data['entry_arvato'] = $this->language->get('entry_arvato');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_log'] = $this->language->get('entry_log');


		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_clear'] = $this->language->get('button_clear');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

 		if (isset($this->error['api_key'])) {
			$data['error_api_id'] = $this->error['api_key'];
		} else {
			$data['error_api_key'] = '';
		}

 		if (isset($this->error['private_key'])) {
			$data['error_private_key'] = $this->error['private_key'];
		} else {
			$data['error_private_key'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_payment'),
			'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('payment/Vismapay', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['action'] = $this->url->link('payment/Vismapay', 'token=' . $this->session->data['token'], 'SSL');
		$data['clear'] = $this->url->link('payment/paytrail/clear','token=' . $this->session->data['token'],'SSL');
		$data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

		if (isset($this->request->post['Vismapay_api_key'])) {
			$data['Vismapay_api_key'] = $this->request->post['Vismapay_api_key'];
		} else {
			$data['Vismapay_api_key'] = $this->config->get('Vismapay_api_key');
		}

		if (isset($this->request->post['Vismapay_order_status_id'])) {
			$data['Vismapay_order_status_id'] = $this->request->post['Vismapay_order_status_id'];
		} else {
			$data['Vismapay_order_status_id'] = $this->config->get('Vismapay_order_status_id');
		}

		if (isset($this->request->post['Vismapay_failed_status_id'])) {
			$data['Vismapay_failed_status_id'] = $this->request->post['Vismapay_failed_status_id'];
		} else {
			$data['Vismapay_failed_status_id'] = $this->config->get('Vismapay_failed_status_id');
		}


		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['Vismapay_geo_zone_id'])) {
			$data['Vismapay_geo_zone_id'] = $this->request->post['Vismapay_geo_zone_id'];
		} else {
			$data['Vismapay_geo_zone_id'] = $this->config->get('Vismapay_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['Vismapay_status'])) {
			$data['Vismapay_status'] = $this->request->post['Vismapay_status'];
		} else {
			$data['Vismapay_status'] = $this->config->get('Vismapay_status');
		}

		if (isset($this->request->post['Vismapay_private_key'])) {
			$data['Vismapay_private_key'] = $this->request->post['Vismapay_private_key'];
		} else {
			$data['Vismapay_private_key'] = $this->config->get('Vismapay_private_key');
		}

		if (isset($this->request->post['Vismapay_banks'])) {
			$data['Vismapay_banks'] = $this->request->post['Vismapay_banks'];
		} else {
			$data['Vismapay_banks'] = $this->config->get('Vismapay_banks');
		}

		if (isset($this->request->post['Vismapay_cards'])) {
			$data['Vismapay_cards'] = $this->request->post['Vismapay_cards'];
		} else {
			$data['Vismapay_cards'] = $this->config->get('Vismapay_cards');
		}

		if (isset($this->request->post['Vismapay_invoice'])) {
			$data['Vismapay_invoice'] = $this->request->post['Vismapay_invoice'];
		} else {
			$data['Vismapay_invoice'] = $this->config->get('Vismapay_invoice');
		}

		if (isset($this->request->post['Vismapay_arvato'])) {
			$data['Vismapay_arvato'] = $this->request->post['Vismapay_arvato'];
		} else {
			$data['Vismapay_arvato'] = $this->config->get('Vismapay_arvato');
		}

		if (isset($this->request->post['Vismapay_sort_order'])) {
			$data['Vismapay_sort_order'] = $this->request->post['Vismapay_sort_order'];
		} else {
			$data['Vismapay_sort_order'] = $this->config->get('Vismapay_sort_order');
		}

		$file = DIR_LOGS . 'Vismapay.log';

		if (file_exists($file)) {
			$data['log'] = file_get_contents($file, FILE_USE_INCLUDE_PATH, null);
		} else {
			$data['log'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('payment/Vismapay.tpl', $data));
	}

	public function clear() {
		$this->language->load('payment/Vismapay');

		$file = DIR_LOGS . 'Vismapay.log';
        if (file_exists($file) && $this->validateClear()) {
		   $handle = fopen($file, 'w+'); 

	    	fclose($handle); 			

		    $this->session->data['success'] = $this->language->get('text_clear_success');
	   }

		$this->response->redirect($this->url->link('payment/Vismapay', 'token=' . $this->session->data['token'], true));		
	}

	protected function validateClear() {
		if (!$this->user->hasPermission('modify', 'payment/Vismapay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'payment/Vismapay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['Vismapay_api_key']) {
			$this->error['api_key'] = $this->language->get('error_api_key');
		}

		if (!$this->request->post['Vismapay_private_key']) {
			$this->error['private_key'] = $this->language->get('error_private_key');
		}

		return !$this->error;
	}
}
