<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Civilipédia</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body>

    <!-- Toast container -->
    <div id="toast-container" aria-live="polite"></div>

    <!-- Navbar Bootstrap native -->
    <nav class="navbar navbar-expand-lg custom-navbar sticky-top">
        <div class="container-fluid px-4">

            <!-- Brand -->
            <a class="navbar-brand" href="/?page=home">
                <i class="fa-solid fa-landmark"></i> Civilipédia
            </a>

            <!-- Recherche desktop — juste après le brand -->
            <form class="search-wrapper d-none d-lg-block position-relative me-3"
                role="search" autocomplete="off" onsubmit="return false;">
                <input class="form-control" type="search" id="searchInput"
                    placeholder="Rechercher un article…" autocomplete="off">
                <ul id="searchResults" class="list-group d-none"></ul>
            </form>

            <!-- Toggler mobile -->
            <button class="navbar-toggler ms-auto" type="button"
                data-bs-toggle="collapse" data-bs-target="#navMain"
                aria-controls="navMain" aria-expanded="false" aria-label="Navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Liens + recherche mobile -->
            <div class="collapse navbar-collapse" id="navMain">

                <!-- Recherche mobile -->
                <form class="search-wrapper d-lg-none position-relative my-2"
                    role="search" autocomplete="off" onsubmit="return false;"
                    style="width:100%;">
                    <input class="form-control" type="search" id="searchInputMobile"
                        placeholder="Rechercher un article…" autocomplete="off">
                </form>

                <!-- Nav links poussés à droite -->
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <?php $currentPage = $_GET['page'] ?? 'home'; ?>

                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'home' ? 'active' : '' ?>"
                            href="/?page=home">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'contact' ? 'active' : '' ?>"
                            href="/?page=contact">Contact</a>
                    </li>

                    <?php if (Auth::isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'article_create' ? 'active' : '' ?>"
                                href="/?page=article_create">
                                <i class="fa-solid fa-plus"></i> Nouvel article
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>"
                                href="/?page=profile">
                                <i class="fa-solid fa-user"></i> Mon profil
                            </a>
                        </li>
                        <?php if (Auth::isAdmin()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle text-warning <?= str_starts_with($currentPage, 'admin') ? 'active' : '' ?>"
                                    href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="fa-solid fa-shield-halved"></i> Admin
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="/?page=admin">
                                            <i class="fa-solid fa-users"></i> Gestion utilisateurs
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="/?page=logout">
                                <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'login' ? 'active' : '' ?>"
                                href="/?page=login">Connexion</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'register' ? 'active' : '' ?>"
                                href="/?page=register">Inscription</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="main-wrapper">

        <script>
            // Recherche live
            (function() {
                const inputs = ['searchInput', 'searchInputMobile'].map(id => document.getElementById(id)).filter(Boolean);
                const list = document.getElementById('searchResults');
                let articles = [];

                fetch('<?= $_ENV['API_URL'] ?>/articles')
                    .then(r => r.ok ? r.json() : null)
                    .then(d => {
                        articles = d?.data ?? [];
                    })
                    .catch(() => {});

                inputs.forEach(input => {
                    input.addEventListener('input', function() {
                        if (!list) return;
                        const q = this.value.trim().toLowerCase();
                        list.innerHTML = '';
                        if (q.length < 2) {
                            list.classList.add('d-none');
                            return;
                        }
                        const stripHtml = html => html ? html.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim() : '';
                        const results = articles.filter(a =>
                            a.title.toLowerCase().includes(q) ||
                            stripHtml(a.content).toLowerCase().includes(q)
                        ).slice(0, 6);
                        if (!results.length) {
                            list.classList.add('d-none');
                            return;
                        }
                        results.forEach(a => {
                            const li = document.createElement('li');
                            li.textContent = a.title;
                            li.addEventListener('click', () => {
                                window.location.href = '/?page=article&id=' + a.id;
                            });
                            list.appendChild(li);
                        });
                        list.classList.remove('d-none');
                    });
                });
                document.addEventListener('click', e => {
                    if (list && !e.target.closest('.search-wrapper')) list.classList.add('d-none');
                });
            })();

            //  Système de toasts 
            const TOAST_ICONS = {
                success: 'fa-circle-check',
                danger: 'fa-circle-exclamation',
                warning: 'fa-triangle-exclamation',
                info: 'fa-circle-info',
            };

            function showToast(message, type = 'info', duration = 4500) {
                const container = document.getElementById('toast-container');
                const toast = document.createElement('div');
                toast.className = 'toast-item toast-' + type;
                const icon = TOAST_ICONS[type] ?? 'fa-circle-info';
                toast.innerHTML =
                    `<span class="toast-item__icon"><i class="fa-solid ${icon}"></i></span>` +
                    `<span class="toast-item__body">${message}</span>` +
                    `<button class="toast-item__close" onclick="dismissToast(this.parentElement)" aria-label="Fermer">` +
                    `<i class="fa-solid fa-xmark"></i></button>`;
                container.appendChild(toast);
                setTimeout(() => dismissToast(toast), duration);
            }

            function dismissToast(el) {
                if (!el || el.classList.contains('toast-out')) return;
                el.classList.add('toast-out');
                setTimeout(() => el?.remove(), 230);
            }

            // Flash toasts issus de la session PHP
            <?php
            $flashes = $_SESSION['flash_toasts'] ?? [];
            unset($_SESSION['flash_toasts']);
            if ($flashes): ?>
                document.addEventListener('DOMContentLoaded', () => {
                    <?php foreach ($flashes as $f): ?>
                        showToast(<?= json_encode(htmlspecialchars($f['msg'])) ?>, <?= json_encode($f['type']) ?>);
                    <?php endforeach; ?>
                });
            <?php endif; ?>
        </script>