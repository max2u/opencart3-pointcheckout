<?php
class ModelExtensionPaymentPointCheckOutPay extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/pointcheckout_pay');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_pointcheckout_pay_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if ($this->config->get('payment_pointcheckout_pay_total') > 0 && $this->config->get('payment_pointcheckout_pay_total') > $total) {
			$status = false;
		} elseif (!$this->config->get('payment_pointcheckout_pay_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}
		if($status && $this->config->get('payment_pointcheckout_pay_applicable_countries')){
		    //check if payment_country is valid
		    $status = false;
		    foreach ($this->config->get('payment_pointcheckout_pay_country') as $applicableCountry){
		        if($applicableCountry == $this->session->data['payment_address']['country_id']){
		            $status = true;
		        }
		    }
		}
		
		//check if user_group is valid
		if($status && $this->config->get('payment_pointcheckout_pay_applicable_usergroups')){
		    $this->load->model('account/customer');
		    $customerInfo = $this->model_account_customer->getCustomer($this->session->data['customer_id']);
		    $status=false;
		    foreach ($this->config->get('payment_pointcheckout_pay_user_group') as $applicableUserGroup){
		        if($applicableUserGroup == $customerInfo['customer_group_id']){
		            $status = true;
		        }
		    }
		}
		
		

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'pointcheckout_pay',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_pointcheckout_pay_sort_order')
			);
		}

		return $method_data;
	}
}