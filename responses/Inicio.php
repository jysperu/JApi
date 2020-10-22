<?php
namespace Response;

class Inicio
{
	public function index ()
	{
		?>
<div class="page page-inicio">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-12 col-sm-8 col-md-6 my-5">
				<div class="card shadow-sm">
					<div class="card-body">
						<h1 class="text-primary">Nueva Aplicación</h1>
						<h5 class="text-secondary mt-n2 mb-4">Respaldado por JApi</h5>
						<p class="mb-0">Este es la página principal por defecto de la aplicación.</p>
						<p>Puede encontrar toda la información del núcleo JApi en <a href="https://github.com/jysperu/JApi" target="_blank" class="btn btn-outline-dark btn-sm py-0">Github</a></p>
						<br>
						<a href="javascript:history.back()" class="btn btn-secondary btn-sm">Regresar</a>
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