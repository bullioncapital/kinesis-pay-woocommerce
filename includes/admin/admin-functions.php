<?php

// Adding 2 custom columns to order list view
add_filter( 'manage_edit-shop_order_columns', 'custom_shop_order_column', 20 );
function custom_shop_order_column($columns)
{
    $reordered_columns = array();

    // Inserting columns to a specific location
    foreach( $columns as $key => $column){
        $reordered_columns[$key] = $column;
        if( $key ==  'order_status' ){
            // Inserting after "Status" column
            $reordered_columns['kinesis_pay_payment_id'] = __( 'Kinesis-Pay Payment ID','theme_domain');
			$reordered_columns['kinesis_pay_payment_status'] = __( 'Kinesis-Pay Payment Status','theme_domain');
        }
    }
    return $reordered_columns;
}

// Adding custom fields meta data for each new column (example)
add_action( 'manage_shop_order_posts_custom_column' , 'custom_orders_list_column_content', 20, 2 );
function custom_orders_list_column_content( $column, $post_id )
{
	if ($column !== 'kinesis_pay_payment_id' && $column !== 'kinesis_pay_payment_status') {
		return;
	}

	global $wpdb;
    $tablename = $wpdb->prefix . 'kinesis_payments';
    $result = $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM $tablename WHERE `order_id` = %d ORDER BY `id` DESC",
      array($post_id)
    ));

    switch ( $column )
    {
        case 'kinesis_pay_payment_id' :
            if(!empty($result[0])) {
                echo $result[0]->payment_id;
			} else {
                echo '<small>-</small>';
			}
            break;
		case 'kinesis_pay_payment_status' :
			if(!empty($result[0])) {
				echo $result[0]->payment_status;
			} else {
				echo '<small>-</small>';
			}
			break;
    }
}