<footer class="lp-footer">
    <!-- Branding -->
    <div class="lp-footer-center lp-footer-mb-30">
        <h2 class="lp-footer-h2">ProspectGoat</h2>
    </div>

    <!-- Contact Row -->
    <div class="lp-footer-contact-row lp-footer-mb-30">
        <div>
            <h5>Phone</h5>
            <p><a href="tel:+14705881505" class="lp-footer-link">(470) 588-1505</a></p>
        </div>

        <div>
            <h5>Office</h5>
            <p><a href="tel:+16784940644" class="lp-footer-link">(678) 494-0644</a></p>
        </div>

        <div>
            <h5>Email</h5>
            <p><a href="mailto:hello@prospectgoat.com" class="lp-footer-link">hello@prospectgoat.com</a></p>
        </div>
    </div>

    <!-- Brokerage Info (CRITICAL FOR GREC) -->
    <div class="lp-footer-center lp-footer-mt-30">
        <h2 class="lp-footer-h2 lp-footer-h2-tight">Keller Williams Realty Partners</h2>
        <p>722 Stonecroft Lane, Woodstock, GA 30188</p>

        <p class="lp-footer-small lp-footer-mt-10">
            ProspectGoat is affiliated with Keller Williams Realty Partners.<br>
            Licensed Real Estate Services in Georgia.
        </p>
    </div>

    <!-- Logos -->
    <div class="lp-footer-center lp-footer-mt-20">
        <img src="{{ asset('KellerWilliams_Realty_Partners_Logo_CMYK.jpg') }}"
            alt="Keller Williams Realty Partners"
            class="lp-footer-logo lp-footer-logo-main"
            loading="lazy"
            decoding="async"
            onerror="this.onerror=null; this.src='https://prospectgoat.com/wp-content/uploads/2026/07/KellerWilliams_Realty_Partners_Logo_CMYK.jpg';">

        <img src="{{ asset('independent-operator.png') }}"
            alt="Each office independently owned and operated"
            class="lp-footer-logo lp-footer-logo-secondary"
            loading="lazy"
            decoding="async"
            onerror="this.onerror=null; this.src='https://prospectgoat.com/wp-content/uploads/2026/07/independent-operator.png';">
    </div>

    <!-- Copyright -->
    <div class="lp-footer-center lp-footer-small lp-footer-mt-30">
        <p>Copyright &copy; {{ now()->year }} ProspectGoat. All Rights Reserved.</p>
    </div>

</footer>