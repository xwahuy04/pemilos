        </main>
        
        <footer class="smart-footer">
            <div class="footer-content">
                <div class="social-links">
                    <a href="https://www.tiktok.com/@smkn.1lumajang?is_from_webapp=1&sender_device=pc" title="TikTok"><i class="fab fa-tiktok"></i></a>
                    <a href="https://www.instagram.com/smkn1lumajang?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" title="Instagram"><i class="fab fa-instagram"></i></a>
                </div>
                <div class="footer-credit">
                    <p>SMKN 1 Lumajang &copy; <?php echo date('Y'); ?> | Dikembangkan oleh <a href=""> Tim Developer OSIS</a></p>
                </div>
            </div>
        </footer>
           <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

            <!-- Bootstrap Bundle (wajib untuk modal, dropdown, toast, dll) -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        <style>
            /* Smart Footer Style */
            .smart-footer {
                background: white;
                border: 2px solid var(--primary-color);
                border-radius: 30px;
                width: 95%;
                max-width: 500px;
                padding: 15px 20px;
                box-shadow: 0 3px 10px rgba(0,0,0,0.1);
                margin: 30px auto;
                margin-top: auto; /* Ini yang bikin footer tetap di bawah jika konten sedikit */
            }
            
            body {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
            }
            
            main {
                flex: 1;
            }
            
            .footer-content {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }
            
            .social-links {
                display: flex;
                gap: 15px;
                margin-bottom: 5px;
            }
            
            .social-links a {
                color: var(--primary-color);
                font-size: 1.1rem;
                transition: all 0.3s;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                border: 1px solid var(--primary-color);
            }
            
            .social-links a:hover {
                background: var(--primary-color);
                color: white;
                transform: translateY(-3px);
            }
            
            .footer-credit {
                text-align: center;
            }
            
            .footer-credit p {
                margin: 0;
                color: #666;
                font-size: 0.8rem;
            }
            
            .footer-credit a {
                color: var(--primary-color);
                text-decoration: none;
                font-weight: bold;
            }
            
            .footer-credit a:hover {
                text-decoration: underline;
            }
        </style>