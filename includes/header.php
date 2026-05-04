<header class="header">
    <div class="header__wrapper">
        <a href="/" class="header__logo">
            <img loading="lazy" src="/assets/images/logo-w.png" alt="Logo" />
        </a>
        <div class="header__navigation-wrapper">
            <?php if (isset($_SESSION['employee_name'])): ?>
                <nav class="header__navigation">
                    <ul class="header__list">
                        <li class="header__list-item">
                            <a href="/pages_table/create_transaction.php">Make Transaction</a>
                        </li>
                        <li class="header__list-item">
                            <a href="/pages_query/accounts_query.php">Search Accounts</a>
                        </li>
                        <li class="header__list-item header__list-item--has-submenu">
                            <span class="header__list-item__dropdown-btn">Dashboard(Queries)</span>
                            <div class="header__list-item__dropdown-content">
                                <a href="/pages_query/client_trans_query.php">Search Client Transactions</a>
                                <a href="/pages_query/top_empl_trans.php">List of Top 5 Transactions per Employee</a>
                                <a href="/pages_query/trans_query.php">Search Transactions from date to date</a>
                                <a href="/pages_query/trans_from_type_query.php">Search Transactions by type</a>
                                <a href="/pages_query/trans_to_account.php">Search Transactions Made To Account</a>
                            </div>
                        </li>
                        <li class="header__list-item header__list-item--has-submenu">
                            <span class="header__list-item__dropdown-btn">Table Views</span>
                            <div class="header__list-item__dropdown-content">
                                <a href="/pages_table/addresses.php">Addresses Table</a>
                                <a href="/pages_table/currencies.php">Currencies Table</a>
                                <a href="/pages_table/roles.php">Roles Table</a>
                                <a href="/pages_table/transaction_types.php">Transaction Types Table</a>
                                <a href="/pages_table/accounts.php">Accounts Table</a>
                                <a href="/pages_table/clients.php">Clients Table</a>
                                <a href="/pages_table/employees.php">Employees Table</a>
                                <a href="/pages_table/transactions.php">Transactions Table</a>
                            </div>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
            <div class="header__buttons-wrapper">
                <?php if (isset($_SESSION['employee_name'])): ?>
                    <div class="user-credentials">
                        <div class="user-info">
                            <div class="user-name">
                                <svg width="100%" height="100%" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 15.5H7.5C6.10444 15.5 5.40665 15.5 4.83886 15.6722C3.56045 16.06 2.56004 17.0605 2.17224 18.3389C2 18.9067 2 19.6044 2 21M16 18L18 20L22 16M14.5 7.5C14.5 9.98528 12.4853 12 10 12C7.51472 12 5.5 9.98528 5.5 7.5C5.5 5.01472 7.51472 3 10 3C12.4853 3 14.5 5.01472 14.5 7.5Z" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <?= htmlspecialchars($_SESSION['employee_name']); ?>
                            </div>
                            <span class="user-role"><?= htmlspecialchars($_SESSION['employee_role']); ?> </span>
                        </div>
                        <a href="/logout.php" class="header__button">
                            <span>Log Out</span>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="/login.php" class="header__button">
                        <span>Log In</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="header__burger"><i></i><i></i><i></i></div>
    </div>
</header>