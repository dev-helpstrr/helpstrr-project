@include('SP.inc.header')
@include('SP.inc.nav-signup')

<div class="container">
    <div class="section">
        <div class="center">
            <h2>Select your Job Role</h2>
        </div>
        <ul class="role-type">
            @foreach ($job_roles->whereIn('id', [1, 2, 27]) as $role)
                <li data-id="{{ $role->zoho_job_role_id }}">
                    <a href="#">
                        <img src="{{ asset('assets/SP/images/' . strtolower(str_replace(' ', '-', $role->role_name)) . '.png') }}"
                            alt="{{ $role->role_name }}">
                        {{ $role->role_name }}
                    </a>
                </li>
            @endforeach
        </ul>
        <!--content inner-->
        <!--multisteps-form-->

        <div class="multisteps-form shadow p-4 rounded bg-white ">

            <!--progress bar-->
            <div class="row">
                <div class="col-12">
                    <div class="multisteps-form__progress">
                        <button class="multisteps-form__progress-btn js-active" type="button" title="User Info">
                            <div class="bullet active">
                                <span><i class="fa fa-mobile-phone"></i></span>
                            </div>
                            <div class="check fa fa-mobile active"></div>
                        </button>


                        <button class="multisteps-form__progress-btn" type="button" title="Address">
                            <div class="bullet">
                                <span><i class="fa fa-lock"></i></span>
                            </div>
                            <div class="check fa fa-lock active"></div>
                        </button>
                        <button class="multisteps-form__progress-btn" type="button" title="Order Info">
                            <div class="bullet">
                                <span><i class="fa fa-user"></i></span>
                            </div>
                            <div class="check fa fa-user"></div>
                        </button>
                        <button class="multisteps-form__progress-btn last" type="button" title="Comments">
                            <div class="bullet ">
                                <span><i class="fa fa-thumbs-up"></i></span>
                            </div>
                            <div class="check fa fa-thumbs-up active"></div>
                        </button>
                    </div>
                </div>
            </div>
            <!--form panels-->
            <div class="row">
                <div class="col-12">
                    <form class="multisteps-form__form">
                        <!--single form panel-->
                        <div class="multisteps-form__panel  js-active" data-animation="slideHorz">

                            <div class="multisteps-form__content">
                                <div class="form-row mt-4">
                                    <div class="col-12">
                                        <label>Mobile No.</label>
                                        <input class="multisteps-form__input form-control" type="tel" id="otp-phone"
                                            required />
                                    </div>

                                </div>
                                <div class="button-row d-flex mt-4">
                                    <button class="btn btn-primary js-btn-next" type="button" id="btn-send-otp"
                                        title="Next">Next</button>
                                </div>
                            </div>
                        </div>
                        <!--single form panel-->
                        <div class="multisteps-form__panel" data-animation="slideHorz">
                            <div class="multisteps-form__content">
                                <div class="form-row mt-4">
                                    <div class="col-12">
                                        <label>OTP</label>
                                        <input class="multisteps-form__input form-control" type="number" id="otp-code"
                                            maxlength="6" placeholder="Enter OTP" required />
                                    </div>
                                </div>
                                <div class="button-row d-flex mt-4">
                                    <button class="btn btn-primary js-btn-prev" type="button"
                                        title="Prev">Prev</button>
                                    <button class="btn btn-primary js-btn-next" type="button" title="Next"
                                        id="btn-verify-otp">Next</button>
                                </div>
                            </div>
                        </div>
                        <!--single form panel-->
                        <div class="multisteps-form__panel" data-animation="slideHorz">
                            <div class="multisteps-form__content">
                                <div class="form-row">
                                    <div class="form-row mt-4">
                                        <input type="hidden" id="ref_id" value="{{ $decodedRefId ?? '' }}">

                                        <div class="col-12">
                                            <div class="label">First Name<span>*</span></div>
                                            <input type="text" id="first_name" class="form-control" value=""
                                                required />
                                        </div>
                                        <div class="col-12">
                                            <div class="label">Last Name<span>*</span></div>
                                            <input type="text" id="last_name" class="form-control" value=""
                                                required />
                                        </div>
                                        <div class="col-12">
                                            <div class="label">Phone Number<span>*</span></div>
                                            <input type="tel" class="form-control" id="mobile_number" value=""
                                                required />
                                        </div>
                                        <div class="col-12">
                                            <div class="label">Whatsapp No<span>*</span></div>
                                            <input type="tel" class="form-control" id="whatsapp_no" value=""
                                                required />
                                        </div>
                                        <div class="col-12">
                                            <div class="label">Date of Birth<span>*</span></div>
                                            <input type="date" class="form-control" id="dob" value=""
                                                required />
                                        </div>
                                        <div class="col-12">
                                            <div class="label">Email<span>*</span></div>
                                            <input type="email" class="form-control" id="email" value=""
                                                required />
                                        </div>
                                        <div class="col-12">
                                            <div class="label">Interested Job Role<span>*</span></div>
                                            <div class="custom-multiselect">
                                                <div class="selected-options" id="selectedOptions"></div>
                                                <input type="text" id="searchBox" placeholder="Search..." />
                                                <div class="options-list" id="optionsList">

                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 mt-2">
                                            <div class="label">Prior Experience Job-Role</div>
                                            <select ame="prior_experience" id="prior_experience"
                                                class="form-control">
                                                <option disabled selected>-- Select Option --</option>

                                                @foreach ($job_roles as $role)
                                                    <option value="{{ $role->zoho_job_role_id }}">
                                                        {{ $role->role_name }}</option>
                                                @endforeach
                                            </select>


                                        </div>
                                        <div class="col-12">
                                            <div class="label">Pincode<span>*</span></div>
                                            <input type="text" class="form-control" id="pincode"
                                                placeholder="Enter Pincode" required />

                                            <div id="pincode-message" style="margin-top:5px;font-size:14px;">
                                            </div>
                                            <div id="pincode-options"></div>
                                            <!-- for listing multiple results -->
                                        </div>
                                        <div class="col-12">
                                            <div class="label">Country<span>*</span></div>
                                            <select name="country_id" id="country" class="form-control" required>

                                                @foreach ($countries as $country)
                                                    <option value="{{ $country->zoho_country_id }}" selected disabled>
                                                        {{ $country->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <div class="label">State<span>*</span></div>
                                            <select name="state_id" id="state" class="form-control" required>
                                                @foreach ($states as $state)
                                                    <option value="{{ $state->zoho_state_id }}" selected disabled>
                                                        {{ $state->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <div class="label">City<span>*</span></div>
                                            <select name="city_id" id="city" class="form-control" required>
                                                <option value="" disabled selected>-- Select City --
                                                </option>
                                                @foreach ($cities as $city)
                                                    <option value="{{ $city->zoho_city_id }}">
                                                        {{ $city->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>


                                        <div class="col-12">
                                            <div class="label">Profile Status</div>
                                            <select name="profile_status" id="profile_status" class="form-control">
                                                <option value="I am a Student">I am a Student</option>
                                                <option value="I am Housewife">I am Housewife</option>
                                                <option value="I am Retired">I am Retired</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="label">Gender</label>
                                            <select class="form-control"name="gender" id="gender">
                                                <option value="">-- Select Option --</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>

                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <input type="checkbox" id=""> By clicking on sign up*
                                        </div>
                                        <div class="col-12">
                                            You agree to the <a href="https://gig4u.co/user-agreement">user
                                                agreement </a> and terms and conditions of Gig4u. Please read
                                            our privacy policy to learn more about how Gig4u collects, uses,
                                            shares, and protects your personal data.
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="button-row d-flex mt-4 col-12">
                                            <button class="btn btn-primary js-btn-prev" type="button"
                                                title="Prev">Prev</button>
                                            <button class="btn btn-primary ml-auto js-btn-next" id="update-details"
                                                type="button" title="Next">Next</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--single form panel-->
                        <div class="multisteps-form__panel center" data-animation="slideHorz">
                            <h3 class="multisteps-form__title">Thank you</h3>
                            <div class="multisteps-form__content">
                                for applying
                                <div class="button-row mt-4">
                                    <button class="btn btn-primary js-btn-prev" type="button"
                                        title="Prev">Prev</button>

                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>

    </div>
</div>

@include('SP.inc.footer')
