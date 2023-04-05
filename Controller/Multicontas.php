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
        $this->load->helper('mercadolivre_helper');
        $this->load->model('Mercadolivre_model');
        $this->load->model("Usuarios_model");
        $this->load->model("UsuarioEmpresa_model");
        $this->load->model("Tipo_tributacao_icms_model");
        $this->load->model("Armazens_model");
        $this->load->model("Produtos_model");
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




    public function getcontas()
    {
        $app_client_values = array(
            "grant_type"        => "authorization_code",
            "client_id"         => $this->client_idML2,
            "client_secret"     => $this->secret_keyML2,
            "code"              => $_GET['code'],
            "redirect_uri"      => //API de redirect
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

                $exist2 =  $this->Usuarios_model->getUsuarios($empresa_idAABB);
             
             $totalcontasml =  $this->Mercadolivre_model->getbyIdempresa($_SESSION['id_empresa'], $results['email']);
             $existeml = $totalcontasml->id_usuario;



                
             $existusuario = $this->Usuarios_model->getUsuarioByEmail($results['email']);
             $existusuarioid = $existusuario->id_usuario;

    
    
          

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
                
                if ($existusuario) {
                    //atualizar empresa do usuario

                    $empr = [
                        'id_empresa' => $empresa_idAABB,
                    ];

                    $this->UsuarioEmpresa_model->editbyusuarui($existusuarioid, $empr);
                    $isSaved = $this->Usuarios_model->editUsuario($existusuarioid, $data);
                } else {
                    $data['datacri'] = date('Y-m-d');
                    $data['usercri'] = $this->session->userdata('nome');
                    $salvo = $this->Usuarios_model->addUsuario($data);
              
                    $data = [
                        'id_empresa' => $_SESSION['id_empresa'],
                        'id_usuario' => $salvo,
                    ];
                    $this->UsuarioEmpresa_model->add($data);
                }
            }





            $savemarketplaces = [
                "app_id" => $this->client_idML2,
                "secret_key" => $this->secret_keyML2,
                'access_token' => $access_data["response"]['access_token'],
                'ultimo_token' => $access_data["response"]['access_token'],
                'ultimo_refresh_token' => $access_data["response"]['refresh_token'],
                'user_id' => $access_data["response"]["user_id"],
                'empresa_id' => $_SESSION['id_empresa'],
                'email' => $results['email'],
                'id_usuario' =>$existeml
            ];
                    if ($existeml) {
                        $this->Mercadolivre_model->edit($existeml, $savemarketplaces);
                    } else {
                        // AKI INSERE
                        $savemarketplaces['id_usuario'] = $salvo;
                        $this->Mercadolivre_model->save($savemarketplaces);
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
