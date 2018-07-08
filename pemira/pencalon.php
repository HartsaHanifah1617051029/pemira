<div class="page-header">
    <h1>Pencalon</h1>
</div>
<div class="panel panel-default">
    <div class="panel-heading">
        <form class="form-inline">
            <input type="hidden" name="m" value="pencalon" />
            <div class="form-group">
                <input class="form-control" type="text" placeholder="Pencarian. . ." name="q" value="<?=$_GET['q']?>" />
            </div>
            <div class="form-group <?=($_SESSION['akses']=='admin') ? '' : 'hidden'?>">
                <a class="btn btn-primary" href="?m=pencalon_tambah"><span class="glyphicon glyphicon-plus"></span> Tambah</a>
            </div>
            
        </form>
    </div>
    <table class="table table-bordered table-hover table-striped">
    <thead>
        <tr>
        
            <th bgcolor="#FF9900"><font color="#FFFFFF">Nomor Urut</font></th>
            <th bgcolor="#FF9900"><font color="#FFFFFF">Gambar</font></th>
            <th bgcolor="#FF9900" class="nw"><font color="#FFFFFF">Nama Pencalon</font></th>
            <th bgcolor="#FF9900"><font color="#FFFFFF">Keterangan</font></th>
            <th bgcolor="#FF9900"><font color="#FFFFFF">Total</font></th>
            <th bgcolor="#FF9900" class=" <?=($_SESSION['akses']=='admin') ? '' : 'hidden'?>"><font color="#FFFFFF">Aksi</font></th>
            
        </tr>
    </thead>
    <?php
    $q = esc_field($_GET['q']);
    $rows = $db->get_results("SELECT c.*, COUNT(p.ID) AS total FROM tb_pencalon c LEFT JOIN tb_pilih p ON p.id_pencalon=c.id_pencalon
        WHERE c.kode_pencalon LIKE '%$q%' 
            OR c.nama_pencalon LIKE '%$q%'
        GROUP BY c.id_pencalon
        ORDER BY c.kode_pencalon");
    foreach($rows as $row):?>
    <tr>
        <td><?=$row->kode_pencalon?></td>
        <td><img src="gambar/<?=$row->gambar?>" style="height: 100px;" /></td>
        <td><?=$row->nama_pencalon?></td>
        <td><?=$row->keterangan?></td>
        <td><?=number_format($row->total)?></td>
        <td class=" <?=($_SESSION['akses']=='admin') ? '' : 'hidden'?>">
            <a class="btn btn-xs btn-warning" href="?m=pencalon_ubah&ID=<?=$row->id_pencalon?>"><span class="glyphicon glyphicon-edit"></span></a>
            <a class="btn btn-xs btn-danger" href="aksi.php?act=pencalon_hapus&ID=<?=$row->id_pencalon?>" onclick="return confirm('Hapus data?')"><span class="glyphicon glyphicon-trash"></span></a>
        </td>
    </tr>
    <?php endforeach;
    ?>
    </table>
</div>