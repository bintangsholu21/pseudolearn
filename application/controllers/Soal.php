<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Soal extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        } else if (!$this->ion_auth->is_admin() && !$this->ion_auth->in_group('dosen')) {
            show_error('Hanya Administrator dan dosen yang diberi hak untuk mengakses halaman ini, <a href="' . base_url('dashboard') . '">Kembali ke menu awal</a>', 403, 'Akses Terlarang');
        }
        $this->load->library(['datatables', 'form_validation']); // Load Library Ignited-Datatables
        $this->load->helper('my'); // Load Library Ignited-Datatables
        $this->load->model('Master_model', 'master');
        $this->load->model('Soal_model', 'soal');
        $this->load->model('Level_model', 'level');
        $this->form_validation->set_error_delimiters('', '');
    }

    public function output_json($data, $encode = true)
    {
        if ($encode) $data = json_encode($data);
        $this->output->set_content_type('application/json')->set_output($data);
    }
    
    public function sourceconnection($id)
    {
        $user = $this->ion_auth->user()->row();
        $data = [
            'user'      => $user,
            'judul'     => 'Soal',
            'subjudul'  => 'Source Connection',
            'soal'      => $this->soal->getSoalById($id),
            'data_sourceconnection' => $this->db->get_where('log_data', ['id_soal'=> $id])->result(),
        ];
    
        if ($this->ion_auth->is_admin()) {
            $data['tb_level'] = $this->level->getAlllevel();
        } else {
            $data['tb_level'] = $this->level->getAlllevel();
        }
    
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('soal/sourceconnection', $data); // Pass $data to the view
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function sourceconnectionSaveTipeData() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get the POST data
            $id_soal = $this->input->post('id_soal');
            $id_user = $this->ion_auth->user()->row()->id;
            $sourceconnections = $this->input->post('tipe_data');
            
            // Check if $sourceconnections is an array and not empty
            if (is_array($sourceconnections) && !empty($sourceconnections)) {
                // Convert the source connections into a single string
                $sourceconnection = implode(' / ', $sourceconnections);
    
                // Check if data with the given id_soal already exists
                $existing_data = $this->db->get_where('log_data', ['id_soal', 'id' => $id_soal])->row();
                
                if ($existing_data) {
                    // If data already exists, update it
                    $this->db->where('id_soal', $id_soal);
                    $this->db->update('log_data', ['id_soal' => $id_soal, 'id_user' => $id_user, 'sourceconnection' => $sourceconnection]);
                } else {
                    // If data doesn't exist, insert it
                    $this->db->insert('log_data', ['id_soal' => $id_soal, 'id_user' => $id_user, 'sourceconnection' => $sourceconnection]);
                }
                    // Redirect atau berikan pesan sukses
                    $this->session->set_flashdata('message', '<div class="alert alert-success alert-dismissible" role="alert" style="position: relative; margin-left:10px; margin-right:10px; box-sizing: border-box;">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    Data berhasil disimpan!
                    </div>');
                    redirect('soal');
                // Redirect or show success message as needed
            } else {
            $this->load->view('soal/u114020995_pseudolearnapp');  

                // Handle the case where $sourceconnections is not valid
                // You might want to show an error message or redirect the user back to the form
        }
    }
}
            
        // In your controller or a helper file
        function splitDeskripsi($deskripsi, $delimiter = '/') {
            return explode($delimiter, $deskripsi);
        }

    public function index()
    {
        $user = $this->ion_auth->user()->row();
        $data = [
            'user' => $user,
            'judul'    => 'Soal',
            'subjudul' => 'Bank Soal'
        ];

        if ($this->ion_auth->is_admin()) {
            //Jika admin maka tampilkan semua matkul
            $data['level'] = $this->db->query('select * from tb_level')->result();
        }

        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('soal/data');
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function detail($id)
    {
        $user = $this->ion_auth->user()->row();
        $data = [
            'user'      => $user,
            'judul'        => 'Soal',
            'subjudul'  => 'Edit Soal',
            'soal'      => $this->soal->getSoalById($id),
        ];

        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('soal/detail');
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function add()
    {
        $user = $this->ion_auth->user()->row();
        $data = [
            'user'      => $user,
            'judul'        => 'Soal',
            'subjudul'  => 'Buat Soal'
        ];

        if ($this->ion_auth->is_admin()) {
            //Jika admin maka tampilkan semua matkul
            // $data['dosen'] = $this->soal->getAllDosen();
            $data['tb_level'] = $this->level->getAlllevel();
           
        } else {
            //Jika bukan maka matkul dipilih otomatis sesuai matkul dosen
            // $data['dosen'] = $this->soal->getMatkulDosen($user->username);
            $data['tb_level'] = $this->level->getAlllevel();
        }

        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('soal/add');
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function edit($id)
    {
        $user = $this->ion_auth->user()->row();
        $data = [
            'user'      => $user,
            'judul'        => 'Soal',
            'subjudul'  => 'Edit Soal',
            'soal'      => $this->soal->getSoalById($id),
        ];

        if ($this->ion_auth->is_admin()) {
            //Jika admin maka tampilkan semua matkul
            // $data['dosen'] = $this->soal->getAllDosen();
            $data['tb_level'] = $this->level->getAlllevel();
        } else {
            //Jika bukan maka matkul dipilih otomatis sesuai matkul dosen
            // $data['dosen'] = $this->soal->getMatkulDosen($user->username);
            $data['tb_level'] = $this->level->getAlllevel();
        }

        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('soal/edit');
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function data($id = null, $dosen = null)
    {
        $this->output_json($this->soal->getDataSoal($id, $dosen), false);
    }

    public function datalevel($id)
    {
        $this->output_json($this->soal->getDataSoalLevel($id), false);
    }

    public function validasi()
    {
        // if ($this->ion_auth->is_admin()) {
        //     $this->form_validation->set_rules('dosen_id', 'Dosen', 'required');
        // }
        // $this->form_validation->set_rules('soal', 'Soal', 'required');
        // $this->form_validation->set_rules('jawaban_a', 'Jawaban A', 'required');
        // $this->form_validation->set_rules('jawaban_b', 'Jawaban B', 'required');
        // $this->form_validation->set_rules('jawaban_c', 'Jawaban C', 'required');
        // $this->form_validation->set_rules('jawaban_d', 'Jawaban D', 'required');
        // $this->form_validation->set_rules('jawaban_e', 'Jawaban E', 'required');
        //$this->form_validation->set_rules('jawaban', 'Kunci Jawaban', 'required');
        $this->form_validation->set_rules('bobot', 'Bobot Soal', 'required|max_length[2]');
    }

    public function file_config()
    {
        $allowed_type     = [
            "image/jpeg", "image/jpg", "image/png", "image/gif",
            "audio/mpeg", "audio/mpg", "audio/mpeg3", "audio/mp3", "audio/x-wav", "audio/wave", "audio/wav",
            "video/mp4", "application/octet-stream"
        ];
        $config['upload_path']      = FCPATH . 'uploads/bank_soal/';
        $config['allowed_types']    = 'jpeg|jpg|png|gif|mpeg|mpg|mpeg3|mp3|wav|wave|mp4';
        $config['encrypt_name']     = TRUE;

        return $this->load->library('upload', $config);
    }

    public function save()
    {
        $method = $this->input->post('method', true);
        $this->validasi();
        $this->file_config();
        $id_soal = $this->input->post('id_soal', true);


        if ($this->form_validation->run() === FALSE) {
            $method === 'add' ? $this->add() : $this->edit($id_soal);
        } else {
            $data = [
                'soal'      => $this->input->post('soal', true),
                //'jawaban'   => $this->input->post('jawaban', true),
                'bobot'     => $this->input->post('bobot', true),
                'judul'     => $this->input->post('judul', true),
            ];

            $abjad = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n','o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];

            // Inputan Opsi
            foreach ($abjad as $abj) {
                $data['opsi_' . $abj]    = $this->input->post('jawaban_' . $abj, true);
            }

            $urut = [1, 2, 3, 4, 5,6,7,8];

            // Inputan Urutan dan variable
            foreach ($urut as $urt) {
                $data['variable_' . $urt]    = $this->input->post('variable_' . $urt, true);
                $data['jenis_data_v' . $urt]    = $this->input->post('tipe_data_' . $urt, true);
            }

            $urut = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n','o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];

            // Inputan Urutan dan checkbox hints
            foreach ($urut as $urt) {
                $data['urut_' . $urt]    = $this->input->post('urut_' . $urt, true);
                $data['clue_' . $urt]    = $this->input->post('chck_' . $urt, true);
            }

            $i = 0;
            foreach ($_FILES as $key => $val) {
                $img_src = FCPATH . 'uploads/bank_soal/';
                $getsoal = $this->soal->getSoalById($this->input->post('id_soal', true));

                $error = '';
                if ($key === 'file_soal') {
                    if (!empty($_FILES['file_soal']['name'])) {
                        if (!$this->upload->do_upload('file_soal')) {
                            $error = $this->upload->display_errors();
                            show_error($error, 500, 'File Soal Error');
                            exit();
                        } else {
                            if ($method === 'edit') {
                                if (!unlink($img_src . $getsoal->file)) {
                                    show_error('Error saat delete gambar <br/>' . var_dump($getsoal), 500, 'Error Edit Gambar');
                                    exit();
                                }
                            }
                            $data['file'] = $this->upload->data('file_name');
                            $data['tipe_file'] = $this->upload->data('file_type');
                        }
                    }
                } else {
                    $file_abj = 'file_' . $abjad[$i];
                    if (!empty($_FILES[$file_abj]['name'])) {
                        if (!$this->upload->do_upload($key)) {
                            $error = $this->upload->display_errors();
                            show_error($error, 500, 'File Opsi ' . strtoupper($abjad[$i]) . ' Error');
                            exit();
                        } else {
                            if ($method === 'edit') {
                                if($getsoal->$file_abj != '') {
                                    if (!unlink($img_src . $getsoal->$file_abj)) {
                                        show_error('Error saat delete gambar', 500, 'Error Edit Gambar');
                                        exit();
                                    }
                                }
                            }
                            $data[$file_abj] = $this->upload->data('file_name');
                        }
                    }
                    $i++;
                }
            }

            if ($this->ion_auth->is_admin()) {
                $pecah = $this->input->post('dosen_id', true);
                $pecah = explode(':', $pecah);
                // $data['dosen_id'] = $pecah[0];
                // $data['matkul_id'] = end($pecah);
                $data['id_level'] = $this->input->post('id_level', true);
            } else {
                // $data['dosen_id'] = $this->input->post('dosen_id', true);
                // $data['matkul_id'] = $this->input->post('matkul_id', true);
                $data['id_level'] = $this->input->post('id_level', true);
            }

            $essay = array(
                'id_level' => $this->input->post('id_level', true),
                'jenis_program' => $this->input->post('jenis_program', true),
            );

            if ($method === 'add') {
                //push array
                $data['created_on'] = time();
                $data['updated_on'] = time();
                $data['jenis_program'] = $this->input->post('jenis_program', true);

                $this->soal->insert_soal($data);

                $insert_data = array(
                    'id_soal' => $this->db->insert_id($data),
                    'id_level' => $this->input->post('id_level', true),
                    'status' => 0,
                    'jenis_program' => $this->input->post('jenis_program', true),
                    'created_on' => time(),
                    'updated_on' => time(),
                );

                $this->db->insert('tb_essay', $insert_data);
                //insert data
                // $this->master->create('tb_soal', $data);
            } else if ($method === 'edit') {
                //push array
                $data['updated_on'] = time();
                $data['jenis_program'] = $this->input->post('jenis_program', true);
                //update data
                $id_soal = $this->input->post('id_soal', true);
                $this->master->update('tb_essay', $essay, 'id_soal', $id_soal);
                $this->master->update('tb_soal', $data, 'id_soal', $id_soal);
            } else {
                show_error('Method tidak diketahui', 404);
            }
            $this->session->set_flashdata('message', '<div class="alert alert-success alert-dismissible" role="alert" style="position: relative; margin-left:10px; margin-right:10px; box-sizing: border-box;">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            Data berhasil disimpan!
          </div>');
            redirect('soal');
        }
    }

    public function delete()
    {
        $chk = $this->input->post('checked', true);

        // Delete File
        foreach ($chk as $id) {
            $abjad = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n','o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
            $path = FCPATH . 'uploads/bank_soal/';
            $soal = $this->soal->getSoalById($id);
            // Hapus File Soal
            if (!empty($soal->file)) {
                if (file_exists($path . $soal->file)) {
                    unlink($path . $soal->file);
                }
            }
            //Hapus File Opsi
            $i = 0; //index
            foreach ($abjad as $abj) {
                $file_opsi = 'file_' . $abj;
                if (!empty($soal->$file_opsi)) {
                    if (file_exists($path . $soal->$file_opsi)) {
                        unlink($path . $soal->$file_opsi);
                    }
                }
            }
        }

        if (!$chk) {
            $this->output_json(['status' => false]);
        } else {
            if ($this->master->delete('tb_soal', $chk, 'id_soal')) {
                $this->output_json(['status' => true, 'total' => count($chk)]);
            }
        }
    }
}
