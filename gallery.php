<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Galeri | Klinik Pergigian Diyana</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <!-- Import Old Standard TT font -->
  <link href="https://fonts.googleapis.com/css2?family=Old+Standard+TT:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Old+Standard+TT:wght@400;700&family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/gallery.css">
  <link rel="stylesheet" href="css/header.css">
 
</head>
<body>

<!-- Header -->
<?php include ('asset/header.php'); ?>

<div class="container-fluid flex-grow-1">
  <div class="row">
    <div class="col-md-12 content" id="content">

      <!-- Hero -->
      <div class="hero">
        <h1>Galeri Kami</h1>
        <p>Lihat lebih dekat kemudahan, rawatan, dan pasukan di Klinik Pergigian Diyana</p>
      </div>

      <!-- Filters -->
      <div class="gallery-filters">
        <button class="active" data-filter="all">Semua</button>
        <button data-filter="facility">Kemudahan Klinik</button>
        <button data-filter="treatment">Rawatan</button>
        <button data-filter="team">Pasukan Kami</button>
      </div>

      <!-- Gallery Grid -->
      <div class="row g-3 gallery-grid" id="galleryGrid">

        <div class="col-6 col-md-4 gallery-item" data-category="facility">
          <img src="img/gallery/facility-1.jpg" alt="Ruang menunggu klinik" onerror="this.src='img/ruang menunggu.jpeg'">
          <div class="gallery-overlay">
            <i class="bi bi-zoom-in"></i>
            <span>Ruang Menunggu</span>
          </div>
        </div>

        <div class="col-6 col-md-4 gallery-item" data-category="facility">
          <img src="img/gallery/facility-2.jpg" alt="Bilik rawatan" onerror="this.src='img/d7.jpeg'">
          <div class="gallery-overlay">
            <i class="bi bi-zoom-in"></i>
            <span>Bilik Rawatan</span>
          </div>
        </div>

        <div class="col-6 col-md-4 gallery-item" data-category="team">
          <img src="img/gallery/team-1.jpg" alt="Doktor gigi" onerror="this.src='img/clinic3.jpeg'">
          <div class="gallery-overlay">
            <i class="bi bi-zoom-in"></i>
            <span>Pasukan Doktor</span>
          </div>
        </div>

        <div class="col-6 col-md-4 gallery-item" data-category="treatment">
          <img src="img/gallery/treatment-1.jpg" alt="Pemeriksaan gigi" onerror="this.src='img/services1.jpg'">
          <div class="gallery-overlay">
            <i class="bi bi-zoom-in"></i>
            <span>Pemeriksaan Gigi</span>
          </div>
        </div>

        <div class="col-6 col-md-4 gallery-item" data-category="treatment">
          <img src="img/gallery/treatment-2.jpg" alt="Rawatan scaling" onerror="this.src='img/treatment.jpg'">
          <div class="gallery-overlay">
            <i class="bi bi-zoom-in"></i>
            <span>Scaling & Pembersihan</span>
          </div>
        </div>

        <div class="col-6 col-md-4 gallery-item" data-category="facility">
          <img src="img/gallery/treatment-3.jpg" alt="Peralatan moden" onerror="this.src='img/gigi palsu.jpg'">
          <div class="gallery-overlay">
            <i class="bi bi-zoom-in"></i>
            <span>Rawatan gigi palsu</span>
          </div>
        </div>

        <div class="col-6 col-md-4 gallery-item" data-category="treatment">
          <img src="img/gallery/treatment-3.jpg" alt="Rawatan ortodontik" onerror="this.src='img/services3.jpg'">
          <div class="gallery-overlay">
            <i class="bi bi-zoom-in"></i>
            <span>Rawatan Ortodontik</span>
          </div>
        </div>

        <div class="col-6 col-md-4 gallery-item" data-category="treatment">
          <img src="img/gallery/treatment-4.jpg" alt="Rawatan kanak-kanak" onerror="this.src='img/services2.jpg'">
          <div class="gallery-overlay">
            <i class="bi bi-zoom-in"></i>
            <span>Rawatan kanak-kanak</span>
          </div>
        </div>

        <div class="col-6 col-md-4 gallery-item" data-category="treatment">
          <img src="img/gallery/treatment-5.jpg" alt="Rawatan kanak-kanak" onerror="this.src='img/d2.jpeg'">
          <div class="gallery-overlay">
            <i class="bi bi-zoom-in"></i>
            <span>Rawatan Extraction</span>
          </div>
        </div>

        <div class="col-6 col-md-4 gallery-item" data-category="treatment">
          <img src="img/gallery/treatment-6.jpg" alt="Rawatan kanak-kanak" onerror="this.src='img/d3.jpeg'">
          <div class="gallery-overlay">
            <i class="bi bi-zoom-in"></i>
            <span>Rawatan Braces</span>
          </div>
        </div>

        <div class="col-6 col-md-4 gallery-item" data-category="facility">
          <img src="img/gallery/facility-4.jpg" alt="Muka depan klinik" onerror="this.src='img/clinic4.jpg'">
          <div class="gallery-overlay">
            <i class="bi bi-zoom-in"></i>
            <span>Muka Depan Klinik</span>
          </div>
        </div>

        <div class="col-6 col-md-4 gallery-item" data-category="facility">
          <img src="img/gallery/facility-5.jpg" alt="Muka depan klinik" onerror="this.src='img/d5.jpeg'">
          <div class="gallery-overlay">
            <i class="bi bi-zoom-in"></i>
            <span>Kaunter Pendaftaran</span>
          </div>
        </div>

      </div>
      <!-- /Gallery Grid -->

    </div>
  </div>
</div>

<!-- Footer -->
<?php include ('asset/footer_index.php'); ?>

<!-- Lightbox Modal -->
<div class="modal fade" id="lightboxModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-body">
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        <button type="button" class="lightbox-nav" id="lightboxPrev"><i class="bi bi-chevron-left"></i></button>
        <img id="lightboxImg" src="" alt="">
        <button type="button" class="lightbox-nav" id="lightboxNext"><i class="bi bi-chevron-right"></i></button>
        <div id="lightboxCaption"></div>
      </div>
    </div>
  </div>
</div>

<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content position-relative">
      <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
      <div class="row g-0">
        <div class="col-md-6 p-4 text-center">
          <h4 class="mb-4 fw-bold">Login</h4>
          <form method="POST" action="login.php">
            <div class="mb-3 text-start">
              <label class="form-label">Username</label>
              <input type="text" name="username" class="form-control" placeholder="Enter Username" required>
            </div>
            <div class="mb-3 text-start">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" placeholder="Enter Password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
          </form>
        </div>
        <div class="col-md-6 d-none d-md-block">
          <img src="img/clinic2.jpeg" alt="Login Promo" class="img-fluid h-100 w-100" style="object-fit:cover; border-radius:0 0 0.3rem 0;">
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/script.js"></script>
</body>
</html>