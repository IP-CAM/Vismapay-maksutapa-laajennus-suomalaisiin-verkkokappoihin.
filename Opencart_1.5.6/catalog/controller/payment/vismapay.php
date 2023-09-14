<?php
class ControllerPaymentVismapay extends Controller {
	protected function index() {
        $this->load->model('checkout/order');
        $this->load->model('payment/vismapay');

        $this->language->load('payment/vismapay');
		$this->data['button_confirm'] = $this->language->get('button_confirm');
		$this->data['text_loading'] = $this->language->get('text_loading');
		$this->data['continue'] = $this->url->link('checkout/success');
		$this->data['auth'] = $this->url->link('checkout/checkout/vismapay','action=auth-payment&method=button');
		$this->data['card'] = $this->url->link('checkout/checkout/vismapay','action=auth-payment&method=card-payment');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $this->data['amount'] = $this->rounder($order_info['total']);
        $this->data['text_card_payment'] = $this->language->get('text_card_payment');
        $this->data['text_success'] = $this->language->get('text_success');
        $this->data['text_pay_button'] = $this->language->get('text_pay_button');
        $this->data['text_failed'] = $this->language->get('text_failed');
        $this->data['text_charging'] = $this->language->get('text_charging');
        $this->data['text_checking'] = $this->language->get('text_checking');
        $this->data['text_token'] = $this->language->get('text_token');
        
        $this->data['entry_card_number'] = $this->language->get('entry_card_number');
        $this->data['entry_month'] = $this->language->get('entry_month');
        $this->data['entry_year'] = $this->language->get('entry_year');
        $this->data['entry_cvv'] = $this->language->get('entry_cvv');
        $this->data['help_cvv'] = $this->language->get('help_cvv');
        $this->data['help_card_number'] = $this->language->get('help_card_number');
        
        $this->data['button_pay'] = $this->language->get('button_pay');
    
	    $this->data['error_create_payment'] = $this->language->get('error_create_payment');
        
        $this->data['action'] = $this->url->link('checkout/checkout/vismapay','action=check-payment-status&token=', 'SSL');
        $this->data['payment_form'] = false;
        
	    require(DIR_SYSTEM . 'library/vismapay.php');

        $payForm = new vismapay\Payform($this->config->get('vismapay_api_key'), $this->config->get('vismapay_private_key'));
        // Start to Actions

         //payment/vismapay &action=auth-payment
         if(isset($this->request->get['action'])){
        	if($this->request->get['action'] == 'auth-payment'){

                $returnUrl = $this->url->link('checkout/checkout/confirm','','SSL');

		        $method = isset($this->request->get['method']) ? $this->request->get['method'] : '';
                $amount = $this->rounder($order_info['total']);

                $languages = array('fi', 'en', 'sv','ru');
                if(in_array($this->session->data['language'], $languages)){
                	$lang = $this->session->data['language'];
                } else {
                	$lang = 'en';
                }
                
                $prefix = str_replace('https://', '', HTTPS_SERVER);
                $prefix = str_replace('http://', '', $prefix);
                $prefix = str_replace('www.', '', $prefix);
                $prefix = str_replace('.', '-', $prefix);
                $prefix = str_replace('/', '-', $prefix);

		        $payForm->addCharge(array(
			        'order_number' => $prefix . '_' . $this->session->data['order_id'],
			        'amount' => $amount,
			         'currency' => 'EUR'
		        ));

		        $payForm->addCustomer(array(
			       'firstname' => $order_info['firstname'], 
			       'lastname' => $order_info['lastname'], 
			       'address_street' => $order_info['payment_address_1'] ,
			       'address_city' => $order_info['payment_city'],
			       'address_zip' => $order_info['payment_postcode']
		        ));

                $products = $this->cart->getProducts();

          
                foreach($products as $product){    

                    $payForm->addProduct(array(
			           'id' => 1, 
			           'title' => $this->language->get('text_tuotteet'),
			           'count' => (int)1,
			           'pretax_price' => $this->rounder($product['price']),
			           'tax' => 0,
			           'price' =>  $this->rounder($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'))),
			           'type' => 2
		             ));
                }
          
            if(!empty($this->session->data['shipping_method'])){
                     $shipping = $this->session->data['shipping_method'];
            	     $shipping['title'] = str_replace('<br/>', ', ', $shipping['title']);
            	     $shipping['title'] = str_replace('<br>', ', ', $shipping['title']);
                if($shipping['cost'] > 0){
		             $payForm->addProduct(array(
			           'id' => 1, 
			           'title' => $shipping['title'],
			           'count' => 1,
			           'pretax_price' => $this->rounder($shipping['cost']),
			           'tax' => $this->taxRate($shipping['tax_class_id']),
			           'price' => $this->rounder($this->tax->calculate($shipping['cost'], $shipping['tax_class_id'], true)),
			           'type' => 2
		             ));
		        }
		    }
		    
            if(!empty($this->session->data['vouchers'])) {
                foreach($this->session->data['vouchers'] as $voucher){
		             $payForm->addProduct(array(
			           'id' => 1, 
			           'title' => $voucher['description'],
			           'count' => (int)1,
			           'pretax_price' => $this->rounder($voucher['amount']),
			           'tax' => 0,
			           'price' => $this->rounder($voucher['amount']),
			           'type' => 2
		             ));
		        }
            }

            $totals = $this->getTotals($this->session->data['order_id']);

               if(!empty($totals['handling'])){
                  $handling = $totals['handling'];

            	   $handling['title'] = str_replace('<br/>', ', ', $handling['title']);
            	   $handling['title'] = str_replace('<br>', ', ', $handling['title']);

		           $payForm->addProduct(array(
			           'id' => 1, 
			           'title' => $handling['title'],
			           'count' => 1,
			           'pretax_price' => $this->rounder($handling['price']),
			           'tax' => $handling['tax_rate'],
			           'price' => $this->rounder($this->tax->calculate($handling['price'], $handling['tax_class_id'], true)),
			           'type' => 3
		            ));
			    }

                if(!empty($totals['fee'])) {
                 $fee = $totals['fee'];

            	   $fee['title'] = str_replace('<br/>', ', ', $fee['title']);
            	   $fee['title'] = str_replace('<br>', ', ', $fee['title']);

		           $payForm->addProduct(array(
			           'id' => 1, 
			           'title' => $fee['title'],
			           'count' => 1,
			           'pretax_price' => $this->rounder($fee['price']),
			           'tax' => $fee['tax_rate'],
			           'price' => $this->rounder($this->tax->calculate($fee['price'], $fee['tax_class_id'], true)),
			           'type' => 3
		            ));
			    }

                if(!empty($this->session->data['voucher'])){
                 $payForm->addProduct(array(
			           'id' => 1, 
			           'title' => 'Lahjakortti',
			           'count' => 1,
			           'pretax_price' => '-'. $this->rounder($this->voucher($this->session->data['voucher'])),
			           'tax' => (int)0,
			           'price' => '-'. $this->rounder($this->voucher($this->session->data['voucher'])),
			           'type' => 3
		            )); 
               }

	            if($method === 'card-payment'){
			         $paymentMethod = array(
			    	      'type' => 'card', 
				          'register_card_token' => 0
			         );
	     	    } else {
			         $paymentMethod = array(
			  	         'type' => 'e-payment', 
				         'return_url' => $returnUrl,
				         'notify_url' => $returnUrl,
				         'lang' => $lang
			         );

			         if(isset($this->request->get['selected'])){
				          $paymentMethod['selected'] = array(strip_tags($this->request->get['selected']));
			         }
		        }

		        $payForm->addPaymentMethod($paymentMethod);

	 	        try{
		  	       $result = $payForm->createCharge();

			       if($result->result == 0){
				       if($method === 'card-payment'){
					     echo json_encode(array(
						     'token' => $result->token,
						     'url' => $payForm::API_URL . '/charge'
					     ));
				       }
				       else {
					       header('Location: ' . $payForm::API_URL . '/token/' . $result->token);
				      }
			      }else{
				      $error_msg = $this->language->get('error_payment');

				      if(isset($result->errors) && !empty($result->errors)){
					       $error_msg .= $this->language->get('error_validation') . print_r($result->errors, true);
				      } else {
				  	      $error_msg .= $this->language->get('error_keys');
				      }

				      exit($error_msg);
			      }
		      }
		      catch(vismapay\PayformException $e){
			      exit($this->language->get('error_exception') . ' ' . $e->getMessage());
		      }
	      } else if($_GET['action'] === 'check-payment-status'){
	          // Start action from Card Payment form
	     	  try{
		    	$result = $payForm->checkStatusWithToken($this->request->get['token']);

		    	echo $result->result == 0 ? 'success' : 'failed';
	      	  }
	    	  catch(vismapay\PayformException $e){
		    	exit($this->language->get('error_exception') . ' ' . $e->getMessage());
	    	  }
    	  }

	      exit();
        } else if(isset($this->request->get['return-from-pay-page'])){
    	     try{
	        	$result = $payForm->checkReturn($this->request->get);

		        if($result->RETURN_CODE == 0){
			        exit($this->language->get('text_succeeded') . ', <a href="'. $this->url->link('common/home') .'">'.$this->language->get('text_continue').'</a>');	
		         } else {
			        exit($this->language->get('text_is_failed') . ' (RETURN_CODE: ' . $result->RETURN_CODE . '), <a href="'. $this->url->link('common/home') .'">' . $this->language->get('text_continue') . '</a>');
		         }
	         }
	         catch(vismapay\PayformException $e){
		           exit($this->language->get('error_exception') . ' ' . $e->getMessage());
	        }
         }
         $this->data['merchantPaymentMethods'] = array();
         try{
    	    $merchantPaymentMethods = $payForm->getMerchantPaymentMethods();

	        if($merchantPaymentMethods->result != 0){
		        exit($this->language->get('error_create_payment'));
	         }
	         $this->data['merchantPaymentMethods'] = $merchantPaymentMethods;
         }
         catch(vismapay\PayformException $e){
	          exit($this->language->get('error_exception') . ' ' . $e->getMessage());
         }

			$this->children = array(
				'common/column_left',
				'common/column_right'
			);

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/vismapay.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/payment/vismapay.tpl';
		} else {
			$this->template = 'default/template/payment/vismapay.tpl';
		}

		$this->render();
	}

    protected function categoryId($product_id){
      $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = '" . $product_id . "'");
      $array = array();
      foreach($query->rows as $result){
        $array[] = $result['category_id'];
      }
      return $array;
    }

    protected function voucher($code){

      $this->load->model('total/voucher');

      $query = $this->model_total_voucher->getVoucher($code);
    
      if(isset($query['amount'])){
        return $query['amount'];
      }
    }

    protected function taxRate($tax_class_id){
        $tax_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "tax_class tc INNER JOIN " . DB_PREFIX . "tax_rule tr ON(tc.tax_class_id = tr.tax_class_id) INNER JOIN " . DB_PREFIX . "tax_rate tt ON(tr.tax_rate_id = tt.tax_rate_id) WHERE tc.tax_class_id = '" . (int)$tax_class_id . "'");
        if(isset($tax_query->row['rate'])){
         return round($tax_query->row['rate'],2);
        }
    }


    protected function getTotals($order_id){
        $result = array();

        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE `order_id` = '" . $order_id . "' AND `code` = 'handling'");
         if(isset($query->row['value']) && $query->row['value'] > 0){

         $result['handling'] = array('title' => $query->row['title'],
                                     'price' => $query->row['value'],
                                     'tax_class_id' => $this->config->get('handling_tax_class_id'),
                                     'tax_rate' => $this->taxRate($this->config->get('handling_tax_class_id'))
                                );
         } else {
            $result['handling'] = array();
         }

         $query2 = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE `order_id` = '" . $order_id . "' AND `code` = 'low_order_fee'");
         if(isset($query2->row['value']) && $query2->row['value'] > 0){

              $result['fee'] = array('title' => $query2->row['title'],
                                     'price' => $query2->row['value'],
                                     'tax_class_id' => $this->config->get('low_order_fee_tax_class_id'),
                                     'tax_rate' => $this->taxRate($this->config->get('low_order_fee_tax_class_id'))
                                );
           } else {
               $result['fee'] = array();
           }

          return $result;

    }

	public function confirm() {
         $this->language->load('payment/vismapay');
         $return_status = $this->request->get['RETURN_CODE'];
         $authcode = $this->request->get['AUTHCODE'];
         $settled = $this->request->get['SETTLED'];
         $order_number = $this->request->get['ORDER_NUMBER'];
         $settled = (!empty($this->request->get['SETTLED']) ? $this->request->get['SETTLED'] : '');
		 
		$this->load->model('checkout/order');
        $log = new Log("vismapay.log");
            if($authcode && $order_number){
                if($return_status == 0){
		           $this->model_checkout_order->confirm($this->session->data['order_id'], $this->config->get('vismapay_order_status_id'), $this->language->get('text_success'));
                   $log->write($this->language->get('text_success'));
		           $this->redirect($this->url->link('checkout/success'));
                } elseif ($return_status == 1 || $return_status == 4 || $return_staus == 10){
		             $this->model_checkout_order->confirm($this->session->data['order_id'], $this->config->get('vismapay_failed_status_id'), $this->language->get('text_failed_' . $return_status));
                     $log->write($this->language->get('text_failed_' . $return_status)); 
			         $this->redirect($this->url->link('checkout/failure'));
                } else {
		             $this->model_checkout_order->confirm($this->session->data['order_id'], $this->config->get('vismapay_failed_status_id'), $this->language->get('text_unknown_failed'));
                     $log->write($this->language->get('text_unknow_failed')); 
			         $this->redirect($this->url->link('checkout/failure'));
                }
            }
	}

    public function rounder($sum){
      $round = round($sum,2);
       if(strpos($round,'.')){
           $parts = explode('.',$round);
           if(strlen($parts[1]) == 1){
            return str_replace('.','',$round . '0');
           }
           return str_replace('.','',$round);
        } else {
           return $round . '00';
        }
    }
}
