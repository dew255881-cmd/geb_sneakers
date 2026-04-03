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
    
    (function(){
        var _0x1a=[37,99,68,101,118,101,108,111,112,101,100,32,98,121,32,84,101,101,114,97,110,97,105,32,84,104,111,110,103,45,117,116,104,97,105,32,40,68,101,119,41,37,99,10,104,116,116,112,115,58,47,47,103,105,116,104,117,98,46,99,111,109,47,100,101,119,50,53,53,56,56,49,45,99,109,100];
        var _0x2b=[99,111,108,111,114,58,32,35,51,52,57,56,100,98,59,32,102,111,110,116,45,115,105,122,101,58,32,49,54,112,120,59,32,102,111,110,116,45,119,101,105,103,104,116,58,32,98,111,108,100,59,32,102,111,110,116,45,102,97,109,105,108,121,58,32,115,97,110,115,45,115,101,114,105,102,59];
        var _0x3c=[99,111,108,111,114,58,32,105,110,104,101,114,105,116,59,32,102,111,110,116,45,115,105,122,101,58,32,49,50,112,120,59,32,102,111,110,116,45,102,97,109,105,108,121,58,32,115,97,110,115,45,115,101,114,105,102,59];
        console.log(String.fromCharCode.apply(null,_0x1a),String.fromCharCode.apply(null,_0x2b),String.fromCharCode.apply(null,_0x3c));
    })();
});
