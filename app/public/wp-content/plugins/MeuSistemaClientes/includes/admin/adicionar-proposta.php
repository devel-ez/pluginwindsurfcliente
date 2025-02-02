<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function msc_render_adicionar_proposta() {
	global $wpdb;
	$mensagem = '';

	// Buscar todos os clientes para o select
	$clientes = $wpdb->get_results(
		"SELECT id, nome FROM {$wpdb->prefix}msc_clientes ORDER BY nome ASC"
	);

	// Buscar todos os serviços para o select
	$servicos = $wpdb->get_results(
		"SELECT id, nome, valor, descricao FROM {$wpdb->prefix}msc_servicos ORDER BY nome ASC"
	);

	// Buscar proposta para edição se necessário
	$proposta = null;
	$itens_proposta = array();
	if ( isset( $_GET['id'] ) ) {
		$proposta = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}msc_propostas WHERE id = %d",
			intval( $_GET['id'] )
		) );

		if ( $proposta ) {
			$itens_proposta = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}msc_proposta_itens WHERE proposta_id = %d",
				$proposta->id
			) );
		}
	}

	// Processar o formulário
	if ( isset( $_POST['msc_salvar_proposta'] ) && check_admin_referer( 'msc_salvar_proposta' ) ) {
		$cliente_id  = intval( $_POST['cliente_id'] );
		$titulo      = sanitize_text_field( $_POST['titulo'] );
		$descricao   = sanitize_textarea_field( $_POST['descricao'] );

		$dados_proposta = array(
			'cliente_id'         => $cliente_id,
			'titulo'             => $titulo,
			'descricao'          => $descricao,
			'status'             => 'pendente',
			'data_criacao'       => current_time( 'mysql' ),
			'data_modificacao'   => current_time( 'mysql' )
		);

		if ( isset( $_POST['proposta_id'] ) && ! empty( $_POST['proposta_id'] ) ) {
			// Atualizar proposta existente
			$wpdb->update(
				$wpdb->prefix . 'msc_propostas',
				$dados_proposta,
				array( 'id' => intval( $_POST['proposta_id'] ) ),
				array( '%d', '%s', '%s', '%s', '%s', '%s' )
			);
			$proposta_id = intval( $_POST['proposta_id'] );

			// Remover itens antigos
			$wpdb->delete( $wpdb->prefix . 'msc_proposta_itens', array( 'proposta_id' => $proposta_id ) );
		} else {
			// Criar nova proposta
			$wpdb->insert(
				$wpdb->prefix . 'msc_propostas',
				$dados_proposta,
				array( '%d', '%s', '%s', '%s', '%s', '%s' )
			);
			$proposta_id = $wpdb->insert_id;
		}

		// Inserir itens da proposta
		if ( $proposta_id && isset( $_POST['servico_id'] ) && is_array( $_POST['servico_id'] ) ) {
			foreach ( $_POST['servico_id'] as $key => $servico_id ) {
				if ( empty( $servico_id ) ) {
					continue;
				}

				$wpdb->insert(
					$wpdb->prefix . 'msc_proposta_itens',
					array(
						'proposta_id'   => $proposta_id,
						'servico_id'    => intval( $servico_id ),
						'quantidade'    => intval( $_POST['quantidade'][ $key ] ),
						'valor_unitario'=> floatval( str_replace( ',', '.', $_POST['valor_unitario'][ $key ] ) ),
						'desconto'      => isset( $_POST['desconto'][ $key ] ) ? floatval( str_replace( ',', '.', $_POST['desconto'][ $key ] ) ) : 0
					),
					array( '%d', '%d', '%d', '%f', '%f' )
				);
			}
		}

		// Define mensagem de sucesso e flag para redirecionamento
		$proposta_salva = true;
		$mensagem       = '<div class="notice notice-success is-dismissible"><p>Proposta salva com sucesso! Redirecionando...</p></div>';
	}

	// Se a proposta foi salva, adicionar script de redirecionamento
	if ( isset( $proposta_salva ) && $proposta_salva ) {
		?>
		<script type="text/javascript">
			setTimeout(function(){
				window.location.href = '<?php echo admin_url( 'admin.php?page=meu-sistema-clientes-propostas' ); ?>';
			}, 1000);
		</script>
		<?php
	}
	?>

	<div class="wrap">
		<h1 class="wp-heading-inline"><?php echo $proposta ? 'Editar Proposta' : 'Nova Proposta'; ?></h1>
		<?php echo $mensagem; ?>

		<form method="post" class="msc-form">
			<?php wp_nonce_field( 'msc_salvar_proposta' ); ?>
			<?php if ( $proposta ) : ?>
				<input type="hidden" name="proposta_id" value="<?php echo esc_attr( $proposta->id ); ?>">
			<?php endif; ?>

			<div class="postbox msc-card">
				<h2 class="hndle"><span>Informações Básicas</span></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><label for="cliente_id">Cliente <span style="color: red;">*</span></label></th>
							<td>
								<select id="cliente_id" name="cliente_id" required class="regular-text">
									<option value="">Selecione um cliente</option>
									<?php foreach ( $clientes as $cli ) : ?>
										<option value="<?php echo esc_attr( $cli->id ); ?>" <?php echo ( $proposta && $proposta->cliente_id == $cli->id ) ? 'selected' : ''; ?>>
											<?php echo esc_html( $cli->nome ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="titulo">Título <span style="color: red;">*</span></label></th>
							<td>
								<input type="text" id="titulo" name="titulo" required class="regular-text" 
								       value="<?php echo $proposta ? esc_attr( $proposta->titulo ) : ''; ?>" placeholder="Título da Proposta">
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="descricao">Descrição</label></th>
							<td>
								<textarea id="descricao" name="descricao" class="regular-text" rows="3"
								          placeholder="Descrição da Proposta"><?php echo $proposta ? esc_textarea( $proposta->descricao ) : ''; ?></textarea>
							</td>
						</tr>
					</table>

					<hr>

					<h3>Serviços</h3>
					<div id="servicos-lista">
						<?php if ( $itens_proposta ) : ?>
							<?php foreach ( $itens_proposta as $item ) : ?>
								<div class="servico-item">
									<table class="form-table">
										<tr>
											<th scope="row"><label>Serviço <span style="color: red;">*</span></label></th>
											<td>
												<select name="servico_id[]" required class="regular-text" onchange="preencherValorUnitario(this)">
													<option value="">Selecione um serviço</option>
													<?php foreach ( $servicos as $servico ) : ?>
														<option value="<?php echo esc_attr( $servico->id ); ?>" data-valor="<?php echo esc_attr( $servico->valor ); ?>"
															<?php echo ( $item->servico_id == $servico->id ) ? 'selected' : ''; ?>>
															<?php echo esc_html( $servico->nome ); ?>
														</option>
													<?php endforeach; ?>
												</select>
											</td>
										</tr>
										<tr>
											<th scope="row"><label>Quantidade <span style="color: red;">*</span></label></th>
											<td>
												<input type="number" name="quantidade[]" value="<?php echo esc_attr( $item->quantidade ); ?>" min="1" class="regular-text" onchange="calcularTotal()">
											</td>
										</tr>
										<tr>
											<th scope="row"><label>Valor Unitário <span style="color: red;">*</span></label></th>
											<td>
												<input type="text" name="valor_unitario[]" value="<?php echo esc_attr( $item->valor_unitario ); ?>" class="regular-text" onchange="calcularTotal()">
											</td>
										</tr>
										<tr>
											<th scope="row"><label>Total do Serviço</label></th>
											<td>
												<input type="text" name="total_servico[]" class="regular-text" readonly value="<?php echo number_format( $item->quantidade * $item->valor_unitario, 2, ',', '.' ); ?>">
											</td>
										</tr>
									</table>
									<p class="msc-item-actions">
										<button type="button" class="button-link remove-servico" title="Remover serviço">
											<span class="dashicons dashicons-trash"></span> Remover
										</button>
									</p>
									<hr>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<div class="servico-item">
								<table class="form-table">
									<tr>
										<th scope="row"><label>Serviço <span style="color: red;">*</span></label></th>
										<td>
											<select name="servico_id[]" required class="regular-text" onchange="preencherValorUnitario(this)">
												<option value="">Selecione um serviço</option>
												<?php foreach ( $servicos as $servico ) : ?>
													<option value="<?php echo esc_attr( $servico->id ); ?>" data-valor="<?php echo esc_attr( $servico->valor ); ?>">
														<?php echo esc_html( $servico->nome ); ?>
													</option>
												<?php endforeach; ?>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row"><label>Quantidade <span style="color: red;">*</span></label></th>
										<td>
											<input type="number" name="quantidade[]" value="1" min="1" class="regular-text" onchange="calcularTotal()">
										</td>
									</tr>
									<tr>
										<th scope="row"><label>Valor Unitário <span style="color: red;">*</span></label></th>
										<td>
											<input type="text" name="valor_unitario[]" class="regular-text" onchange="calcularTotal()">
										</td>
									</tr>
									<tr>
										<th scope="row"><label>Total do Serviço</label></th>
										<td>
											<input type="text" name="total_servico[]" class="regular-text" readonly>
										</td>
									</tr>
								</table>
								<p class="msc-item-actions">
									<button type="button" class="button-link remove-servico" title="Remover serviço">
										<span class="dashicons dashicons-trash"></span> Remover
									</button>
								</p>
								<hr>
							</div>
						<?php endif; ?>
					</div>
					<p>
						<button type="button" id="adicionar-servico" class="button">
							<span class="dashicons dashicons-plus-alt2"></span> Adicionar Serviço
						</button>
					</p>

					<table class="form-table">
						<tr>
							<th scope="row"><label>Total Geral</label></th>
							<td>
								<input type="text" id="total_geral" class="regular-text" readonly>
							</td>
						</tr>
					</table>

					<p class="submit">
						<button type="submit" name="msc_salvar_proposta" class="button button-primary">
							<?php echo $proposta ? 'Atualizar Proposta' : 'Criar Proposta'; ?>
						</button>
						<a href="<?php echo admin_url( 'admin.php?page=meu-sistema-clientes-propostas' ); ?>" class="button">Cancelar</a>
					</p>
				</div>
			</div>
		</form>
	</div>

	<style>
		.msc-card {
			margin-top: 20px;
		}
		.msc-card .inside {
			padding: 15px;
		}
		.servico-item {
			background: #fff;
			border: 1px solid #e5e5e5;
			padding: 15px;
			margin-bottom: 15px;
			border-radius: 4px;
		}
		.msc-item-actions {
			text-align: right;
			margin: 0;
		}
		.msc-item-actions button {
			color: #a00;
		}
		@media (max-width: 782px) {
			.form-table th,
			.form-table td {
				display: block;
				width: 100%;
			}
		}
	</style>

	<script>
		jQuery(document).ready(function($) {
			// Adicionar novo serviço clonando o primeiro item
			$('#adicionar-servico').on('click', function() {
				var template = $('#servicos-lista .servico-item:first').clone();
				// Limpar os valores dos inputs clonados
				template.find('input').val('');
				template.find('select').val('');
				$('#servicos-lista').append(template);
			});

			// Remover serviço (garantindo que pelo menos um item permaneça)
			$(document).on('click', '.remove-servico', function() {
				if ($('#servicos-lista .servico-item').length > 1) {
					$(this).closest('.servico-item').remove();
					calcularTotal();
				}
			});
		});

		// Preenche o valor unitário com base na opção selecionada
		function preencherValorUnitario(select) {
			var valor = select.options[select.selectedIndex].dataset.valor;
			var servicoItem = select.closest('.servico-item');
			if (valor) {
				servicoItem.querySelector('input[name="valor_unitario[]"]').value = valor;
			} else {
				servicoItem.querySelector('input[name="valor_unitario[]"]').value = '';
			}
			calcularTotal(); // Recalcula o total após alteração
		}

		// Calcula o total de cada serviço e o total geral da proposta
		function calcularTotal() {
			var totalGeral = 0;
			document.querySelectorAll('.servico-item').forEach(function(servico) {
				var quantidade    = parseFloat(servico.querySelector('input[name="quantidade[]"]').value) || 0;
				var valorUnitario = parseFloat(servico.querySelector('input[name="valor_unitario[]"]').value.replace(',', '.')) || 0;
				var totalServico  = quantidade * valorUnitario;
				servico.querySelector('input[name="total_servico[]"]').value = totalServico.toFixed(2);
				totalGeral += totalServico;
			});
			document.getElementById('total_geral').value = totalGeral.toFixed(2);
		}
	</script>
	<?php
}
