<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Ujian extends CI_Controller
{

	public $mhs, $user;

	public function __construct()
	{
		parent::__construct();
		if (!$this->ion_auth->logged_in()) {
			redirect('auth');
		}
		$this->load->library(['datatables', 'form_validation']); // Load Library Ignited-Datatables
		$this->load->helper('my');
		$this->load->model('Master_model', 'master');
		$this->load->model('Soal_model', 'soal');
		$this->load->model('Ujian_model', 'ujian');
		$this->load->model('Level_model', 'level');
		$this->form_validation->set_error_delimiters('', '');

		$this->user = $this->ion_auth->user()->row();
		$this->mhs 	= $this->ujian->getIdMahasiswa($this->user->username);
	}

	public function akses_dosen()
	{
		if (!$this->ion_auth->in_group('dosen')) {
			show_error('Halaman ini khusus untuk dosen untuk membuat Test Online, <a href="' . base_url('dashboard') . '">Kembali ke menu awal</a>', 403, 'Akses Terlarang');
		}
	}

	public function akses_mahasiswa()
	{
		if (!$this->ion_auth->in_group('mahasiswa')) {
			show_error('Halaman ini khusus untuk mahasiswa mengikuti ujian, <a href="' . base_url('dashboard') . '">Kembali ke menu awal</a>', 403, 'Akses Terlarang');
		}
	}

	public function output_json($data, $encode = true)
	{
		if ($encode) $data = json_encode($data);
		$this->output->set_content_type('application/json')->set_output($data);
	}

	// public function json($id = null)
	// {
	// 	$this->akses_dosen();

	// 	$this->output_json($this->ujian->getDataUjian($id), false);
	// }

	public function convert_tgl($tgl)
	{
		$this->akses_dosen();
		return date('Y-m-d H:i:s', strtotime($tgl));
	}

	/**
	 * BAGIAN MAHASISWA
	 */

	public function list_json_old()
	{
		$this->akses_mahasiswa();

		$list = $this->ujian->getListUjian($this->mhs->id_mahasiswa, $this->mhs->kelas_id);

		$this->output_json($list, false);
	}

	public function list_json($id_level = null)
	{
		$this->akses_mahasiswa();
		$mhs = $this->mhs;
		// print_r($mhs);
		$data = $this->ujian->getListUjian($id_level, $mhs->id_mahasiswa);
		// for ($i = 0; $i < count($data); $i++) {
		// 	$data[$i]->id_ujian_enc = urlencode($this->encryption->encrypt($data[$i]->id_ujian));
		// }

		echo json_encode($data);
	}

	public function list_level()
	{
		$this->akses_mahasiswa();

		$user = $this->ion_auth->user()->row();

		$data = [
			'user' 		=> $user,
			'judul'		=> 'Soal',
			'subjudul'	=> 'List Soal',
			'mhs' 		=> $this->ujian->getIdMahasiswa($user->username),
			'total'     => $this->db->query('select sum(nilai) as total from nilai where id_user = ?', $user->id)->row_array()['total'],
			'totalessay' => $this->db->query('select sum(nilaiessay) as totalessay from nilai_essay where id_user = ?', $user->id)->row_array()['totalessay'],
			'totalnilai' => $this->db->query('select sum(bobot) as totalnilai from tb_essay')->row_array()['totalnilai'],
			'totaldnd' => $this->db->query('select sum(bobot) as totaldnd from tb_soal')->row_array()['totaldnd']

		];
		$this->load->view('_templates/dashboard/_header.php', $data);
		$this->load->view('ujian/list');
		$this->load->view('_templates/dashboard/_footer.php');
	}

	public function list_ujian()
	{
		$this->akses_mahasiswa();

		$user = $this->ion_auth->user()->row();

		$data = [
			'user' 		=> $user,
			'judul'		=> 'Soal',
			'subjudul'	=> 'List Soal',
			'mhs' 		=> $this->ujian->getIdMahasiswa($user->username),
			'total'     => $this->db->query('select sum(nilai) as total from nilai where id_user = ?', $user->id)->row_array()['total'],
			'totalessay' => $this->db->query('select sum(nilaiessay) as totalessay from nilai_essay where id_user = ?', $user->id)->row_array()['totalessay'],
			'totalnilai' => $this->db->query('select sum(bobot) as totalnilai from tb_essay')->row_array()['totalnilai'],
			'totaldnd' => $this->db->query('select sum(bobot) as totaldnd from tb_soal')->row_array()['totaldnd']
		];
		$this->load->view('_templates/dashboard/_header.php', $data);
		$this->load->view('ujian/list_ujian');
		$this->load->view('_templates/dashboard/_footer.php');
	}

	public function encrypt()
	{
		$id = $this->input->post('id', true);
		$key = urlencode($this->encryption->encrypt($id));
		// $decrypted = $this->encryption->decrypt(rawurldecode($key));
		$this->output_json(['key' => $key]);
	}

	public function soal()
	{
		$this->akses_mahasiswa();
		$user = $this->ion_auth->user()->row();
		$data = [
			'user' 		=> $user,
			'judul'		=> 'Ujian',
			'subjudul'	=> 'Token Ujian',
			'mhs' 		=> $this->ujian->getIdMahasiswa($user->username)
		];
		$this->load->view('_templates/topnav/_header.php', $data);
		$this->load->view('ujian/soal');
		$this->load->view('_templates/topnav/_footer.php');
	}

	public function save_history($id_soal)
	{
		$id_user = $this->session->userdata('user_id');
		$user = $this->db->query('select * from users where id = ?', $id_user)->row_array();
		$soal = $this->db->query('select * from tb_soal where id_soal = ?', $id_soal)->row_array();
		$count_data = $this->db->query('SELECT * FROM history_ujian WHERE idsoal = ? and iduser = ?', [$id_soal, $id_user])->num_rows();
		if ($count_data === 0) {
			$this->db->insert('history_ujian', [
				'idsoal' => $id_soal,
				'iduser' => $id_user,
			]);
		}
		$this->session->sess_expiration = 0; // expires in 4 hours
	}

	public function save_percobaan($id_soal)
	{
		// Decrypt Id
		$id_user = $this->session->userdata('user_id');
		$soal = $this->db->query('select * from tb_soal where id_soal = ?', $id_soal)->row_array();
		$click = $this->db->query('select * from history_percobaan where id_soal = ? and id_user = ?', [$id_soal, $id_user])->num_rows();
		$data['id_user'] = $id_user;
		$data['id_soal'] = $id_soal;
		$data['id_level'] = $soal['id_level'];
		$data['jumlah'] = $click + 1;
		$this->db->insert('history_percobaan', $data);
		$this->session->sess_expiration = 0; // expires in 4 hours
		$this->output_json(['status' => true]);
	}

	function save_confidence($id_soal)
	{
		$id_user = $this->session->userdata('user_id');
		$click = $this->db->query('select * from confidence_tag where id_soal = ? and id_user = ?', [$id_soal, $id_user])->num_rows();
		$data['id_user'] = $id_user;
		$data['id_soal'] = $id_soal;
		$data['confidence'] = $this->input->post('confidence');
		$data['waktu'] = $this->input->post('waktu');
		$this->db->insert('confidence_tag', $data);
		$this->session->sess_expiration = 0; // expires in 4 hours
		$this->session->set_userdata(array(
			'confidence' => $this->input->post('confidence'),
			'waktu' => $this->input->post('waktu')
		));
	}

	function save_condition($id_soal)
	{
		$id_user = $this->session->userdata('user_id');
		$confidences = $this->session->userdata('confidence_id');
		// $click = $this->db->query('select * from users where id = ?', $id_user)->row_array();
		// $confidences = $this->db->query('SELECT DISTINCT(id) FROM confidence_tag', [$id_soal, $id_user])->row_array();
		$this->db->insert('conditions', [
			'id_soal' => $id_soal,
			'id_user' => $id_user,
			// 'username' => $click['username'],
			'status_jawaban' => $this->input->post('condition'),
			'confidence' => $this->session->confidence,
			'waktu' => $this->session->waktu,
		]);
		$this->session->sess_expiration = 0; // expires in 4 hours
		$this->output_json(['status' => true]);
	}
	
	function generateModalTipeData($no, $value, $id_soal) {
		// Ambil data dari sourceconnection berdasarkan id_soal dengan batasan 1
		$sourceconnections = $this->db
			->select('sourceconnection')
			->where('sourceconnection IS NOT NULL', null, false)
			->where('id_soal', $id_soal) // Filter berdasarkan id_soal
			->where('id_user', 1) // Pastikan untuk mengurutkan data berdasarkan ID secara descending agar yang terakhir muncul pertama
			//Ambil data dari input id_user 1 yang terakhir
			->order_by('id', 'DESC')
			->limit(1)
			->get('log_data')
			->result_array();
		// var_dump($sourceconnections);
		// Inisialisasi modal HTML
		$modal = '<div class="modal fade" id="modal' . $no . '" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
			<div class="modal-dialog" role="document">
				<div class="modal-content" style="width:auto; height:900px;">
					<div class="modal-header">
						<h5 class="modal-title" id="exampleModalLabel" style="text-align:center">Pilih Deskripsi</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					
					<div class="modal-body" style="width:auto; height:700px; text-align:center; padding-right: 40px;">';
					// tampilkan value
					$modal .= '<input type="hidden" name="tipe_data_jawaban" id="tipe_data_jawaban' . $no . '" value=\'{"jenis_' . $no . '":"' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"}\'>'; // input isi value

	
		// Loop through each sourceconnection
		foreach ($sourceconnections as $sourceconnection) {
			// Pisahkan sourceconnection menjadi baris-baris
			$descriptions = explode('/', $sourceconnection['sourceconnection']);
			foreach ($descriptions as $index => $description) {
				// Tambahkan radio button ke dalam modal body dengan ID yang unik
				$modal .= '<div class="form-check" style="margin-left: 20px;">
								<input class="form-check-input" type="radio" value="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '" name="deskripsi" id="deskripsi' . $no . '_' . $index . '">
								<label class="form-check-label" for="deskripsi' . $no . '_' . $index . '">' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '</label>
							</div>';
			}
		}
		
		$modal .= '</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-primary" data-dismiss="modal" onclick="pilihDeskripsi(' . $no . ')">Pilih</button>
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
					</div>
				</div>
			</div>                
		</div>';
	
		return $modal;
	}
	 
	public function pilihDeskripsi($no) {
	    //Data terakhir table log_data = 
		$id_soal = $this->input->post('id_soal');
		$sourceconnection = $this->input->post('sourceconnection');
		//get id table log_data = max id where id_soal = $id_soal
		$table = $this->db->query('select * from log_data where id_soal = ? order by id desc limit 1', $id_soal)->row_array();
		//id
		$id = $table['id'];
		//update table log_data
		$this->db->where('id', $id);
		$this->db->update('log_data', ['sourceconnection' => $sourceconnection]);
		// Kirim pesan JSON ke client bahwa deskripsi telah dipilih
		$this->output_json([
			'status' => true,
			 'data' => $this->db->query('select * from log_data where id = ?', $id)->row_array()	
		]);
	}

	public function index()
	{
		$this->akses_mahasiswa();
		$id = $this->input->get('key', true);

		$soal 		= $this->ujian->getSoal($id);
		//$soal_urut_ok = $soal;
		//echo "tes";
		//print_r($soal_urut_ok);

		$mhs		= $this->mhs;
		$levelId = 0;
		$i = 0;
		foreach ($soal as $s) {
			$levelId = $s->id_level;
			$soal_per = new stdClass();
			$soal_per->id_soal 		= $s->id_soal;
			$soal_per->id_level 	= $s->id_level;
			$soal_per->soal 		= $s->soal;
			$soal_per->judul 		= $s->judul;
			$soal_per->opsi_a 		= $s->opsi_a;
			$soal_per->opsi_b 		= $s->opsi_b;
			$soal_per->opsi_c 		= $s->opsi_c;
			$soal_per->opsi_d 		= $s->opsi_d;
			$soal_per->opsi_e 		= $s->opsi_e;
			$soal_per->opsi_f 		= $s->opsi_f;
			$soal_per->opsi_g 		= $s->opsi_g;
			$soal_per->opsi_h 		= $s->opsi_h;
			$soal_per->opsi_i 		= $s->opsi_i;
			$soal_per->opsi_j 		= $s->opsi_j;
			$soal_per->opsi_k 		= $s->opsi_k;
			$soal_per->opsi_l 		= $s->opsi_l;
			$soal_per->opsi_m 		= $s->opsi_m;
			$soal_per->opsi_n 		= $s->opsi_n;
			$soal_per->opsi_o 		= $s->opsi_o;
			$soal_per->opsi_p 		= $s->opsi_p;
			$soal_per->opsi_q 		= $s->opsi_q;
			$soal_per->opsi_r 		= $s->opsi_r;
			$soal_per->opsi_s 		= $s->opsi_s;
			$soal_per->opsi_t 		= $s->opsi_t;
			$soal_per->opsi_u 		= $s->opsi_u;
			$soal_per->opsi_v 		= $s->opsi_v;
			$soal_per->opsi_w 		= $s->opsi_w;
			$soal_per->opsi_x 		= $s->opsi_x;
			$soal_per->opsi_y 		= $s->opsi_y;
			$soal_per->opsi_z 		= $s->opsi_z;
			$soal_per->urut_a 			= $s->urut_a;
			$soal_per->urut_b 			= $s->urut_b;
			$soal_per->urut_c 			= $s->urut_c;
			$soal_per->urut_d 			= $s->urut_d;
			$soal_per->urut_e 			= $s->urut_e;
			$soal_per->urut_f 			= $s->urut_f;
			$soal_per->urut_g 			= $s->urut_g;
			$soal_per->urut_h 			= $s->urut_h;
			$soal_per->urut_i 			= $s->urut_i;
			$soal_per->urut_j 			= $s->urut_j;
			$soal_per->urut_k 			= $s->urut_k;
			$soal_per->urut_l 			= $s->urut_l;
			$soal_per->urut_m 			= $s->urut_m;
			$soal_per->urut_n 			= $s->urut_n;
			$soal_per->urut_o 			= $s->urut_o;
			$soal_per->urut_p 			= $s->urut_p;
			$soal_per->urut_q 			= $s->urut_q;
			$soal_per->urut_r 			= $s->urut_r;
			$soal_per->urut_s 			= $s->urut_s;
			$soal_per->urut_t 			= $s->urut_t;
			$soal_per->urut_u 			= $s->urut_u;
			$soal_per->urut_v 			= $s->urut_v;
			$soal_per->urut_w 			= $s->urut_w;
			$soal_per->urut_x 			= $s->urut_x;
			$soal_per->urut_y 			= $s->urut_y;
			$soal_per->urut_z 			= $s->urut_z;
			$soal_per->clue_a 			= $s->clue_a;
			$soal_per->clue_b 			= $s->clue_b;
			$soal_per->clue_c 			= $s->clue_c;
			$soal_per->clue_d 			= $s->clue_d;
			$soal_per->clue_e 			= $s->clue_e;
			$soal_per->clue_f 			= $s->clue_f;
			$soal_per->clue_g 			= $s->clue_g;
			$soal_per->clue_h 			= $s->clue_h;
			$soal_per->clue_i 			= $s->clue_i;
			$soal_per->clue_j 			= $s->clue_j;
			$soal_per->clue_k 			= $s->clue_k;
			$soal_per->clue_l 			= $s->clue_l;
			$soal_per->clue_m 			= $s->clue_m;
			$soal_per->clue_n 			= $s->clue_n;
			$soal_per->clue_o 			= $s->clue_o;
			$soal_per->clue_p 			= $s->clue_p;
			$soal_per->clue_q 			= $s->clue_q;
			$soal_per->clue_r 			= $s->clue_r;
			$soal_per->clue_s 			= $s->clue_s;
			$soal_per->clue_t 			= $s->clue_t;
			$soal_per->clue_u 			= $s->clue_u;
			$soal_per->clue_v 			= $s->clue_v;
			$soal_per->clue_w 			= $s->clue_w;
			$soal_per->clue_x 			= $s->clue_x;
			$soal_per->clue_y 			= $s->clue_y;
			$soal_per->clue_z 			= $s->clue_z;
			$soal_per->variable_1 			= $s->variable_1;
			$soal_per->variable_2 			= $s->variable_2;
			$soal_per->variable_3 			= $s->variable_3;
			$soal_per->variable_4 			= $s->variable_4;
			$soal_per->variable_5 			= $s->variable_5;
			$soal_per->variable_6 			= $s->variable_6;
			$soal_per->variable_7 			= $s->variable_7;
			$soal_per->variable_8 			= $s->variable_8;
			$soal_per->jenis_data_v1 			= $s->jenis_data_v1;
			$soal_per->jenis_data_v2 			= $s->jenis_data_v2;
			$soal_per->jenis_data_v3 			= $s->jenis_data_v3;
			$soal_per->jenis_data_v4 			= $s->jenis_data_v4;
			$soal_per->jenis_data_v5 			= $s->jenis_data_v5;
			$soal_per->jenis_data_v6 			= $s->jenis_data_v6;
			$soal_per->jenis_data_v7 			= $s->jenis_data_v7;
			$soal_per->jenis_data_v8 			= $s->jenis_data_v8;
			$soal_urut_ok[$i] 		= $soal_per;
			$i++;
		}

		$arr_opsi = array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z");
		$arr_clue = array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z");
		$var_opsi = array(1, 2, 3, 4, 5, 6, 7, 8);
		$jenis_opsi = array(1, 2, 3, 4, 5, 6, 7, 8);
		// shuffle($var_opsi);
		// shuffle($jenis_opsi);
		$var_count = 8;
		$jenis_count = 8;
		$html = '';
		$no = 1;
		$feedback = $this->ujian->getLevelFeedback($levelId);
		if ($feedback) {
			$feedback = [
				'tipe_data' => $feedback->tipe_data, 'input' => $feedback->input, 'process' => $feedback->process, 'output' => $feedback->output
			];
		} else {
			$feedback = [
				'tipe_data' => '', 'input' => '', 'process' => '', 'output' => ''
			];
		}
		//$feedbackStr = '';
		// if (!empty($soal_urut_ok)) {
		foreach ($soal_urut_ok as $s) {
			//print_r($soal_urut_ok);
			//echo "tes1";
			$path = 'uploads/bank_soal/';
			$html .= '<input type="hidden" id="id_soal" name="id_soal_' . $no . '" value="' . $s->id_soal . '">';
			$html .= '<input type="hidden" id="id_user" name="user-id" value="' . $mhs->id_mahasiswa . '">';
			$html .= '<input type="hidden" name="rg_' . $no . '" id="rg_' . $no . '" value="r">';
			$html .= '<div class="step" id="widget_' . $no . '">';
			$html .= '<main class="quiz">
				<div class="quiz__description description">
			
					<p class="description__question">';
			$html .= '<div class="text-center"><div class="w-25"></div></div>' . $s->soal . '<div class="funkyradio"></p>';
			// ubah warna disini
			$html .= '<div class="description__data-type data-type" style="margin-left: -3px; width:auto; background-color: #ECF0F5;border-color:#afb1b5">
				<h4 class="data-type__title" style="background-color: #102C57;"><b>Tipe Data</b></h4>
				<ul class="data-type__items">';
			for ($i = 0; $i < $var_count; $i++) {
				$var = "jenis_data_v" . $var_opsi[$i];
				!empty($s->$var)? 
				
				// tambahkan onclick
				$html .= '<li class="data-type__item draggable" id="opsi_jenis_' . $var_opsi[$i] . '" value="' . $s->$var . '" onclick="showModal(' . $no . ', \'' . $s->$var . '\')" style="cursor: pointer;">' . $s->$var . '</li>' : '';
				$html .= $this->generateModalTipeData($no, $s->$var, $s->id_soal);
				// Script JavaScript
				$html .= '<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>';
				$html .= '<script>
				
				function handleDrop(event) {
					event.preventDefault();
					var value = event.dataTransfer.getData("text");
					var no = 1; // Nomor modal yang akan ditampilkan, kamu mungkin perlu mengatur ini berdasarkan konteks aplikasimu
					showModal(no, value); // Menampilkan modal dengan nilai yang di-drop
				}
				
				// Fungsi untuk menampilkan modal dengan nomor dan nilai tertentu
				function showModal(no, value) {
					var modalID = "modal" + no;
					var id_user = $("#id_user").val();
					var modalTitle = "Source Connection: " + value + " (User ID: " + id_user + ")";
					$("#" + modalID + " .modal-title").html(modalTitle);
					$("#" + modalID).modal("show");
					
					var formattedValue = \'{"jenis_\' + no + \'":"\' + value + \'"}\';
					// $("#tipe_data_jawaban" + no).val(formattedValue);
				}
				
				// Buatkan Ajax ketika pilihDeskripsi di klik
				function pilihDeskripsi(no) {
					var sourceconnection = $("input[name=deskripsi]:checked").val();
					var id_soal = $("#id_soal").val();
					var id_user = $("#id_user").val();
					// var tipe_data_jawaban = $("#tipe_data_jawaban" + no).val();
					// var waktu = $("#waktu").val();
					// var status_jawaban_tipedata = $("#status_jawaban_tipedata").val();
					// var status_jawaban = $("#status_jawaban").val();
					// var status_jawaban_algoritma = $("#status_jawaban_algoritma").val();
					// var jawaban = $("#jawaban").val();
					// var detail_jawaban_tipedata = $("#detail_jawaban_tipedata").val();
					// var detail_jawaban_algoritma = $("#detail_jawaban_algoritma").val();
					// var is_submit = $("#is_submit").val();
				
					// Kirim nilai tersebut ke server 
					$.ajax({
						url: "' . base_url('ujian/pilihDeskripsi/') . '" + no,
						type: "POST",
						data: {
							sourceconnection: sourceconnection,
							id_soal: id_soal,
							id_user: id_user,
							// tipe_data_jawaban: tipe_data_jawaban,
							// waktu: waktu,
							// status_jawaban_tipedata: status_jawaban_tipedata,
							// status_jawaban: status_jawaban,
							// status_jawaban_algoritma: status_jawaban_algoritma,
							// jawaban: jawaban,
							// detail_jawaban_tipedata: detail_jawaban_tipedata,
							// detail_jawaban_algoritma: detail_jawaban_algoritma,
							// is_submit: is_submit
						},
						success: function(response) {
							// $("#tipe_data_jawaban" + no).val(response.tipe_data_jawaban_baru);
						},
						error: function(xhr, status, error) {
							// Handle error
						}
					});
				}
	
				document.addEventListener("DOMContentLoaded", function() {
					// Event listener untuk mendeteksi drop
					document.addEventListener("drop", handleDrop);
					
					// Event listener untuk mencegah default behavior saat dragover
					document.addEventListener("dragover", function(event) {
						event.preventDefault();
					});
				
					// Membuat fungsi confident saat di drop
					$(".data-type__item").on("dragstart", function(event) {
						event.dataTransfer.setData("text", event.target.innerHTML);
					});
				
					
				});
				</script>';	
			}
			$html .= '</ul>
						</div>';
			// ubah warna disini
			$html .= '<div class="description__algorithm algorithm" style="margin-left: -3px; width:auto; background-color: #ECF0F5;border-color:#afb1b5">
						<h4 class="algorithm__title" style="background-color: #102C57;"><b>Algoritma</b></h4>
						<ul class="algorithm__items">';
			for ($j = 0; $j < $this->config->item('jml_opsi'); $j++) {
				$array_clues = [];
				for ($k = 0; $k < $this->config->item('jml_opsi'); $k++) {
					$isClue = "clue_" . $arr_clue[$k];
					$clues = $s->$isClue;
					if ($clues) {
						$clues = 'opsi_' . $s->$clues;
						array_push($array_clues, $clues);
					}
				}
				$opsi 			= "opsi_" . $arr_opsi[$j];
				if (!in_array($opsi, $array_clues)) {
					$pilihan_opsi 	= !empty($s->$opsi) ? $s->$opsi : "";

					!empty($s->$opsi) ? $html .= '<li class="algorithm__item draggable" style="width:300px; margin-bottom: 5px; margin-top: 5px; color: white;" id="opsi_' . strtolower($arr_opsi[$j]) . '">' . $pilihan_opsi . '</li>' : '';
				}
			}
			$html .= '</ul>
						</div>
						
					</div>
					</div>
					<div class="quiz__answer answer" style="width: 40rem; margin-top: 1.4rem;">
						<table class="answer__content">
							<tbody>
								<tr>  
								<div style="width: 100%; background: #102C57;  text-align: center; padding: 1px; color: white; border-radius: 10px;">
								<h4 style="font-weight: bold;">Tipe Data</h4>
								</div>
								</tr>
								<tr> 
									<td style="background: #ECF0F5;">
										<table>
											<tbody>';
			for ($i = 0; $i < $jenis_count; $i++) {
				$var = "variable_" . $jenis_opsi[$i];
				!empty($s->$var) ? $html .= '<tr style="height: 40px;"><th>' . $s->$var . '</th><td class="drop-zone" style="background: #ECF0F5;" id="jenis_' . $jenis_opsi[$i] . '"></td>
											</tr>' : '';
			}
			$html .= '</tbody>
										</table>
									</td>
								</tr>
							</tbody>
						</table>
						<table class="answer__content">
							<div style="width: 100%; background: #102C57;  text-align: center; padding: 1px; color: white; border-radius: 10px;">
							<h4 style="font-weight: bold;">Algoritma</h4>
							</div>
							<tbody>
								<tr>
								
									
								</tr>';
			if ($s->clue_a) {
				$clue = $s->clue_a;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_a) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;" id="jawaban_' . $s->urut_a . '"></td></tr>' : '';
			}
			if ($s->clue_b) {
				$clue = $s->clue_b;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_b) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;"  id="jawaban_' . $s->urut_b . '"></td></tr>' : '';
			}
			if ($s->clue_c) {
				$clue = $s->clue_c;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_c) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;"  id="jawaban_' . $s->urut_c . '"></td></tr>' : '';
			}
			if ($s->clue_d) {
				$clue = $s->clue_d;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_d) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;"  id="jawaban_' . $s->urut_d . '"></td></tr>' : '';
			}
			if ($s->clue_e) {
				$clue = $s->clue_e;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_e) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;"  id="jawaban_' . $s->urut_e . '"></td></tr>' : '';
			}

			if ($s->clue_f) {
				$clue = $s->clue_f;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_f) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;"  id="jawaban_' . $s->urut_f . '"></td></tr>' : '';
			}

			if ($s->clue_g) {
				$clue = $s->clue_g;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_g) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;"  id="jawaban_' . $s->urut_g . '"></td></tr>' : '';
			}

			if ($s->clue_h) {
				$clue = $s->clue_h;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_h) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;"  id="jawaban_' . $s->urut_h . '"></td></tr>' : '';
			}

			if ($s->clue_i) {
				$clue = $s->clue_i;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_i) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;" id="jawaban_' . $s->urut_i . '"></td></tr>' : '';
			}

			if ($s->clue_j) {
				$clue = $s->clue_j;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_j) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;" id="jawaban_' . $s->urut_j . '"></td></tr>' : '';
			}
			if ($s->clue_k) {
				$clue = $s->clue_k;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_k) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;" id="jawaban_' . $s->urut_k . '"></td></tr>' : '';
			}
			if ($s->clue_l) {
				$clue = $s->clue_l;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_l) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;" id="jawaban_' . $s->urut_l . '"></td></tr>' : '';
			}
			if ($s->clue_m) {
				$clue = $s->clue_m;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_m) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;" id="jawaban_' . $s->urut_m . '"></td></tr>' : '';
			}
			if ($s->clue_n) {
				$clue = $s->clue_n;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_n) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;" id="jawaban_' . $s->urut_n . '"></td></tr>' : '';
			}
			if ($s->clue_o) {
				$clue = $s->clue_o;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_o) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;" id="jawaban_' . $s->urut_o . '"></td></tr>' : '';
			}
			if ($s->clue_p) {
				$clue = $s->clue_p;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_p) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;" id="jawaban_' . $s->urut_p . '"></td></tr>' : '';
			}
			if ($s->clue_q) {
				$clue = $s->clue_q;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_q) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;" id="jawaban_' . $s->urut_q . '"></td></tr>' : '';
			}
			if ($s->clue_r) {
				$clue = $s->clue_r;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_r) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;" id="jawaban_' . $s->urut_r . '"></td></tr>' : '';
			}
			if ($s->clue_s) {
				$clue = $s->clue_s;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_s) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;" id="jawaban_' . $s->urut_s . '"></td></tr>' : '';
			}
			if ($s->clue_t) {
				$clue = $s->clue_t;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_t) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;" id="jawaban_' . $s->urut_t . '"></td></tr>' : '';
			}
			if ($s->clue_u) {
				$clue = $s->clue_u;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_u) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;" id="jawaban_' . $s->urut_u . '"></td></tr>' : '';
			}
			if ($s->clue_v) {
				$clue = $s->clue_v;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_v) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;" id="jawaban_' . $s->urut_v . '"></td></tr>' : '';
			}
			if ($s->clue_w) {
				$clue = $s->clue_w;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_w) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;" id="jawaban_' . $s->urut_w . '"></td></tr>' : '';
			}
			if ($s->clue_x) {
				$clue = $s->clue_x;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_x) ? $html .= '<tr style="height: 50px;"><td class="drop-zone"  style="background: #ECF0F5;" id="jawaban_' . $s->urut_x . '"></td></tr>' : '';
			}
			if ($s->clue_y) {
				$clue = $s->clue_y;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_y) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;" id="jawaban_' . $s->urut_y . '"></td></tr>' : '';
			}
			if ($s->clue_z) {
				$clue = $s->clue_z;
				$clue = $s->$clue;
				$clue = "opsi_" . $clue;
				$clue = $s->$clue;
				$html .= '<tr style="height: 50px;"><td style="background: #ECF0F5;"><span>' . $clue . '</span></td></tr>';
			} else {
				!empty($s->urut_z) ? $html .= '<tr style="height: 50px;"><td class="drop-zone" style="background: #ECF0F5;"  id="jawaban_' . $s->urut_z . '"></td></tr>' : '';
			}
			$html .= '</tbody>
						</table>
					</div>
					<!-- ALERT -->
					<div id="success-alert" class="alert" style="display: none; ">
						<h4>Jawaban Anda benar, silahkan lanjut ke studi kasus berikutnya!</h4>
						<img src="' . base_url() . 'template/images/success.png" alt="success" />
						<button type="button" id="btn_corrects" onclick="return submit_nilai(' . $s->id_soal . ',' . $s->id_level . ',1);" class="btn btn-m btn-info"><b>Lanjut</b></button>
					</div>
					<div id="fail-alert" class="alert" style="display: none;height:550px; width: 700px">
					<span>
						<h2 style="color: red;"><b>Jawaban Anda masih salah, silahkan menyusun ulang!</b></h2></br>
						<small id="tipe_data_feedback" style="display:none;"><b>Teliti kembali tipe data Anda :<b>' . $feedback['tipe_data'] . '</small>
						<small id="input_feedback" style="display:none;">Teliti kembali inputan Anda : ' . $feedback['input'] . '</small>
						<small id="process_feedback" style="display:none;">Teliti kembali proses Anda :' . $feedback['process'] . '</small>
						<small id="output_feedback" style="display:none;">Ups! Outputnya salah : ' . $feedback['output'] . '</small>
						</p>
						</span>
						<img src="' . base_url() . 'template/images/fail.jpeg" style="width:120px;" alt="fail" /></br><br>
						
						<button type="button" id="btn_incorrects" onclick="return submit_nilai(' . $s->id_soal . ',' . $s->id_level . ',0);" class="btn btn-m btn-info"><b>Close<b></button>
					</div>
					</main>';
		}
		$html .= '</div>';
		$no++;
		// }
		//}

		// Enkripsi Id Tes
		// $id_tes = $this->encryption->encrypt($detail_tes->id);
		$timeTaken = null;
		$userId = $this->session->userdata('user_id');
		$getTimeTaken = $this->ujian->getTimeTakenByIdKey($id, $userId);
		if ($getTimeTaken) {
			$timeTaken = $getTimeTaken->waktu;
			$str_time = $timeTaken;
			$str_time = preg_replace("/^([\d]{1,2})\:([\d]{2})$/", "00:$1:$2", $str_time);
			sscanf($str_time, "%d:%d:%d", $hours, $minutes, $seconds);
			$timeTaken = $hours * 3600 + $minutes * 60 + $seconds; //on scond calculate
		}


		$data = [
			'user' 		=> $this->user,
			'mhs'		=> $this->mhs,
			'judul'		=> 'Ujian',
			'subjudul'	=> 'Lembar Ujian',
			// 'soal'		=> $detail_tes,
			'no' 		=> $no,
			'html' 		=> $html,
			'timeTaken' => $timeTaken,
			'levelId'	=> $levelId,
			'id_tes'	=> $id
		];
		$this->load->view('_templates/topnav/_header.php', $data);
		$this->load->view('ujian/sheet');
		$this->load->view('_templates/topnav/_footer.php');
	}

	public function simpan_hasil($id, $status)
	{
		$this->session->sess_expiration = 0; // expires in 4 hours
		// Decrypt Id
		$id_user = $this->session->userdata('user_id');
		$soal = $this->db->query('select * from tb_soal where id_soal = ?', $id)->row_array();
		$data['id_user'] = $id_user;
		$data['id_soal'] = $id;
		$data['id_level'] = $soal['id_level'];
		$data['nilai'] = $status == 0 ? 0 : $soal['bobot'];
		$cek = $this->db->query('select * from nilai where id_user = ? and id_soal = ?', [$id_user, $id]);
		if ($cek->num_rows() == 0) {
			$this->db->insert('nilai', $data);
		} else {
			$id = $cek->row()->id;
			$this->db->update('nilai', $data, ['id' => $id]);
		}
		$this->output_json(['status' => true]);
	}

	public function simpan_satu()
	{
		// Decrypt Id
		$id_tes = $this->input->post('id', true);
		$id_tes = $this->encryption->decrypt($id_tes);

		$input 	= $this->input->post(null, true);
		$list_jawaban 	= "";
		for ($i = 1; $i < $input['jml_soal']; $i++) {
			$_tjawab 	= "opsi_" . $i;
			$_tidsoal 	= "id_soal_" . $i;
			$_ragu 		= "rg_" . $i;
			$jawaban_ 	= empty($input[$_tjawab]) ? "" : $input[$_tjawab];
			$list_jawaban	.= "" . $input[$_tidsoal] . ":" . $jawaban_ . ":" . $input[$_ragu] . ",";
		}
		$list_jawaban	= substr($list_jawaban, 0, -1);
		$d_simpan = [
			'list_jawaban' => $list_jawaban
		];

		// Simpan jawaban
		$this->master->update('h_ujian', $d_simpan, 'id', $id_tes);
		$this->output_json(['status' => true]);
	}

	public function simpan_akhir()
	{
		// Decrypt Id
		$id_tes = $this->input->post('id', true);
		$id_tes = $this->encryption->decrypt($id_tes);

		// Get Jawaban
		$list_jawaban = $this->ujian->getJawaban($id_tes);

		// Pecah Jawaban
		$pc_jawaban = explode(",", $list_jawaban);

		$jumlah_benar 	= 0;
		$jumlah_salah 	= 0;
		$jumlah_ragu  	= 0;
		$nilai_bobot 	= 0;
		$total_bobot	= 0;
		$jumlah_soal	= sizeof($pc_jawaban);

		foreach ($pc_jawaban as $jwb) {
			$pc_dt 		= explode(":", $jwb);
			$id_soal 	= $pc_dt[0];
			$jawaban 	= $pc_dt[1];
			$ragu 		= $pc_dt[2];

			$cek_jwb 	= $this->soal->getSoalById($id_soal);
			$total_bobot = $total_bobot + $cek_jwb->bobot;

			$jawaban == $cek_jwb->jawaban ? $jumlah_benar++ : $jumlah_salah++;
		}

		$nilai = ($jumlah_benar / $jumlah_soal)  * 100;
		$nilai_bobot = ($total_bobot / $jumlah_soal)  * 100;

		$d_update = [
			'jml_benar'		=> $jumlah_benar,
			'nilai'			=> number_format(floor($nilai), 0),
			'nilai_bobot'	=> number_format(floor($nilai_bobot), 0),
			'status'		=> 'N'
		];

		$this->master->update('h_ujian', $d_update, 'id', $id_tes);
		$this->output_json(['status' => TRUE, 'data' => $d_update, 'id' => $id_tes]);
	}

	public function listCondition_json($id, $id_soal)
	{
		//$this->akses_mahasiswa();

		$data = $this->ujian->detailLogConditions2($id, $id_soal);

		echo json_encode($data);
	}
	public function listConfidence_json($id, $id_soal)
	{
		//$this->akses_mahasiswa();

		$data = $this->ujian->detailLogConfidence2($id, $id_soal);

		echo json_encode($data);
	}
}
