<?php

class produtosintegrar extends YS_Controller
{




	public function __construct()
	{
		parent::__construct();
		$this->client_idML     = '6481354972963130'; //'7666057004227519';
		$this->secret_keyML    = 'd6LpwDSvUHGk7LCv4R6w2UBFkrXZcXAI'; //'YUB7mDL6MOv5hl7MtbiALFUNoQFZ4OoE';
		$this->client_idML2    = '6481354972963130';
		$this->secret_keyML2   = 'd6LpwDSvUHGk7LCv4R6w2UBFkrXZcXAI';
		$this->load->library('MPRestClient');
		$this->load->helper('Mercadolivre_helper');
		$this->load->model('Mercadolivre_model');
		$this->load->model("Usuarios_model");
		$this->load->model("UsuarioEmpresa_model");
		$this->load->model("Tipo_tributacao_icms_model");
		$this->load->model("Armazens_model");
		$this->load->model("Produtos_model");
		$this->load->model('Estoques_model');
		$this->load->model('Anexo_model');
	}






	public function index()
	{

		$this->Produtos_model->control = '';
		$this->Produtos_model->isPaginate = true;
		$vasco = $this->input->post('qtd');
		if ($vasco == null) {
			$vasco = 10;
		} elseif ($vasco > 100) {
			$vasco = 100;
		};
		$this->Produtos_model->limitResults = $vasco;
		$csts = $this->Tipo_tributacao_icms_model->getAll();
		//falto relacionar isso na index...

		$armazemP = $this->Armazens_model->buscarPrincipal();
		$armazemP = $armazemP->id ? $armazemP->id : null;
		// var_dump($armazemP); die;

		$products = $this->Produtos_model->getAll(
			$this->input->post('codigo'),
			str_replace(' ', '', $this->input->post('nome')),
			$this->input->post('gruposdeproduto'),
			$this->input->post('grupossubdeproduto'),
			$this->input->post('marcadeproduto'),
			$this->input->post('tipo'),
			$this->input->post('bloqueado'),
			$this->input->get('order'),
			null,
			$this->input->post('cst'),
			$armazemP
		);
		//$etiquetas =  $this->Etiquetas_model->getEtiquetas();

		$this->view('produtosintegrar.index', [
			'products' => $products,
			'csts' => $csts,
			// falto...

		]);
	}





	public function integrarproduto($id)
	{


		$data =  $this->Mercadolivre_model->getbyIdempresa($_SESSION['id_empresa'], $_SESSION['email']);

		//token, ultimo!
		$access_token = $data->ultimo_token;
		$sucess = true;

		$this->load->model('produtos_model');
		//pegou o produto
		$produto = $this->produtos_model->getById($id);

		//pega armazem
		$armazemP = $this->Armazens_model->buscarPrincipal();
		$armazemP = $armazemP->id ? $armazemP->id : null;
		//qtd em estoque
		$qtd = $this->Estoques_model->getStockByProductAndArmazem($id, $armazemP);


		//categoria	
		if (empty($produto->mercadolivre_category_id)) {
			$this->session->set_flashdata('alert', [
				'danger',
				'Categoria nao informada'
			]);
			$sucess = false;
		}
		//passou na quantidade!
		if (empty($qtd) || $qtd == 0) {
			$this->session->set_flashdata('alert', [
				'danger',
				'o Produto nao tem quantidade!'
			]);
			$sucess = false;
		}

		if ($sucess == true) {

			$imagens_anuncio    = array();
			$attributes_anuncio = array();
			$variations_anuncio = array();
			$n                  = 0;
			$atach              = array();
			$attributes_anuncio = get_all_product_attributes_ml($id); //pegar todos os atributos
			$varia              = get_all_product_variations($id); //pegar todas variacoes
			$n                  = 0;
			$nPicturesAttributes = 0;

			foreach ($varia as $key => $value) {

				$variations_values = $this->Mercadolivre_model->get_variations($value['id'], $value['produto_id']);
				$n2 = 0;
				$n1 = 0;

				$attributes_combinations = array();
				$tags = array();
				$insertAtt = false;

				//aki envia as variações
				foreach ($variations_values as $values) {

					if ($values["variacao_id"] == "FABRIC_DESIGN") {

						if (!empty($values["variacao_value_id"])) {
							$tags[$n1]['id']                   = $values["variacao_id"];
							$tags[$n1]['value_id']             = $values["variacao_value_id"];
						} else {
							$tags[$n1]['id']                   = $values["variacao_id"];
							$tags[$n1]['value_id']             = null;
							$tags[$n1]['value_name']           = $values["name"];
						}
						$n1++;
					} else {
						/* 	var_dump($values);
					die; */
						if ($values["variacao_id"]  == 'COLOR' && empty($values["name_ml"])) {
							print_r('tem erro');
							die();
						}

						$attributes_combinations[$n2]['id']         = $values["variacao_id"];
						$attributes_combinations[$n2]['name']       = $values["name_ml"];
						$attributes_combinations[$n2]['value_name'] = $values["name"];

						if (!empty($values["variacao_value_id"])) {

							$attributes_combinations[$n2]['value_id']             = $values["variacao_value_id"];
						} else {
							$attributes_combinations[$n2]['value_id']             = null;
						}
						$n2++;
					}
				}

				$variations_values = $this->Mercadolivre_model->get_variations_pictures($value['id'], $value['product']);

				$pictures = array();
				$n3 = 0;
				foreach ($variations_values as $values) {
					$pictures[$n3]         = 'http://mlb-s2-p.mlstatic.com/' . $values["id_ml"] . '-O.jpg';
					$n3++;
					$nPicturesAttributes++;
				}

				//somente envia se a variação tiver combinações
				if (count($variations_values) > 0) {
					$variations_anuncio[$n]['attribute_combinations']   = $attributes_combinations;
					if (count($tags) > 0) {
						$variations_anuncio[$n]['tags']   = $tags;
					}
					$variations_anuncio[$n]['picture_ids']              = $pictures;
					$variations_anuncio[$n]['price']                    = $value['price'];
					$variations_anuncio[$n]['available_quantity']       = intval($value['available_quantity']);
					$variations_anuncio[$n]['sold_quantity']            = 0;
					$n++;
				}
			}


			$anexo = $this->Anexo_model->getByID($this->Produtos_model->table, $id);

			if ($anexo) {
				foreach ($anexo as $key => $value) {
					$url = base_url();
					//{{ base_url() }}public/clientes/{{ $_SESSION['id_empresa'] }}/img/{{ $anexo[0]->caminho  }}
					$imagens_anuncio[$n]['source'] = "$url" . "public/clientes/" . $_SESSION['id_empresa'] . "/img/" . $anexo[0]->caminho . "";

					$n++;
				}
			}




			$shipping = array();

			$_att = $this->Mercadolivre_model->get_products_shipping($id);

			if (count($_att) > 0) {
				foreach ($_att as $_att) {

					$free_methos = array();


					$n_methods = 0;

					$tags = array();
					$n_tag = 0;




					if (empty($_att["dimensions"])) {
						$shipping = array(
							"mode"              => $_att["mode_ml"],
							"logistic_type"     => $_att["logistic_type"],
							"local_pick_up"     => (empty($_att["local_pick_up"]) || $_att["local_pick_up"] == 0) ? false : true,
							"free_shipping"     => (empty($_att["free_shipping"]) || $_att["free_shipping"] == 0) ? false : true,
							//   "free_methods"      => $free_methos,
							"tags"              => $tags
						);
					} else {
						$shipping = array(
							"mode"              => $_att["mode_ml"],
							"logistic_type"     => $_att["logistic_type"],
							"local_pick_up"     => (empty($_att["local_pick_up"]) || $_att["local_pick_up"] == 0) ? false : true,
							"free_shipping"     => (empty($_att["free_shipping"]) || $_att["free_shipping"] == 0) ? false : true,
							//  "free_methods"      => $free_methos,
							"tags"              => $tags,
							"dimensions"        => $_att["dimensions"]
						);
					}
				}
			} else {
				$shipping = array(
					"mode" => "not_specified",
					"local_pick_up" => true,
					"free_shipping" => false,
					"logistic_type" => "not_specified"
				);
			}

			$anuncio = "gold_pro";
			if ($produto->mercadolivre_listeningtype == 1 || empty($produto->mercadolivre_listeningtype)) {
				$anuncio = "gold_special";
			}

			if (!empty($produto->codigo)) {

				$isSku  = false;

				foreach ($attributes_anuncio as $_att2) {

					$isSku = $_att2['id'] == 'SELLER_SKU' ? true : $isSku;
				}

				if (!$isSku) {
					//insert in the product
					$data_attribute = [
						'produto_id'            => $id,
						'atributo_id'          => 'SELLER_SKU',
						'atributo_value_id'    => null,
						'value_name'            => $produto->codigo,
					];
					$this->db->insert('mkp_produtos_atributos', $data_attribute);

					$attributes_anuncio = get_all_product_attributes_ml($id);
				}
			}

			$acao = "inserido";

			//garantia
			$sale_terms = array();

			if (!empty($produto->dgarantia) && !empty($produto->dgarantia)) {

				$warranty_period = null;
				if ($produto->pgarantia == 'day') {
					$warranty_period = 'dias';
				} elseif ($produto->pgarantia == 'moth') {
					$warranty_period = 'Mes';
				} elseif ($produto->pgarantia == 'year') {
					$warranty_period = 'ano';
				}

				/* if($produto->dgarantia == 'fabrica'){
				$garantia = 'Garantia de fábrica';
			}elseif ($produto->dgarantia == 'sem'){
				$garantia = 'Sem garantia';
			}elseif ($produto->dgarantia == 'vendedor'){
				$garantia = 'Por conta do vendedor';
			} */

				array_push($sale_terms, array("id" => "WARRANTY_TYPE", "name" => $produto->description_ml));



				array_push($sale_terms, array("id" => "WARRANTY_TIME",   "value_name" => $produto->dgarantia . ' ' . $warranty_period));
			}

			if (empty($produto->mercadolivre_code)) {
				$item = array(
					"title"                 => "$produto->nome_prod",
					"category_id"           => empty($produto->subcategory) ? $produto->category_id : $produto->subcategory,
					"price"                 => $produto->preco_venda,
					"currency_id"           => "BRL",
					"available_quantity"    => $qtd,
					"buying_mode"           => "buy_it_now",
					"listing_type_id"       => $anuncio,
					"condition"             => empty($produto->mercadolivre_condition) ? 'new' : $produto->mercadolivre_condition,
					"description"           => $produto->description_ml,
					"warranty"              => $produto->garantia,
					"pictures"              => $imagens_anuncio,
					"attributes"            => $attributes_anuncio,
					"variations"            => $variations_anuncio,
					"accepts_mercadopago"   => true,
					"shipping"              => $shipping,
					"sale_terms"       		=> $sale_terms
				);

				$request = array(
					"uri" => "/items",
					"headers" => array("Authorization" => 'Bearer ' . $access_token),
					"params" => array(
						"access_token" => $access_token
					),
					"data" => $item
				);
				$access_data = MPRestClient::post($request);

				if ($access_data["status"] == 405) {
					$access_data = MPRestClient::put($request);
				}

				//atualiza
			} else {

				$item = array(
					"title"             => "$produto->nome_prod",
					"price"             => $produto->preco_venda,
					"available_quantity" => $qtd,
					"pictures"          => $imagens_anuncio,
					"attributes"        => $attributes_anuncio,
					"variations"        => $variations_anuncio,
					//"description"       => $produto->description_ml
				);

				$request = array(
					"uri" => "/items/" . $produto->mercadolivre_code,
					"headers" => array("Authorization" => 'Bearer ' . $access_token),
					"params" => array(
						"access_token" => $access_token
					),
					"data" => $item
				);
				$access_data = MPRestClient::post($request);
				$acao = "alterado";


				if ($access_data["status"] == 405) {
					$access_data = MPRestClient::put($request);
				}

				if (isset($access_data['status']) && $access_data['status'] == 400) {

					if (isset($access_data['response']['cause'][0]['cause_id']) && ($access_data['response']['cause'][0]['cause_id'] == 240  || $access_data['response']['cause'][0]['cause_id'] == 339)) {

						$imagens_anuncio    = array();
						foreach ($variations_anuncio as $_att2) {
							$n3 = 0;

							foreach ($_att2['picture_ids'] as $picture) {

								$imagens_anuncio[$n3]['source'] = $picture;

								$n3++;
							}
						}

						$item = array(
							"title" => "$produto->nome_prod",
							"pictures" => $imagens_anuncio,
							"variations" => $variations_anuncio,
						);

						$request = array(
							"uri" => "/items/" . $produto->mercadolivre_code,
							"headers" => array("Authorization" => 'Bearer ' . $access_token),
							"params" => array(
								"access_token" => $access_token
							),
							"data" => $item
						);
						$access_data = MPRestClient::post($request);

						if ($access_data["status"] == 405) {
							$access_data = MPRestClient::put($request);
						}
					}
				}
			}


			if ($access_data["status"] == 201 || $access_data["status"] == 200) {

				if (!empty($access_data["response"]["id"])) {
					$this->db->where('id', $id);
					$this->db->update('produtos', array(
						'mercadolivre_code' => $access_data["response"]["id"],
						'mercadolivre_permalink' => $access_data["response"]["permalink"],
						'mercadolivre_msg_integration' => "Integrado com sucesso",
						'mercadolivre_status_integration' => 1,
						'mercadolivre_date_integration' => date('Y-m-d H:i:s')
					));
				}

				//('success', 'Produto '.$acao.' com sucesso.');    
				$this->session->set_flashdata('alert', [
					'success',
					'Produto ' . $acao . ' com sucesso.'
				]);
			} else {

				$msgintegration = "";

				if ($access_data["response"]["message"] == "seller.unable_to_list") {

					$msgintegration = 'Erro ao inserir no Mercado Livre: Seus dados estão incompletos no Mercado Livre, acesse e complete os seus dados no sistema do Mercado Livre. (Se sua conta for nova você ainda não cadastrou nenhum produto manualmente, é necessário você se cadastrar manualmente no Mercado Livre para confirmar algumas informações como seu CPF/CNPJ e confirmação de endereço. Basta cadastrar um produto qualquer pela primeira vez e depois de validar a conta pode seguir com a cópia normalmente.)';
				} else {

					$msgAux = isset($access_data["response"]["cause"][0]["message"]) ? $access_data["response"]["cause"][0]["message"] : $access_data["response"]["message"];

					if ($msgAux == 'Client not allowed to update item null logistic_type.' || $msgAux == 'Attribute [SHIPMENT_PACKING] ignored because it is not modifiable.') {

						$msgAux = 'O MODO DE FRETE não é permitido para esse produto. Altere o modo de frete e integre novamente (Se o anúncio estimer com "Mercado Envios 1" altere para "Mercado Envios 2").';
					} elseif ($msgAux == 'price is not modifiable.') {

						$msgAux = 'O preço não pode ser alterado. verifique se o seu produto não está PAUSADO ou BLOQUEADO no Mercado Livre.';
					} elseif ($msgAux == 'Item pictures are mandatory for listing type gold_pro' || $msgAux == 'Item pictures are mandatory for listing type gold_special') {

						$msgAux = 'É obrigatório a inclusão de fotos para integrar esse produto. Inclua as fotos e tente integrar novamente.';
					} elseif ($msgAux == 'Cannot update title when item has bids') {

						$msgAux = 'Não é possível atualizar o título quando o item tem vendas.';
					}

					$msgintegration = 'Erro ao inserir no Mercado Livre: ' . $msgAux;
				}

				$this->session->set_flashdata('alert', [
					'danger',
					"$msgintegration"
				]);

				$this->db->where('id', $id);
				$this->db->update('produtos', array(
					'mercadolivre_msg_integration' => $msgintegration,
					'mercadolivre_status_integration' => 0,
					'mercadolivre_date_integration' => date('Y-m-d H:i:s')
				));
			}
		}

		if ($id) {
			redirect('Produtos/editar/' . $id);
		} else {
			$this->session->set_flashdata('alert', [
				'danger',
				"Erro ao inserir produtos"
			]);
			redirect('produtos');
		}
	}
}
