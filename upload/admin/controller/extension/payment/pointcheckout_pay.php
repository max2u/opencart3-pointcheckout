<?php
class ControllerExtensionPaymentPointCheckOutPay extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/pointcheckout_pay');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_pointcheckout_pay', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		$data['pointcheckout_staging'] = false;
		
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['key'])) {
			$data['error_key'] = $this->error['key'];
		} else {
			$data['error_key'] = '';
		}

		if (isset($this->error['secret'])) {
			$data['error_secret'] = $this->error['secret'];
		} else {
			$data['error_secret'] = '';
		}


		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/pointcheckout_pay', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/pointcheckout_pay', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_pointcheckout_pay_key'])) {
			$data['payment_pointcheckout_pay_key'] = $this->request->post['payment_pointcheckout_pay_key'];
		} else {
			$data['payment_pointcheckout_pay_key'] = $this->config->get('payment_pointcheckout_pay_key');
		}

		if (isset($this->request->post['payment_pointcheckout_pay_secret'])) {
			$data['payment_pointcheckout_pay_secret'] = $this->request->post['payment_pointcheckout_pay_secret'];
		} else {
			$data['payment_pointcheckout_pay_secret'] = $this->config->get('payment_pointcheckout_pay_secret');
		}

		if (isset($this->request->post['payment_pointcheckout_pay_applicable_countries'])) {
			$data['payment_pointcheckout_pay_applicable_countries'] = $this->request->post['payment_pointcheckout_pay_applicable_countries'];
		} else {
			$data['payment_pointcheckout_pay_applicable_countries'] = $this->config->get('payment_pointcheckout_pay_applicable_countries');
		}
		
		if (isset($this->request->post['payment_pointcheckout_pay_specific_countries'])) {
		    $data['payment_pointcheckout_pay_specific_countries'] = $this->request->post['payment_pointcheckout_pay_specific_countries'];
		} else {
		    $data['payment_pointcheckout_pay_specific_countries'] = $this->config->get('payment_pointcheckout_pay_specific_countries');
		}
		
		if (isset($this->request->post['payment_pointcheckout_pay_applicable_usergroups'])) {
		    $data['payment_pointcheckout_pay_applicable_usergroups'] = $this->request->post['payment_pointcheckout_pay_applicable_usergroups'];
		} else {
		    $data['payment_pointcheckout_pay_applicable_usergroups'] = $this->config->get('payment_pointcheckout_pay_applicable_usergroups');
		}
		
		if (isset($this->request->post['payment_pointcheckout_pay_specific_usergroups'])) {
		    $data['payment_pointcheckout_pay_specific_usergroups'] = $this->request->post['payment_pointcheckout_pay_specific_usergroups'];
		} else {
		    $data['payment_pointcheckout_pay_specific_usergroups'] = $this->config->get('payment_pointcheckout_pay_specific_usergroups');
		}
		
		if (isset($this->request->post['payment_pointcheckout_pay_test'])) {
			$data['payment_pointcheckout_pay_test'] = $this->request->post['payment_pointcheckout_pay_test'];
		} else {
			$data['payment_pointcheckout_pay_test'] = $this->config->get('payment_pointcheckout_pay_test');
		}

		if (isset($this->request->post['payment_pointcheckout_pay_applicable_countries'])) {
			$data['payment_pointcheckout_pay_applicable_countries'] = $this->request->post['payment_pointcheckout_pay_applicable_countries'];
		} else {
			$data['payment_pointcheckout_pay_applicable_countries'] = $this->config->get('payment_pointcheckout_pay_applicable_countries');
		}

		if (isset($this->request->post['payment_pointcheckout_pay_total'])) {
			$data['payment_pointcheckout_pay_total'] = $this->request->post['payment_pointcheckout_pay_total'];
		} else {
			$data['payment_pointcheckout_pay_total'] = $this->config->get('payment_pointcheckout_pay_total');
		}

		if (isset($this->request->post['payment_pointcheckout_pay_order_status_id'])) {
			$data['payment_pointcheckout_pay_order_status_id'] = $this->request->post['payment_pointcheckout_pay_order_status_id'];
		} else {
			$data['payment_pointcheckout_pay_order_status_id'] = $this->config->get('payment_pointcheckout_pay_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_pointcheckout_pay_geo_zone_id'])) {
			$data['payment_pointcheckout_pay_geo_zone_id'] = $this->request->post['payment_pointcheckout_pay_geo_zone_id'];
		} else {
			$data['payment_pointcheckout_pay_geo_zone_id'] = $this->config->get('payment_pointcheckout_pay_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_pointcheckout_pay_status'])) {
			$data['payment_pointcheckout_pay_status'] = $this->request->post['payment_pointcheckout_pay_status'];
		} else {
			$data['payment_pointcheckout_pay_status'] = $this->config->get('payment_pointcheckout_pay_status');
		}

		if (isset($this->request->post['payment_pointcheckout_pay_sort_order'])) {
			$data['payment_pointcheckout_pay_sort_order'] = $this->request->post['payment_pointcheckout_pay_sort_order'];
		} else {
			$data['payment_pointcheckout_pay_sort_order'] = $this->config->get('payment_pointcheckout_pay_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/pointcheckout_pay', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/pointcheckout_pay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_pointcheckout_pay_key']) {
			$this->error['key'] = $this->language->get('error_key');
		}

		if (!$this->request->post['payment_pointcheckout_pay_secret']) {
			$this->error['secret'] = $this->language->get('error_secret');
		}

		return !$this->error;
	}
}