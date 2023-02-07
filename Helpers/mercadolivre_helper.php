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
    $CI = &get_instance();
    //$CI->load->model('mercadolivre_model');
    $CI->load->library('mprestclient');

    $empresa_id =  $empresa_id;
    $CI->load->model('Mercadolivre_model');
    $data = $CI->Mercadolivre_model->get_config($empresa_id);

    if (isset($data->ultimo_refresh_token)) {

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



        if ($manual) {
            if ($access_data['status'] == 200) {
                $CI->db->where('id', $empresa_id);
                $CI->db->update('mkp_marketplacesconnected', array('ultimo_token' => $access_data['response']['access_token'], 'ultimo_refresh_token' => $access_data['response']['refresh_token'], 'expires_in' => $access_data['response']['expires_in']));
                $CI->db->where('id', $empresa_id);
                $CI->db->update('mkp_marketplacesconnected', array('mercadolivre_connected_status' => 0));

                return true;
            } else {

                if (isset($access_data['response']['error']) && $access_data['response']['error'] == 'invalid_grant') {
                    $CI->db->where('id', $empresa_id);
                    $CI->db->update('mkp_marketplacesconnected', array('mercadolivre_connected_status' => 1));
                }

                return false;
            }
        }

        if ($access_data['status'] == 200) {

            $CI->db->where('empresa_id', $empresa_id);
            $CI->db->update('crm_mercadolivre', array('ultimo_token' => $access_data['response']['access_token'], 'ultimo_refresh_token' => $access_data['response']['refresh_token']));

            return true;
        } else {

            if (isset($access_data['response']['error']) && $access_data['response']['error'] == 'invalid_grant') {
                $CI->db->where('id', $empresa_id);
                $CI->db->update('crm_empresas', array('mercadolivre_connected_status' => 1));
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




function get_all_product_attributes_ml($id)
{

    $attributes_anuncio =  array();
    $CI = &get_instance();

    $CI->db->select('mkp_produtos_atributos.atributo_id, mkp_produtos_atributos.produto_id, mkp_atributos.name as attribute_name, mkp_produtos_atributos.atributo_value_id, mkp_produtos_atributos.value_name');
    $CI->db->join('mkp_atributos', 'mkp_produtos_atributos.atributo_id = mkp_atributos.id_ml');
    $CI->db->where('mkp_produtos_atributos.produto_id', $id);
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

            if ($value["atributo_id"] != "PACKAGE_HEIGHT" && $value["atributo_id"] != "PACKAGE_WIDTH" && $value["atributo_id"] != "PACKAGE_LENGTH" && $value["atributo_id"] != "PACKAGE_WEIGHT") {
                //aki passa id_nl

                // $name = get_attribute_value($value['attribute_value_id'],$value_name);
                $name = null;
                $attributes_anuncio[$n]['id'] = $value["atributo_id"];
                $attributes_anuncio[$n]['name'] = $value["attribute_name"];
                $attributes_anuncio[$n]['value_id'] = $id_value;
                $attributes_anuncio[$n]['value_name'] = empty($value_name) ? $value["atributo_value_id"] : $value_name;

                if ($value["atributo_id"] == 'ITEM_CONDITION') {

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

function get_all_product_variations($id)
{

    $attributes =  array();

    $CI = &get_instance();
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

define('TEMP_FOLDER', FCPATH . 'temp' . '/');


function get_attribute_value($id, $name = null)
{
    $value = '';

    if (empty($id)) {
        $value = $name;
    } else {

        if (!is_numeric($id)) {
            $value = $name;
        } else {
            $CI = &get_instance();
            $CI->db->select('name');
            $CI->db->from('crm_attribute_values');
            $CI->db->where('id_ml', $id);
            $crm_variations = $CI->db->get()->row();
            if (isset($crm_variations->name)) {
                $value = $crm_variations->name;
            } else {
                $value = $id;
            }
        }
    }
    if ($id == -1) {
        $value   = null;
    }
    return $value;
}




function get_description_ml($produto, $empresa_id = '')
{
    $CI = &get_instance();
    $CI->load->model('mercadolivre_model');
    $CI->load->library('MPRestClient');

    $data = $CI->Mercadolivre_model->get_config($empresa_id);


    if (!isset($data->ultimo_token))
        return '';

    $access_token = $data->ultimo_token;

    $array = array(
        "access_token" => $access_token
    );

    $description = "";
    $request_desc = array(
        "uri" => "/items/" . $produto . "/description",
        "headers" => array("Authorization" => 'Bearer ' . $access_token),
        "params" => $array
    );

    $access_desc = MPRestClient::get($request_desc);
    if (isset($access_desc["response"]["plain_text"])) {
        $description = $access_desc["response"]["plain_text"];
    }

    return $description;
}

function get_sku_from_json($access_data3)
{
    $sku = null;
    if (isset($access_data3["response"]["attributes"])) {
        foreach ($access_data3["response"]["attributes"] as $query => $results3) {
            //se for o SKU salva no campo do produto
            if ($results3["id"] == "SELLER_SKU") {
                if (!empty($results3["value_name"])) {
                    $sku = $results3["value_name"];
                }
            }
        }
    }
    return trim(str_replace("'", "", $sku));
}

function import_products_get_itens($page, $limit, $scroll_id = null, $milProdutos, $empresa_id)
{
    $CI = &get_instance();
    $CI->load->model('Mercadolivre_model');
    $CI->load->library('MPRestClient');
    $data = $CI->Mercadolivre_model->getByContaActive();

    $CI = &get_instance();
    //  $data = json_decode($data);
    print_r($milProdutos);
    echo " milprodutos<br/><br/>";
    print_r($data);
    echo " data<br/><br/>";
    print_r($scroll_id);
    echo " scroll_id<br/><br/>";
    if ($milProdutos) {
        if (empty($scroll_id)) {
            print_r($data->user_id);
            echo " data->user_id<br/><br/>";
            $array = array(
                "access_token" => $data->ultimo_token,
                "search_type" => "scan",
                'limit' => 100,
            );
            $request = array(
                "uri" => '/users/' . $data->user_id . '/items/search',
                "headers" => array("Authorization" => 'Bearer ' . $data->ultimo_token),
                "params" => $array
            );
            $access_data_scroll = MPRestClient::get($request); //busca os produtos
            $scroll_id = $access_data_scroll["response"]["scroll_id"];
        }

        $array = array(
            "access_token" => $data->ultimo_token,
            "orders" => "stop_time_desc",
            'limit' => $limit,
            'search_type' => 'scan',
            'scroll_id' => $scroll_id
        );
    } else {
        $array = array(
            "access_token" => $data->ultimo_token,
            "orders" => "stop_time_desc",
            'limit' => $limit,
            'offset' => $page
        );
    }

    $request = array(
        "uri" => "/users/" . $data->user_id . "/items/search",
        "headers" => array("Authorization" => 'Bearer ' . $data->ultimo_token),
        "params" => $array
    );

    $access_data = MPRestClient::get($request); //busca os produtos


    return $access_data;
}

function getproductbyMlId($id, $lterObj = false)
{
    $CI = &get_instance();

    $CI->db->select('produtos.id AS id,mkp_prods_ids_in.date_integration AS mercadolivre_date_integration, produtos.preco_venda AS preco_venda, mkp_prods_ids_in.date_last_update AS mercadolivre_date_update');
    $CI->db->from('produtos');
    $CI->db->join("mkp_prods_ids_in", "produtos.id = mkp_prods_ids_in.product_id AND mkp_prods_ids_in.mktplace_id = '" . $id . "'");
    //$CI->db->where('produtos.d_e_l_e_t_e', '');
    //$product = $CI->db->get()->row();
    $product = $CI->db->get()->row();
   
    //print_r($product);echo " product<br/><br/>";die();
    $product_id = isset($product->id) ? $product->id : 0;

   

   // return !$lterObj ? $product_id : $product;
   return $product_id;
}

function product_save_crm($id, $naoGravaId = true, $data = null, $origin = null)
{

    $CI = &get_instance();
    $CI->load->library('MPRestClient');
    $data = json_decode($data);
    $nImport = 0;

    //busca os dados do produto
    $request3 = array(
        'uri' => "/items/$id",
        "headers" => array("Authorization" => 'Bearer ' . $data->ultimo_token),
        "params" => array("access_token" => $data->ultimo_token)
    );
    $access_data3 = MPRestClient::get($request3);

    if (!isset($access_data3["response"]["id"])) {
        return 0;
    }

    $product_id = getproductbyMlId($access_data3["response"]["id"], false);
    $status = ($access_data3["response"]["status"] == "paused") ? 0 : 1;

   
    if ($product_id == 0) {

        $nImport++;
        $idMl = !$naoGravaId ? '' : $access_data3['response']['id'];
        $strock = !$naoGravaId ? 1 : $access_data3["response"]["available_quantity"];
        $sold = !$naoGravaId ? 0 : $access_data3["response"]["sold_quantity"];

        //verifica se a categoria existe, se nao existir cadastra a mesma no CRM
        //get_categories($access_data3['response']['category_id']);

        $request_desc = array(
            'uri' => "/items/" . $access_data3['response']['id'] . "/description",
            "headers" => array("Authorization" => 'Bearer ' . $data->ultimo_token),
            'params' => array('access_token' => $data->ultimo_token)
        );

        $access_desc = MPRestClient::get($request_desc);
        $description = isset($access_desc['response']['plain_text']) ? $access_desc['response']['plain_text'] : '';
        $sku = get_sku_from_json($access_data3);

        $dgarantia = $access_data3["response"]['sale_terms'][1]['values'][0]['struct']['number'];
        $pgarantia = $access_data3["response"]['sale_terms'][1]['values'][0]['struct']['unit'];
        var_dump($access_data3);
        $data_product = [
            'nome' => $access_data3["response"]["title"],
            'description_ml' => $description,
            'mercadolivre_category_id' => $access_data3["response"]["category_id"],
            'preco_venda' => $access_data3["response"]["price"],
            'mercadolivre_status' => $status,
            'available_quantity' => $access_data3["response"]["available_quantity"],
            'mercadolivre_code' => $access_data3["response"]["id"],
            'codigo' => $sku,
            'mercadolivre_condition' => $access_data3["response"]["condition"],
            'mercadolivre_permalink' => $access_data3["response"]["permalink"],
            'thumbnail' => $access_data3["response"]["thumbnail"],
            'insert_date' => date("Y-m-d H:i:s"),
            'datacri' => date("Y-m-d H:i:s"),
            'usercri' => 'cron',
            'insert_origin' => $origin,
            'dgarantia' => $dgarantia,
            'pgarantia' => $pgarantia,
            'mercadolivre_listeningtype' => get_listenungtype_value($access_data3["response"]["listing_type_id"]),
        ];

         $CI->db->insert('produtos', $data_product);
        $product_id = $CI->db->insert_id();

        insert_prods_mktp_ids(
            $product_id,
            $idMl,
            'meli',
            $status,
            $sold,
            0,
            0,
            0,
            0,
            false,
            null
        );

        

        //ATULIZA ESTOQUE
      
        $CI->load->model('Armazens_model');
        $CI->load->model('Estoques_model');
        $armazemP = $CI->Armazens_model->buscarPrincipal();
		$armazemP = $armazemP->id ? $armazemP->id : null;
          $existEstq = $CI->Estoques_model->idproduto($product_id,);
	                    //	$CI->Estoques_model->addpai($product_id, $estqIds);
						if(empty($existEstq)){
                                        $data = [
                                            'id_produto' => $product_id,
                                            'id_armazem' => 1,
                                            'quantidade' => $strock,
                                            'datacri'    => date('Y-m-d'),
                                            'datamod'    => date('Y-m-d'),
                                        ];
                                        $CI->Estoques_model->add($data);
                             }
 

        /*if (isset($sku) && !empty($sku)) {
            $CI->load->model('stocks_model');
            $CI->stocks_model->verify_add_stock($sku, $access_data3["response"]["title"], $empresa_id, $strock);
        }*/
      

        //importa as imagens do produto



        $CI->load->model('Imagens_model');
        $CI->load->model('Anexo_model');
        foreach ($access_data3["response"]["pictures"] as $query => $results3) {

            $idimg = rand(5, 1232322);
            //$isUpdated = $this->Produtos_model->editProduct($_POST['id'], $data);
            $data = [
                'id_produto' =>  $product_id,
                'id_imagem' => $idimg,
            ];
            // $upl = file_get_contents($results3['secure_url']);
            $a =  $CI->Imagens_model->saveimgs($data);
            $PastaRaiz = getCwd() . '/public/clientes/' . $_SESSION['id_empresa'] . '/img/' . $results3['id'] . '.jpg';
            //$logo = 
            $url = $results3['secure_url'];
            file_put_contents($PastaRaiz, file_get_contents($url));
            $data = [
                'datamod'   => date('Y-m-d'),
                'usermod'  => $CI->session->userdata('nome'),
                'usercri' =>$CI->session->userdata('nome'),
                'datacri' => date('Y-m-d'),
                'caminho' => $results3['id'].'.jpg',
                'tipoarquivo' => 'jpg',
                'nometabela' => 'produtos',
                'idrelacao' => $idimg,
            ];
            $CI->db->insert('anexos', $data);
            $data_image = [
                'produto_id' => $product_id,
                'external_link' => $results3["url"],
                'external' => 'mercadolivre',
                'id_ml' => $results3["id"],
                'secure_url' => $results3["secure_url"],
                'size' => $results3["size"],
                'max_size' => $results3["max_size"],
                'quality' => $results3["quality"],
            ];
            $CI->db->insert('produtos_imagens', $data_image);
        }

        save_product_attributes($access_data3, $product_id);
        save_product_variations($access_data3, $product_id);
        save_product_shipping($access_data3, $product_id);
    } else {
        update_prods_mktp_ids(
            $product_id,
            $access_data3['response']['id'],
            'meli',
            $status,
            $access_data3["response"]["sold_quantity"],
            0,
            0,
            0,
            0,
            0,
            null,
            null,
         
        );
    }
    return $nImport;
}

function get_listenungtype_value($listing_type_id)
{
    return $listing_type_id == 'gold_pro' ? 2 : 1;
}

function save_product_attributes($access_dataAT, $product_id)
{

    $CI = &get_instance();

    foreach ($access_dataAT["response"]["attributes"] as $query => $results3) {

        $CI->db->select('id_ml');
        $CI->db->from('mkp_atributos');
        $CI->db->where('id_ml', $results3["id"]);
        $product = $CI->db->get()->row();

        if (!$product) {
            $data_attribute = [
                'id_ml' => $results3["id"],
                'name' => $results3["name"],
                'attribute_group_id' => $results3["attribute_group_id"],
                'attribute_group_name' => $results3["attribute_group_name"],
                'produto_id' => $product_id
            ];
            $CI->db->insert('mkp_atributos', $data_attribute);
        }

        var_dump($results3);

        $attribute_value_1 = null;
        if (!empty($results3["value_id"])) {
            $CI->db->select('id_ml');
            $CI->db->from('mkp_attribute_values');
            $CI->db->where('attribute', $results3["id"]);
            $CI->db->where('id_ml', $results3["value_id"]);
            $attribute_value = $CI->db->get()->row();
            if (!$attribute_value) {
                $data_attribute = [
                    'id_ml' => $results3["value_id"],
                    'name' => $results3["value_name"],
                    'attribute' => $results3["id"]
                ];
                $CI->db->insert('mkp_attribute_values', $data_attribute);
            }
            $attribute_value_1 = $results3["value_id"];
        }

        //insert in the product
        $data_attribute = [
            'produto_id' => $product_id,
            'atributo_id' => $results3["id"],
            'atributo_value_id' => $attribute_value_1,
            'value_name' =>  $results3["value_name"],
        ];
        $CI->db->insert('mkp_produtos_atributos', $data_attribute);
    }

    return true;
}

function save_product_variations($access_dataVR, $product_id)
{

    $CI = &get_instance();
    foreach ($access_dataVR["response"]["variations"] as $query => $results3) {

        //insere a variação
        $CI->db->select('id_ml');
        $CI->db->from('mkp_variations');
        $CI->db->where('product', $product_id);
        $CI->db->where(" id_ml = '" . $results3["id"] . "'");
        $attribute_value = $CI->db->get()->row();

        if (!$attribute_value) {
            $data_attribute = [
                'id_ml' => $results3["id"],
                'price' => $results3["price"],
                'available_quantity' => $results3["available_quantity"],
                'sold_quantity' => $results3["sold_quantity"],
                'catalog_product_id' => $results3["catalog_product_id"],
                'product' => $product_id
            ];
            $CI->db->insert('mkp_variations', $data_attribute);
            $id_variation = $CI->db->insert_id();

            if ($id_variation) {
                foreach ($results3["attribute_combinations"] as $query => $results4) {
                    $variation_id = $results4["id"];
                    $variation_value_id = $results4["value_id"];

                    $CI->db->select('id_ml');
                    $CI->db->from('mkp_variations_values');
                    $CI->db->where('id_ml', $variation_value_id);
                    $CI->db->where(" variation = '" . $variation_id . "'");
                    $attribute_value = $CI->db->get()->row();
                    if (!$attribute_value && !empty($variation_value_id)) {
                        $data_attribute = [
                            'id_ml' => $variation_value_id,
                            'name' => $results4["value_name"],
                            'variation' => $variation_id
                        ];
                        $CI->db->insert('mkp_variations_values', $data_attribute);
                    }

                    //insert in the variation product value
                    $data_attribute = [
                        'product_id' => $product_id,
                        'variation_id' => $variation_id,
                        'variation_value_id' => $variation_value_id,
                        'crm_ml_variations_id' => $id_variation,
                        'name' => $results4["value_name"],
                        'name_ml' => $results4["name"]
                    ];
                    $CI->db->insert('mkp_product_variations', $data_attribute);
                }

                foreach ($results3["picture_ids"] as $query => $results4) {

                    $CI->db->select('id_ml');
                    $CI->db->from('mkp_variations_pictures');
                    $CI->db->where('product', $product_id);
                    $CI->db->where('id_variation', $id_variation);
                    $CI->db->where('id_ml', $results4);

                    $attribute_value = $CI->db->get()->row();
                    if (!$attribute_value) {
                        $data_attribute = [
                            'id_ml' => $results4,
                            'id_variation' => $id_variation,
                            'product' => $product_id
                        ];
                        $CI->db->insert('mkp_variations_pictures', $data_attribute);
                    }
                }
            }
        }
    }

    return true;
}

function save_product_shipping($access_dataSP, $product_id)
{

    $CI = &get_instance();

    if (isset($access_dataSP["response"]["shipping"])) {

        $mode = isset($access_dataSP["response"]["shipping"]["mode"]) ? $access_dataSP["response"]["shipping"]["mode"] : "";
        $local_pick_up = isset($access_dataSP["response"]["shipping"]["local_pick_up"]) ? $access_dataSP["response"]["shipping"]["local_pick_up"] : "";
        $free_shipping = isset($access_dataSP["response"]["shipping"]["free_shipping"]) ? $access_dataSP["response"]["shipping"]["free_shipping"] : "";
        $logistic_type = isset($access_dataSP["response"]["shipping"]["logistic_type"]) ? $access_dataSP["response"]["shipping"]["logistic_type"] : "";
        $dimensions = isset($access_dataSP["response"]["shipping"]["dimensions"]) ? $access_dataSP["response"]["shipping"]["dimensions"] : "";
        if (empty($mode)) {
            return true;
        }

        $free_methods = array();
        if (isset($access_dataSP["response"]["shipping"]["free_methods"])) {
            $free_methods = $access_dataSP["response"]["shipping"]["free_methods"];
        }

        $tags = array();
        if (isset($access_dataSP["response"]["shipping"]["tags"])) {
            $tags = $access_dataSP["response"]["shipping"]["tags"];
        }

        $CI->db->select('produto_id');
        $CI->db->from('mkp_produtos_shipping');
        $CI->db->where('produto_id', $product_id);
        $CI->db->where('mode_ml', $mode);

        $crm_products_shipping = $CI->db->get()->row();
        if (!$crm_products_shipping) {
            $data_attribute = [
                'mode_ml' => $mode,
                'local_pick_up' => $local_pick_up,
                'free_shipping' => $free_shipping,
                'logistic_type' => $logistic_type,
                'dimensions' => $dimensions,
                'produto_id' => $product_id
            ];
            $CI->db->insert('mkp_produtos_shipping', $data_attribute);
            $id_crm_products_shipping = $CI->db->insert_id();
        } else {
            $id_crm_products_shipping = $crm_products_shipping->id_product_shipping;
        }

        foreach ($free_methods as $query => $results4) {
            $CI->db->select('id_free_method');
            $CI->db->from('mkp_products_shipping_free_methods');
            $CI->db->where('product', $product_id);
            $CI->db->where('id_ml', $results4["id"]);
            $CI->db->where('id_product_shipping', $id_crm_products_shipping);
            $crm_products_shipping_free_methods = $CI->db->get()->row();

            if (!$crm_products_shipping_free_methods) {
                $data_attribute = [
                    'id_ml' => $results4["id"],
                    'product' => $product_id,
                    'id_product_shipping' => $id_crm_products_shipping
                ];
                $CI->db->insert('mkp_products_shipping_free_methods', $data_attribute);
                $id_crm_products_shipping_free_methods = $CI->db->insert_id();
            } else {
                $id_crm_products_shipping_free_methods = $crm_products_shipping_free_methods->id_free_method;
            }

            $rule = array();
            $rule = $results4["rule"];
            if (isset($results4["rule"])) {
                $value = null;
                if (isset($results4["rule"]["value_ml"])) {
                    $value = $results4["rule"]["value_ml"];
                }
                $default = null;
                if (isset($results4["rule"]["value_ml"])) {
                    $default = $results4["rule"]["default"];
                }
                $free_shipping_flag = null;
                if (isset($results4["rule"]["value_ml"])) {
                    $free_shipping_flag = $results4["rule"]["free_shipping_flag"];
                }

                $CI->db->select('id_free_method_rule');
                $CI->db->from('mkp_products_shipping_free_methods_rules');
                $CI->db->where('product', $product_id);
                $CI->db->where('value_ml', $value);
                $CI->db->where('free_mode', $results4["rule"]["free_mode"]);
                $CI->db->where('id_free_method', $id_crm_products_shipping_free_methods);

                $crm_products_shipping_free_methods_rules = $CI->db->get()->row();

                if (!$crm_products_shipping_free_methods_rules) {
                    $data_attribute = [
                        'free_mode' => $results4["rule"]["free_mode"],
                        'value_ml' => $value,
                        'default' => $default,
                        'free_shipping_flag' => $free_shipping_flag,
                        'product' => $product_id,
                        'id_free_method' => $id_crm_products_shipping_free_methods
                    ];
                    $CI->db->insert('mkp_products_shipping_free_methods_rules', $data_attribute);
                }
            }
        }

        foreach ($tags as $query => $results4) {
            $CI->db->select('id_shipping_tag');
            $CI->db->from('mkp_products_shipping_tags');
            $CI->db->where('product', $product_id);
            $CI->db->where('id_ml', $results4);
            $CI->db->where('id_products_shipping', $id_crm_products_shipping);
            $crm_products_shipping_tags = $CI->db->get()->row();

            if (!$crm_products_shipping_tags) {
                $data_attribute = [
                    'id_ml' => $results4,
                    'product' => $product_id,
                    'id_products_shipping' => $id_crm_products_shipping
                ];
                $CI->db->insert('mkp_products_shipping_tags', $data_attribute);
            }
        }
    }
    return true;

}



function update_prods_mktp_ids($product_id, $mktplace_id, $mktplace, $mktplace_status, $mktplace_sales, $mktplace_views, $mktplace_likes, $mktplace_coments, $mktplace_discount_id, $mktplace_deleted, $status_integration, $date_integration){

    if(empty($mktplace_id)){
        return false;
    }

    $CI           =& get_instance();
    $updateFields = array();

    if(!empty($mktplace_status)){
        $updateFields['mktplace_status'] = $mktplace_status;
    }
    if(!empty($mktplace_sales) && $mktplace_sales != 0){
        $updateFields['mktplace_sales'] = $mktplace_sales;
    }
    if(!empty($mktplace_views) && $mktplace_views != 0){
        $updateFields['mktplace_views'] = $mktplace_views;
    }
    if(!empty($mktplace_likes) && $mktplace_likes != 0){
        $updateFields['mktplace_likes'] = $mktplace_likes;
    }    
    if(!empty($mktplace_coments) && $mktplace_coments != 0){
        $updateFields['mktplace_coments'] = $mktplace_coments;
    }  
    if(!empty($mktplace_discount_id) && $mktplace_discount_id != 0){
        $updateFields['mktplace_discount_id'] = $mktplace_discount_id;
    }  
    if(!empty($status_integration)){
        $updateFields['status_integration'] = $status_integration;
    }  
    if(!empty($date_integration)){
        $updateFields['date_integration'] = $date_integration;
    }  
    if(!empty($date_integration)){
        $updateFields['date_integration'] = $date_integration;
    }  
    if(sizeof($updateFields) > 0){
        $updateFields['date_last_update'] = date("Y-m-d H:i:s");
        $updateFields['mktplace_deleted'] = $mktplace_deleted;
        $CI->db->where("mktplace_id = '".$mktplace_id."' ");
        $CI->db->where('mktplace', $mktplace);
        $CI->db->update('mkp_prods_ids_in', $updateFields);
    }
    return true; 
}

function include_clients($client_mktp_id, $access_token, $opp_id = null, $phone = null, $firstname = null, $last_name = null, $document = null, $company = null)
{

    $CI =& get_instance();
    $CI->load->library('MPRestClient');

    $client_id = 0;

    //search and add customer
    $CI->db->select('id');
    $CI->db->from('clientes');
    $CI->db->where("mktplace_id = '".$client_mktp_id."' ");
    $CI->db->where("mktplace_id <> '' ");
    $client = $CI->db->get()->row();

    $array = array(
        "access_token"=>$access_token
    );

    if ($client) {
        $client_id = $client->userid;
    }else{

        $firstname = !empty($firstname) ? $firstname : '';
        $city = '';
        $street = '';
        $lastname = !empty($last_name) ? $last_name : '';
        $number = '';
        $complement = '';
        $state = '';
        $cgc = !empty($document) ? $document : '';
        $zip = '';
        $ie = '';
        $company = !empty($company) ? $company : '';
        $neighborhood = '';

        if(!empty($opp_id)){

            $requestBillingInfo = array(
                "uri" => "/orders/".$opp_id.'/billing_info',
                "headers" => array("Authorization"=>'Bearer '.$access_token),
                "params" => $array
            );            
            $access_dataBillingInfo = MPRestClient::get($requestBillingInfo);  

            if(!isset($access_dataBillingInfo['response']) && empty($company))
                return 0;

            if(isset($access_dataBillingInfo['response'])){
                foreach ($access_dataBillingInfo['response'] as $response) {

                    if(isset($response['additional_info'])){
                        foreach ($response['additional_info'] as $user) {

                            if($user['type'] == 'FIRST_NAME')
                                $firstname = $user['value'];

                            if($user['type'] == 'CITY_NAME')
                                $city = $user['value'];

                            if($user['type'] == 'STREET_NAME')
                                $street = $user['value'];

                            if($user['type'] == 'LAST_NAME')
                                $lastname = $user['value'];

                            if($user['type'] == 'STREET_NUMBER')
                                $number = $user['value'];

                            if($user['type'] == 'COMMENT')
                                $complement = $user['value'];

                            if($user['type'] == 'STATE_NAME')
                                $state = $user['value'];

                            if($user['type'] == 'DOC_NUMBER')
                                $cgc = $user['value'];

                            if($user['type'] == 'ZIP_CODE')
                                $zip = $user['value'];

                            if($user['type'] == 'STATE_REGISTRATION')
                                $ie = $user['value'];

                            if($user['type'] == 'BUSINESS_NAME')
                                $company = $user['value'];

                            if($user['type'] == 'NEIGHBORHOOD')
                                $neighborhood = $user['value'];

                        }
                    }
                }
            }

            /*if(!empty($state)){
                $CI->db->select('stateid');
                $CI->db->where('statename', $state);
                $state = $CI->db->get('crm_state')->row();
                $state = isset($state->stateid) ? $state->stateid : null;
            }*/

            if(empty($company)){
                $company =  $firstname.' '.$lastname;
            }

        }else{

            $request = array(
                "uri" => "/users/".$client_mktp_id,
                "headers" => array("Authorization"=>'Bearer '.$access_token),
                "params" => $array
            );
            $access_dataBillingInfo = MPRestClient::get($request);

            if(!isset($access_dataBillingInfo['response']) && empty($company)){
                return 0;
            }

            if(isset($access_dataBillingInfo['response'])){
                foreach ($access_dataBillingInfo['response'] as $user) {

                    $company    = $user['nickname'];
                    $city       = $user['city'];

                    /*if(!empty($user['state']))
                    {
                        $CI->db->select('stateid');
                        $CI->db->where('initial', substr($user['state'],3));
                        $state = $CI->db->get('crm_state')->row();
                        $state = isset($state->stateid) ? $state->stateid : null;
                    }*/

                }
            }   
        } 

        $street .= (!empty($number) ? ', '.$number : '' );
        $street .= (!empty($complement) ? ', '.$complement : '' );
        $street .= (!empty($neighborhood) ? ', '.$neighborhood : '' );
        
        $data_client = [
            'razaosocial' => $company,
            'nomefantasia' => $company,
            'telcomercial' => $phone,
            'documento' => $cgc,
            'mktplace_id' => $client_mktp_id,
            'nomecliente' => $firstname,
            'cidade' => $city,
            'inscestadual'=> $ie,
            'rua' => $street,
            'estado' => $state,
            'cep' => $zip
        ];
        $CI->db->insert('clientes', $data_client);
        $client_id = $CI->db->insert_id();
    }

    return $client_id;
//teste
}
