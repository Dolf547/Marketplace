<?php
class Marketplace extends YS_Controller {
	function __construct() {
		parent::__construct();
		$this->load->helper(array('codegen_helper'));
		$this->load->model('Naturezas_model','',TRUE);
		$this->load->library('form_validation');
		$this->load->helper('number');
		$this->load->model('CRON_Produtos_model');

	}	
	function index(){
        $this->CRON_Produtos_model->mercadolivre_importa_produtos_autoload();
	}

}