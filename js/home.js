
        // Header scroll effect
        const header = document.querySelector('.header');
        const mainContent = document.querySelector('.main-content');

        mainContent.addEventListener('scroll', () => {
            if (mainContent.scrollTop > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Intersection Observer for scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px',
            root: mainContent
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        // Observe fade-in elements
        document.querySelectorAll('.fade-in-up').forEach(el => {
            observer.observe(el);
        });

        // Observe animation elements
        document.querySelectorAll('.fade-in-left, .fade-in-right').forEach(el => {
            observer.observe(el);
        });

        // Observe service cards with staggered animation
        document.querySelectorAll('.service-card').forEach((card, index) => {
            card.style.transitionDelay = `${index * 0.1}s`;
            observer.observe(card);
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Parallax effect for floating shapes
        mainContent.addEventListener('scroll', () => {
            const scrolled = mainContent.scrollTop;
            const shapes = document.querySelectorAll('.shape');
            
            shapes.forEach((shape, index) => {
                const speed = 0.1 + (index * 0.05);
                const yPos = -(scrolled * speed);
                shape.style.transform = `translateY(${yPos}px) rotate(${scrolled * 0.1}deg)`;
            });
        });

        // Add loading animation
        window.addEventListener('load', () => {
            document.body.style.opacity = '0';
            document.body.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 100);
        });

        // Enhanced tech elements animation
        const techElements = document.querySelectorAll('.tech-element');
        techElements.forEach((element, index) => {
            element.addEventListener('mouseenter', () => {
                element.style.transform = `scale(1.2) rotate(${Math.random() * 360}deg)`;
                element.style.transition = 'transform 0.3s ease';
            });
            
            element.addEventListener('mouseleave', () => {
                element.style.transform = 'scale(1) rotate(0deg)';
            });
        });

        // Central element click interaction
        const centralElement = document.querySelector('.central-element');
        if (centralElement) {
            centralElement.addEventListener('click', () => {
                centralElement.style.animation = 'none';
                setTimeout(() => {
                    centralElement.style.animation = 'pulse 2s ease-in-out infinite';
                }, 100);
            });
        }
    