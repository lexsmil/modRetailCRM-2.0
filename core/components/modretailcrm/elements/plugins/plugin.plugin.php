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
        //Проверяю наличие пользователя в базе CRM
        $user_response = $modRetailCrm->request->customersGet($order['user_id'], 'externalId', $site);
        if($user_response->getStatusCode() == 404){
            $customer_profile = $pdo->getArray('modUserProfile', array('internalKey' => $order['user_id']));
            $customer = array();
            $customer['externalId'] =  $order['user_id'];
            $customer['firstName'] = $customer_profile['fullname'];
            $customer['email'] = $customer_profile['email'];
            if(!empty($customer_profile['phone'])){
                $customer['phones'][]['number'] = $customer_profile['phone'];
            }
            $response = $modRetailCrm->request->customersCreate($customer, $site);
        }

        $orderData['customer']['externalId'] = $order['user_id'];
        $orderData['externalId'] = $order['num'];
        //$orderData['externalId'] = $order['id']; Желающим идентифицировать заказ по id
        $orderData['firstName'] = !empty($order['address']['receiver']) ? $order['address']['receiver'] : $order['profile']['fullname'];
        $orderData['phone'] = !empty($order['address']['phone']) ? $order['address']['phone'] : $order['profile']['phone'];
        $orderData['email'] = $order['profile']['email'];

        $tmpName = explode(' ', $orderData['firstName']);
        if(count($tmpName) == 3){
            $orderData['lastName'] = $tmpName[0];
            $orderData['firstName'] = $tmpName[1];
            $orderData['patronymic'] = $tmpName[2];
        }


        foreach ($order['products'] as $key=>$product) {
            $orderData['items'][$key]['initialPrice'] = $product['price'];
            $orderData['items'][$key]['purchasePrice'] = $product['price'];
            $orderData['items'][$key]['productName'] = $product['name'];
            $orderData['items'][$key]['quantity'] = $product['count'];
            $orderData['items'][$key]['offer']['externalId'] = $product['id'];
            foreach($product['options'] as $k=>$v){
                $orderData['items'][$key]['properties'][] = array('name' => $k, 'value' => $v); 
            }

            if($order['weight']> 0){
                $orderData['weight'] = $order['weight'];
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
        $orderData['delivery']['cost'] = $order['delivery']['price'];
        if(!empty($order['delivery']['retailcrm_delivery_code'])){
            $orderData['delivery']['code'] = $order['delivery']['retailcrm_delivery_code'];
        }

        if(!empty($order['payment']['retailcrm_payment_code'])){
            $orderData['payments'][0]['type'] = $order['payment']['retailcrm_payment_code'];
        }

        
        $response = $modRetailCrm->request->ordersCreate($orderData, $site);       
        break;

    case 'OnMODXInit':
        $modx->loadClass('msDelivery');
        $modx->map['msDelivery']['fields']['retailcrm_delivery_code'] = '';
        $modx->map['msDelivery']['fieldMeta']['retailcrm_delivery_code'] = array(
            'dbtype' => 'varchar',
            'precision' => '255',
            'phptype' => 'string',
            'null' => true,
        );

        $modx->loadClass('msPayment');
        $modx->map['msPayment']['fields']['retailcrm_payment_code'] = '';
        $modx->map['msPayment']['fieldMeta']['retailcrm_payment_code'] = array(
            'dbtype' => 'varchar',
            'precision' => '255',
            'phptype' => 'string',
            'null' => true,
        );


        $modretailcrmCache = $modx->cacheManager->get('modRetailCRMData', array(xPDO::OPT_CACHE_KEY=>'modretailcrm'));

        if (!$modretailcrmCache || !$modretailcrmCache['ext_delivery']) {
            $modRetailCRMData = array();

            $sql = "SELECT * FROM {$modx->getTableName('msDelivery')} LIMIT 1";
            $q = $modx->prepare($sql);
            $q->execute();
            $arr = $q->fetchAll(PDO::FETCH_ASSOC);
            if(!array_key_exists('retailcrm_delivery_code', $arr[0])){
                $manager = $modx->getManager();
                $manager->addField('msDelivery', 'retailcrm_delivery_code');
            }

            $modRetailCRMData = array('ext_delivery'=> 1);
            $options = array(
                xPDO::OPT_CACHE_KEY => 'modretailcrm',
            );
            $modx->cacheManager->set('modRetailCRMData', $modRetailCRMData, 0, $options);

        }

        if (!$modretailcrmCache || !$modretailcrmCache['ext_payment']) {
            $modRetailCRMData = array();

            $sql = "SELECT * FROM {$modx->getTableName('msPayment')} LIMIT 1";
            $q = $modx->prepare($sql);
            $q->execute();
            $arr = $q->fetchAll(PDO::FETCH_ASSOC);
            if(!array_key_exists('retailcrm_payment_code', $arr[0])){
                $manager = $modx->getManager();
                $manager->addField('msPayment', 'retailcrm_payment_code');
            }

            $modRetailCRMData = array('ext_payment'=> 1);
            $options = array(
                xPDO::OPT_CACHE_KEY => 'modretailcrm',
            );
            $modx->cacheManager->set('modRetailCRMData', $modRetailCRMData, 0, $options);

        }
        break;
    case 'msOnManagerCustomCssJs':
        if ($page != 'settings') return;
        $modx->controller->addHtml("
            <script type='text/javascript'>
            
                //Добавляю к выборке полей retailcrm_delivery_code
                Ext.override(miniShop2.grid.Delivery, {
                    getParentFields: miniShop2.grid.Delivery.prototype.getFields(),
                    getFields: function () {
                        var parentFields = this.getParentFields;
                        parentFields.push('retailcrm_delivery_code');
                        return parentFields;
                    },
                  
                });
                
                //Добавляю к окне доставки поле retailcrm_delivery_code
                Ext.ComponentMgr.onAvailable('minishop2-window-delivery-update', function(config){
                    this.fields[0]['items'][0]['items'].push(
                        {
                            xtype: 'textfield',
                            name: 'retailcrm_delivery_code',
                            fieldLabel:'Символьный код доставки в RetailCRM',
                            anchor: '99%',
                            id: 'minishop2-window-delivery-update-retailcrm_delivery_code'
                            
                        }
                    );
                });
            
            
                //Добавляю к выборке полей retailcrm_payment_code
                Ext.override(miniShop2.grid.Payment, {
                    getParentFields: miniShop2.grid.Payment.prototype.getFields(),
                    getFields: function () {
                        var parentFields = this.getParentFields;
                        parentFields.push('retailcrm_payment_code');
                        return parentFields;
                    },
                  
                });
                
                //Добавляю к окне доставки поле retailcrm_delivery_code
                Ext.ComponentMgr.onAvailable('minishop2-window-payment-update', function(config){
                    this.fields[0]['items'][0]['items'].push(
                        {
                            xtype: 'textfield',
                            name: 'retailcrm_payment_code',
                            fieldLabel:'Символьный код оплаты в RetailCRM',
                            anchor: '99%',
                            id: 'minishop2-window-payment-update-retailcrm_payment_code'
                            
                        }
                    );
                });
        </script>");
        break;
}