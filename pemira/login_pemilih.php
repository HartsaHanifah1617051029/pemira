
<div class="page-header">
    <h1>Login Pemilih</h1>
</div>
<div class="row">
    <div class="col-md-4">        
        <?php if($_POST) include 'aksi.php'; ?>
        <div id="log">
        <form class="form-signin" action="?m=login_pemilih" method="post">                        
            <div class="form-group">
                <label>No KTP</label>
                <input type="text" required=required class="form-control" placeholder="No KTP" name="user" autofocus />
            </div>
            <div class="form-group">            
                <label>Password</label>
                <input type="password" required=required id="inputPassword" class="form-control" placeholder="Password" name="pass" />  
            </div>      
            <div class="form-group">                
                <button class="btn btn-primary" type="submit"><span class="glyphicon glyphicon-log-in"></span> Masuk</button>                
            </div>     
        </form>    
        </div>  
    </div>
</div>
