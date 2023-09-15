<?php
class ModelPaymentVismapay extends Model {
	public function getMethod($address, $total) {
		$this->load->language('payment/vismapay');

		if ($this->config->get('vismapay_status')) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('vismapay_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

			if (!$this->config->get('vismapay_geo_zone_id')) {
        		$status = true;
      		} elseif ($query->num_rows) {
      		  	$status = true;
      		} else {
     	  		$status = false;
			}	
      	} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'vismapay',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('vismapay_sort_order')
			);
		}

		return $method_data;
	}

    public function getCoupon($price, $product_id, $category, $code){
       $query = $this->db->query("SELECT `discount` FROM `" . DB_PREFIX . "coupon_product` cp LEFT JOIN `" . DB_PREFIX . "coupon` c ON(cp.coupon_id = c.coupon_id) WHERE c.type = 'P' AND c.code = '" . $code . "' AND cp.product_id = '" . $product_id . "'");

       $query2 = $this->db->query("SELECT `discount` FROM `" . DB_PREFIX . "coupon_product` cp LEFT JOIN `" . DB_PREFIX . "coupon` c ON(cp.coupon_id = c.coupon_id) WHERE c.type = 'F' AND c.code = '" . $code . "' AND cp.product_id = '" . $product_id . "'");

        $one = $price/100;

      if($query->num_rows){
         $min = $one * $query->row['discount'];
         return $price - $min;
      } elseif ($query2->num_rows){
            $dis2 = $query2->row['discount'];
            return $price - $dis2;
      } elseif (!empty($category)) {
            $discount = 0;
            foreach($category as $id){
                  $find = $this->db->query("SELECT `discount` FROM `" . DB_PREFIX . "coupon_category` cc LEFT JOIN `" . DB_PREFIX . "coupon` c ON(cc.coupon_id = c.coupon_id) WHERE type = 'P' AND c.code = '" . $code . "' AND cc.category_id = '" . $id . "'");
                  $find2 = $this->db->query("SELECT `discount` FROM `" . DB_PREFIX . "coupon_category` cc LEFT JOIN `" . DB_PREFIX . "coupon` c ON(cc.coupon_id = c.coupon_id) WHERE type = 'F' AND c.code = '" . $code . "' AND cc.category_id = '" . $id . "'");
                  if(!empty($find->row['discount'])){
                     $min = $one * $find->row['discount'];
                     $discount = $price - $min;
                  } elseif (!empty($find2->row['discount'])){
                          $dis2 = $find2->row['discount'];
                          $discount = $price - $dis2;
                  }
            }

            if($discount){
               return $discount;
            }
      }
      return $price;
    }
    public function getCouponShip($code){
          $query = $this->db->query("SELECT discount FROM `" . DB_PREFIX . "coupon` WHERE `type` = 'P' AND `code` = '" . $code . "' AND `shipping` = '1'");
            if($query->num_rows){
               return $query->row['discount'];
          }

    }
}
