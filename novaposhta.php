<?php
 class NP{
 	/* Город отправителя */
	 public static $out_city='Киев';
 	/* Отправитель */	 
	 public static $out_company='ПП Петров';
 	/* Склад */	 
	 public static $out_warehouse='1';	 
 	/* Представитель отправителя */	 
	 public static $out_name='Петров Иван Иваныч';	 
 	/* Телефон отправителя */	 
	 public static $out_phone='0671234567';	 
 	/* API ключ */	 
	 public static $api_key='123ab45678901abc1234ab1a1234567a';	 
 	/* Описание посылки */	 
	 public static $description='Взуття';
 	/* Описание упаковки */	 
	 public static $pack='Коробка';	 
	 	 
	 /**
	  * Функция отправки запроса на сервер Новой почты
	  	$xml — запрос
	  */
	 static public function send($xml){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://orders.novaposhta.ua/xml.php');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	 }
	 
	 /**
	  * Запрос на расчёт стоимости доставки 
	  	$to_city — город получатель
	  	$weight — вес
	  	$pub_price — заявленная стоимость
	  	$height — высота коробки	  		  		  	
	  	$width — ширина коробки	  		  		  	
	  	$depth — длинна коробки	  		  		  		  	
	  */
	 public static function price($to_city,$weight,$pub_price,$date,$height=0,$width=0,$depth=0){
		$xml='<?xml version="1.0" encoding="utf-8"?>
		<file>
		<auth>'.NP::$api_key.'</auth>
		<countPrice>
        	<senderCity>'.NP::$out_city.'</senderCity>
        	<recipientCity>'.$to_city.'</recipientCity>
        	<mass>'.$weight.'</mass>
        	<height>'.$depth.'</height>
        	<width>'.$width.'</width>
        	<depth>'.$depth.'</depth>
        	<publicPrice>'.$pub_price.'</publicPrice>
        	<deliveryType_id>4</deliveryType_id>
        	<floor_count>0</floor_count>
        	<date>'.$date.'</date>
        	</countPrice>
        </file>';
		
		$xml = simplexml_load_string(NP::send($xml));
		return $xml->cost;
	} 	
	 /**
	  * Запрос на создание декларации на отправку 
	  	$order_id — номер заказа на вашем сайте (для вашего удобства)
	  	$city — город получения
	  	$warehouse — номер склада получения
	  	$name — имя получателя	  		  		  	
	  	$surname — фамилия получателя	  		  		  	
	  	$phone — телефон получателя	  		  		  		  	
	  	$weight — вес посылки	  		  		  	
	  	$pub_price — заявленная стоимость	  		  		  	
	  	$date — дата отправки
	  	$payer — плательщик (1 — получатель, 0 — отправитель, 2 — третья сторона)	  	
	  */
	 public static function ttn($order_id,$city,$warehouse,$name,$surname,$phone,$weight,$pub_price,$date,$payer=0){
		$xml='<?xml version="1.0" encoding="utf-8"?>
		<file>
		<auth>'.NP::$api_key.'</auth>
		<order
	        order_id="'.$order_id.'"
	
	        sender_city="'.NP::$out_city.'"
	        sender_company="'.NP::$out_company.'"
	        sender_address="'.NP::$out_warehouse.'"
	        sender_contact="'.NP::$out_name.'"
	        sender_phone="'.NP::$out_phone.'"
	
	        rcpt_city_name="'.$city.'"
	        rcpt_name="ПП '.$surname.'"
	        rcpt_warehouse="'.$warehouse.'"
	        rcpt_contact="'.$name.'"
	        rcpt_phone_num="'.$phone.'"
	        
	        pack_type="'.NP::$pack.'"
	        description="'.NP::$description.'"
	
	        pay_type="1"
	        payer="'.$payer.'"
	
	        cost="'.$pub_price.'"
	        date="'.$date.'" 
	        weight="'.$weight.'">
	        <order_cont
            	cont_description="'.NP::$description.'" />
            </order>
        </file>';
		
		$xml = simplexml_load_string(NP::send($xml));
		return array('oid'=>$order_id,'ttn'=>trim($xml->order->attributes()->np_id));
	} 
	
	 /**
	  * Запрос на удаление декларации из базы Новой почты
	  	$ttn — номер декларации, которую нужно удалить
	  */
	public static function remove($ttn){
		$xml='<?xml version="1.0" encoding="utf-8"?>
		<file>
		<auth>'.NP::$api_key.'</auth>
		<close>'.$ttn.'</close>
		</file>';
		
		$xml = simplexml_load_string(NP::send($xml));
	}
	 /**
	  * Запрос на печать маркировок для декларации (производит перенаправление на страницу печати)
	  	$ttn — номер декларации, которую нужно напечатать
	  */	
	public static function printit($ttn){
		header('location: http://orders.novaposhta.ua/pformn.php?o='.$ttn.'&num_copy=4&token='.NP::$api_key);
	}
	
	
	 /**
	  * Запрос на получение списка складов Новой почты для определённого города (или полный список, если город не указан)
	  	$filter — город, по которому нужно отфильтровать список складов Новой почты
	  */
	public static function warenhouse($filter=false){
		$xml='<?xml version="1.0" encoding="utf-8"?>
		<file>
		<auth>'.NP::$api_key.'</auth>
		<warenhouse/>';
		if($filter){
			$xml.='<filter>'.$filter.'</filter>';
		}
		$xml.='</file>';
		
		$xml = simplexml_load_string(NP::send($xml));
		return($xml);
	}
	
	
	 /**
	  * Запрос на получение списка населённых пунктов, в которых есть склады Новой почты
	  */	
	public static function city(){
		$xml='<?xml version="1.0" encoding="utf-8"?>
		<file>
		<auth>'.NP::$api_key.'</auth>
		<city/>
		</file>';
		
		$xml = simplexml_load_string(NP::send($xml));
		return($xml);
	}
	/**
	*	Функция, преобразовывает резултат работы запроса city в структурированный по областям массив
	*/
	public static function cities_array(){
		$cities=NP::city();
		$states_cities=array();
		foreach($cities->result->cities->city as $city){
			if(!isset($states_cities[trim($city->areaNameUkr)]))
				$states_cities[trim($city->areaNameUkr)]=array();
			$states_cities[trim($city->areaNameUkr)][]=trim($city->nameUkr);
		}
		return $states_cities;
	}		 
 }