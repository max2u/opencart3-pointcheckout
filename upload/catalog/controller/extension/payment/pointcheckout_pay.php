<?php
class ControllerExtensionPaymentPointCheckOutPay extends Controller {
    public function index() {
        $this->load->language('extension/payment/pointcheckout_pay');
        return $this->load->view('extension/payment/pointcheckout_pay');
        
    }
    
    //Stage 1 Sending data to pointcheckout and redirect user to paymet page if success
    
    public function send() {
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
            
            $_BASE_URL='';
            if ($this->config->get('payment_pointcheckout_pay_env') == '2'){
                $_BASE_URL='https://pay.staging.pointcheckout.com';
            }elseif(!$this->config->get('payment_pointcheckout_pay_env')){
                $_BASE_URL='https://pay.pointcheckout.com';
            }else{
                $_BASE_URL='https://pay.test.pointcheckout.com';
            }
            
            $headers = array(
                'Content-Type: application/json',
                'Api-Key:'.$this->config->get('payment_pointcheckout_pay_key'),
                'Api-Secret:'.$this->config->get('payment_pointcheckout_pay_secret'),
            );
            
            $products= $this->model_checkout_order->getOrderProducts($this->session->data['order_id']);
            $items = array();
            $i = 0;
            foreach ($products as $product){
                $item = (object) array(
                    'name'=> $product['name'],
                    'sku' => $product['product_id'],
                    'quantity' => $product['quantity'],
                    'total' =>$this->currency->format($product['price']*$product['quantity'], $this->session->data['currency'], '', false));
                $items[$i++] = $item;
            }
            $json = array();
            $storeOrder = array();
            $storeOrder['referenceId'] = $order_info['order_id'];
            $storeOrder['items'] = array_values($items);
            //calculating totals
            $calculatedGrandtotal = 0;
            //looping totals and store data in our storeOrder
            $order_totals=$this->model_checkout_order->getOrderTotals($this->session->data['order_id']);
            foreach ($order_totals as $total) {
                switch( $total['code']){
                    case 'sub_total':
                        $storeOrder['subtotal'] = $this->currency->format($total['value'], $this->session->data['currency'], '', false);
                        $calculatedGrandtotal+=$storeOrder['subtotal'];
                        break;
                    case 'shipping':
                        $shipping = $this->currency->format($total['value'], $this->session->data['currency'], '', false);
                        //in case more than one shipping charges are there 
                        if(isset($storeOrder['shipping'])){
                            $storeOrder['shipping'] += $shipping;
                            $calculatedGrandtotal+=$shipping;
                        }else{
                            $storeOrder['shipping'] = $shipping;
                            $calculatedGrandtotal+=$shipping;
                        }
                        break;
                    case 'tax':
                        $tax = $this->currency->format($total['value'], $this->session->data['currency'], '', false);
                        //in case more than one tax charges are there
                        if(isset($storeOrder['tax'])){
                            $storeOrder['tax'] += $tax;
                            $calculatedGrandtotal+=$tax;
                        }else{
                            $storeOrder['tax'] = $tax;
                            $calculatedGrandtotal+=$tax;
                        }
                        break;
                    case 'discount':
                        $discount = $this->currency->format($total['value'], $this->session->data['currency'], '', false);
                        //in case more than one discount charges are there
                        if(isset($storeOrder['discount'])){
                            $storeOrder['discount'] +=  $discount;
                            $calculatedGrandtotal-= $discount;
                        }else{
                            $storeOrder['discount'] =  $discount;
                            $calculatedGrandtotal-= $discount;
                        }
                        break;
                    case 'total':
                        $storeOrder['grandtotal'] = $this->currency->format($total['value'], $this->session->data['currency'], '', false);
                        break;
                }
            }
            //calculate the diff between calculated grandtotal and given grandtotal from order 
            if($calculatedGrandtotal > $storeOrder['grandtotal']){
                $grandTotalDiff = $calculatedGrandtotal-$storeOrder['grandtotal'];
            }else{
                $grandTotalDiff =$storeOrder['grandtotal']-$calculatedGrandtotal;
            }
            if($grandTotalDiff>0){
                //round the difference to 2 decimals 
                    $grandTotalDiff>=0.009  && $grandTotalDiff<0.01?$grandTotalDiff=0.01:0;
                //accepted to have up to but not 0.05 differnce and would be added to shipping just to avoid having errors in numbers comparing by pointcheckout
                if($grandTotalDiff<0.05){
                    $storeOrder['shipping']+=$grandTotalDiff;
                }else{
                    $json['error'] ="Order totals dose not add up";
                }
            }
            $storeOrder['currency'] = $order_info['currency_code'];
            //prepare customer Information
            $customer = array();
            $customer['firstname'] = $order_info['firstname'];
            $customer['lastname'] = $order_info['lastname'];
            $customer['email'] = $order_info['email'];
            $customer['phone'] = $order_info['telephone'];
            
            $billingAddress = array();
            $billingAddress['name'] = $order_info['payment_firstname'].' '.$order_info['payment_lastname'];
            $billingAddress['address1'] = $order_info['payment_address_1'];
            $billingAddress['address2'] = $order_info['payment_address_2'];
            $billingAddress['city'] = $order_info['payment_city'];
            $billingAddress['state'] = $order_info['payment_city'];
            $billingAddress['country'] = $order_info['payment_country'];
            
            $shippingAddress = array();
            $shippingAddress['name'] = $order_info['shipping_firstname'].' '.$order_info['shipping_lastname'];
            $shippingAddress['address1'] = $order_info['shipping_address_1'];
            $shippingAddress['address2'] = $order_info['shipping_address_2'];
            $shippingAddress['city'] = $order_info['shipping_city'];
            $shippingAddress['state'] = $order_info['shipping_city'];
            $shippingAddress['country'] = $order_info['shipping_country'];
            
            $customer['billingAddress'] = $billingAddress;
            $customer['shippingAddress'] = $shippingAddress;
            
            $storeOrder['customer'] = $customer;
            //convert storeOrder array to json format object
            $storeOrder = json_encode($storeOrder);
            //open http connection
            $curl = curl_init($_BASE_URL.'/api/v1.0/checkout');
            
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $storeOrder);
            //sending request
            $response = curl_exec($curl);
            //close connection
            curl_close($curl);
            
            //alert error if response is failure
            if (!$response) {
                $json['error']='Error Connecting to PointCheckout - Please Try again later';
            }else{
                $response_info = json_decode($response);
                //prepare response to pointcheckout payment tag ajax request
                if (($response_info->success == 'true')) {
                    $message = '';
                    if (isset($response_info->result)) {
                        $resultData = $response_info->result;
                        if (isset($resultData->checkoutId)){
                            $message .='PointCheckout Payment Id: '.$resultData->checkoutId."\n";
                            $this->session->data['checkoutId']=$resultData->checkoutId;
                        }
                        $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_pointcheckout_pay_order_status_id'), $message, false);
                    }
                    $json['success'] = $_BASE_URL.'/checkout/'.$resultData->checkoutKey;
                } else {
                    $json['error'] = $response_info->error ;
                }
            }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    //Stage 2 Finalize Payment after user return back from payment page either success or failure
    
    public function confirm() {
        
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($_REQUEST['reference']);
        
        $_BASE_URL='';
        if ($this->config->get('payment_pointcheckout_pay_env') == '2'){
            $_BASE_URL='https://pay.staging.pointcheckout.com';
        }elseif(!$this->config->get('payment_pointcheckout_pay_env')){
            $_BASE_URL='https://pay.pointcheckout.com';
        }else{
            $_BASE_URL='https://pay.test.pointcheckout.com';
        }
        
        $headers = array(
            'Content-Type: application/json',
            'Api-Key:'.$this->config->get('payment_pointcheckout_pay_key'),
            'Api-Secret:'.$this->config->get('payment_pointcheckout_pay_secret'),
        );
        $curl = curl_init($_BASE_URL.'/api/v1.0/checkout/'.$_REQUEST['checkout']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($curl);
        
        if (!$response) {
            $this->log->write('DoDirectPayment failed: ' . curl_error($curl) . '(' . curl_errno($curl) . ')');
            curl_close($curl);
            $this->forwardFailure('Error Connecting to PointCheckout - Payment Failed',$this->session->data['order_id']);
        }
        curl_close($curl);
        $message = '';
        $response_info = json_decode($response);
        //check response and redirect user to either success or failure page
        if (($response_info->success == 'true' && $response_info->result->status =='PAID')) {
            $message.= 'PointCheckout Payment Confirmed'."\n" ;
            $message.= 'payment status: '.$response_info->result->status;
            $this->forwardSuccess($message,$this->session->data['order_id']);
        }elseif(!$response_info->success == 'true'){
            $message.='PointCheckout Payment Failed'."\n";
            $this->log-write('[ERROR} PointCheckout response with error payment failed   error msg is :'.$response_info->error);
            $this->forwardFailure($message,$this->session->data['order_id']);
        }else{
            $message ='PointCheckout Payment did not complete'."\n";
            $message.= 'payment is : CANCELED'."\n";
            $this->forwardFailure($message,$this->session->data['order_id']);
        }
        
    }
    private function forwardFailure($message,$currentOrderId){
        $this->load->model('checkout/order');
        $this->model_checkout_order->addOrderHistory($currentOrderId, $this->config->get('payment_pointcheckout_pay_payment_failed_status_id'), $message, false);
        $failureurl = $this->url->link('checkout/failure');
        ob_start();
        header('Location: '.$failureurl);
        ob_end_flush();
        die();
    }
    
    private function forwardSuccess($message,$currentOrderId){
        $this->load->model('checkout/order');
        $this->model_checkout_order->addOrderHistory($currentOrderId, $this->config->get('payment_pointcheckout_pay_payment_success_status_id'), $message, false);
        $successurl = $this->url->link('checkout/success');
        ob_start();
        header('Location: '.$successurl);
        ob_end_flush();
        die();
    }
    
}