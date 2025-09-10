<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WEB OSIS SMKN 1 LUMAJANG</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1e40af;
            --accent: #00b4d8;
            --white: #ffffff;
            --light: #f8f9fa;
            --dark: #111827;
            --text: #374151;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, sans-serif;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes bubble {
            0% { transform: translateY(0) rotate(0deg); opacity: 0; }
            50% { opacity: 0.8; }
            100% { transform: translateY(-100vh) rotate(360deg); opacity: 0; }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        body {
            background-color: var(--light);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }
        
        .bubbles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }
        
        .bubble {
            position: absolute;
            bottom: -100px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 50%;
            pointer-events: none;
            animation: bubble linear infinite;
        }
        
        header {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 100;
            animation: fadeIn 0.8s ease-out;
        }
        
        header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .container {
            flex: 1;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }
        
        /* Desktop Layout */
        .desktop-layout {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        /* Mobile Layout */
        .mobile-layout {
            display: none;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            gap: 1.5rem;
            padding: 1.5rem 0;
            -webkit-overflow-scrolling: touch;
        }
        
        .menu-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            width: 320px;
            height: 380px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            animation: fadeIn 0.8s ease-out;
            will-change: transform;
        }
        
        .menu-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .menu-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }
        
        .menu-card::after {
            content: '';
            position: absolute;
            bottom: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, var(--primary-light), transparent 70%);
            opacity: 0.1;
            z-index: -1;
            transition: all 0.5s ease;
        }
        
        .menu-card:hover::after {
            transform: scale(1.2);
            opacity: 0.15;
        }
        
        .mobile-layout .menu-card {
            scroll-snap-align: center;
            flex: 0 0 85%;
            margin: 0 0.5rem;
            animation: float 6s ease-in-out infinite;
        }
        
        .menu-icon {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary);
            background: rgba(37, 99, 235, 0.1);
            width: 90px;
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .menu-card:hover .menu-icon {
            transform: scale(1.1);
            background: rgba(37, 99, 235, 0.2);
            color: var(--primary-dark);
        }
        
        .menu-card h2 {
            color: var(--dark);
            margin-bottom: 1rem;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .menu-card p {
            color: var(--text);
            line-height: 1.6;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }
        
        .btn {
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            color: var(--white);
            border: none;
            padding: 0.7rem 1.8rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        footer {
            background: var(--dark);
            color: var(--white);
            text-align: center;
            padding: 1.5rem;
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
        }
        
        /* Mobile Scrollbar */
        .mobile-layout::-webkit-scrollbar {
            height: 8px;
        }
        
        .mobile-layout::-webkit-scrollbar-track {
            background: rgba(241, 241, 241, 0.5);
            border-radius: 10px;
        }
        
        .mobile-layout::-webkit-scrollbar-thumb {
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: 10px;
        }
        
        /* Responsive Breakpoint */
        @media (max-width: 768px) {
            .desktop-layout {
                display: none;
            }
            
            .mobile-layout {
                display: flex;
            }
            
            .container {
                padding: 2rem 0.5rem;
            }
            
            header h1 {
                font-size: 1.5rem;
            }
            
            .menu-card {
                height: 350px;
            }
        }
    </style>
</head>
<body>
    <div class="bubbles" id="bubbles"></div>
    
    <header>
        <h1>WEB OSIS SMKN 1 LUMAJANG</h1>
    </header>
    
    <div class="container">
        <!-- Desktop View -->
        <div class="desktop-layout">
            <div class="menu-card" onclick="window.location.href='pemilos.php'">
                <div class="menu-icon"><i class="fas fa-vote-yea"></i></div>
                <h2>PEMILOS</h2>
                <p>Pemilihan Ketua OSIS SMKN 1 Lumajang. Pilih kandidat terbaik untuk memimpin organisasi kita.</p>
                <button class="btn">Masuk</button>
            </div>
            
            <div class="menu-card" onclick="window.location.href='classmeet.php'">
                <div class="menu-icon"><i class="fas fa-trophy"></i></div>
                <h2>CLASSMEET</h2>
                <p>Kompetisi antar kelas yang menumbuhkan semangat sportivitas dan kekeluargaan.</p>
                <button class="btn">Masuk</button>
            </div>
        </div>
        
        <!-- Mobile View -->
        <div class="mobile-layout" id="mobileSlider">
            <div class="menu-card" onclick="window.location.href='pemilos.php'">
                <div class="menu-icon"><i class="fas fa-vote-yea"></i></div>
                <h2>PEMILOS</h2>
                <p>Pemilihan Ketua OSIS SMKN 1 Lumajang. Pilih kandidat terbaik untuk memimpin organisasi kita.</p>
                <button class="btn">Masuk</button>
            </div>
            
            <div class="menu-card" onclick="window.location.href='classmeet.php'">
                <div class="menu-icon"><i class="fas fa-trophy"></i></div>
                <h2>CLASSMEET</h2>
                <p>Kompetisi antar kelas yang menumbuhkan semangat sportivitas dan kekeluargaan.</p>
                <button class="btn">Masuk</button>
            </div>
        </div>
    </div>
    
    <footer>
        <p>&copy; <span id="year"></span> OSIS SMKN 1 Lumajang. All Rights Reserved.</p>
    </footer>
    
    <script>
        // Set copyright year
        document.getElementById('year').textContent = new Date().getFullYear();
        
        // Create bubbles
        function createBubbles() {
            const bubbles = document.getElementById('bubbles');
            const bubbleCount = window.innerWidth < 768 ? 20 : 40;
            
            for (let i = 0; i < bubbleCount; i++) {
                const bubble = document.createElement('div');
                bubble.classList.add('bubble');
                
                const size = Math.random() * 100 + 50;
                const posX = Math.random() * window.innerWidth;
                const delay = Math.random() * 5;
                const duration = Math.random() * 15 + 10;
                const opacity = Math.random() * 0.1 + 0.05;
                
                bubble.style.width = `${size}px`;
                bubble.style.height = `${size}px`;
                bubble.style.left = `${posX}px`;
                bubble.style.animationDuration = `${duration}s`;
                bubble.style.animationDelay = `${delay}s`;
                bubble.style.opacity = opacity;
                
                bubbles.appendChild(bubble);
            }
        }
        
        // Mobile auto-slide with pause on interaction
        let slideInterval;
        function setupMobileSlider() {
            if (window.innerWidth <= 768) {
                const slider = document.getElementById('mobileSlider');
                const cards = document.querySelectorAll('.mobile-layout .menu-card');
                let currentIndex = 0;
                const cardWidth = cards[0].offsetWidth + 24; // card width + gap
                
                function slideNext() {
                    currentIndex = (currentIndex + 1) % cards.length;
                    slider.scrollTo({
                        left: currentIndex * cardWidth,
                        behavior: 'smooth'
                    });
                }
                
                // Auto-slide every 3 seconds
                slideInterval = setInterval(slideNext, 3000);
                
                // Pause auto-slide on user interaction
                slider.addEventListener('touchstart', () => {
                    clearInterval(slideInterval);
                });
                
                slider.addEventListener('touchend', () => {
                    slideInterval = setInterval(slideNext, 3000);
                });
                
                slider.addEventListener('scroll', () => {
                    currentIndex = Math.round(slider.scrollLeft / cardWidth);
                });
            }
        }
        
        // Initialize animations
        window.addEventListener('DOMContentLoaded', function() {
            createBubbles();
            setupMobileSlider();
            
            // Animate cards on load
            const cards = document.querySelectorAll('.menu-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.2}s`;
            });
        });
        
        // Responsive adjustments
        window.addEventListener('resize', function() {
            // Recreate bubbles
            const bubbles = document.getElementById('bubbles');
            bubbles.innerHTML = '';
            createBubbles();
            
            // Reset mobile slider
            if (window.innerWidth > 768) {
                clearInterval(slideInterval);
                const slider = document.getElementById('mobileSlider');
                if (slider) slider.scrollTo({ left: 0 });
            } else {
                setupMobileSlider();
            }
        });
    </script>
</body>
</html>