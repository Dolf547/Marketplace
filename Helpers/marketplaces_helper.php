<?php
defined('BASEPATH') or exit('No direct script access allowed');

function insert_prods_mktp_ids($product_id, $mktplace_id, $mktplace, $mktplace_status, $mktplace_sales, $mktplace_views, $mktplace_likes, $mktplace_coments, $mktplace_discount_id, $is_bulk_used, $date_last_update){
    $data_mkt = [
        'product_id' => $product_id,
        'mktplace_id' => $mktplace_id,
        'mktplace' => $mktplace,
        'mktplace_status' => $mktplace_status,
        'mktplace_sales' => $mktplace_sales,
        'mktplace_views' => $mktplace_views,
        'mktplace_likes' => $mktplace_likes,
        'mktplace_coments' => $mktplace_coments,
        'mktplace_discount_id' => $mktplace_discount_id,
        'date_last_update' => empty($date_last_update) ? date("Y-m-d H:i:s") : $date_last_update,
        'status_integration' => 0,
        'price' => 0
    ];

    $CI =& get_instance();
    $CI->db->select('id_mpii');
    $CI->db->where('mktplace_id', $mktplace_id);
    $CI->db->where('mktplace', $mktplace);
    $exist = $CI->db->get('mkp_prods_ids_in')->row();

    if(isset($exist->id_mpii)){
        return $data_mkt;
    }else{
        $CI->db->insert('mkp_prods_ids_in', $data_mkt);   
        return $CI->db->insert_id();
    }
}