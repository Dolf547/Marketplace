<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Get Mercado Livre Token
 * @param  integer  $empresa_id company ID 
 * @return boolean
 */
function get_token_ml($empresa_id = null, $manual = false, $ultimo_token = null)
{
//EMPRESA ID  = ID DO MKP_MARKETPLACES; ID UNICO, NAO O ID DA EMPRESA!
    $CI =& get_instance();
    //$CI->load->model('mercadolivre_model');
    $CI->load->library('mprestclient');

    $empresa_id =  $empresa_id;
    $CI->load->model('Mercadolivre_model');
    $data = $CI->Mercadolivre_model->get_config($empresa_id);
    
    if(isset($data->ultimo_refresh_token)){
    
        $app_client_values = array(
            "grant_type"        => "refresh_token", 
            "client_id"         => $data->app_id, 
            "client_secret"     => $data->secret_key,
            "refresh_token"     => $data->ultimo_refresh_token
        );

        $access_data = MPRestClient::post(array(
            "uri" => "/oauth/token",
            "data" => $app_client_values,
            "headers" => array(
                "content-type" => "application/x-www-form-urlencoded"
            )
        ));


    
        if($manual){
            if($access_data['status'] == 200){
                $CI->db->where('id', $empresa_id);  
                $CI->db->update('mkp_marketplacesconnected', array('ultimo_token'=>$access_data['response']['access_token'], 'ultimo_refresh_token' => $access_data['response']['refresh_token'], 'expires_in' => $access_data['response']['expires_in']));      
                $CI->db->where('id', $empresa_id);  
                $CI->db->update('mkp_marketplacesconnected', array('mercadolivre_connected_status'=> 0));      

                return true;
            
            }else{

                if(isset($access_data['response']['error']) && $access_data['response']['error'] == 'invalid_grant'){
                    $CI->db->where('id', $empresa_id);  
                    $CI->db->update('mkp_marketplacesconnected', array('mercadolivre_connected_status'=> 1));      
                }

                return false;
            }
        
        }

        if($access_data['status'] == 200){

            $CI->db->where('empresa_id', $empresa_id);  
            $CI->db->update('crm_mercadolivre', array('ultimo_token'=>$access_data['response']['access_token'], 'ultimo_refresh_token' => $access_data['response']['refresh_token']));
            
            return true;
        
        }else{

            if(isset($access_data['response']['error']) && $access_data['response']['error'] == 'invalid_grant'){
                $CI->db->where('id', $empresa_id);  
                $CI->db->update('crm_empresas', array('mercadolivre_connected_status'=> 1));
            }
            
            /* $this->session->set_flashdata('alert', [
                'danger',
                'Adicione sua conta do Mercado Livre (ou se a mesma já estiver adicionada) clique novamente no botão : "ADICIONAR CONTA COM MERCADO LIVRE EM MULTICONTAS. '
            ]);
            redirect("multicontas");  */
        }
    }
    
    return false;

}




function get_all_product_attributes_ml($id){
    
    $attributes_anuncio =  array();    
    $CI =& get_instance();
 
    $CI->db->select('mkp_produtos_atributos.atributo_id, mkp_atributos.name as attribute_name, mkp_produtos_atributos.atributo_value_id, mkp_produtos_atributos.value_name');
    $CI->db->join('mkp_atributos', 'mkp_produtos_atributos.atributo_id = mkp_atributos.id_ml');  
    $CI->db->where('produto_id', $id);   
    $_att = $CI->db->get('mkp_produtos_atributos')->result_array();
    

    if (count($_att) > 0) {
        $n      = 0;
        
        foreach ($_att as $key => $value) {
            $id_value = null;
            $value_name = $value["value_name"];

            /* if(!empty($value["atributo_value_id"])){
             
                $CI->db->select('name, id_ml');
                $CI->db->from('crm_attribute_values USE INDEX (crm_attribute_values_id_mlx)');
                $CI->db->where('id_ml', $value["attribute_value_id"]);
                $product = $CI->db->get()->row();
                if(isset($product->id_ml)){
                    $id_value = $product->id_ml;
                    $value_name = $product->name;
                }                
            } */
         
            if($value["atributo_id"] != "PACKAGE_HEIGHT" && $value["atributo_id"] != "PACKAGE_WIDTH" && $value["atributo_id"] != "PACKAGE_LENGTH" && $value["atributo_id"] != "PACKAGE_WEIGHT"){
                    //aki passa id_nl
                   
               // $name = get_attribute_value($value['attribute_value_id'],$value_name);
               $name = null;
                $attributes_anuncio[$n]['id'] = $value["atributo_id"];
                $attributes_anuncio[$n]['name'] = $value["attribute_name"];
                $attributes_anuncio[$n]['value_id'] = $id_value;
                $attributes_anuncio[$n]['value_name'] = empty($value_name) ? $value["atributo_value_id"] : $value_name ;
               
                if($value["atributo_id"] == 'ITEM_CONDITION'){
                    
                   $values = array(array(
                        "id"     => $id_value,
                        "name"   => $name,
                        "struct" => null,
                    ));                    

                    $attributes_anuncio[$n]["values"] = $values;

                }
                $n++;                    
            }
        }        
    }
  
    return $attributes_anuncio;
}

function get_all_product_variations($id){
    
    $attributes =  array();
    
    $CI =& get_instance();
    $CI->db->where('produto_id', $id);
    $CI->db->where('qtd_disponivel >', 0);
    
    $_att = $CI->db->get('mkp_ml_variacoes')->result_array();
    if (count($_att) > 0) {
        foreach ($_att as $_att) {
            array_push($attributes, $_att);
        }
    }

    return $attributes;

}

function get_temp_dir()
{
    if (function_exists('sys_get_temp_dir')) {
        $temp = sys_get_temp_dir();
        if (@is_dir($temp) && is_writable($temp)) {
            return rtrim($temp, '/\\') . '/';
        }
    }

    $temp = ini_get('upload_tmp_dir');
    if (@is_dir($temp) && is_writable($temp)) {
        return rtrim($temp, '/\\') . '/';
    }

    $temp = TEMP_FOLDER;
    if (is_dir($temp) && is_writable($temp)) {
        return $temp;
    }

    return '/tmp/';
}

define('TEMP_FOLDER',FCPATH .'temp' . '/');


function get_attribute_value($id,$name = null) {
    $value = '';
    
    if(empty($id)){
        $value = $name;
    }else{
        
        if(!is_numeric($id)){
            $value = $name;
        }else{
            $CI =& get_instance();
            $CI->db->select('name');
            $CI->db->from('crm_attribute_values');
            $CI->db->where('id_ml', $id);
            $crm_variations = $CI->db->get()->row();
            if(isset($crm_variations->name)){
                $value = $crm_variations->name;
            }else{
                $value = $id;
            }
        }        
    
    }
    if($id == -1 ){
        $value   = null;
    }
    return $value;
}    
