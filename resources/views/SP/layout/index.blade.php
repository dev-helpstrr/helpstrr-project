@include('SP.inc.header')
  <section class="coming-section">
    <div class="container">
      <div class="row justify-content-center">
        <!-- Left Text Section -->
        <div class="col-lg-7 text-center text-lg-start mb-5 mb-lg-0">
          <img src="{{ asset('assets/SP/images/logo.png') }}" alt="Helpstrr" class="logo">
          <h1 class="title mb-3">COMING SOON</h1>
          <p class="subtitle">We're building India's largest Home Service Platform.</p>
          <p class="app-download">Download the<br> Helpstrr App <a href="{{ (config('app.mobile_playstore_url')) }}" target="_blank"><i class="bi bi-android2"></i></a></p>
        </div>

        <!-- Right Image Section -->
        <div class="col-lg-5 text-center">
          <div class="main-sec"><img src="{{ asset('assets/SP/images/coming-soon.png') }}" alt="Helpstrr" class="hero-img"></div>
        </div>
      </div>
    </div>
  </section>

  <!-- Bootstrap JS & Icons -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</body>
</html>
