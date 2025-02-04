<div class="row">
    <div class="col-sm-12">
    <?= form_open_multipart('soal/sourceconnectionSaveTipeData', array('id' => 'formsoal'), array('method' => 'edit', 'id_soal' => $soal->id_soal)); ?>
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title"><?= $subjudul ?></h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-8 col-sm-offset-2">
                    <?php
                    // Langkah 1: Query untuk mengambil data terbaru berdasarkan id_user dan id_soal
                        $this->db->where('id_soal', $soal->id_soal);
                        $this->db->where('id_user', 1); // Tambahkan filter berdasarkan id_user 1
                        $this->db->where('sourceconnection IS NOT NULL', null, false);
                        $this->db->order_by('id', 'DESC'); // Mengurutkan berdasarkan id secara descending untuk mendapatkan data terbaru
                        $this->db->limit(1); // Hanya mengambil satu data terbaru
                        $data_sourceconnection = $this->db->get('log_data')->result();
                        // Proses data dan tampilkan dalam modal atau elemen HTML lainnya sesuai kebutuhan
                        if (empty($data_sourceconnection)) {
                            // Jika tidak ada data untuk id_user = 1, tampilkan deskripsi kosong
                            echo '<div class="col-sm-12">
                                    <label for="file">Source Connection</label>
                                    <div class="form-group pull-right">
                                        <input name="tipe_data[]" id="tipe_data" style="width:905px;" class="form-control" value="' . set_value('tipe_data') . '">
                                        <small class="help-block pull-right" style="color: #dc3545">' . form_error('tipe_data') . '</small>
                                    </div>
                                  </div>';
                        } else {
                            foreach ($data_sourceconnection as $key => $value) {
                                // Tampilkan data yang diambil dalam input textbox
                                echo '<div class="col-sm-12">
                                        <label for="file">Source Connection</label>
                                        <div class="form-group">
                                            <input name="tipe_data[]" id="tipe_data" style="width:905px;" class="form-control" value="' . htmlspecialchars($value->sourceconnection) . '">
                                            <small class="help-block" style="color: #dc3545">' . form_error('variable_' . $key) . '</small>
                                        </div>
                                      </div>';
                            }
                        }
                    ?>
                            
                        <!-- 
                            Source Connection 
                        -->
                        <div class="col-sm-12">
                            <div id="wrapperone">
                                <div class="row" id="formvartipe_1">
                                </div>
                            </div>
                            <div class="form-group pull-right">
                                <a href="<?= base_url('soal') ?>" class="btn btn-flat btn-default"><i class="fa fa-arrow-left"></i> Batal</a>
                                <button type="submit" id="submit" class="btn btn-flat bg-purple"><i class="fa fa-save"></i> Simpan</button>
                                <!-- <button onclick="addOption('vartipe')" type="button" class="btn btn-flat bg-red">Tambah Data</button> -->
                                <!-- <button onclick="removeOption('vartipe')" type="button" class="btn btn-flat bg-yellow">Hapus Data</button> -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?= form_close(); ?>
    </div>
</div>

<script>
    
    // const addOption = (type) => {
    //     if (type == "vartipe") {
    //         const currentIndex = $("[id*='formvartipe_']").length + 1;
    //         if(currentIndex == 9) return;
    //         const elemt = `<div class="row" id="formvartipe_${currentIndex}">
    //             <div class="col-xs-6">
    //                 <label for="file">Source Connection ${currentIndex}</label>
    //                 <div class="form-group">
    //                     <input name="tipe_data[]" id="tipe_data" style="width:905px"; class="form-control" value="<?= set_value('tipe_data') ?>">
    //                     <small class="help-block" style="color: #dc3545"><?= form_error('variable_${currentIndex}') ?></small>
    //                 </div>
    //             </div>
    //         </div>`;
    //         $("#wrapperone").append(elemt);
    //     }
    // }
    // const removeOption = (type) => {
    //     if (type == 'vartipe') {
    //         const currentIndex = $("[id*='formvartipe_']").length;
    //         if(currentIndex == 1) return;
    //         $(`#formvartipe_${currentIndex}`).remove();
    //     }else if(type == 'answer'){
    //         const currentIndex = $("[id*='formanswer_']").length;
    //         if(currentIndex == 1) return;
    //         $(`#formanswer_${currentIndex}`).remove();
    //     }else if(type == 'keyanswer') {
    //         const currentIndex = $("[id*='formkeyanswer_']").length;
    //         if(currentIndex == 1) return;
    //         $(`#formkeyanswer_${currentIndex}`).remove();
    //     }
    // }
</script>