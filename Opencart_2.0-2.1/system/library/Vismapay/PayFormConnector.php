<?php

namespace Vismapay;

interface PayFormConnector
{
	public function request($url, $post_arr);
}
