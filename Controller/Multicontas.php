<?php

class Multicontas extends YS_Controller
{
    private $secret_keyML;
    private $client_idML2;
    private $secret_keyML2;




    public function __construct()
    {
        parent::__construct();
        $this->client_idML     = '6481354972963130'; //'7666057004227519';
        $this->secret_keyML    = 'd6LpwDSvUHGk7LCv4R6w2UBFkrXZcXAI'; //'YUB7mDL6MOv5hl7MtbiALFUNoQFZ4OoE';
        $this->client_idML2    = '6481354972963130';
        $this->secret_keyML2   = 'd6LpwDSvUHGk7LCv4R6w2UBFkrXZcXAI';
        $this->load->library('MPRestClient');
        $this->load->model('Mercadolivre_model');
        $this->load->model("Usuarios_model");
        $this->load->model("UsuarioEmpresa_model");
    }








    public function index()
    {
        if ($_SESSION['cnpj_empresa'] == '03224734000142') {
            $empresas = $this->UsuarioEmpresa_model->getEmpresas();
        } else {
            $empresas = $this->UsuarioEmpresa_model->getEmpresas($_SESSION['id']);
        }
        $usuarios = $this->UsuarioEmpresa_model->getUsuarios2($empresas, $this->input->get('order'));


        $this->view('multicontas.index', [
            'usuarios' => $usuarios,
        ]);
    }


   /*  public function pagina()
    {
        $this->view('multicontas.pagina', [
        ]);

    }
 */


    public function getcontas()
    {
        $app_client_values = array(
            "grant_type"        => "authorization_code",
            "client_id"         => $this->client_idML2,
            "client_secret"     => $this->secret_keyML2,
            "code"              => $_GET['code'],
            "redirect_uri"      => 'https://localhost/yoursystem/multicontas/getcontas'
        );

        $access_data = MPRestClient::post(array(

            "uri" => "/oauth/token",
            "data" => $app_client_values,
            "headers" => array(
                "content-type" => "application/x-www-form-urlencoded"
            )

        ));
        /*  var_dump($access_data["response"]);
          die; */
        //salva o user id 
        if (!empty($access_data["response"]["user_id"])) {

            $savemarketplaces = [
                "app_id" => $this->client_idML2,
                "secret_key" => $this->secret_keyML2,
                'access_token' => $access_data["response"]['access_token'],
                'ultimo_token' => $access_data["response"]['access_token'],
                'ultimo_refresh_token' => $access_data["response"]['refresh_token'],
                'user_id' => $access_data["response"]["user_id"],
                'empresa_id' => $_SESSION['id_empresa']

            ];
            $exist = $this->Mercadolivre_model->getbyIdempresa($_SESSION['id_empresa']);
            if ($exist) {
                $this->Mercadolivre_model->edit($_SESSION['id_empresa'], $savemarketplaces);
            } else {
                $this->Mercadolivre_model->save($savemarketplaces);
            }
        }

        if (!empty($access_data["response"]["user_id"])) {

            $this->db->select('empresa_id');
            $this->db->from('mkp_marketplacesconnected');
            $this->db->where('user_id', $access_data["response"]["user_id"]);
            $verf = $this->db->get()->row();

            //busca os dados do usuário
            $array = array(
                "access_token" => $access_data["response"]["access_token"]
            );

            $request = array(
                "uri" => "/users/me",
                "headers" => array("Authorization" => 'Bearer ' . $access_data["response"]["access_token"]),
                "params" => $array
            );
            $access_data3 = MPRestClient::get($request);



            if ($access_data3["status"] == 200) {

                $name       = "";
                $lastname   = "";
                $email      = "";
                $phone      = "";
                $document   = "";
                $address    = "";
                $city       = "";
                $state      = "";
                $socialId   = "";
                $companyName = "";
                $zipCode    = "";

                foreach ($access_data3 as $query => $results) {

                    $socialId   = $results["id"];
                    $name       = $results["first_name"];
                    $lastname   = $results["last_name"];
                    $companyName = isset($results["company"]["corporate_name"]) ? $results["company"]["corporate_name"] : null;
                    $phone      = isset($results["phone"]["number"]) ? $results["phone"]["number"] : null;
                    $email      = $results["email"];
                    $document   = isset($results["identification"]["number"]) ? $results["identification"]["number"] : null;
                    $address    = isset($results["address"]["address"]) ? $results["address"]["address"] : null;
                    $city       = isset($results["address"]["city"]) ? $results["address"]["city"] : null;
                    $state      = isset($results["address"]["state"]) ? $results["address"]["state"] : null;
                    $zipCode    = isset($results["address"]["zip_code"]) ? $results["address"]["zip_code"] : null;
                }

                $name =  "$name " . "$lastname";
                //adiciona as multiplas contas   no usuarios        
                $lCriaconta     = false;
                $empresa_idAABB = $_SESSION['id_empresa'];
                $exist2 =  $this->Usuarios_model->getUsuarioByEmail2($results['email']);

                $data = [
                    'ativo' => 1,
                    'senha_usuario' => md5(123456),
                    'usermod' => $this->session->userdata('nome'),
                    'cpf_usuario' => $results['identification']["number"],
                    'nome_usuario' =>  $name,
                    'email_usuario' => $results['email'],
                    'permissao' => 1,
                    'nivel_usuario' => 1,  /* VASCO */
                    'mercadolibre' => true,
                ];
                if ($exist2) {
                    $isSaved = $this->Usuarios_model->editUsuario($exist2->id_usuario, $data);
                    /*  var_dump($data);
                die; */
                } else {
                    $data['datacri'] = date('Y-m-d');
                    $data['usercri'] = $this->session->userdata('nome');
                    $isSaved = $this->Usuarios_model->addUsuario($data);
                    $data = [
                        'id_empresa' => $_SESSION['id_empresa'],
                        'id_usuario' => $isSaved,
                    ];
                    $this->UsuarioEmpresa_model->add($data);
                }
            }
            $this->db->select('empresa_id');
            $this->db->from('mkp_marketplacesconnected');
            $this->db->where('user_id', $access_data["response"]["user_id"]);
            $verf1 = $this->db->get()->row();

            if ($verf1) {
                if ($verf1->empresa_id == $empresa_idAABB) {
                    //atualiza os dados para se reconectar ao Mercado Livre
                    $this->db->where('user_id', $access_data["response"]["user_id"]);
                    $this->db->where('empresa_id', $empresa_idAABB);
                    $this->db->update('mkp_marketplacesconnected', array(
                        'app_id'                => $this->client_idML2,
                        'secret_key'            => $this->secret_keyML2,
                        'ultimo_refresh_token'  => $access_data["response"]["refresh_token"],
                        'ultimo_token'          => $access_data["response"]["access_token"],
                    ));
                    /*  $this->session->set_flashdata('alert', [
                                'success',
                                'Os dados de sua conta foram atualizados com sucesso!'
                            ]); */
                    //  redirect('Multicontas');
                }
                //entrando aki

            }

            //2 - verifica se essa conta já não existe no Idealize
            /*  $this->db->select('empresa_id');
                    $this->db->from('crm_mercadolivre');
                    $this->db->where('user_id', $access_data["response"]["user_id"]);
                    $verf = $this->db->get()->row();
                    
                    if ($verf) {
                    
                        $this->db->select('id,email,grupo');
                        $this->db->from('crm_empresas');
                        $this->db->where('id', $verf->empresa_id);
                        $empresa = $this->db->get()->row();
                    
                        if ($empresa) {
                    
                            if($empresa->grupo <> 0){
                                set_alert('warning', 'A conta que você quer adicionar já está vinculada. Caso não apareça na lista abaixo entre em contato conosco.');
                                redirect(admin_url('settings/multiaccount'));            
                            }
        
                            if ($empresa->id != $empresa_idAABB) {
                                
                                //adiciona a empresa no grupo
                                $data_product = [
                                    'empresa_id'    => $empresa->id,
                                    'grupo_id'      => $idGrupo,
                                    'plano'         => 1,
                                ];                            
                                $this->db->insert('crm_empresas_grupos_in', $data_product);                
        
                                $this->db->where('id', $empresa->id);
                                $this->db->where('grupo = 0');
                                $this->db->set('grupo', $idGrupo);
                                $this->db->update('crm_empresas');
        
                            }else{
                                $lCriaconta = true;
                            }
                        }                
                    }else{
                        $lCriaconta = true;
                    }
        
                    //caso tenha que criar uma conta nova
                    if($lCriaconta){
                        
                        $array = array(
                            "access_token"=>$access_data["response"]["access_token"]
                        );
        
                        $request = array(
                            "uri" => "/users/me",
                            "headers" => array("Authorization"=>'Bearer '.$access_data["response"]["access_token"]),
                            "params" => $array                
                        );
                        $access_data3 = MPRestClient::get($request);
        
                        if ($access_data3["status"] == 200) {
        
                            $name       = "";
                            $lastname   = "";
                            $email      = "";
                            $phone      = "";
                            $document   = "";
                            $address    = "";
                            $city       = "";
                            $state      = "";
                            $socialId   = "";
                            $companyName= "";
                            $zipCode    = "";
        
                            foreach ($access_data3 as $query => $results) {
                                
                                $socialId   = $results["id"];
                                $name       = $results["first_name"];
                                $lastname   = $results["last_name"];
                                $companyName= isset($results["company"]["corporate_name"]) ? $results["company"]["corporate_name"] : null;
                                $phone      = isset($results["phone"]["number"]) ? $results["phone"]["number"] : null;    
                                $email      = $results["email"];
                                $document   = isset($results["identification"]["number"]) ? $results["identification"]["number"] : null;    
                                $address    = isset($results["address"]["address"]) ? $results["address"]["address"] : null;
                                $city       = isset($results["address"]["city"]) ? $results["address"]["city"] : null;
                                $state      = isset($results["address"]["state"]) ? $results["address"]["state"] : null;
                                $zipCode    = isset($results["address"]["zip_code"]) ? $results["address"]["zip_code"] : null;
        
                            }                    
        
                            if(check_black_list_email($email))
                            {
        
                                redirect(site_url('landing'));
                                
                            } 
        
                            if(!empty($email) || !empty($socialId)){
                                
                                if(empty($email)){
                                    $email = $socialId;
                                }
        
                                $where = "email='".$email."' ";
                                $checkUser = $this->DatabaseModel->access_database('crm_staff','select','',$where);        
                                
                                if(empty($checkUser)) {
                                    
                                    //verifica se ja nao existe uma empresa com esse e-mail ou id
                                    $checkEmail = $this->DatabaseModel->access_database('crm_empresas','select','',array('email'=>$email));
                                    if(empty($checkEmail) ) {
        
                                        //insere o cliente para a conta de administração
                                        $where = "email='".$email."' AND empresa_id = 4 ";
                                        $resultCli = $this->DatabaseModel->access_database('crm_leads','select','',$where);
                                        
                                        if (empty($resultCli)) {
                                            //$CliId = $this->ldg_crate_client($name,$lastname,$email,true);
                                            $CliId = $this->ldg_crate_client($name, $lastname, $email, true, $companyName, $phone, $document, $city, $address, $zipCode);
        
                                        }else{
                                            $CliId = $resultCli[0]['id'];
                                        } 
        
                                        //$eid = $this->ldg_crate_company_account($name,$lastname,$email,$CliId,true);
                                        
                                        //busca a data do ultimo pagamento
                                        $this->db->select('data_pagamento,plan_id');
                                        $this->db->from('crm_empresas');
                                        $this->db->where('id', $empresa_idAABB);
                                        $empresa = $this->db->get()->row();
        
                                        $data_pagamento = isset($empresa->data_pagamento) ? $empresa->data_pagamento : null;
                                        $plan_id        = isset($empresa->plan_id) ? $empresa->plan_id : null;
        
                                        $eid = $this->ldg_crate_company_account($name,$lastname,$email,$CliId,true, $companyName, $phone, $document, $city, false, $plan_id, $data_pagamento );
        
                                        //search the email
                                        $where = "email='".$email."' ";
                                        $result = $this->DatabaseModel->access_database('crm_empresas','select','',$where);
        
                                        if (!empty($result)) {                                    
                                            
                                            $data['active']         =  1 ;
                                            $data['empresa_id']     = $eid;
                                            $data['app_id']         = $this->client_idML2;
                                            $data['secret_key']     = $this->secret_keyML2;
                                            $data['ultimo_refresh_token']  = $access_data["response"]["refresh_token"];
                                            $data['user_id']        = $access_data["response"]["user_id"];
                                            $data['ultimo_token']   = $access_data["response"]["access_token"];
                                            $this->db->insert('crm_mercadolivre', $data);
                                            $insert_id = $this->db->insert_id();
        
                                            $data_product = [
                                                'empresa_id'    => $eid,
                                                'grupo_id'      => $idGrupo,
                                                'plano'         => 1,
                                            ];
                                            $this->db->insert('crm_empresas_grupos_in', $data_product);                
                                            
                                            $this->db->where('id', $eid);
                                            $this->db->where('grupo = 0');
                                            $this->db->set('grupo', $idGrupo);
                                            $this->db->update('crm_empresas');
        
                                            $this->login_create_company_params($eid);
                                            
                                            $this->ldg_create_login_account($email,$name,$lastname,$email,$eid,"Mercado Livre",null,true,null,$socialId, null, $idGrupo);
        
                                            //$this->ldg_create_login_account($email,$name,$lastname,$email,$eid,"Mercado Livre",null,true,null,$socialId);
                                        }    
                                    }   
                                }    
                            }    
                        }    
                    } */


            $this->session->set_flashdata('alert', [
                'success',
                'Conta(s) adicionadas com sucesso'
            ]);
            redirect("multicontas");
        }

        //  set_alert('danger', 'Erro ao criar sua conta. Entre em contato conosco');
        //redirect(site_url('authentication/admin'));
        //  redirect(admin_url('mercadolivre/dashboard'));
    }
}
