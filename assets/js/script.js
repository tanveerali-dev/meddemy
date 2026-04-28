// ==================== NAVBAR SCROLL EFFECT ====================
window.addEventListener('scroll', function() {
    const navbar = document.getElementById('mainNav');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// ==================== COUNTER ANIMATION ====================
function animateCounter(element, target, duration = 2000) {
    let start = 0;
    const increment = target / (duration / 16); // 60fps
    const timer = setInterval(() => {
        start += increment;
        if (start >= target) {
            element.textContent = Math.ceil(target);
            clearInterval(timer);
        } else {
            element.textContent = Math.ceil(start);
        }
    }, 16);
}

// Intersection Observer for counter animation
const observerOptions = {
    threshold: 0.5,
    rootMargin: '0px'
};

const counterObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const counter = entry.target;
            const target = parseInt(counter.getAttribute('data-target'));
            animateCounter(counter, target);
            observer.unobserve(counter);
        }
    });
}, observerOptions);

// Observe all counter elements
document.addEventListener('DOMContentLoaded', function() {
    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
        counterObserver.observe(counter);
    });
});

// ==================== SMOOTH SCROLL ====================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href !== '#' && href !== '') {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                const offsetTop = target.offsetTop - 80; // Account for fixed navbar
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        }
    });
});

// ==================== ANIMATE ON SCROLL ====================
const animateOnScrollObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
});

// Observe elements for animation
document.addEventListener('DOMContentLoaded', function() {
    const animateElements = document.querySelectorAll('.feature-card, .why-card, .course-box, .testimonial-card, .stat-box');
    animateElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        animateOnScrollObserver.observe(el);
    });
});

// ==================== PROGRESS BAR ANIMATION ====================
const progressObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const progressBars = entry.target.querySelectorAll('.timeline-progress');
            progressBars.forEach(bar => {
                const percentage = bar.getAttribute('data-percentage');
                bar.style.width = percentage + '%';
            });
            progressObserver.unobserve(entry.target);
        }
    });
}, {
    threshold: 0.5
});

document.addEventListener('DOMContentLoaded', function() {
    const growthTimeline = document.querySelector('.growth-timeline');
    if (growthTimeline) {
        // Reset widths
        const progressBars = growthTimeline.querySelectorAll('.timeline-progress');
        progressBars.forEach(bar => {
            bar.style.width = '0%';
            bar.style.transition = 'width 1.5s ease';
        });
        progressObserver.observe(growthTimeline);
    }
});

// ==================== WHATSAPP BUTTON ANIMATION ====================
document.addEventListener('DOMContentLoaded', function() {
    const whatsappBtn = document.querySelector('.whatsapp-btn');
    if (whatsappBtn) {
        // Add pulse effect on hover
        whatsappBtn.addEventListener('mouseenter', function() {
            this.querySelector('img').style.animation = 'none';
            setTimeout(() => {
                this.querySelector('img').style.animation = 'pulse 0.5s ease';
            }, 10);
        });
    }
});

// ==================== SCROLL TO TOP ====================
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Show scroll to top button
let scrollToTopBtn;
window.addEventListener('scroll', function() {
    if (window.scrollY > 500) {
        if (!scrollToTopBtn) {
            scrollToTopBtn = document.createElement('button');
            scrollToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
            scrollToTopBtn.className = 'scroll-to-top';
            scrollToTopBtn.onclick = scrollToTop;
            document.body.appendChild(scrollToTopBtn);
            
            // Add CSS for scroll to top button
            const style = document.createElement('style');
            style.textContent = `
                .scroll-to-top {
                    position: fixed;
                    bottom: 110px;
                    right: 30px;
                    width: 50px;
                    height: 50px;
                    background: linear-gradient(135deg, #FFD700, #ffed4e);
                    border: none;
                    border-radius: 50%;
                    color: #000;
                    font-size: 1.2rem;
                    cursor: pointer;
                    box-shadow: 0 5px 20px rgba(255,215,0,0.4);
                    z-index: 999;
                    transition: all 0.3s ease;
                    animation: fadeInUp 0.5s ease;
                }
                .scroll-to-top:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 30px rgba(255,215,0,0.6);
                }
            `;
            document.head.appendChild(style);
        }
        scrollToTopBtn.style.display = 'block';
    } else if (scrollToTopBtn) {
        scrollToTopBtn.style.display = 'none';
    }
});

// ==================== LAZY LOADING IMAGES ====================
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.getAttribute('data-src');
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
});

// ==================== NAVBAR MOBILE MENU ====================
document.addEventListener('DOMContentLoaded', function() {
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    if (navbarToggler && navbarCollapse) {
        // Close mobile menu when clicking a link
        const navLinks = navbarCollapse.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    navbarToggler.click();
                }
            });
        });
    }
});

// ==================== PRELOADER (Optional) ====================
window.addEventListener('load', function() {
    const preloader = document.getElementById('preloader');
    if (preloader) {
        setTimeout(() => {
            preloader.style.opacity = '0';
            setTimeout(() => {
                preloader.style.display = 'none';
            }, 300);
        }, 500);
    }
});

// ==================== PARALLAX EFFECT ====================
window.addEventListener('scroll', function() {
    const scrolled = window.pageYOffset;
    const parallaxElements = document.querySelectorAll('.hero-image, .features-image, .growth-image');
    
    parallaxElements.forEach(element => {
        const speed = 0.5;
        element.style.transform = `translateY(${scrolled * speed}px)`;
    });
});

// ==================== TYPING EFFECT (Optional for Hero Section) ====================
function typeWriter(element, text, speed = 100) {
    let i = 0;
    element.innerHTML = '';
    
    function type() {
        if (i < text.length) {
            element.innerHTML += text.charAt(i);
            i++;
            setTimeout(type, speed);
        }
    }
    
    type();
}

// ==================== PREVENT CARD LINK DEFAULT BEHAVIOR ====================
document.addEventListener('DOMContentLoaded', function() {
    const courseCards = document.querySelectorAll('.course-box-link');
    const featureCards = document.querySelectorAll('.feature-card-link');
    
    // Add smooth hover effects
    [...courseCards, ...featureCards].forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
});

// ==================== CONSOLE LOG ====================
console.log('%c MEDDEMY - Excellence in Education ', 'background: #FFD700; color: #000; font-size: 20px; font-weight: bold; padding: 10px;');
console.log('%c Website loaded successfully! ', 'background: #000; color: #FFD700; font-size: 14px; padding: 5px;');