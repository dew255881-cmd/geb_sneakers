document.addEventListener('DOMContentLoaded', function() {

    var toggle = document.querySelector('.mobile-toggle');
    var mobileMenu = document.querySelector('.mobile-menu');

    if (toggle && mobileMenu) {
        toggle.addEventListener('click', function() {
            mobileMenu.classList.toggle('open');
            toggle.innerHTML = mobileMenu.classList.contains('open') ? '&#10005;' : '&#9776;';
        });
    }

    document.querySelectorAll('.size-btn:not(.disabled)').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.size-btn').forEach(function(b) {
                b.classList.remove('selected');
            });
            btn.classList.add('selected');
            var input = document.getElementById('selected_size');
            if (input) {
                input.value = btn.getAttribute('data-size');
            }
        });
    });

    var addToCartForm = document.getElementById('addToCartForm');
    if (addToCartForm) {
        addToCartForm.addEventListener('submit', function(e) {
            var sizeInput = document.getElementById('selected_size');
            if (!sizeInput || !sizeInput.value) {
                e.preventDefault();
                alert('กรุณาเลือกไซส์');
            }
        });
    }

    var uploadArea = document.querySelector('.upload-area');
    var fileInput = document.querySelector('.upload-area input[type="file"]');
    if (uploadArea && fileInput) {
        fileInput.addEventListener('change', function() {
            var fileName = this.files[0] ? this.files[0].name : '';
            var textEl = uploadArea.querySelector('.upload-text');
            if (textEl && fileName) {
                textEl.textContent = fileName;
            }
        });
    }

    document.querySelectorAll('.qty-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var form = btn.closest('form');
            var action = btn.getAttribute('data-action');
            var qtyDisplay = form.querySelector('.qty-value');
            var qtyInput = form.querySelector('input[name="qty"]');
            var current = parseInt(qtyInput.value);

            if (action === 'increase') {
                qtyInput.value = current + 1;
                if (qtyDisplay) qtyDisplay.textContent = current + 1;
            } else if (action === 'decrease' && current > 1) {
                qtyInput.value = current - 1;
                if (qtyDisplay) qtyDisplay.textContent = current - 1;
            }

            form.submit();
        });
    });

    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(function() {
                alert.remove();
            }, 300);
        }, 4000);
    });

    // Hero Slider
    var slides = document.querySelectorAll('.hero-slider .slide');
    var dots = document.querySelectorAll('.hero-slider .slider-dot');
    var prevBtn = document.querySelector('.hero-slider .slider-btn.prev');
    var nextBtn = document.querySelector('.hero-slider .slider-btn.next');
    
    if (slides.length > 0) {
        var currentSlide = 0;
        var slideInterval;

        function showSlide(index) {
            slides.forEach(function(slide) { slide.classList.remove('active'); });
            if (dots.length > 0) {
                dots.forEach(function(dot) { dot.classList.remove('active'); });
                dots[index].classList.add('active');
            }
            slides[index].classList.add('active');
            currentSlide = index;
        }

        function nextSlide() {
            var next = (currentSlide + 1) % slides.length;
            showSlide(next);
        }

        function prevSlide() {
            var prev = (currentSlide - 1 + slides.length) % slides.length;
            showSlide(prev);
        }

        function startSlideShow() {
            slideInterval = setInterval(nextSlide, 5000);
        }

        function stopSlideShow() {
            clearInterval(slideInterval);
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                nextSlide();
                stopSlideShow();
                startSlideShow();
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                prevSlide();
                stopSlideShow();
                startSlideShow();
            });
        }

        dots.forEach(function(dot, index) {
            dot.addEventListener('click', function() {
                showSlide(index);
                stopSlideShow();
                startSlideShow();
            });
        });

        startSlideShow();
    }

    // Password Visibility Toggle
    document.querySelectorAll('.password-toggle-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var input = btn.parentElement.querySelector('input');
            var icon = btn.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

});
