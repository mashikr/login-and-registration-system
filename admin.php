<?php include("includes/header.php") ?>
<?php include("includes/navbar.php") ?>
	
<div class="container">

	<div class="jumbotron">
		<h1 class="text-center">
			<?php
				if (is_login()) {
					echo "Logged in";
				} else {
					redirect("index.php");				}
			?>
		</h1>
	</div>

<?php include("includes/footer.php") ?>