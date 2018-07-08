<?php
    include'functions.php';
?>
<!DOCTYPE html>
<html lang="id">
  <head> 
    <title>Pemira FMIPA Unila</title>
    <link href="assets/css/journal-bootstrap.min.css" rel="stylesheet"/>
    <link href="assets/css/general.css" rel="stylesheet"/>       
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>               
  </head>  
<body>
    <div class="container">  
        <header style="background: url(header2.png); height: 194px;"> 
            <div class="row" >
                <div class="col-md-3">                </div>
                <div class="col-md-6"></div>
                <div class="col-md-3"></div>
            </div>
        </header>
        <nav class="navbar navbar-default navbar-static-top">
        <div class="container-fluid">
            <div class="navbar-header">
              <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>              </button>
            </div>
            <div id="navbar" class="navbar-collapse collapse">
              <ul class="nav navbar-nav">
              <?php if($_SESSION['level']!=='admin'): ?>
              <li><a href="?"><span class="glyphicon glyphicon-calendar"></span> Pemira</a></li>
              <li><a href="?m=tanda_terima"><span class="glyphicon glyphicon-glyphicon glyphicon-cloud"></span> Pencoblosan</a></li>
              <li><a href="?m=daftar_peserta"><span class="glyphicon glyphicon-glyphicon glyphicon-user"></span> Daftar Cagub & Cawagub</a></li>
              <li><a href="?m=hasil_voting"><span class="glyphicon glyphicon-glyphicon glyphicon-signal"></span> Hasil Pemira</a></li>
              <?php endif?>
              
              <?php if($_SESSION['level']!='admin' || !$_SESSION['login']):?>
              <li><a href="?m=login"><span class="glyphicon glyphicon-calendar"></span> Admin & Pengawas Pemira</a></li>
              <?php endif?>                
              <?php if($_SESSION['level']=='admin'):?>
                <li><a href="?m=pencalon"><span class="glyphicon glyphicon-user"></span> Pencalon</a></li>
                <li><a href="?m=pemilih"><span class="glyphicon glyphicon-th-large"></span> Pemilih</a></li> 
                <li><a href="?m=hasil_voting"><span class="glyphicon glyphicon-glyphicon glyphicon-signal"></span> Hasil Pemira</a></li>               
              <?php endif ?>                          
              </ul>          
              <ul class="nav navbar-nav navbar-right">
              <?php if($_SESSION['login']):?>
                <li><a href="aksi.php?act=logout"><span class="glyphicon glyphicon-log-out"></span> Logout</a></li>
              <?php endif ?> 
              </ul>
            </div>
        </div>
        </nav>          
        <div class="">
            <?php
                if(file_exists($mod.'.php')){
                    if($mod=='tanda_terima' && $_SESSION['level']!='pemilih'){
                        redirect_js('?m=login_pemilih');
                    } else {
                        include $mod.'.php';
                    }                               
                }else
                    include 'pilkada.php';
            ?>
        </div>   
        <marquee bgcolor=orange behavior=scrool direction=left> <font color=white> PEMIRA 2018 BEM FMIPA Universitas Lampung </marquee>                       
    </div>
    <footer class="footer">    </footer>
    </body>
</html>