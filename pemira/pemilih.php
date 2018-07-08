<div class="page-header">
    <h1>Pemilih</h1>
</div>
<div class="panel panel-default">
    <div class="panel-heading">        
        <form class="form-inline">
            <input type="hidden" name="m" value="pemilih" />
            <div class="form-group">
                <input class="form-control" type="text" placeholder="Pencarian. . ." name="q" value="<?=$_GET['q']?>" />
            </div>

            <div class="form-group  <?=($_SESSION['akses']=='admin') ? '' : 'hidden'?>">
                <a class="btn btn-primary" href="?m=pemilih_tambah"><span class="glyphicon glyphicon-plus"></span> Tambah</a>
            </div>
          
        </form>
    </div>
    <table class="table table-bordered table-hover table-striped">
    <thead>
        <tr>
            <th  bgcolor="#FF9900"><font color="#FFFFFF">No</font></th>
            <th  bgcolor="#FF9900"><font color="#FFFFFF">KTP</font></th>
            <th  bgcolor="#FF9900"><font color="#FFFFFF">Nama Pemilih</font></th>
            <th  bgcolor="#FF9900"><font color="#FFFFFF">Jurusan</font></th>
            <th  bgcolor="#FF9900"><font color="#FFFFFF">Status</font></th>
            <th class=" <?=($_SESSION['akses']=='admin') ? '' : 'hidden'?>"  bgcolor="#FF9900"><font color="#FFFFFF">Aksi</font></th>
        </tr>
    </thead>
    <?php
    $q = esc_field($_GET['q']);
    $rows = $db->get_results("SELECT m.*, p.ID AS pilih FROM tb_pemilih m LEFT JOIN tb_pilih p ON p.id_pemilih=m.id_pemilih WHERE nama_pemilih LIKE '%$q%' ORDER BY m.id_pemilih");
    $no=0;
    foreach($rows as $row):?>
    <tr>
        <td><?=++$no ?></td>
        <td><?=$row->ktp?></td>
        <td><?=$row->nama_pemilih?></td>
        <td><?=$row->alamat?></td>
        <td><?=($row->pilih) ? '<span class="glyphicon glyphicon-check"></span>' : ''?></td>
        <td class=" <?=($_SESSION['akses']=='admin') ? '' : 'hidden'?>">
            <a class="btn btn-xs btn-warning" href="?m=pemilih_ubah&ID=<?=$row->id_pemilih?>"><span class="glyphicon glyphicon-edit"></span></a>
            <a class="btn btn-xs btn-danger" href="aksi.php?act=pemilih_hapus&ID=<?=$row->id_pemilih?>" onclick="return confirm('Hapus data?')"><span class="glyphicon glyphicon-trash"></span></a>
        </td>
    </tr>
    <?php endforeach;
    ?>
    </table>
</div>