  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <a class="navbar-brand" href="{{env('APP_URL')}}"><img src="{{ asset('assets/SP/images/logo.png') }}" alt="Helpstrr"></a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
        <ul class="navbar-nav me-3">
          <li class="nav-item"><a class="nav-link" href="{{env('APP_URL')}}">HOME</a></li>
          <li class="nav-item"><a class="nav-link" href="#">ABOUT US</a></li>
          <li class="nav-item"><a class="nav-link" href="#">OUR SERVICES</a></li>
          <li class="nav-item"><a class="nav-link" href="#">CONTACT US</a></li>
        </ul>
        <a href="{{env('MOBILE_PLAYSTORE_URL')}}" class="btn btn-register">Register Now</a>
      </div>
    </div>
  </nav>
