<?php
if (!$modx->getService('modretailcrm','modRetailCrm', MODX_CORE_PATH.'components/modretailcrm/model/modretailcrm/')) {  
    $modx->log(1, '[ModRetailCrm] - Not found class RetailCrm');
    return;
}

$pdo = $modx->getService('pdoFetch');

$site = $modx->getOption('modretailcrm_siteCode');
$apiKey = $modx->getOption('modretailcrm_apiKey');
$crmUrl = $modx->getOption('modretailcrm_url');

$modRetailCrm = new modRetailCrm($modx, $apiKey, $crmUrl, $site);

switch ($modx->event->name) {
    case 'OnUserSave':
        if ($mode == modSystemEvent::MODE_NEW) {
            if ($modx->context->key != 'mgr' ) {
                if ($profile = $modx->getObject('modUserProfile', $user->get('id'))) {
                    $customer = array();
                    $customer['externalId'] =  $user->get('id');
                    $customer['firstName'] = $profile->fullname;
                    $customer['email'] = $profile->email;
                    if(!empty($profile->phone)){
                        $customer['phones'][]['number'] = $profile->phone;
                    }
                    
                    $response = $modRetailCrm->request->customersCreate($customer, $site);  
                    
                }
                
            }
        }
        break;
    case 'msOnCreateOrder':
        $order = $msOrder->toArray();
        $order['address'] = $pdo->getArray('msOrderAddress', array('id' => $order['id']), array('sortby' => 'id'));
        $order['delivery'] = $pdo->getArray('msDelivery', array('id' => $order['delivery']), array('sortby' => 'id'));
        $order['payment'] = $pdo->getArray('msPayment', array('id' => $order['payment']), array('sortby' => 'id'));
        $order['profile'] = $pdo->getArray('modUserProfile', array('internalKey' => $order['user_id']), array('sortby' => 'id'));
        $order['products'] = $pdo->getCollection('msOrderProduct', array('order_id' => $order['id']), array('sortby' => 'id'));        
        
        $orderData = array();
        $orderData['customer']['externalId'] = $order['user_id'];
        $orderData['externalId'] = $order['id'];
        $orderData['firstName'] = !empty($order['address']['receiver']) ? $order['address']['receiver'] : $order['profile']['fullname'];
        $orderData['phone'] = !empty($order['address']['phone']) ? $order['address']['phone'] : $order['profile']['phone'];
        $orderData['email'] = $order['profile']['email'];
     
        foreach ($order['products'] as $key=>$product) {
            $orderData['items'][$key]['initialPrice'] = $product['price'];
            $orderData['items'][$key]['purchasePrice'] = $product['price'];
            $orderData['items'][$key]['productName'] = $product['name'];
            $orderData['items'][$key]['quantity'] = $product['count'];
            $orderData['items'][$key]['offer']['externalId'] = $product['id'];
            foreach($product['options'] as $k=>$v){
                $orderData['items'][$key]['properties'][] = array('name' => $k, 'value' => $v); 
            }
		}
		
		$fields = array(
            'index' => 'Индекс', 
            'country' => 'Страна', 
            'region' => 'Регион', 
            'city' => 'Город', 
            'metro' => 'Метро', 
            'street' => 'Улица', 
            'building' => 'Дом', 
            'room' => 'Квартира\офис'
        );
        $address = '';
        foreach($fields as $field=>$comment){
            if(!empty($order['address'][$field])){
                $address .= $comment.':'.$order['address'][$field].' 
                ';
                if($field == 'room'){
                    $orderData['delivery']['address']['flat'] = $order['address'][$field];
                }else{
                    $orderData['delivery']['address'][$field] = $order['address'][$field];
                }
                
            }
        }
        
        $orderData['delivery']['address']['text'] = $address;
        $orderData['customerComment'] = $order['address']['comment'];
        $orderData['delivery']['code'] = $order['delivery']['description'];
        $orderData['delivery']['cost'] = $order['delivery']['price'];
        
        $orderData['payments'][0]['type'] = $order['payment']['description'];
        
        $response = $modRetailCrm->request->ordersCreate($orderData, $site);       
        break;
}