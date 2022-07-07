@extends('default')
@section('title', ' - Listagem de Multiplas contas')
@include('components.breadcrumbs', [
'icon' => 'dashboard',
'levelOne' => 'Painel de Controle',
'secondLevel' => 'Multiplas contas',
'secondLevelLink' => 'Multiplas contas'
])
@section('main')
<!-- <section class="ys-top-box margin-bottom-lg min">
	<div class="row margin-none">
		<form method="post" action="?page=1" role="form">
			<div class='row'>
				<div class="col-md-12">
					<header class="ys-header-titles">
						<div class='row'>
							<div class='col-sm-6'>
								<h2 class="ys-title-itens text-success">Busca de Multiplas contas</h2>
							</div>
							<div class='col-sm-6'>
								<button type="submit" name="Limpar" id="Limpar" class="btn btn-warning btn-primary pull-right">Limpar</button>
								<button type="submit" name="Filtrar" id="Filtrar" class="btn btn-submit btn-primary pull-right mr-2">Filtrar</button>
							</div>
						</div>
					</header>
				</div>
			</div>

		</form>
	</div>
</section> -->
<div class="ys-content-box margin-bottom-lg">
	<div class="col-md-12">
		<header class="ys-header-titles flex">
			<h2 class="ys-title-itens text-success">Listagem de contas Marketplace</h2>
			<a href="http://auth.mercadolivre.com.br/authorization?client_id=6481354972963130&response_type=code&redirect_uri=https://localhost/yoursystem/multicontas/getcontas" class="btn btn-success   test-leads-email-integration" id="add_meli"><i class="fa fa-lock"></i>&nbsp;&nbsp;Adicionar Conta com mercado Livre</a>

		</header>
	</div>
	<div class="col-md-12">
		<div class="table-responsive">
			<table class="table table-hover">
				<thead>
					<tr id='cabecalho-table' >
						<th style="width: 80px; text-align: center"></th>
						<th class= "text-primary">Nome da conta</th>
						<th class= "text-primary">Email principal da conta</th>
						<th class= "text-primary">Adicionada em</th>
						<th class= "text-primary">Marketplace</th>
						<th class= "text-primary">Status</th>
					</tr>
				</thead>
				<tbody>
					<?php
					?>

					@foreach ($usuarios as $row)
					<tr>
						<td style="width: 80px; text-align: center">

						</td>
						<td>
							<a>
								{{ $row->nome_usuario }}
							
						</td>
						<td>{{ $row->email_usuario }}</td>
						<td>{{ dateFormat($row->datacri) }}</td>
						<td><?php if($row->mercadolibre == 't'){
							echo "Mercado livre";
						}?></td>
						<td><?php if($row->ativo == '1'){
							echo "Ativo";
						}?></td>
						<td></td>
					</tr>
					<?php $cont++ ?>
					@endforeach
				</tbody>
			</table>
		</div>
	</div>
	<div class="col-md-12">
		<?= $this->pagination->create_links(); ?>
	</div>
</div>
@endsection
@section('js')
<script>
	var deleteItem = function(e) {
		var id = e;
		var del = confirm("Realmente deseja deletar este registro?");
		if (del) {
			window.location.href = '{{ base_url() }}origem/excluir/' + id;
		}
		return
	}
</script>
@endsection