<?php
/*
Plugin Name: Paylike orders
Description: See latest orders
Author: Vass Ardei
Version: 0.1
*/
add_action('admin_menu', 'add_new_orders_menu');
 
function add_new_orders_menu(){
    add_menu_page( 'PL New Order', 'PL New Order', 'manage_options', 'new-orders', 'show_new_orders' );
}

/* A java way to extract the damn key!
  import java.util.Base64;

    public class Base64{

     public static void main(String []args){
         String result = "Basic " +  new String(Base64.getEncoder().encode((":" + "key").getBytes()));
            System.out.println(result);
     }
}
*/

function show_new_orders(){
    echo "<h1>New Orders</h1>";
    global $wpdb; 
    $table_name = $wpdb->prefix . 'orders';  
    $results = $wpdb->get_results('SELECT id FROM ' . $table_name);
    
    $filterOut =  array();
    if(!empty($results)) {
        foreach($results as $row){  
            $filterOut[$row->id] = true;
        }
    }
    $key = "Basic {addKeyHere}";
    $merchantId = "{addMerchantIdHere}";
    $url = "https://api.paylike.io/merchants/" . $merchantId . "/transactions?limit=1000";
    $request = new WP_REST_Request( 'GET', '/wp/v2/posts' );

    $args = array(
      'headers' => array(
        'Authorization' => $key,
        'Content-Type' => 'application/json'
      ),
    );
    $response = wp_remote_get( $url, $args );
    $response_code = wp_remote_retrieve_response_code( $response );
    $response_body = wp_remote_retrieve_body( $response );
    $data = json_decode( $response_body );

    if($response_code == 200){
        $contentRows = '';
        $alternate = true;
        for ($i = 0; $i < count($data); $i++) {
            $item =  $data[$i];
            if($filterOut[$item->id]){
                continue;
            }
            $alternateText = '';
            if($alternate){
                $alternateText = 'alternate';
                $alternate = !$alternate;
            }
            $contentRows .= ' <tr class="'. $alternateText .'">
            <th class="column-columnname">' . $item->id . ' </th>
            <td class="column-columnname">' . $item->amount / 100 . $item->currency . ' </td>
            <td class="column-columnname">' . $item->custom->name . ' </td>
            <td class="column-columnname">' . $item->custom->email . ' </td>
            <td class="column-columnname">' . $item->custom->phone . ' </td>
            <td class="column-columnname">' . $item->custom->adress . ' - ' . $item->custom->postalCode . '</td>
            <td class="column-columnname">' . $item->created . '</td>
            <td class="column-column">
            <form action="" method="post">
                <input class="hidden" type="text" name="finishId" value="' . $item->id . '"/>
                <textarea class="hidden" name="finishData">' . $response_body . '</textarea>
            <input type="submit" value="Seen"/>
            </td>
        </tr>';
        }

        echo ('
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th class="manage-column" scope="col">ID</th> 
                    <th class="manage-column" scope="col">Suma</th>
                    <th class="manage-column" scope="col">Nume</th>
                    <th class="manage-column" scope="col">Email</th>
                    <th class="manage-column" scope="col">Phone</th>
                    <th class="manage-column" scope="col">Adresa</th> 
                    <th class="manage-column" scope="col">Date</th> 
                    <th class="manage-column" scope="col">Done</th> 
                </tr>
            </thead>
            <tbody>'
              . $contentRows .   
            '</tbody>
        </table>
        ');

    } else {
        echo "<h1>No data to see</h1>";
    }

    
}

if (isset($_POST['finishId'])) {
    setDone($_POST['finishId'], $_POST['finishData']);
}

// you need to create this table. probably encrypt this (depending on data)
function setDone($orderId, $data) {
    global $wpdb; 
    $table_name = $wpdb->prefix . 'orders';  

    $wpdb->insert($table_name, array('id' => $orderId, 'data' => $data));
}

?>
