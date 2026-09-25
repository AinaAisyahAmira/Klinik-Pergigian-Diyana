<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Services | Klinik Pergigian Diyana</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <!-- Import Old Standard TT font -->
  <link href="https://fonts.googleapis.com/css2?family=Old+Standard+TT:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Old+Standard+TT:wght@400;700&family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/services.css">
  <link rel="stylesheet" href="css/header.css">
</head>
<body>

<!-- Header -->
<?php include ('asset/header.php'); ?>

<div class="container-fluid flex-grow-1">
  <div class="row">
    <!-- Main Content -->
    <div class="col-md-12 content" id="content">

      <!-- Hero -->
      <div class="hero image-hero text-center">
        <h1>Perkhidmatan Kami</h1>
        <p>Rawatan pergigian yang profesional, selamat, dan disesuaikan untuk setiap ahli keluarga.</p>

      </div>

      <!-- Services Grid -->
      <div class="row g-4">

        <div class="col-6 col-md-4">
          <div class="service-card">
            <div class="service-icon">
              <img src="img/scaling.jpg" alt="Scaling and Polishing">
            </div>
            <h5>Scaling and Polishing</h5>
            <p>Pembersihan menyeluruh untuk membuang plak dan kotoran gigi.</p>
          </div>
        </div>

        <div class="col-6 col-md-4">
          <div class="service-card">
            <div class="service-icon">
              <img src="img/filling.jpg" alt="Filling">
            </div>
            <h5>Filling</h5>
            <p>Rawatan tampalan bagi memulihkan gigi yang berlubang.</p>
          </div>
        </div>

        <div class="col-6 col-md-4">
          <div class="service-card">
            <div class="service-icon">
              <img src="img/whitening.jpg" alt="Whitening">
            </div>
            <h5>Whitening</h5>
            <p>Merawat gigi kelihatan lebih putih dan cerah secara selamat.</p>
          </div>
        </div>

        <div class="col-6 col-md-4">
          <div class="service-card">
            <div class="service-icon">
              <img src="img/denture.jpg" alt="Denture">
            </div>
            <h5>Denture</h5>
            <p>Gigi palsu yang direka khas mengikut keperluan pesakit.</p>
          </div>
        </div>

        <div class="col-6 col-md-4">
          <div class="service-card">
            <div class="service-icon">
              <img src="img/extraction.jpg" alt="Extraction">
            </div>
            <h5>Extraction</h5>
            <p>Pencabutan gigi dilakukan dengan selamat dan minimum kesakitan.</p>
          </div>
        </div>

        <div class="col-6 col-md-4">
          <div class="service-card">
            <div class="service-icon">
              <img src="img/pediatric_dentistry.jpg" alt="Pediatric Dentistry">
            </div>
            <h5>Pediatric Dentistry</h5>
            <p>Penjagaan gigi mesra kanak-kanak dalam suasana yang selesa.</p>
          </div>
        </div>
</div>
      </div>
      <!-- /Services Grid -->

    </div> <!-- end content -->
  </div>
</div>

<!-- Footer -->
<?php include ('asset/footer_index.php'); ?>

<!-- Login Modal (besar + pangkah tindih atas gambar) -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content position-relative">
      <!-- Pangkah absolute tindih atas gambar -->
      <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
      <div class="row g-0">
        <!-- Left side: form -->
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
        <!-- Right side: image -->
        <div class="col-md-6 d-none d-md-block">
          <img src="img/clinic2.jpeg" alt="Login Promo" class="img-fluid h-100 w-100" style="object-fit:cover; border-radius:0 0 0.3rem 0;">
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>