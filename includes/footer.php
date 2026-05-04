<footer>
    <div class="footer__inner">
        <div class="footer__top">
            <div class="footer__logo-container">
                <a href="/" target="_blank" class="footer__logo">
                    <img loading="lazy" src="/assets/images/logo-w.png" alt="Logo" />
                </a>
            </div>

            <nav class="footer__nav" aria-label="Footer navigation">
                <?php if (isset($_SESSION['employee_name'])): ?>
                    <div class="nav__group">
                        <h3 class="nav__group-label">Navigate</h3>
                        <a href="/pages_table/create_transaction.php">Make Transaction</a>
                        <a href="/pages_query/accounts_query.php">Search Accounts</a>
                    </div>
                <?php endif; ?>
                <div class="nav__group">
                    <h3 class="nav__group-label">Partner</h3>
                    <a href="https://www1.tu-varna.bg/tu-varna/" class="uni-link">
                        <svg
                            class="uni-icon"
                            viewBox="0 0 24 24"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M12 3L1 9l11 6 11-6-11-6z"
                                stroke="#c9a84c"
                                stroke-width="1.5"
                                stroke-linejoin="round"
                                fill="none"
                            />
                            <path
                                d="M5 12v5c0 1.657 3.134 3 7 3s7-1.343 7-3v-5"
                                stroke="#c9a84c"
                                stroke-width="1.5"
                                stroke-linecap="round"
                                fill="none"
                            />
                            <line
                                x1="23"
                                y1="9"
                                x2="23"
                                y2="15"
                                stroke="#c9a84c"
                                stroke-width="1.5"
                                stroke-linecap="round"
                            />
                        </svg>
                        Tu Varna Website
                    </a>
                </div>
            </nav>
        </div>

        <div class="footer__bottom">
            <p>© 2026 Bank Project by Daniel Nikolov 24621602.</p>
        </div>
    </div>
</footer>