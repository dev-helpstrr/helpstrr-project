@include('SP.inc.header')
@include('SP.inc.nav-index')

<!-- Hero Section -->
<section class="home-banner">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="banner-conent">
                    <h1>Grow Your Income with <BR>Daily Opportunities</h1>

                </div>
            </div>
            <div class="col-md-6">
                <div class="slider-block">
                    <div class="slider">
                        <img src="{{ asset('assets/SP/images/slider-a.png') }}" alt="Cleaning Professional">
                        <img src="{{ asset('assets/SP/images/slider-b.png') }}" alt="Cleaning Professional" />
                        <img src="{{ asset('assets/SP/images/slider-c.png') }}" alt="Cleaning Professional" />
                        <img src="{{ asset('assets/SP/images/slider-d.png') }}" alt="Cleaning Professional" />
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
<!-- Hero Section -->

<!-- Refer Section -->
<section class="refer-section">
    <div class="container">
        <div class="row align-items-center">

            <div class="col-md-5">
                <div class="txt-block">
                    <h2>Join the {{ env('APP_NAME') }} <br>
                        Partner Network</h2>
                    <p>Earn income by offering your skills, Flexible hours, verified clients, and tr'usted payments.</p>
                    <a href="{{ (config('app.mobile_playstore_url')) }}"><img src="{{ asset('assets/SP/images/playstore.png') }}"
                            alt="playstore"></a>
                </div>
            </div>
            <div class="col-md-7">

                <div class="refer-box">

                    <h2>Refer a Friend</h2>
                    <form id="referForm" class="mt-3">
                        @csrf
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="text" id="name" name="name" class="form-control"
                                    placeholder="Name" required>
                            </div>
                            <div class="col-md-6">
                                <input type="text" id="phone" name="phone" class="form-control"
                                    placeholder="Number" required>
                            </div>
                            <div class="col-12">
                                <select id="job_role_id" name="job_role_id" class="form-control" required>
                                    <option value="" disabled selected>Select Job Role</option>
                                    @foreach ($job_roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->role_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn w-100">SEND</button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services -->
<section class="services">
    <div class="container">
        <div id="owl-demo" class="owl-carousel owl-theme">
            <div class="item">
                <figure class="one"><img src="{{ asset('assets/SP/images/maid.png') }}" alt="Maids"></figure>
                <h5>Maids</h5>
                <p>Daily or part-time maids for cleaning, cooking, or household assistance.</p>
            </div>
            <div class="item">
                <figure class="two"><img src="{{ asset('assets/SP/images/driver.png') }}" alt="Drivers"></figure>
                <h5>Drivers</h5>
                <p>On-demand or permanent drivers for personal and corporate needs.</p>
            </div>
            <div class="item">
                <figure class="three"><img src="{{ asset('assets/SP/images/bartender.png') }}" alt="Bartenders">
                </figure>
                <h5>Bartenders</h5>
                <p>Skilled bartenders for private parties, events, or retaurants.</p>
            </div>
            <div class="item">
                <figure class="four"><img src="{{ asset('assets/SP/images/chef.png') }}" alt="Chefs"></figure>
                <h5>Chefs</h5>
                <p>Cooks and chefs for home, events and hospitality requirements.</p>
            </div>


        </div>
    </div>
</section>

<!-- About Section -->
<section class="about-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2>About {{ env('APP_NAME') }}</h2>
                <h5>We’re More Than a Platform</h5>
                <p class="m-b">We’re a community that bridges the gap between service providers and customers through
                    structured onboarding and quality assurance.</p>
                <div class="mission-box">
                    <h4>Our Mission</h4>
                    <p>To create a trusted, tech-enabled ecosystem where service providers get dignified work
                        opportunities and customers receive reliable services at their doorstep.</p>
                </div>
                <div class="txt-box">
                    <p>{{ env('APP_NAME') }} connects households and businesses with reliable service providers in
                        real-time—from professional chefs to skilled drivers and trusted maids.</p>
                </div>
            </div>
            <div class="col-md-4 img-sec">
                <figure><img src="{{ asset('assets/SP/images/logo.png') }}" alt="Helpstrr" class="img-fluid"></figure>
            </div>
        </div>
    </div>
</section>

<!-- Experience Section -->
<section class="experience-section">
    <div class="container">

        <div class="row align-items">
            <div class="col-md-5">
                <h2>Backed by <br>Experience</h2>
                <img src="{{ asset('assets/SP/images/chefs.jpg') }}" alt="Worker Image">
            </div>
            <div class="col-md-7">
                <div class="content-section">
                    <p>{{ env('APP_NAME') }} is a proud sister concern of 2COMS Group and Gig4U—bringing three decades
                        of workforce management expertise and gig economy innovation to home services.</p>
                    <div class="item">
                        <figure><img src="{{ asset('assets/SP/images/work.png') }}" alt="Work on Your Terms"></figure>
                        <div class="content-block">
                            <h5>Work on Your Terms:</h5>
                            Enjoy complete freedom to choose when, where, and how you work.
                        </div>
                    </div>
                    <div class="item">
                        <figure><img src="{{ asset('assets/SP/images/quality.png') }}" alt="Comprehensive Insurance">
                        </figure>
                        <div class="content-block">
                            <h5>Comprehensive Insurance:</h5>
                            Stay protected with coverage designed specifically for service providers.
                        </div>
                    </div>
                    <div class="item">
                        <figure><img src="{{ asset('assets/SP/images/support.png') }}" alt="24/7 Dedicated Support">
                        </figure>
                        <div class="content-block">
                            <h5>24/7 Dedicated Support:</h5>
                            Get assistance anytime you need, whenever you need it.
                        </div>
                    </div>
                    <div class="item">
                        <figure><img src="{{ asset('assets/SP/images/payment.png') }}" alt="Payment Options">
                        </figure>
                        <div class="content-block">
                            <h5>Flexible Payment Options:</h5>
                            Choose payment schedules that suit your needs.
                        </div>
                    </div>
                    <div class="item last">
                        <figure><img src="{{ asset('assets/SP/images/no-commission.png') }}" alt="No Commission">
                        </figure>
                        <div class="content-block">
                            <h5>Minimal or No Commission:</h5>
                            Keep more of what you earn with our low-fee or zero-commission model.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Support Section -->
<section class="support-section">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <img src="{{ asset('assets/SP/images/cta.png') }}" alt="">
            </div>
            <div class="col-md-8">
                <div class="txt-block">
                    <h2>Still Have Questions</h2>
                    <p>Our support team is here to help you 24/7</p>
                    <div class="btn-area">
                        <a href="tel:7604023124" class="btn btn-call">Call Support</a>

                        <!-- WhatsApp -->
                        <a href="https://wa.me/917604023124" target="_blank" class="btn btn-whatsapp">WhatsApp
                            Call</a>

                        <a href="{{ (config('app.mobile_playstore_url')) }}" class="btn btn-dark">Register Now</a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>


@include('SP.inc.footer-index')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $('#referForm').on('submit', function(e) {
            e.preventDefault();

            const name = $('#name').val().trim();
            const phone = $('#phone').val().trim();
            const job_role_id = $('#job_role_id').val();

            // Basic Validation
            if (!name || !phone || !job_role_id) {
                Swal.fire({
                    icon: 'warning',
                    title: 'All fields are required!',
                    confirmButtonColor: '#ed246f'
                });
                return;
            }

            // Phone number validation (10 digits)
            if (!/^[6-9]\d{9}$/.test(phone)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid phone number!',
                    text: 'Please enter a valid 10-digit Indian phone number.',
                    confirmButtonColor: '#ed246f'
                });
                return;
            }

            $.ajax({
                url: "{{ url('sp/referral') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    referral_to_name: name,
                    referral_to_phone: phone,
                    referral_to_job_role: job_role_id
                },
                beforeSend: function() {
                    Swal.showLoading();
                },
                success: function(response) {
                    Swal.close();
                    Swal.fire({
                        icon: 'success',
                        title: 'Referral Sent!',
                        text: response.message ||
                            'Thank you for referring your friend!',
                        confirmButtonColor: '#6b3080'
                    });
                    $('#referForm')[0].reset();
                },
                error: function(xhr) {
                    Swal.close();
                    let msg = 'Something went wrong. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: msg,
                        confirmButtonColor: '#ed246f'
                    });
                }
            });
        });
    });
</script>
