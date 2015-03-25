<?php
/**
 * @file
 * Novaposhta class(NP) file.
 */

/**
 * Class NP.
 *
 * NovaPoshta API class handler.
 */
class NP {
  /**
   * Out city.
   *
   * @var string
   */
  public static $out_city = 'Киев';
  /**
   * Out company.
   *
   * @var string
   */
  public static $out_company = 'ПП Петров';
  /**
   * Out Warehouse.
   *
   * @var string
   */
  public static $out_warehouse = '1';
  /**
   * Out name.
   *
   * @var string
   */
  public static $out_name = 'Петров Иван Иваныч';
  /**
   * Out phone.
   *
   * @var string
   */
  public static $out_phone = '0671234567';
  /**
   * API key
   *
   * @var string
   */
  public static $api_key = '123ab45678901abc1234ab1a1234567a';
  /**
   * Description.
   *
   * @var string
   */
  public static $description = 'Взуття';
  /**
   * Pack type.
   *
   * @var string
   */
  public static $pack = 'Коробка';

  /**
   * Function for sending a request to the NovaPoshta server.
   *
   * @param string $xml
   *   XML-request string.
   *
   * @return mixed
   *   Response.
   */
  static public function send($xml) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://orders.novaposhta.ua/xml.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
  }

  /**
   * Request for calculating a delivery price.
   *
   * @param string $to_city
   *   Recipient city.
   *
   * @param int|string $weight
   *   Weight.
   *
   * @param int|string $pub_price
   *   Public price.
   *
   * @param string $date
   *   Date.
   *
   * @param int $height
   *   Height.
   *
   * @param int $width
   *   Width.
   *
   * @param int $depth
   *   Depth.
   *
   * @return \SimpleXMLElement[]
   *   Price.
   */
  public static function price($to_city, $weight, $pub_price, $date, $height = 0, $width = 0, $depth = 0) {
    $xml = '<?xml version="1.0" encoding="utf-8"?>';
    $xml .= '<file>';
    $xml .= '<auth>' . NP::$api_key . '</auth>';
    $xml .= '<countPrice>';
    $xml .= '<senderCity>' . NP::$out_city . '</senderCity>';
    $xml .= '<recipientCity>' . $to_city . '</recipientCity>';
    $xml .= '<mass>' . $weight . '</mass>';
    $xml .= '<height>' . $height . '</height>';
    $xml .= '<width>' . $width . '</width>';
    $xml .= '<depth>' . $depth . '</depth>';
    $xml .= '<publicPrice>' . $pub_price . '</publicPrice>';
    $xml .= '<deliveryType_id>4</deliveryType_id>';
    $xml .= '<floor_count>0</floor_count>';
    $xml .= '<date>' . $date . '</date>';
    $xml .= '</countPrice>';
    $xml .= '</file>';
    $xml = simplexml_load_string(NP::send($xml));
    return $xml->cost;
  }

  /**
   * Request to create a declaration to send.
   *
   * @param int|string $order_id
   *   Order number of the delivery.
   *
   * @param string $city
   *   Recipient city.
   *
   * @param string|int $warehouse
   *   Recipient warehouse number.
   *
   * @param string $name
   *   Recipient name.
   *
   * @param string $surname
   *   Recipient surname.
   *
   * @param string|int $phone
   *   Recipient phone number.
   *
   * @param string|int $weight
   *   Weight.
   *
   * @param int|string $pub_price
   *   Public price.
   *
   * @param string $date
   *   Date.
   *
   * @param int $payer
   *   The payer.
   *
   * @return array
   *   Response array.
   */
  public static function ttn($order_id, $city, $warehouse, $name, $surname, $phone, $weight, $pub_price, $date, $payer = 0) {
    $xml = '<?xml version="1.0" encoding="utf-8"?>';
    $xml .= '<file>';
    $xml .= '<auth>' . NP::$api_key . '</auth>';
    $xml .= '<order';
    $xml .= 'order_id="' . $order_id . '"';

    $xml .= 'sender_city="' . NP::$out_city . '"';
    $xml .= 'sender_company="' . NP::$out_company . '"';
    $xml .= 'sender_address="' . NP::$out_warehouse . '"';
    $xml .= 'sender_contact="' . NP::$out_name . '"';
    $xml .= 'sender_phone="' . NP::$out_phone . '"';

    $xml .= 'rcpt_city_name="' . $city . '"';
    $xml .= 'rcpt_name="ПП ' . $surname . '"';
    $xml .= 'rcpt_warehouse="' . $warehouse . '"';
    $xml .= 'rcpt_contact="' . $name . '"';
    $xml .= 'rcpt_phone_num="' . $phone . '"';

    $xml .= 'pack_type="' . NP::$pack . '"';
    $xml .= 'description="' . NP::$description . '"';

    $xml .= 'pay_type="1"';
    $xml .= 'payer="' . $payer . '"';

    $xml .= 'cost="' . $pub_price . '"';
    $xml .= 'date="' . $date . '"';
    $xml .= 'weight="' . $weight . '">';

    $xml .= '<order_cont cont_description="'.NP::$description.'" />';
    $xml .= '</order></file>';

    $xml = simplexml_load_string(NP::send($xml));
    return array(
      'oid' => $order_id,
      'ttn' => trim($xml->order->attributes()->np_id),
    );
  }

  /**
   * A request to delete the declaration from the NovaPoshta database.
   *
   * @param string|int $ttn
   *   Declaration ID.
   */
  public static function remove($ttn) {
    $xml = '<?xml version="1.0" encoding="utf-8"?>';
    $xml .= '<file>';
    $xml .= '<auth>' . NP::$api_key . '</auth>';
    $xml .= '<close>' . $ttn . '</close>';
    $xml .= '</file>';
    $xml = simplexml_load_string(NP::send($xml));
  }

  /**
   * Request for printing labels for the declaration.
   *
   * Note: redirects to the printing page.
   *
   * @param string|int $ttn
   *   Declaration ID.
   */
  public static function printit($ttn) {
    $header = 'location: http://orders.novaposhta.ua/pformn.php';
    $header .= '?o=' . $ttn;
    $header .= '&num_copy=4';
    $header .= '&token=' . NP::$api_key;
    header($header);
  }

  /**
   * A request for getting a list of NovaPoshta warehouses by particular city.
   *
   * Or a complete list, if the city is not specified.
   *
   * @param bool|string $filter
   *   The city in which you want to filter the list of NovaPoshta warehouses.
   *
   * @return \SimpleXMLElement
   *   XML-response.
   */
  public static function warenhouse($filter = false) {
    $xml = '<?xml version="1.0" encoding="utf-8"?>';
    $xml .= '<file>';
    $xml .= '<auth>' . NP::$api_key . '</auth>';
    $xml .= '<warenhouse/>';
    // Add the filter value if it exist.
    if ($filter) {
      $xml .= '<filter>' . $filter . '</filter>';
    }
    $xml .= '</file>';

    $xml = simplexml_load_string(NP::send($xml));
    return($xml);
  }

  /**
   * A request for getting a list of settlements, which have a NovaPoshta warehouses.
   */
  public static function city() {
    $xml = '<?xml version="1.0" encoding="utf-8"?>';
    $xml .= '<file>';
    $xml .= '<auth>' . NP::$api_key . '</auth>';
    $xml .= '<city/></file>';

    $xml = simplexml_load_string(NP::send($xml));
    return($xml);
  }
  
  /**
   * Results converter.
   *
   * The function converts the result of the query in the
   * city on an associative array of regions.
   */
  public static function cities_array() {
    $cities = NP::city();
    $states_cities = array();
    if ($cities->result->cities->city) {
      foreach ($cities->result->cities->city as $city) {
        if (!isset($states_cities[trim($city->areaNameUkr)])) {
          $states_cities[trim($city->areaNameUkr)] = array();
        }
        $states_cities[trim($city->areaNameUkr)][] = trim($city->nameUkr);
      }
    }
    return $states_cities;
  }
}
