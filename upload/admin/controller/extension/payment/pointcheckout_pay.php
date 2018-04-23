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

		if (isset($this->error['specific_countries'])) {
		    $data['error_specific_countries'] = $this->error['specific_countries'];
		} else {
		    $data['error_specific_countries'] = '';
		}
		
		if (isset($this->error['user_group'])) {
		    $data['error_user_group'] = $this->error['user_group'];
		} else {
		    $data['error_user_group'] = '';
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
		$data['user_token'] = $this->session->data['user_token'];

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
		
		if (isset($this->request->post['payment_pointcheckout_pay_env'])) {
		    $data['payment_pointcheckout_pay_env'] = $this->request->post['payment_pointcheckout_pay_env'];
		} else {
		    $data['payment_pointcheckout_pay_env'] = $this->config->get('payment_pointcheckout_pay_env');
		}
		

		if (isset($this->request->post['payment_pointcheckout_pay_applicable_countries'])) {
			$data['payment_pointcheckout_pay_applicable_countries'] = $this->request->post['payment_pointcheckout_pay_applicable_countries'];
		} else {
			$data['payment_pointcheckout_pay_applicable_countries'] = $this->config->get('payment_pointcheckout_pay_applicable_countries');
		}
		
		$data['payment_pointcheckout_pay_country']=array();
		if (isset($this->request->post['payment_pointcheckout_pay_country'])) {
		    $data['payment_pointcheckout_pay_country']=$this->request->post['payment_pointcheckout_pay_country'];
		    $countries = $this->request->post['payment_pointcheckout_pay_country'];
		} else {
		    $countries = $this->config->get('payment_pointcheckout_pay_country');
		}
		
		
		$this->load->model('localisation/country');
		if(isset($countries)){
		    foreach ($countries as $country_id) {
		        $country_info = $this->model_localisation_country->getCountry($country_id);		        
		        if ($country_info) {
		            $data['payment_pointcheckout_pay_country'][] = array(
		                'country_id' => $country_info['country_id'],
		                'name'        => $country_info['name']
		            );
		        }
		    }
		}
		
		
		if($data['payment_pointcheckout_pay_applicable_countries']){
		    $data['pointcheckout_specific_countries']= '';
		    $data['pointcheckout_hide_countries']= '';
		}else{
		    $data['pointcheckout_specific_countries']= 'disabled';
		    $data['pointcheckout_hide_countries']= 'hidden';
		}
		
		
		if (isset($this->request->post['payment_pointcheckout_pay_applicable_usergroups'])) {
		    $data['payment_pointcheckout_pay_applicable_usergroups'] = $this->request->post['payment_pointcheckout_pay_applicable_usergroups'];
		} else {
		    $data['payment_pointcheckout_pay_applicable_usergroups'] = $this->config->get('payment_pointcheckout_pay_applicable_usergroups');
		}
		
		if($data['payment_pointcheckout_pay_applicable_usergroups']){
		    $data['pointcheckout_specific_user_groups']= '';
		    $data['pointcheckout_hide_groups']= '';
		}else{
		    $data['pointcheckout_specific_user_groups']= 'disabled';
		    $data['pointcheckout_hide_groups']= 'hidden';
		}
		
		
		$data['payment_pointcheckout_pay_user_group']=array();
		if (isset($this->request->post['payment_pointcheckout_pay_user_group'])) {
		    $data['payment_pointcheckout_pay_user_group']=$this->request->post['payment_pointcheckout_pay_user_group'];
		    $user_groups = $this->request->post['payment_pointcheckout_pay_user_group'];
		} else {
		    $user_groups = $this->config->get('payment_pointcheckout_pay_user_group');
		}
		
		
		$this->load->model('customer/customer_group');
		if(isset($user_groups)){
		    foreach ($user_groups as $group_id) {
		        $group_info = $this->model_customer_customer_group->getCustomerGroup($group_id);	
		        if ($group_info) {
		            $data['payment_pointcheckout_pay_user_group'][] = array(
		                'group_id' => $group_info['customer_group_id'],
		                'name'     => $group_info['name']
		            );
		        }
		    }
		}
		

		if (isset($this->request->post['payment_pointcheckout_pay_total'])) {
			$data['payment_pointcheckout_pay_total'] = $this->request->post['payment_pointcheckout_pay_total'];
		} else {
			$data['payment_pointcheckout_pay_total'] = $this->config->get('payment_pointcheckout_pay_total');
		}
		
		if (isset($this->request->post['payment_pointcheckout_pay_order_status_id'])) {
		    $data['payment_pointcheckout_pay_order_status_id'] = $this->request->post['payment_pointcheckout_pay_order_status_id'];
		} else if(null !== $this->config->get('payment_pointcheckout_pay_order_status_id')) {
		    $data['payment_pointcheckout_pay_order_status_id'] = $this->config->get('payment_pointcheckout_pay_order_status_id');
		}else{
		    $data['payment_pointcheckout_pay_order_status_id']=1;//default value is pendding 1
		}
		
		if (isset($this->request->post['payment_pointcheckout_pay_payment_failed_status_id'])) {
		    $data['payment_pointcheckout_pay_payment_failed_status_id'] = $this->request->post['payment_pointcheckout_pay_payment_failed_status_id'];
		} else if(null !== $this->config->get('payment_pointcheckout_pay_payment_failed_status_id')) {
		    $data['payment_pointcheckout_pay_payment_failed_status_id'] = $this->config->get('payment_pointcheckout_pay_payment_failed_status_id');
		}else{
		    $data['payment_pointcheckout_pay_payment_failed_status_id']=10;//default value is failed 10
		}
		
		if (isset($this->request->post['payment_pointcheckout_pay_payment_success_status_id'])) {
		    $data['payment_pointcheckout_pay_payment_success_status_id'] = $this->request->post['payment_pointcheckout_pay_payment_success_status_id'];
		} else if (null !== $this->config->get('payment_pointcheckout_pay_payment_success_status_id')){
		    $data['payment_pointcheckout_pay_payment_success_status_id'] = $this->config->get('payment_pointcheckout_pay_payment_success_status_id');
		}else{
		    $data['payment_pointcheckout_pay_payment_success_status_id']=2;//default value is proccessing 2
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
	
	
	public function country_autocomplete() {
	    $json = array();
	    
	    if (isset($this->request->get['filter_name'])) {
	        $this->load->model('extension/payment/pointcheckout_pay');
	        
	        $filter_data = array(
	            'filter_name' => $this->request->get['filter_name'],
	            'sort'        => 'name',
	            'order'       => 'ASC',
	            'start'       => 0,
	            'limit'       => 10
	        );
	        
	        $results = $this->model_extension_payment_pointcheckout_pay->getCountries($filter_data);
	        
	        foreach ($results as $result) {
	            $json[] = array(
	                'country_id' => $result['country_id'],
	                'name'        => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
	            );
	        }
	    }
	    
	    $sort_order = array();
	    
	    foreach ($json as $key => $value) {
	        $sort_order[$key] = $value['name'];
	    }
	    
	    array_multisort($sort_order, SORT_ASC, $json);
	    
	    $this->response->addHeader('Content-Type: application/json');
	    $this->response->setOutput(json_encode($json));
	}
	
	public function user_group_autocomplete() {
	    $json = array();
	    
	    if (isset($this->request->get['filter_name'])) {
	        $this->load->model('extension/payment/pointcheckout_pay');
	        
	        $filter_data = array(
	            'filter_name' => $this->request->get['filter_name'],
	            'sort'        => 'cgd.name',
	            'order'       => 'ASC',
	            'start'       => 0,
	            'limit'       => 10
	        );
	        
	        $results = $this->model_extension_payment_pointcheckout_pay->getUserGroups($filter_data);
	        
	        foreach ($results as $result) {
	            $json[] = array(
	                'group_id' => $result['customer_group_id'],
	                'name'        => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
	            );
	        }
	    }
	    
	    $sort_order = array();
	    
	    foreach ($json as $key => $value) {
	        $sort_order[$key] = $value['name'];
	    }
	    
	    array_multisort($sort_order, SORT_ASC, $json);
	    
	    $this->response->addHeader('Content-Type: application/json');
	    $this->response->setOutput(json_encode($json));
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
		
		if($this->request->post['payment_pointcheckout_pay_applicable_usergroups'] && !isset($this->request->post['payment_pointcheckout_pay_user_group'])){
		    $this->error['user_group']=$this->language->get('error_user_group');
		}
		
		if($this->request->post['payment_pointcheckout_pay_applicable_countries'] && !isset($this->request->post['payment_pointcheckout_pay_country'])){
		    $this->error['specific_countries']=$this->language->get('error_specific_country');
		}

		return !$this->error;
	}
}