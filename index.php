<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Klinik Pergigian Diyana</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Old+Standard+TT:wght@400;700&family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/index.css">
  <link rel="stylesheet" href="css/header.css">
  <link rel="stylesheet" href="css/booking_calendar.css">

</head>
<body>

  <!-- Existing project header -->
  <?php include ('asset/header.php'); ?>

  <main>
    <!-- HERO -->
    <section class="hero">
      <div class="container">
        <div class="hero-content">
          <h1>Tak sabar nak tersenyum lebar?</h1>
          <p>Rawatan pergigian profesional, selamat, dan mesra pelanggan untuk membantu anda mengekalkan senyuman yang lebih sihat.</p>

          <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-brand"
                    data-bs-toggle="modal"
                    data-bs-target="#bookingModal">
              <i class="bi bi-calendar2-check me-2"></i>Book Appointment
            </button>

            <a href="services.php" class="btn btn-light-outline">
              Lihat Perkhidmatan <i class="bi bi-arrow-right ms-2"></i>
            </a>
          </div>

          <div class="trust-row">
            <div class="trust-item"><i class="bi bi-person-check"></i> Doktor Berpengalaman</div>
            <div class="trust-item"><i class="bi bi-tools"></i> Peralatan Moden</div>
            <div class="trust-item"><i class="bi bi-shield-check"></i> Mesra & Selamat</div>
            <div class="trust-item"><i class="bi bi-tag"></i> Harga Berpatutan</div>
          </div>
        </div>
      </div>
    </section>

    <!-- PERKHIDMATAN UTAMA -->
    <section class="section" id="perkhidmatan">
      <div class="container">
        <div class="section-heading">
          <h2>Perkhidmatan Utama</h2>
          <p>Perkhidmatan pergigian berkualiti untuk kesihatan mulut dan senyuman anda.</p>
          <div class="heading-line"></div>
        </div>

        <div class="row g-4">

          <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
            <div class="service-card">
              <div class="service-icon"><i class="bi bi-search"></i></div>
              <div class="service-card-body">
                <h4>Pemeriksaan Gigi</h4>
                <p>Pemeriksaan menyeluruh untuk mengetahui keadaan gigi dan mulut.</p>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
            <div class="service-card">
              <div class="service-icon"><i class="bi bi-stars"></i></div>
              <div class="service-card-body">
                <h4>Scaling & Polishing</h4>
                <p>Membersihkan plak dan karang gigi untuk kebersihan mulut.</p>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
            <div class="service-card">
              <div class="service-icon"><i class="bi bi-plus-circle"></i></div>
              <div class="service-card-body">
                <h4>Tampalan Gigi</h4>
                <p>Rawatan gigi berlubang bagi membantu memulihkan fungsi gigi.</p>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
            <div class="service-card">
              <div class="service-icon"><i class="bi bi-brightness-high"></i></div>
              <div class="service-card-body">
                <h4>Pemutihan Gigi</h4>
                <p>Membantu mencerahkan warna gigi dengan rawatan profesional.</p>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
            <div class="service-card">
              <div class="service-icon"><i class="bi bi-emoji-smile"></i></div>
              <div class="service-card-body">
                <h4>Ortodontik</h4>
                <p>Rawatan untuk membantu mendapatkan susunan gigi yang lebih baik.</p>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
            <div class="service-card">
              <div class="service-icon"><i class="bi bi-heart-pulse"></i></div>
              <div class="service-card-body">
                <h4>Cabutan Gigi</h4>
                <p>Cabutan gigi yang selamat berdasarkan penilaian doktor.</p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- KENAPA PILIH KAMI -->
    <section class="section section-soft" id="tentang">
      <div class="container">
        <div class="section-heading">
          <h2>Kenapa Pilih Kami?</h2>
          <p>Keutamaan kami adalah keselesaan, keselamatan dan pengalaman pelanggan.</p>
          <div class="heading-line"></div>
        </div>

        <div class="row g-4">
          <div class="col-12 col-sm-6 col-lg">
            <div class="reason-card">
              <div class="reason-icon"><i class="bi bi-person-badge"></i></div>
              <h4>Doktor Berpengalaman</h4>
              <p>Pasukan doktor gigi yang berkelayakan, berpengalaman dan prihatin.</p>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg">
            <div class="reason-card">
              <div class="reason-icon"><i class="bi bi-gear"></i></div>
              <h4>Peralatan Moden</h4>
              <p>Menggunakan teknologi dan peralatan terkini untuk rawatan yang lebih efektif.</p>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg">
            <div class="reason-card">
              <div class="reason-icon"><i class="bi bi-tag"></i></div>
              <h4>Harga Berpatutan</h4>
              <p>Perkhidmatan berkualiti dengan harga yang berpatutan.</p>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg">
            <div class="reason-card">
              <div class="reason-icon"><i class="bi bi-emoji-smile"></i></div>
              <h4>Mesra Pelanggan</h4>
              <p>Kakitangan yang ramah dan sentiasa membantu dalam setiap lawatan.</p>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg">
            <div class="reason-card">
              <div class="reason-icon"><i class="bi bi-geo-alt"></i></div>
              <h4>Lokasi Strategik</h4>
              <p>Lokasi klinik yang mudah dicapai dan selesa untuk semua pelanggan.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- VISI MISI + LOKASI -->
    <section class="section">
      <div class="container">
        <div class="row g-4">

          <div class="col-lg-6" id="visi-misi">
            <div class="about-card">
              <div class="section-heading text-start mb-4">
                <h2>Visi & Misi</h2>
                <p>Matlamat kami dalam memberikan perkhidmatan pergigian.</p>
                <div class="heading-line ms-0"></div>
              </div>

              <div class="vm-item">
                <div class="vm-icon"><i class="bi bi-bullseye"></i></div>
                <div>
                  <h4>Visi</h4>
                  <p>Menjadi klinik pergigian pilihan utama di kawasan ini dengan menyediakan perkhidmatan berkualiti tinggi dan mesra pelanggan.</p>
                </div>
              </div>

              <div class="vm-item">
                <div class="vm-icon"><i class="bi bi-heart"></i></div>
                <div>
                  <h4>Misi</h4>
                  <p>Memberikan rawatan pergigian yang profesional, selamat, dan berkesan kepada semua pesakit sambil mengekalkan standard kebersihan dan keselamatan yang tinggi.</p>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-6" id="lokasi">
            <div class="location-card">
              <div class="row g-0 h-100">
                <div class="col-md-5">
                  <div class="location-info">
                    <h3>Lokasi Klinik</h3>

                    <div class="contact-line">
                      <i class="bi bi-geo-alt-fill"></i>
                      <span>Klinik Pergigian Diyana, Arau, Perlis, Malaysia</span>
                    </div>

                    <div class="contact-line">
                      <i class="bi bi-clock-fill"></i>
                      <span>Waktu operasi mengikut jadual klinik.</span>
                    </div>

                    <a href="https://www.google.com/maps/search/?api=1&query=Klinik+Pergigian+Diyana+Arau"
                       target="_blank"
                       class="btn btn-brand btn-sm mt-2">
                      <i class="bi bi-map me-1"></i> Lihat Google Maps
                    </a>
                  </div>
                </div>

                <div class="col-md-7">
                  <div class="map-wrap">
                    <iframe
                      src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3020.009999097656!2d100.26815157350349!3d6.428584824262125!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x304ca341c86c2c0f%3A0x1cb70514f1cc41c!2sKlinik%20Pergigian%20Diyana%20Arau!5e1!3m2!1sms!2smy!4v1785920078058!5m2!1sms!2smy"
                      loading="lazy"
                      allowfullscreen=""
                      referrerpolicy="strict-origin-when-cross-origin">
                    </iframe>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- TIPS -->
    <section class="section section-soft" id="tips">
      <div class="container">
        <div class="section-heading">
          <h2>Tips Penjagaan Gigi</h2>
          <p>Amalkan penjagaan gigi yang baik untuk senyuman yang sihat dan menawan.</p>
          <div class="heading-line"></div>
        </div>

        <div class="row g-4">

          <div class="col-12 col-sm-6 col-lg">
            <div class="tip-card">
              <div class="tip-number">1</div>
              <i class="bi bi-brush"></i>
              <h4>Berus Gigi 2 Kali Sehari</h4>
              <p>Berus gigi pada waktu pagi dan sebelum tidur dengan ubat gigi berfluorida.</p>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg">
            <div class="tip-card">
              <div class="tip-number">2</div>
              <i class="bi bi-droplet"></i>
              <h4>Gunakan Flos Gigi</h4>
              <p>Bersihkan celah gigi setiap hari untuk mengurangkan plak dan sisa makanan.</p>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg">
            <div class="tip-card">
              <div class="tip-number">3</div>
              <i class="bi bi-cup-hot"></i>
              <h4>Kurangkan Gula</h4>
              <p>Hadkan makanan dan minuman manis bagi membantu mencegah gigi berlubang.</p>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg">
            <div class="tip-card">
              <div class="tip-number">4</div>
              <i class="bi bi-cup-straw"></i>
              <h4>Minum Air Kosong</h4>
              <p>Air kosong membantu membilas sisa makanan dan mengekalkan kelembapan mulut.</p>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg">
            <div class="tip-card">
              <div class="tip-number">5</div>
              <i class="bi bi-calendar2-check"></i>
              <h4>Jumpa Doktor Gigi</h4>
              <p>Lakukan pemeriksaan secara berkala mengikut nasihat profesional pergigian.</p>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section class="section" id="faq">
      <div class="container">
        <div class="section-heading">
          <h2>FAQ (Soalan Lazim)</h2>
          <p>Jawapan ringkas kepada persoalan yang sering ditanya oleh pelanggan.</p>
          <div class="heading-line"></div>
        </div>

        <div class="faq-wrap">
          <div class="accordion" id="faqAccordion">

            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                  <i class="bi bi-question-circle me-3"></i>
                  Adakah perlu membuat janji temu?
                </button>
              </h2>
              <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                  Ya. Janji temu membantu memastikan masa rawatan lebih teratur. Anda boleh membuat tempahan melalui sistem janji temu.
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                  <i class="bi bi-question-circle me-3"></i>
                  Adakah klinik menerima walk-in?
                </button>
              </h2>
              <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                  Walk-in boleh diterima bergantung pada kekosongan slot dan keadaan operasi klinik pada hari tersebut.
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                  <i class="bi bi-question-circle me-3"></i>
                  Adakah scaling sakit?
                </button>
              </h2>
              <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                  Sensasi semasa scaling berbeza mengikut keadaan gigi dan gusi setiap individu. Doktor akan menilai keadaan anda sebelum rawatan.
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                  <i class="bi bi-question-circle me-3"></i>
                  Berapa lama rawatan tampalan gigi?
                </button>
              </h2>
              <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                  Tempoh rawatan bergantung pada keadaan dan jenis gigi yang dirawat. Doktor akan menerangkan prosedur selepas pemeriksaan.
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                  <i class="bi bi-question-circle me-3"></i>
                  Apakah kaedah pembayaran yang diterima?
                </button>
              </h2>
              <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                  Kaedah pembayaran adalah tertakluk kepada kemudahan pembayaran yang disediakan oleh pihak klinik.
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                  <i class="bi bi-question-circle me-3"></i>
                  Bagaimana cara membuat temu janji?
                </button>
              </h2>
              <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                  Klik butang <strong>Book Appointment</strong> dan log masuk ke sistem untuk meneruskan proses tempahan janji temu.
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="container pb-5">
      <div class="cta">
        <div class="row align-items-center g-4">
          <div class="col-lg-8">
            <h2>Sedia untuk senyuman yang lebih sihat?</h2>
            <p>Tempah temu janji anda bersama Klinik Pergigian Diyana hari ini.</p>
          </div>
          <div class="col-lg-4 text-lg-end">
            <button class="btn"
                    data-bs-toggle="modal"
                    data-bs-target="#bookingModal">
              <i class="bi bi-calendar2-check me-2"></i>Book Appointment
            </button>
          </div>
        </div>
      </div>
    </section>

  </main>

  <!-- Existing project footer -->
  <?php include ('asset/footer_index.php'); ?>

  <!-- Login Modal -->
  <div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content position-relative">
        <button type="button"
                class="btn-close position-absolute top-0 end-0 m-3"
                data-bs-dismiss="modal"
                aria-label="Close"></button>

        <div class="row g-0">
          <div class="col-md-6 p-4 p-lg-5 text-center">
            <h4 class="mb-4 fw-bold">Login</h4>

            <form method="POST" action="login.php">
              <div class="mb-3 text-start">
                <label class="form-label">Username</label>
                <input type="text"
                       name="username"
                       class="form-control"
                       placeholder="Enter Username"
                       required>
              </div>

              <div class="mb-3 text-start">
                <label class="form-label">Password</label>
                <input type="password"
                       name="password"
                       class="form-control"
                       placeholder="Enter Password"
                       required>
              </div>

              <button type="submit" class="btn btn-brand w-100">
                Login
              </button>
            </form>
          </div>

          <div class="col-md-6 d-none d-md-block">
            <img src="img/clinic2.jpeg"
                 alt="Klinik Pergigian Diyana"
                 class="img-fluid h-100 w-100"
                 style="object-fit:cover; border-radius:0 0.3rem 0.3rem 0;">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Booking Modal: dentist, treatment, calendar, time slot -->
  <div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content position-relative">
        <button type="button"
                class="btn-close position-absolute top-0 end-0 m-3"
                data-bs-dismiss="modal"
                aria-label="Close"></button>

        <div class="row g-0">
          <div class="col-md-6 p-4 p-lg-5 text-center booking-col">
            <h4 class="mb-4 fw-bold">Booking Appointment</h4>

            <div class="mb-3 text-start">
              <label for="dentistSelect" class="form-label">Select Dentist</label>
              <div class="themed-select">
                <i class="bi bi-person-badge select-icon"></i>
                <select id="dentistSelect" class="form-select" required>
                  <option value="">Select Dentist</option>
                  <?php
                    // TODO: replace this hardcoded list with a query against your
                    // dentists table, or "SELECT DISTINCT dentist FROM appointments",
                    // if you don't have a dedicated dentists table yet.
                    // Bug fix: this list previously didn't match the real dentist
                    // names used in our_dentist.php ('Dr Nur Diyana', 'Dr Huda
                    // Sofiah'), so bookings made here for the other names could
                    // never match a real doctor or show correct availability.
                    $dentist_list = ['Dr Nur Diyana', 'Dr Huda Sofiah','Dr Afif Azmi','Dr Mohd Ghazali','Dr Marina Shanthini',];
                    foreach ($dentist_list as $d) {
                        echo '<option value="' . htmlspecialchars($d) . '">' . htmlspecialchars($d) . '</option>';
                    }
                  ?>
                </select>
              </div>
            </div>

            <div class="mb-3 text-start">
              <label for="treatment" class="form-label">Treatment Type</label>
              <div class="themed-select">
                <i class="bi bi-heart-pulse select-icon"></i>
                <select id="treatment" class="form-select" required>
                    <option value="">Select Treatment</option>
                    <option>Scaling</option>
                    <option>Filling</option>
                    <option>Extraction</option>
                    <option>Braces</option>
                    <option>Whitening</option>
                </select>
              </div>
            </div>

            <!-- Availability calendar: hidden until a dentist is chosen,
                 since open/full/unavailable days are per-dentist. -->
            <div class="mb-3 text-start calendar-card" id="calendarWrap" style="display:none;">
              <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                <button type="button" class="btn btn-link cal-nav p-0" id="prevMonthBtn"><i class="bi bi-chevron-left"></i></button>
                <h6 class="cal-title mb-0" id="calMonthLabel"></h6>
                <button type="button" class="btn btn-link cal-nav p-0" id="nextMonthBtn"><i class="bi bi-chevron-right"></i></button>
              </div>
              <div class="calendar-grid weekday-row">
                <div>SU</div><div>MO</div><div>TU</div><div>WE</div><div>TH</div><div>FR</div><div>SA</div>
              </div>
              <div class="calendar-grid" id="calendarDays"></div>
              <div class="legend d-flex flex-wrap gap-3 mt-2 px-1 small">
                <span><i class="dot dot-available"></i> Available</span>
                <span><i class="dot dot-unavailable"></i> Not available</span>
                <span><i class="dot dot-selected"></i> Selected</span>
                <span><i class="dot dot-full"></i> Fully booked</span>
              </div>
            </div>

            <div class="mb-3 text-start" id="timeSlotSection" style="display:none;">
              <label class="form-label">Available Times</label>
              <div class="d-flex flex-wrap gap-2" id="timeSlotList"></div>
            </div>

            <div class="alert alert-warning small d-none" id="bookingMsg"></div>

            <button type="button" class="btn btn-brand w-100" id="continueToRegisterBtn" disabled>
              Continue <i class="bi bi-arrow-right ms-1"></i>
            </button>
          </div>

          <div class="col-md-6 d-none d-md-block">
            <img src="img/back2.jpg"
                 alt="Klinik Pergigian Diyana"
                 class="img-fluid h-100 w-100"
                 style="object-fit:cover; border-radius:0 0.3rem 0.3rem 0;">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Register Modal: opens after a slot is chosen in the booking modal.
       Submits both the account fields AND the chosen slot (carried in
       hidden inputs) to register.php in one POST. -->
  <div class="modal fade" id="registerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content position-relative">
        <button type="button"
                class="btn-close position-absolute top-0 end-0 m-3"
                data-bs-dismiss="modal"
                aria-label="Close"></button>

        <div class="p-4 p-lg-5">
          <button type="button" class="btn btn-link p-0 mb-3" id="backToBookingBtn">
            <i class="bi bi-arrow-left"></i> Back to appointment details
          </button>

          <h4 class="mb-1 fw-bold">Almost done</h4>
          <p class="text-muted small mb-4" id="registerSummary"></p>

          <form id="registerForm">
            <div class="mb-3 text-start">
              <label for="regName" class="form-label">Full Name</label>
              <input type="text" name="name" id="regName" class="form-control" placeholder="e.g. Aiman Yusof" required>
            </div>

            <div class="mb-3 text-start">
              <label for="regUsername" class="form-label">Username</label>
              <input type="text" name="username" id="regUsername" class="form-control" placeholder="Choose a username" required>
            </div>

            <div class="mb-3 text-start">
              <label for="regEmail" class="form-label">Email</label>
              <input type="email" name="email" id="regEmail" class="form-control" placeholder="name@example.com" required>
            </div>

            <div class="mb-3 text-start">
              <label for="regPhone" class="form-label">Phone Number</label>
              <input type="tel" name="phone" id="regPhone" class="form-control" placeholder="e.g. 012-3456789" required>
            </div>

            <div class="mb-3 text-start">
              <label for="regNic" class="form-label">NIC</label>
              <input type="text" name="nic" id="regNic" class="form-control" placeholder="e.g. 991231-14-5566" required>
            </div>

            <div class="mb-3 text-start">
              <label for="regPassword" class="form-label">Password</label>
              <input type="password" name="password" id="regPassword" class="form-control" placeholder="At least 8 characters" minlength="8" required>
            </div>

            <div class="alert alert-warning small d-none" id="registerMsg"></div>

            <!-- Carried over from the booking modal -->
            <input type="hidden" name="dentist" id="hiddenDentist">
            <input type="hidden" name="treatment" id="hiddenTreatment">
            <input type="hidden" name="date" id="hiddenDate">
            <input type="hidden" name="time" id="hiddenTime">

            <button type="submit" class="btn btn-brand w-100" id="submitBookingBtn">
              Register &amp; Book Appointment
            </button>
          </form>

          <!-- Shown in place of the form once register.php confirms the booking -->
          <div id="bookingSuccess" class="text-start d-none">
            <div class="text-center mb-3">
              <i class="bi bi-check-circle-fill" style="font-size:2.5rem; color:#2f7a6b;"></i>
            </div>
            <h5 class="text-center mb-3">Appointment Pending!</h5>
            <h6 class="text-center text-muted mb-3">Your appointment has been successfully booked and is now pending confirmation. Login to your account to view the status.</h6>
            <p class="text-center text-muted mb-4" id="bookingSuccessDetails"></p>
            <button type="button" class="btn btn-brand w-100" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/booking_calendar.js"></script>
</body>
</html>