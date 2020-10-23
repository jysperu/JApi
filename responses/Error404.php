<?php
namespace Response;

class Error404
{
	public function index ()
	{
		?>
<div class="page page-404">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-12 col-sm-8 col-md-6 my-5">
				<div class="card shadow-sm">
					<div class="card-body">
						<h1 class="text-primary"><?= _t('Página No Encontrada'); ?></h1>
						<h5 class="text-muted mt-n2 mb-4">Error 404</h5>
						<p class="mb-0"><?= _t('La página que buscas no existe o ha sido movido temporalmente.'); ?></p>
						<p><?= _t('Por favor valide el enlace de acceso o comuniquese con el administrador.'); ?></p>
						<br>
						<a href="<?= url(); ?>" class="btn btn-primary btn-lg"><?= _t('Ir al INICIO', 2, 'asd', 'asaa'); ?></a>
						&nbsp;&nbsp;|&nbsp;&nbsp;
						<a href="javascript:history.back()" class="btn btn-secondary btn-sm"><?= _t('Regresar'); ?></a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script src="https://assets.jys.pe/using.js/using.full.min.js"></script>
<script>
	Using('bootstrap');
</script>
		<?php
	}
}