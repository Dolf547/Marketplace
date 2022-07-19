<?php

class Mercadolivre_model extends YS_Model{

	public $table = 'mkp_marketplacesconnected';
	public $tableIdentifier = 'id';
	
	function save($categorias){
		$this->db->insert($this->table, $categorias);
		if ($this->db->affected_rows()) {
			return true;
		}
		return false;
	}
	function delete($ID){
		$this->db->where($this->tableIdentifier, $ID);
		$data = [
			'datamod' => date('Y-m-d'),
			'd_e_l_e_t_e' => '1'
		];
		return $this->db->update($this->table, $data);
	}

    public function getbyIdempresa($id, $mail)
	{
		$this->db->where('empresa_id', $id);
        $this->db->where('email', $mail);
		return $this->db->get($this->table)->row();
	}

  

	public function get_config($id)
	{
		return $this->db->get($this->table)->row();

	}

	public function get_group()
	{
	
		return $this->db->update('mkp_categorias');

	}

    
	public function edit($idempresa, $data)
	{
		$this->db->where('id_usuario', $idempresa);
		return $this->db->update($this->table, $data);
	}



	public function get_attributes_response($category)
    {

        $attributes = array();

        $this->db->select('response, date_updated');
        $this->db->where('categoria', $category);        
        $response = $this->db->get('mkp_categorias_response')->row();

        if($response){
            
            $dEnd       = new DateTime(date('Y-m-d H:i:s'));
            $dStart     = new DateTime($response->date_updated);
            $dDiff      = $dStart->diff($dEnd);

            if($dDiff->days > 30){

                $attributes = $this->get_attributes_response_api($category);

                $this->db->where('categoria', $category);
                $this->db->update('mkp_categorias_response', array(
                    'response'      => serialize($attributes),
                    'date_updated'  => date("Y-m-d H:i:s"),
                ));                
            
            }else{
            
                $attributes = unserialize($response->response);    
            
            }

        }else{
            
            $attributes = $this->get_attributes_response_api($category);
    
            $this->db->insert('mkp_categorias_response', array(
                'categoria'          => $category,
                'response'          => serialize($attributes),
                'date_updated'      => date("Y-m-d H:i:s"),
            ));

        }

        return $attributes;
    }


	public function get_attributes_response_api($category)
    {
       
        $this->load->library('mprestclient');
        $request3 = array(
            "uri" => "/categories/".$category."/attributes",
        );        
        return MPRestClient::get($request3);

    }


    public function add_attibutes_product($data)
    {
        if(empty($data['atributo_id'])){
            return false;
        }

        //$data['empresa_id'] = $data['attribute_empresa_id'];
       /*  unset($data['attribute_empresa_id']); */
        $this->db->insert('mkp_produtos_atributos', $data);

        return $this->db->insert_id();
    }

    public function add_mkp_variations($data){
        $this->db->insert('mkp_produtos_variacoes', $data);
        $insert_id = $this->db->insert_id();
        return $insert_id ? $insert_id : false;
    }



public function add_ml_variation($data){
    $this->db->insert('mkp_ml_variacoes', $data);
    $insert_id = $this->db->insert_id();
    return $insert_id ? $insert_id : false;

}

public function add_frete($data){
    $this->db->insert('mkp_produtos_shipping', $data);
    $insert_id = $this->db->insert_id();
    return $insert_id ? $insert_id : false;

}

public function updatefrete($id,$data){
    $this->db->where('produto_id', $id);
    return $this->db->update('mkp_produtos_shipping', $data);
}



public function get_frete_byid($id){
    $this->db->where('produto_id', $id);
    return $this->db->get('mkp_produtos_shipping')->row();

}


public function get_variations($value_id, $product_id)
    {

        $this->db->where('mkp_ml_variacao', $value_id);
        $this->db->where('produto_id', $product_id);

        return $this->db->get('mkp_produtos_variacoes')->result_array();
    }



    public function get_variations_pictures($value_id, $product_id)
    {

        $this->db->where('id_variation', $value_id);
        $this->db->where('produto_id', $product_id);

        return $this->db->get('mkp_variation_picture')->result_array();
    }


    public function get_products_shipping($product_id, $oneresult = false)
    {
        $this->db->where('produto_id', $product_id);
        if($oneresult){
            return $this->db->get('mkp_produtos_shipping')->row();
        }else{
            return $this->db->get('mkp_produtos_shipping')->result_array();    
        }
        
    }



   


}
