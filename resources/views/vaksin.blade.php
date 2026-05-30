
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
		<link rel="stylesheet" href="https://sinkarkes.kemkes.go.id/assets/vendor/bootstrap/css/bootstrap.min.css">
		<link rel="stylesheet" href="https://sinkarkes.kemkes.go.id/assets/vendor/font-awesome/css/font-awesome.min.css">
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
		
		<script>
			var refreshCaptchaURL = "https://sinkarkes.kemkes.go.id/vaksinasi_int/vaksinasi_int_public/refresh_captcha";
		</script>
		
		

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
<!--									<nav class="header-nav-top">
										<ul class="nav nav-pills">
											<li class="hidden-xs">
												<a href="https://sinkarkes.kemkes.go.id/portal/profil/visi_misi"><i class="fa fa-angle-right"></i> Tentang Kami</a>
											</li>
											<li class="hidden-xs">
												<a href="https://sinkarkes.kemkes.go.id/contact/contact_us"><i class="fa fa-angle-right"></i> Kontak Kami</a>
											</li>
											<li>
												<span class="ws-nowrap"><i class="fa fa-phone"></i> (021) 4266-920</span>
											</li>
										</ul>
									</nav>-->
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
				<input type="text" id="no_dokumen" name="no_dokumen" class="form-control" value="E26-000008897"/>
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
	</div>
</section>
<script>
let jenis_dokumen = 'icv';
let barcode = 'E26-000008897';
$(document).ready(function() {
	$('#jenis_dokumen').val(jenis_dokumen);
	if(jenis_dokumen != '' && barcode != ''){
		setTimeout(function(){
			$('#btn_cari').trigger('click');
		}, 1000);
	}
	console.log(jenis_dokumen, barcode);
	$('#btn_cari').click(function(e) {
		e.preventDefault();
		var $this = $(this);
		if ($this.find('i').hasClass('fa-spin')) return false;
		$this.find('i').removeClass('fa-search')
				.addClass('fa-refresh')
				.addClass('fa-spin')
				.prop('disabled', true);
				$('#result-block').slideUp()
				.load('{{ url("welcome/check_document/search") }}', $.param({
					'jenis_dokumen': $('#jenis_dokumen').val(),
					'no_dokumen': $('#no_dokumen').val()
				}), function() {
					$('#result-block').slideDown();
					$('#btn_cari').find('i')
							.removeClass('fa-refresh')
							.removeClass('fa-spin')
							.addClass('fa-search')
							.prop('disabled', false);
				});
	})
})
</script>			</div>
			<footer id="footer" class="">
				
				<div class="container">
				
	<div class="row">
						
						<div class="col-md-3">
							<div class="contact-details">
						<!--	<div id="service-boxes" class="container"  style="background-image: url(assets/img/watermark.png);"> -->
								<h4><i class="fa fa-life-ring"></i>&nbsp;&nbsp;Help Desk</h4>
								<p>Direktorat Surveilans dan Karantina Kesehatan <br/>Direktorat Jenderal Pencegahan dan Pengendalian Penyakit<br/>Kementerian Kesehatan RI</p>
								<ul class="contact">
									<li><p><i class="fa fa-building"></i> Kantor Ditjen P2P<br />Kementerian Kesehatan Republik Indonesia Gedung dr. M. Adhyatma lantai 6</p></li>
									<li><p><i class="fa fa-map-marker"></i> Jl. HR Rasuna Said Kav. X-5 No. 4-9<br />Jakarta Selatan</p></li>
                                    <!--
									<li><p><i class="fa fa-phone"></i> </p></li>
									<li><p><i class="fa fa-fax"></i> </p></li>
                                    !-->
									<li><p><i class="fa fa-envelope"></i><a href="/cdn-cgi/l/email-protection#dba8b2b5b0baa9b0bea8f5b0beb6b0bea8f5bcb4f5b2bf" target="_top">sinkarkes.kemkes.go.id</a></p></li>
								</ul>
							<!--</div>-->
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
									<!--	<li><a href="https://sinkarkes.kemkes.go.id/portal/welcome/faq">FAQ's</a></li>
										<li><a href="https://sinkarkes.kemkes.go.id/portal/welcome/sitemap">Sitemap</a></li> -->
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
		<script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script src="https://sinkarkes.kemkes.go.id/assets/vendor/jquery.appear/jquery.appear.min.js"></script>
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

		<!-- Google Analytics: Change UA-XXXXX-X to be your site's ID. Go to https://www.google.com/analytics/ for more information.
		<script>
			(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
		
			ga('create', 'UA-12345678-1', 'auto');
			ga('send', 'pageview');
		</script>
		 -->
	<script defer src="https://static.cloudflareinsights.com/beacon.min.js/v833ccba57c9e4d2798f2e76cebdd09a11778172276447" integrity="sha512-57MDmcccJXYtNnH+ZiBwzC4jb2rvgVCEokYN+L/nLlmO8rfYT/gIpW2A569iJ/3b+0UEasghjuZH/ma3wIs/EQ==" data-cf-beacon='{"version":"2024.11.0","token":"e2a765f324f4412da187da8b414d804f","server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
</body>
</html>
		