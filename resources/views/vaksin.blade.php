<!DOCTYPE html>
<html lang="id">
	<head>

		<!-- Basic -->
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">	

		<title>Sistem Informasi Kesehatan Pelabuhan</title>	

		<meta name="description" content="Sistem Informasi Kekarantinaan Kesehatan Kementerian Republik Indonesia">
		<meta name="author" content="Ardi Soebrata">
		<meta name="keywords" content="Kekarantinaan Kesehatan, Kementerian Kesehatan, Republik Indonesia">

		<!-- Favicon -->
		<link rel="apple-touch-icon" sizes="180x180" href="https://sinkarkes.kemkes.go.id/assets/img/favicons/apple-touch-icon.png?v=BGGrN6p0zJ">
		<link rel="icon" type="image/png" href="https://sinkarkes.kemkes.go.id/assets/img/favicons/favicon-32x32.png?v=BGGrN6p0zJ" sizes="32x32">
		<link rel="icon" type="image/png" href="https://sinkarkes.kemkes.go.id/assets/img/favicons/favicon-16x16.png?v=BGGrN6p0zJ" sizes="16x16">
		<link rel="manifest" href="https://sinkarkes.kemkes.go.id/assets/img/favicons/manifest.json?v=BGGrN6p0zJ">
		<link rel="mask-icon" href="https://sinkarkes.kemkes.go.id/assets/img/favicons/safari-pinned-tab.svg?v=BGGrN6p0zJ" color="#40ada6">
		<link rel="shortcut icon" href="https://sinkarkes.kemkes.go.id/assets/img/favicons/favicon.ico?v=BGGrN6p0zJ">
		<meta name="apple-mobile-web-app-title" content="Simkespel">
		<meta name="application-name" content="Simkespel">
		<meta name="msapplication-config" content="https://sinkarkes.kemkes.go.id/assets/img/favicons/browserconfig.xml?v=BGGrN6p0zJ">
		<meta name="theme-color" content="#40ada6">

		<!-- Mobile Metas -->
		<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">

		<!-- Web Fonts  -->
		<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800%7CShadows+Into+Light" rel="stylesheet" type="text/css">

		<!-- Vendor CSS -->
          {{-- <link rel="stylesheet" href="{{ asset('vendor/bootstrap.min.css') }}"> --}}
		  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.6.3/css/font-awesome.min.css">
		<link rel="stylesheet" href="https://sinkarkes.kemkes.go.id/assets/vendor/bootstrap/css/bootstrap.min.css">
		<link rel="stylesheet" href="https://sinkarkes.kemkes.go.id/assets/vendor/simple-line-icons/css/simple-line-icons.min.css">
		<link rel="stylesheet" href="https://sinkarkes.kemkes.go.id/assets/vendor/owl.carousel/assets/owl.carousel.min.css">
		<link rel="stylesheet" href="https://sinkarkes.kemkes.go.id/assets/vendor/owl.carousel/assets/owl.theme.default.min.css">
		<link rel="stylesheet" href="https://sinkarkes.kemkes.go.id/assets/vendor/magnific-popup/magnific-popup.min.css">

		<!-- Theme CSS -->
		<link rel="stylesheet" href="https://sinkarkes.kemkes.go.id/assets/css/portal/theme.css">
		<link rel="stylesheet" href="https://sinkarkes.kemkes.go.id/assets/css/portal/theme-elements.css">
		<link rel="stylesheet" href="https://sinkarkes.kemkes.go.id/assets/css/portal/theme-blog.css">
		<link rel="stylesheet" href="https://sinkarkes.kemkes.go.id/assets/css/portal/theme-shop.css">
		<link rel="stylesheet" href="https://sinkarkes.kemkes.go.id/assets/css/portal/theme-animate.css">

		<!-- Current Page CSS -->
		<link rel="stylesheet" href="https://sinkarkes.kemkes.go.id/assets/vendor/rs-plugin/css/settings.css">
		<link rel="stylesheet" href="https://sinkarkes.kemkes.go.id/assets/vendor/rs-plugin/css/layers.css">
		<link rel="stylesheet" href="https://sinkarkes.kemkes.go.id/assets/vendor/rs-plugin/css/navigation.css">
		
		
		<link href="https://sinkarkes.kemkes.go.id/assets/css/simple-lists.css" rel="stylesheet" media="screen" />
		<link href="https://sinkarkes.kemkes.go.id/assets/css/../vendor/select2/select2.css" rel="stylesheet" media="screen" />

		<!-- Skin CSS -->
		<link rel="stylesheet" href="https://sinkarkes.kemkes.go.id/assets/css/portal/skin.css">

		<!-- Theme Custom CSS -->
		<link rel="stylesheet" href="https://sinkarkes.kemkes.go.id/assets/css/portal/custom.css">

		<!-- Head Libs -->
		<script src="https://sinkarkes.kemkes.go.id/assets/vendor/modernizr/modernizr.min.js"></script>
		<script src="https://sinkarkes.kemkes.go.id/assets/vendor/jquery/jquery.min.js"></script>

	</head>
	<body>
		
		<div class="body" >
			<header id="header" data-plugin-options='{"stickyEnabled": true, "stickyEnableOnBoxed": true, "stickyEnableOnMobile": true, "stickyStartAt": 57, "stickySetTop": "-57px", "stickyChangeLogo": true}'>
				<div class="header-body">
					<div class="header-container container ">
					
						<div class="header-row">
						
							<div class="header-column">
                                                            <div class="header-logo">
                                                                <a href="https://sinkarkes.kemkes.go.id/portal/welcome">
                                                                    <img src="https://sinkarkes.kemkes.go.id/assets/img/SINKARKES.png" alt="KEMENKES - SIMKESPEL" height="90" data-sticky-height="55" data-sticky-top="45">
                                                                </a>
                                                            </div>
							</div>
							<div class="header-column">
								<div class="header-row">
									<div class="header-search hidden-xs">
										<form id="searchForm" action="https://sinkarkes.kemkes.go.id/portal" method="get">
											<div class="input-group">
												<input type="text" class="form-control" name="q" id="q" placeholder="Search..." required>
												<span class="input-group-btn">
													<button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button>
												</span>
											</div>
										</form>
									</div>
								</div>
								<div class="header-row">
									<div class="header-nav">
										<button class="btn header-btn-collapse-nav" data-toggle="collapse" data-target=".header-nav-main">
											<i class="fa fa-bars"></i>
										</button>
										<ul class="header-social-icons social-icons hidden-xs">
											
										</ul>
										<div class="header-nav-main header-nav-main-effect-1 header-nav-main-sub-effect-1 collapse">
											<nav>
												<ul class="nav nav-pills" id="mainNav">
													<li class="dropdown active"><a href="https://sinkarkes.kemkes.go.id/vaksinasi_int/vaksinasi_int_public/add" class="dropdown-toggle">Pelayanan</a><ul class="dropdown-menu"><li class=""><a href="https://sinkarkes.kemkes.go.id/vaksinasi_int/vaksinasi_int_public/add">Registrasi Vaksinasi Internasional</a></li><li class=""><a href="https://sinkarkes.kemkes.go.id/portal/welcome/pelayanan_kapal">Layanan Kapal</a></li><li class=""><a href="https://sinkarkes.kemkes.go.id/portal/welcome/pelayanan_plbdn">Deklarasi Pos Lintas Batas Negara</a></li><li class=""><a href="https://sinkarkes.kemkes.go.id/portal/layanan_penumpang/index">Layanan Izin Sakit dan Laik Terbang</a></li><li class=" active"><a href="https://sinkarkes.kemkes.go.id/welcome/check_document">Cek Nomor Dokumen</a></li></ul></li><li class="dropdown"><a href="https://sinkarkes.kemkes.go.id/#" class="dropdown-toggle">IHR</a><ul class="dropdown-menu"><li class=""><a href="https://sinkarkes.kemkes.go.id/ihr/news_public">Berita</a></li><li class=""><a href="https://sinkarkes.kemkes.go.id/ihr/ihr_public">Referensi</a></li></ul></li><li class="dropdown"><a href="https://sinkarkes.kemkes.go.id/news/news_public/index" class="dropdown-toggle">Berita</a><ul class="dropdown-menu"><li class=""><a href="https://sinkarkes.kemkes.go.id/news/news_public/index">Berita Nasional</a></li><li class=""><a href="https://sinkarkes.kemkes.go.id/news/news_public/index/beritadunia">Berita Dunia</a></li><li class=""><a href="https://sinkarkes.kemkes.go.id/news/news_public/index/berita">Berita Seputar Balai Karkes</a></li><li class=""><a href="https://sinkarkes.kemkes.go.id/pengumuman/pengumuman_public/index">Pengumuman</a></li><li class=""><a href="https://sinkarkes.kemkes.go.id/events/events_public/index">Kegiatan</a></li></ul></li><li class="dropdown"><a href="https://sinkarkes.kemkes.go.id/reference/reference_public/index" class="dropdown-toggle">Peraturan</a><ul class="dropdown-menu"><li class=""><a href="https://sinkarkes.kemkes.go.id/welcome/reference_public/kategori/regulasipresiden">Regulasi Presiden</a></li><li class=""><a href="https://sinkarkes.kemkes.go.id/welcome/reference_public/kategori/pp">Peraturan Pemerintah</a></li><li class=""><a href="https://sinkarkes.kemkes.go.id/welcome/reference_public/kategori/regulasimenkes">Regulasi Menteri Kesehatan</a></li><li class=""><a href="https://sinkarkes.kemkes.go.id/welcome/reference_public/kategori/regulasilainnya">Regulasi Menteri Lainnya</a></li><li class=""><a href="https://sinkarkes.kemkes.go.id/welcome/reference_public/kategori/regulasip2pl">Regulasi Dirjen P2P</a></li><li class=""><a href="https://sinkarkes.kemkes.go.id/welcome/reference_public/kategori/regulasikemkes">Regulasi Dirjen Kemkes Lainnya</a></li><li class=""><a href="https://sinkarkes.kemkes.go.id/welcome/reference_public/kategori/regulasidirjen">Regulasi Dirjen Lainnya</a></li><li class=""><a href="https://sinkarkes.kemkes.go.id/welcome/reference_public/kategori/uu">UU & Peraturan Lainnya</a></li><li class=""><a href="https://sinkarkes.kemkes.go.id/welcome/reference_public/kategori/referensi">Referensi &amp; Peraturan</a></li><li class=""><a href="https://sinkarkes.kemkes.go.id/ihr/ihr_public/index">International Health Regulations</a></li></ul></li><li class=""><a href="https://sinkarkes.kemkes.go.id/kkp/kkp_public">Profil Balai Karkes</a></li><li class=""><a href="https://sinkarkes.kemkes.go.id/portal/profil/visi_misi">Visi Misi</a></li><li class=""><a href="https://sinkarkes.kemkes.go.id/portal/rumah_sakit">Faskes</a></li><li class=""><a href="https://sinkarkes.kemkes.go.id/auth/login"><i class="fa fa-user"></i> Login</a></li>												</ul>
											</nav>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</header>

			<div role="main" class="main">
				<section class="page-header page-header-custom-background mb-none" data-stellar-background-ratio="0" style="background-image: url(https://sinkarkes.kemkes.go.id/assets/img/slide-cek-dokumen.jpg);">
	<div class="container">
		<div class="row">
			<div class="col-md-12">
				<h1><i class="fa fa-check-square-o"></i> Cek Dokumen</h1>
			</div>
		</div>
	</div>
</section>

<section class="section section-default mt-none">
	<div class="container">
		<div class="heading heading-border heading-bottom-border heading-primary">
			<h3 class="heading-primary">Apa Itu Cek Nomor Dokumen?</h3>
		</div>
		<p>Cek nomor dokumen adalah sebuah fitur yang kami sediakan untuk memastikan keaslian dokumen atau sertifikat yang dikeluarkan dan telah terdaftar pada database sertifikat nasional Balai Kekarantinaan Kesehatan Republik Indonesia.</p>
		<div class="heading heading-border heading-bottom-border heading-primary">
			<h3 class="heading-primary">Tata Cara Pencarian</h3>
		</div>
		<ol class="list list-ordened list-ordened-style-3 list-primary">
			<li class="appear-animation fadeInUp appear-animation-visible" data-appear-animation="fadeInUp" data-appear-animation-delay="0">Pilih Dokumen atau Sertifikat yang akan dicek pada kolom pilihan Jenis Dokumen</li>
			<li class="appear-animation fadeInUp appear-animation-visible" data-appear-animation="fadeInUp" data-appear-animation-delay="300" style="animation-delay: 300ms;">Masukan No Registrasi untuk Buku Kesehatan Kapal atau Nomor ICV pada kolom Nomor Dokumen/IMO No/Barcode untuk melakukan pengecekan keaslian Dokumen</li>
			<li class="appear-animation fadeInUp appear-animation-visible" data-appear-animation="fadeInUp" data-appear-animation-delay="600" style="animation-delay: 600ms;">Masukan Barcode yang tertera pada sertifikat pada kolom Nomor Dokumen/IMO No/Barcode untuk melakukan pengecekan Sertifikat Lainnya</li>
			<li class="appear-animation fadeInUp appear-animation-visible" data-appear-animation="fadeInUp" data-appear-animation-delay="900" style="animation-delay: 900ms;">Lalu klik tombol Cari yang terdapat dibawah kolom Nomor Dokumen/IMO No/Barcode </li>
		</ol>
    </div>
</section>

<div class="container">
	<form class="form-horizontal">
		<div class="form-group">
			<label class="col-md-4 control-label">Jenis Dokumen</label>
			<div class="col-md-8">
				<select name="jenis_dokumen" id="jenis_dokumen" class="form-control">
					<option value="">--Pilih--</option>
											<option value="icv" selected>ICV</option>
											<option value="icv-haji" >ICV Haji</option>
											<option value="phqc" >PHQC</option>
											<option value="izin_karantinaan" >Izin Karantina COP</option>
											<option value="health_book" >Buku Kesehatan Kapal</option>
											<option value="sscec" >Sertifikat Sanitasi Kapal (SSCEC, SSCC)</option>
											<option value="pengujian_kesehatan" >Pengujian Kesehatan</option>
											<option value="pemeriksaan_obat_alkes_kapal" >Pengawasan Obat-obatan dan Alkes Kapal</option>
											<option value="sertifikat_air" >Pengawasan Kualitas Air</option>
											<option value="laik_terbang" >Sertifikat Laik Terbang</option>
											<option value="sanitasi_jasaboga" >Higienis Jasa Boga</option>
											<option value="sailing_permit" >Sertifikat Sanitasi Kapal Sailing Permit</option>
											<option value="izin_jenazah" >Izin Lalu Lintas Jenazah</option>
											<option value="izin_sakit" >Izin Lalu Lintas Orang Sakit</option>
											<option value="omkaba" >Health Certificate</option>
											<option value="kontraindikasi" >Kontraindikasi</option>
									</select>
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-4 control-label">Nomor Dokumen/IMO No/Barcode/No. Porsi</label>
			<div class="col-md-8">
				<input type="text" id="no_dokumen" name="no_dokumen" class="form-control" value="E26-000271822"/>
			</div>
		</div>
		<div class="row">
			<div class="col-md-offset-4 col-md-8">
				<a href="" id="btn_cari" class="btn btn-primary"><i class="fa fa-search"></i> Cari</a>
			</div>
		</div>
	</form>
</div>
<section class="section section-default">
	<div class="container">
		<div id="result-block" style="display: none;"></div>

		{{--
			====== DATA STATIS / MOCK ======
			Dua blok di bawah ini cuma dipakai sebagai "database" sementara di sisi
			browser, selama endpoint welcome/check_document/search belum jadi.
			- #tpl-icv-found  : ditampilkan kalau no_dokumen ketemu di staticFoundDocs
			- #tpl-not-found  : ditampilkan kalau tidak ketemu

			Nanti kalau endpoint backend-nya sudah siap:
			1. Hapus dua div tpl-* ini (dan isi icv-nya bisa dipindah ke view
			   terpisah, mis. resources/views/partials/icv.blade.php, lalu
			   di-render dari controller).
			2. Di JS paling bawah, ganti isi function doSearch() supaya balik
			   pakai $.load(url, data, callback) seperti sebelumnya.
		--}}
		<div id="tpl-icv-found" style="display: none;">
			<style>
	.icv{
		/* margin: 0 auto; */
		padding: 20px;
		background-color: #f5e19f;
		position: relative;
		background: rgb(141,178,227);
		background: linear-gradient(36deg, rgba(141,178,227,1) 1%, rgba(245,225,159,1) 15%);
	}
	.icv .watermark{
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
		height:230px;
		opacity: 0.2;
		filter: grayscale(100%) brightness(80%) contrast(120%);
	}
	.icv-header{
		text-align: center;
	}
	.icv-header img{
		margin-right: 10px;
	}
	.icv-header div{
		margin-bottom: 5px;
	}
	.icv-header h3{
		margin-top:19px;
		margin-bottom: 0px;
		font-size: 20px;
		font-weight: bold;
		line-height: 1.3;
	}
	.icv-header p{
		font-size: 16px;
		line-height: 1.3;
	}
	.icv-body{
		margin-top: 20px;
	}
	.icv-body p{
		padding: 0px;
		margin: 0px 0px 5px 0px;
	}
	.text-bold{
		font-weight: bold;
	}
	.icv .main-data{
		border-bottom: 1px solid #f1d380;
		padding-bottom: 5px;
		margin-bottom: 5px;
		position: relative;
	}
	.main-data img{
		top:0;
		right:0;
		width: 133px;
	}
	.icv .data-detail{
		font-size: 12px;
	}
	.data-detail p.title{
		font-size: 12px;
		font-weight: bold;
		padding:0px;
		margin: 0px;
	}
	.data-detail p{
		padding:0px;
		margin: 0px;
	}
	.icv-table{
		width: 100%;
		font-size: 10px;
	}
	.icv-table th, .icv-table td{
		padding: 5px;
		vertical-align:top;
		line-height: 1.4;
	}
	.icv-table th{
		font-weight: normal;
		background-color: #e8d79c;
	}
	.icv-footer{
		margin-top: 20px;
		text-align:center;
		font-size: 11px;
	}
	.icv-footer p{
		padding:0px;
		margin: 0px;
		line-height: 1.4;
	}
			</style>
			<div class="row">
				<div class="col-md-6 col-md-offset-4 icv">
					<img src="https://sinkarkes.kemkes.go.id/assets/img/logo1.png"  class="watermark"> <br>

					<section class="icv-header">
						<div>
							<img src="https://sinkarkes.kemkes.go.id/assets/img/pancasila.png" alt="Logo Garuda" height="40"> <br>
						</div>
						<div>
							<img src="https://sinkarkes.kemkes.go.id/assets/img/who.png" alt="Logo WHO" height="30">
							<img src="https://sinkarkes.kemkes.go.id/assets/img/kemkes-landscape.png" alt="Logo Kemenkes" height="30">
							<img src="https://sinkarkes.kemkes.go.id/assets/img/satusehat.png" alt="Logo Satusehat" height="30">
						</div>
						<h3>International Certificate of Vaccination (Prophylaxis)</h3>
						<p>Certificat Internatiional de Vaccination ou de Prophylaxie</p>
					</section>
					<section class="icv-body">
						<div class="main-data">
							<table style="width: 100%">
								<tr>
									<td>
										<p class="text-bold" id="name_j">ACHMAD RI***</p>
										<p id="pass_id">Passport X6260***</p>
										<p id="date_birth">18th October 2000</p>
									</td>
									<td style="width:146px; text-align: center">
										<img src="https://sinkarkes.kemkes.go.id/welcome/check_document/qrcode?data=https://sinkarkes.kemkes.go.id/welcome/check_document?t=RTI2LTAwMDI3MTgyMi5pY3Y=" alt="">
										E26-000271822
									</td>
								</tr>
							</table>
						</div>
						<div class="data-detail">
							<p class="title">In accordance with the International Health Regulations</p>
							<p>compormement au Reglement sanitaire international</p>
							<table class="icv-table" style="margin-bottom: 20px">
								<thead>
									<tr>
										<th>
											<strong>Vaccine or Prophylaxis</strong><br>
											Vaccin ou agent prophylactique
										</th>
										<th>
											<strong>Manufacturer and Batch no. of vaccine or prophylaxis</strong><br>
											Fabircant du vaccin ou de l'agent prophylactique prophylactique et numero du lot
										</th>
										<th>
											<strong>Date</strong><br>
											Date
										</th>
										<th>
											<strong>Valid Until</strong><br>
											Valiable jusqu'au
										</th>
										<th>
											<strong>Administering Location & Supervising Clinician</strong><br>
											Lieu d'administration et Clinicien superviseur
										</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td class="text-bold">MENINGITIS MENINGOCOCCUS</td>
										<td>BIOFARMA B20241228</td>
										<td>7th March 2026</td>
										<td>21st March 2029</td>
										<td>
											Bandara Internasional I Gusti Ngurah Rai / RSU SURYA HUSADHA NUSA DUA / I Komang Nesa Trianta
										</td>
									</tr>
									<tr>
										<td class="text-bold">POLIO</td>
										<td>BIOFARMA 21000725</td>
										<td>7th March 2026</td>
										<td>30th November -0001</td>
										<td>
											Bandara Internasional I Gusti Ngurah Rai / RSU SURYA HUSADHA NUSA DUA / I Komang Nesa Trianta
										</td>
									</tr>
								</tbody>
							</table>
							<table class="icv-table">
								<thead>
									<tr>
										<th>
											<strong>Disease targeted</strong>
										</th>
										<th>
											<strong>Date</strong>
										</th>
										<th>
											<strong>Manufacture and Batch No. of vaccine or prophylaxis</strong>
										</th>
										<th>
											<strong>Next Booster</strong>
										</th>
										<th>
											<strong>Official stamp and signature</strong>
										</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td class="text-bold"></td>
										<td></td>
										<td></td>
										<td></td>
										<td></td>
									</tr>
									<tr>
										<td class="text-bold"></td>
										<td></td>
										<td></td>
										<td></td>
										<td></td>
									</tr>
								</tbody>
							</table>
						</div>
					</section>
					<section class="icv-footer">
						<div class="pagination">
							<strong>Penafisan (Disclaimer):</strong>
							<p>Nomor kode ICV elektronik (eICV) berbeda dengan nomor seri ICV fisik</p>
							<br>
						</div>
						<div class="issued">
							<p class="text-bold">This certificate was issued by Ministry of Health of Indonesia</p>
							<p>Ce certificat a été délivré par le ministère Indonésien de la Santé</p>
						</div>
					</section>
				</div>
			</div>
		</div>
		<div id="tpl-not-found" style="display: none;">
			<div style="padding: 60px 0; text-align: center;">
				<p style="font-size: 16px; color: #777; margin: 0;">Maaf, dokumen yang Anda cari tidak dapat Kami temukan.</p>
			</div>
		</div>
	</div>
</section>
<script>
	// ====== Search masih STATIS / MOCK (belum manggil backend) ======
	// Nomor dokumen yang dianggap "ketemu" untuk demo ini.
	// Tinggal tambah string baru ke array ini kalau mau nambah contoh lain.
	var staticFoundDocs = ['E26-000271822'];

	$(document).ready(function() {

		function doSearch() {
			var $btn = $('#btn_cari');
			if ($btn.find('i').hasClass('fa-spin')) return false;

			$btn.find('i').removeClass('fa-search')
					.addClass('fa-refresh')
					.addClass('fa-spin')
					.prop('disabled', true);

			var noDokumen = $.trim($('#no_dokumen').val()).toUpperCase();

			$('#result-block').slideUp(200, function() {
				// setTimeout ini cuma buat simulasi delay pencarian, boleh dihapus
				setTimeout(function() {
					var found = staticFoundDocs.indexOf(noDokumen) !== -1;
					var html = found ? $('#tpl-icv-found').html() : $('#tpl-not-found').html();

					$('#result-block').html(html).slideDown();

					$btn.find('i')
							.removeClass('fa-refresh')
							.removeClass('fa-spin')
							.addClass('fa-search')
							.prop('disabled', false);
				}, 500);
			});
		}

		// Auto-cari sekali saat halaman pertama kali dibuka, kalau nomor dokumen sudah terisi
		var jenisDokumen = $('#jenis_dokumen').val();
		var noDokumenAwal = $('#no_dokumen').val();
		if (jenisDokumen != '' && noDokumenAwal != '') {
			setTimeout(doSearch, 1000);
		}

		$('#btn_cari').click(function(e) {
			e.preventDefault();
			doSearch();
		});
	});
</script>			</div>
			<footer id="footer" class="">
				
				<div class="container">
				
	<div class="row">
						
						<div class="col-md-3">
							<div class="contact-details">
								<h4><i class="fa fa-life-ring"></i>&nbsp;&nbsp;Help Desk</h4>
								<p>Direktorat Surveilans dan Karantina Kesehatan <br/>Direktorat Jenderal Pencegahan dan Pengendalian Penyakit<br/>Kementerian Kesehatan RI</p>
								<ul class="contact">
									<li><p><i class="fa fa-building"></i> Kantor Ditjen P2P<br />Kementerian Kesehatan Republik Indonesia Gedung dr. M. Adhyatma lantai 6</p></li>
									<li><p><i class="fa fa-map-marker"></i> Jl. HR Rasuna Said Kav. X-5 No. 4-9<br />Jakarta Selatan</p></li>
									<li><p><i class="fa fa-envelope"></i> sinkarkes.kemkes.go.id</p></li>
								</ul>
							</div>
						</div>
						<div class="col-md-3">
							<div>
								<h4><i class="fa fa-user-md"></i>&nbsp;&nbsp;Pelayanan</h4>
								<ul>
									<li><a href="https://sinkarkes.kemkes.go.id/vaksinasi_int/vaksinasi_int_public/add">Registrasi Vaksinasi Internasional</a></li>
									<li><a href="https://sinkarkes.kemkes.go.id/contact/contact_us">Kontak Kami</a></li>
								</ul>
								<h4><i class="fa fa-lock"></i>&nbsp;&nbsp;SINKARKES</h4>
								<ul>
									<li><a href="https://sinkarkes.kemkes.go.id/auth/login">Login</a></li>
									<li><a href="https://www.kespel.depkes.go.id/mail/">Email</a></li>
								</ul>
							</div>
						</div>
						<div class="col-md-3">
							<h4><i class="fa fa-building-o"></i>&nbsp;&nbsp;Balai Karkes</h4>
							<ul>
								<li><a href="https://sinkarkes.kemkes.go.id/news/news_public">Profil Balai Kekarantinaan Kesehatan</a></li>
								<li><a href="https://sinkarkes.kemkes.go.id/news/news_public/index">Berita</a></li>
								<li><a href="https://sinkarkes.kemkes.go.id/pengumuman/pengumuman_public/index">Pengumuman</a></li>
								<li><a href="https://sinkarkes.kemkes.go.id/events/events_public/index">Kegiatan</a></li>
								<li><a href="https://sinkarkes.kemkes.go.id/reference/reference_public/index">Referensi &amp; Peraturan</a></li>
								<li><a href="https://sinkarkes.kemkes.go.id/ihr/ihr_public/index">International Health Regulations</a></li>
							</ul>
						</div>
						<div class="col-md-3">
							<h4><i class="fa fa-link"></i>&nbsp;&nbsp;LINKS</h4>
							<ul>
								<li><a href="https://www.depkes.go.id">Kementerian Kesehatan</a></li>
								<li><a href="https://www.puskeshaji.depkes.go.id">Pusat Kesehatan Haji</a></li>
								<li><a href="https://www.gizikia.depkes.go.id">Direktorat Bina Gizi dan KIA</a></li>
								<li><a href="https://www.pppl.depkes.go.id">Ditjen PP &  PL</a></li>
								<li><a href="https://www.jkn.kemkes.go.id">Jaminan Kesehatan Nasional</a></li>
								<li><a href="https://www.who.int/eng">WHO</a></li>
							</ul>
						</div>
					</div>
				</div>
				<div class="footer-copyright">
					<div class="container">
						<div class="row">
							<div class="col-md-1">
								<a href="https://sinkarkes.kemkes.go.id/portal" class="logo">
									<img src="https://sinkarkes.kemkes.go.id/assets/img/kkp_logo.gif" alt="KEMENKES - SIMKESPEL" width="68" class="img-responsive">
								</a>
							</div>
							<div class="col-md-7">
								<p>Copyright &copy;2026 Direktorat Surveilans dan Karantina Kesehatan &mdash; Direktorat Jenderal Pencegahan dan Pengendalian Penyakit &mdash; Kementerian Kesehatan RI</p>
							</div>
							<div class="col-md-4">
								<nav id="sub-menu">
									<ul>
										<li><a href="https://sinkarkes.kemkes.go.id/contact/contact_us">Kontak Kami</a></li>
									</ul>
								</nav>
							</div>
						</div>
					</div>
				</div>
			</footer>
		</div>
		
		<!-- Vendor -->
		<script src="https://sinkarkes.kemkes.go.id/assets/vendor/jquery.appear/jquery.appear.min.js"></script>
		<script src="https://sinkarkes.kemkes.go.id/assets/vendor/jquery.easing/jquery.easing.min.js"></script>
		<script src="https://sinkarkes.kemkes.go.id/assets/vendor/jquery-cookie/jquery-cookie.min.js"></script>
		<script src="https://sinkarkes.kemkes.go.id/assets/vendor/bootstrap/js/bootstrap.min.js"></script>
		<script src="https://sinkarkes.kemkes.go.id/assets/vendor/common/common.min.js"></script>
		<script src="https://sinkarkes.kemkes.go.id/assets/vendor/jquery.stellar/jquery.stellar.min.js"></script>
		<script src="https://sinkarkes.kemkes.go.id/assets/vendor/jquery.easy-pie-chart/jquery.easy-pie-chart.min.js"></script>
		<script src="https://sinkarkes.kemkes.go.id/assets/vendor/jquery.gmap/jquery.gmap.min.js"></script>
		<script src="https://sinkarkes.kemkes.go.id/assets/vendor/jquery.lazyload/jquery.lazyload.min.js"></script>
		<script src="https://sinkarkes.kemkes.go.id/assets/vendor/isotope/jquery.isotope.min.js"></script>
		<script src="https://sinkarkes.kemkes.go.id/assets/vendor/owl.carousel/owl.carousel.min.js"></script>
		<script src="https://sinkarkes.kemkes.go.id/assets/vendor/magnific-popup/jquery.magnific-popup.min.js"></script>
		<script src="https://sinkarkes.kemkes.go.id/assets/vendor/vide/vide.min.js"></script>
		
		<!-- Theme Base, Components and Settings -->
		<script src="https://sinkarkes.kemkes.go.id/assets/js/portal/theme.js"></script>
		
		<!-- Current Page Vendor and Views -->
		<script src="https://sinkarkes.kemkes.go.id/assets/vendor/rs-plugin/js/jquery.themepunch.tools.min.js"></script>
		<script src="https://sinkarkes.kemkes.go.id/assets/vendor/rs-plugin/js/jquery.themepunch.revolution.min.js"></script>
		
		<!-- Theme Custom -->
		
		<script src="https://sinkarkes.kemkes.go.id/assets/js/../vendor/select2/select2.js"></script>
		<script src="https://sinkarkes.kemkes.go.id/assets/js/portal/custom.js"></script>
		
		<!-- Theme Initialization Files -->
		<script src="https://sinkarkes.kemkes.go.id/assets/js/portal/theme.init.js"></script>

</body>
</html>