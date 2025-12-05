 <nav class="navbar navbar-expand-lg" aria-label="Offcanvas navbar large">
	<div class="container">
		<h1><a class="navbar-brand" href="{{ env('APP_URL') }}"><?= env('APP_NAME'); ?>
</a> </h1>
		<button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar2" aria-controls="offcanvasNavbar2" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="offcanvasNavbar2" aria-labelledby="offcanvasNavbar2Label">
			<div class="offcanvas-header">

				<button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
			</div>
			<div class="offcanvas-body">
				<div id="google_translate_element" style="display:none;"></div>
				<div class="justify-content-end flex-grow-1 translator">
				<select id="custom_translate" onchange="translateLanguage(this.value)">
				    <option value="">Select Language</option>
				    <option value="en">English</option>
				    <option value="hi">Hindi</option>
				    <option value="bn">Bengali</option>
				     <option value="ta">Tamil</option>
				    <option value="te">Telugu</option>
				    <option value="mr">Marathi</option>
				    <option value="gu">Gujarati</option>
				    <option value="kn">Kannada</option>
				    <option value="ml">Malayalam</option>
				    <option value="pa">Punjabi</option>
					<option value="ur">Urdu</option>
				</select>
				</div>
				<ul class="navbar-nav pe-3">
					<li class="nav-item"> <a class="nav-link active" href="{{ env('APP_URL') }}">Home</a> </li>
					{{-- <li class="nav-item"> <a class="nav-link" href="#">Benefits</a> </li>
					<li class="nav-item dropdown"> <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Categories</a>
						<ul class="dropdown-menu">
							<li><a class="dropdown-item" href="#">Action</a></li>
							<li><a class="dropdown-item" href="#">Another action</a></li>
							<li><a class="dropdown-item" href="#">Something else here</a></li>
						</ul>
					</li>
					<li class="nav-item"> <a class="nav-link" href="#">Help</a> </li>
					<li class="nav-item"> <a class="nav-link" href="#">About</a> </li> --}}
				</ul>
				{{-- <button class="btn btn-outline-success" type="submit" data-bs-toggle="modal" data-bs-target="#modalRegister">Register Now</button> --}}
			</div>
		</div>
	</div>
</nav>
