<nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <span class="navbar-brand">LogIn</span>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li><a href="index.php">Home</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="#contact">Contact</a></li>
            <?php
              if (is_login()) {
                echo '<li><a href="logout.php">Logout</a></li>';
              } else {
                echo '<li><a href="login.php">Login</a></li>';
              }
            ?>            
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>
	
<div class="container">