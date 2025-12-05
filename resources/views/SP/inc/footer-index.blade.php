  <footer class="footer">
    <div class="container">
		<div class="row">
			<div class="col-md-3 logo-block">
				<img src="{{ asset('assets/SP/images/logo.png') }}" alt="Helpstrr" class="mb-2">
			</div>
			<div class="col-md-6">
				<div class="footer-link">`
					<ul>
						<li><a href="#">Home</a> |</li>
						<li><a href="#">About Us</a> | </li>
						<li><a href="#">Contact Us</a></li>
					</ul>
				</div>
			</div>
			<div class="col-md-3">
				<div class="social-link">
					<ul>
						<li><a href="{{ env('WEBSITE_FOOTER_FACEBOOK_URL') }}" target="_blank"><i class="fa fa-facebook"></i></a></li>
						<li><a href="{{ env('WEBSITE_FOOTER_LINKEDIN_URL') }}" target="_blank"><i class="fa fa-linkedin"></i></a></li>
						<li><a href="{{ env('WEBSITE_FOOTER_TWITTER_URL') }}" target="_blank"><i class="fa fa-twitter"></i></a></li>
					</ul>
				</div>
			</div>
    </div>
  </footer>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="{{ asset('assets/SP/js/bootstrap.bundle.min.js') }}" ></script><script src="{{ asset('assets/SP/js/owl.carousel.min.js') }}"></script>

<script>
$(document).ready(function(){
  const slides = $('.slider img');
  let index = 0;
  const total = slides.length;
  const duration = 5000; // time between slides (ms)

  setInterval(() => {
    slides.eq(index).removeClass('active');
    index = (index + 1) % total;
    slides.eq(index).addClass('active');
  }, duration);
});

jQuery("#owl-demo").owlCarousel({
  autoplay: true,
  rewind: false, /* use rewind if you don't want loop */
  margin: 20,
  loop: true,
   /*
  animateOut: 'fadeOut',
  animateIn: 'fadeIn',
  */
  responsiveClass: true,
  autoHeight: true,
  autoplayTimeout: 7000,
  smartSpeed: 800,
  nav: false,

  responsive: {
    0: {
      items: 1
    },

    600: {
      items: 2
    },

    1024: {
      items: 3
    },

    1366: {
      items: 3
    }
  }
});
</script>
</body>
</html>
