<?php
$var = $db->get_var("SELECT tanda_terima FROM tb_pilih WHERE id_pemilih='$_SESSION[id_pemilih]'");
?>
<div class="page-header">
    <h1>Tanda Terima Pemilihan Gubernur BEM FMIPA UNILA</h1>
</div>
<p>Hasil suara yang telah Anda masukkan telah tercatat pada sistem Pemira Online</p>
<center><a href="index.php"> Kembali ke Home </a></center>