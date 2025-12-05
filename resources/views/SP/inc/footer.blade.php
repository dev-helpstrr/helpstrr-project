<footer>
    <div class="container">
        <h3><?= env('APP_NAME'); ?></h3>
        <p>Empowering service providers across India</p>
        <ul>
            <li><a href="">Privacy Policy</a></li>
            <li><a href="">Terms of Service</a></li>
            <li><a href="">Contact Us</a></li>
        </ul>
    </div>
</footer>

<script src="{{ asset('js/bootstrap/bootstrap.min.js') }}"></script>

<script src="{{ asset('js/bootstrap/popper.min.js') }}"></script>


{{-- <script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script> --}}
<!-- Load jQuery -->
<script src="{{ asset('js/jquery/jquery-3.7.1.min.js') }}"></script>



<script src="{{ asset('assets/SP/js/otpverify.js') }}"></script>
<script type="text/javascript">

(function() {
  var gt = document.createElement("script");
  gt.src = "https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit";
  gt.async = true;
  document.head.appendChild(gt);
})();

    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
                pageLanguage: 'en'
            },
            'google_translate_element'
        );
    }

    function translateLanguage(lang) {
        var select = document.querySelector("select.goog-te-combo");
        if (select) {
            select.value = lang;
            select.dispatchEvent(new Event("change"));
        }
    }
</script>

</body>

</html>
